<?php

namespace Alphavel\Framework;

use Swoole\Http\Server;

class Application
{
    private static ?Application $instance = null;

    private Container $container;

    private array $providers = [];

    private array $bootedProviders = [];
    
    private array $deferredProviders = []; // Lazy-loaded providers

    private ?Server $server = null;

    private array $config = [];

    private array $requestPool = [];
    
    private bool $booted = false;

    private function __construct()
    {
        $this->container = Container::getInstance();
        $this->container->instance('app', $this);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register(string $provider, bool $defer = false): void
    {
        if (isset($this->providers[$provider])) {
            return;
        }

        // Lazy loading: defer provider registration until boot
        if ($defer && !$this->booted) {
            $this->deferredProviders[$provider] = true;
            return;
        }

        $providerInstance = new $provider($this);
        $providerInstance->register();

        $this->providers[$provider] = $providerInstance;
    }

    /**
     * Discover providers from composer packages with extra.alphavel.providers
     */
    public function discoverProviders(): array
    {
        $cacheFile = __DIR__ . '/../../../storage/cache/providers.php';

        // Check cache first (invalidate on composer update)
        if (file_exists($cacheFile)) {
            $cached = require $cacheFile;
            $installedPath = __DIR__ . '/../../../vendor/composer/installed.json';
            if (isset($cached['timestamp']) && $cached['timestamp'] >= filemtime($installedPath)) {
                return $cached['providers'] ?? [];
            }
        }

        $providers = [];
        $installedPath = __DIR__ . '/../../../vendor/composer/installed.json';

        if (! file_exists($installedPath)) {
            return $providers;
        }

        $installed = json_decode(file_get_contents($installedPath), true);
        $packages = $installed['packages'] ?? [];

        foreach ($packages as $package) {
            if (isset($package['extra']['alphavel']['providers'])) {
                $packageProviders = (array) $package['extra']['alphavel']['providers'];
                $providers = array_merge($providers, $packageProviders);
            }
        }

        // Cache the results
        if (! is_dir(dirname($cacheFile))) {
            mkdir(dirname($cacheFile), 0755, true);
        }

        file_put_contents($cacheFile, "<?php\nreturn " . var_export([
            'timestamp' => time(),
            'providers' => $providers,
        ], true) . ";\n");

        return $providers;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        // Register deferred providers before booting
        foreach ($this->deferredProviders as $provider => $_) {
            $this->register($provider, false);
        }
        $this->deferredProviders = [];

        // Boot all registered providers
        foreach ($this->providers as $name => $provider) {
            if (! isset($this->bootedProviders[$name])) {
                $provider->boot();
                $this->bootedProviders[$name] = true;
            }
        }

        $this->booted = true;
    }

    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }

    public function singleton(string $abstract, callable $concrete): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    public function bind(string $abstract, callable $concrete): void
    {
        $this->container->bind($abstract, $concrete);
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    public function loadConfig(string $path): void
    {
        $this->config = array_merge($this->config, require $path);
    }

    public function run(): void
    {
        $this->boot();

        $host = $this->config('server.host', '0.0.0.0');
        $port = $this->config('server.port', 9999);
        
        // 🚀 PERFORMANCE: Support BASE mode (faster for HTTP/REST APIs)
        $mode = $this->config('server.mode', 'base');
        $swooleMode = strtolower($mode) === 'base' ? SWOOLE_BASE : SWOOLE_PROCESS;

        $this->server = new Server($host, $port, $swooleMode);

        $this->server->set([
            'worker_num' => $this->config('server.workers', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 8),
            'reactor_num' => $this->config('server.reactors', function_exists('swoole_cpu_num') ? swoole_cpu_num() * 2 : 8),
            'enable_coroutine' => true,
            'max_coroutine' => $this->config('server.max_coroutine', 100000),
            'max_conn' => $this->config('server.max_connections', 10000),
            'max_request' => $this->config('server.max_request', 0),
            'dispatch_mode' => $this->config('server.dispatch_mode', 1),
            'open_tcp_nodelay' => true,
            'enable_reuse_port' => true,
            'buffer_output_size' => 2 * 1024 * 1024,
            'package_max_length' => 2 * 1024 * 1024,
        ]);

        $this->server->on('request', [$this, 'handleRequest']);
        $this->server->start();
    }

    public function handleRequest($request, $response): void
    {
        // Request Pooling: Reuse Request object if available
        if (!empty($this->requestPool)) {
            $psr = array_pop($this->requestPool);
        } else {
            $psr = $this->make('request');
        }

        $psr->createFromSwoole($request);
        $router = $this->make('router');

        $route = $router->dispatch($psr->getUri(), $psr->getMethod());

        if (! $route) {
            $response->status(404);
            $response->end(json_encode(['error' => 'Not Found']));
            
            // Recycle request
            if (count($this->requestPool) < 1024) {
                $this->requestPool[] = $psr;
            }
            return;
        }

        // 🚀 RAW ROUTE: Ultra-fast path (zero overhead)
        if ($route['handler'] === '__RAW__') {
            $this->handleRawRoute($route['raw_config'], $request, $response);
            
            // Recycle request
            if (count($this->requestPool) < 1024) {
                $this->requestPool[] = $psr;
            }
            return;
        }

        try {
            $result = $this->make('pipeline')
                ->send($psr)
                ->through($route['middlewares'] ?? [])
                ->then(fn ($req) => $this->callController($route, $req));

            $this->sendResponse($response, $result);
        } catch (\Throwable $e) {
            $this->make('exception')->renderSwoole($e, $response);
        } finally {
            // Recycle request
            if (count($this->requestPool) < 1024) {
                $this->requestPool[] = $psr;
            }
            
            // Release DB connection if it was used
            if (class_exists(\Alphavel\Database\DB::class)) {
                \Alphavel\Database\DB::release();
            }
        }
    }

    /**
     * Handle raw route with zero overhead
     * Bypasses entire framework stack for maximum performance
     */
    private function handleRawRoute(array $config, $request, $response): void
    {
        $handler = $config['handler'];
        $contentType = $config['content_type'];

        $response->header('Content-Type', $contentType);

        // String: Direct output
        if (is_string($handler)) {
            $response->end($handler);
            return;
        }

        // Array: JSON encode
        if (is_array($handler)) {
            $response->end(json_encode($handler));
            return;
        }

        // Closure: Full control over Swoole request/response
        if ($handler instanceof \Closure) {
            $handler($request, $response);
            return;
        }
    }

    private function callController(mixed $route, Request $request): Response
    {
        // If route is a Closure directly (shouldn't happen but handle it)
        if ($route instanceof \Closure) {
            $result = $route($request);
            return $result instanceof Response ? $result : Response::make()->json($result);
        }

        $handler = $route['handler'];

        // Handle Closure routes
        if ($handler instanceof \Closure) {
            $result = $handler($request, ...array_values($route['params'] ?? []));
            
            // If closure returns Response, use it directly
            if ($result instanceof Response) {
                return $result;
            }
            
            // Otherwise wrap in Response
            return Response::make()->json($result);
        }

        // Handle Controller@method routes
        [$controller, $method] = $handler;

        // Transient Pattern with Autowiring:
        // Container resolves dependencies automatically using cached reflection
        // Performance: Reflection runs once per class, then cached in memory
        // Safety: Fresh instance per request prevents state leakage
        $instance = $this->container->make($controller);

        return $instance->$method($request, ...array_values($route['params'] ?? []));
    }

    private function sendResponse($swooleResponse, Response $response): void
    {
        $swooleResponse->status($response->getStatusCode());

        foreach ($response->getHeaders() as $key => $value) {
            $swooleResponse->header($key, $value);
        }

        $swooleResponse->end($response->getContent());
    }
}
