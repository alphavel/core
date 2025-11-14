<?php

namespace Alphavel\Core;

use Swoole\Http\Server;

class Application
{
    private static ?Application $instance = null;

    private Container $container;

    private array $providers = [];

    private array $bootedProviders = [];

    private ?Server $server = null;

    private array $config = [];

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

    public function register(string $provider): void
    {
        if (isset($this->providers[$provider])) {
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
        foreach ($this->providers as $name => $provider) {
            if (! isset($this->bootedProviders[$name])) {
                $provider->boot();
                $this->bootedProviders[$name] = true;
            }
        }
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
        $port = $this->config('server.port', 9501);

        $this->server = new Server($host, $port);

        $this->server->set([
            'worker_num' => $this->config('server.workers', swoole_cpu_num() * 2),
            'reactor_num' => $this->config('server.reactors', swoole_cpu_num() * 2),
            'enable_coroutine' => true,
            'max_coroutine' => $this->config('server.max_coroutine', 100000),
            'max_conn' => $this->config('server.max_connections', 10000),
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
        $psr = $this->make('request')->createFromSwoole($request);
        $router = $this->make('router');

        $route = $router->dispatch($psr->getUri(), $psr->getMethod());

        if (! $route) {
            $response->status(404);
            $response->end(json_encode(['error' => 'Not Found']));

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
        }
    }

    private function callController(array $route, Request $request): Response
    {
        [$controller, $method] = $route['handler'];

        $instance = new $controller();

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
