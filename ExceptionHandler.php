<?php

namespace Alphavel\Core;

use Throwable;

class ExceptionHandler
{
    private static ?ExceptionHandler $instance = null;

    private bool $debug = true;

    private array $handlers = [];

    private function __construct()
    {
        //
    }

    public static function getInstance(): ExceptionHandler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register(): void
    {
        set_exception_handler([$this, 'handle']);
        set_error_handler([$this, 'handleError']);
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public function registerHandler(string $exceptionClass, callable $handler): void
    {
        $this->handlers[$exceptionClass] = $handler;
    }

    public function handle(Throwable $e): void
    {
        foreach ($this->handlers as $class => $handler) {
            if ($e instanceof $class) {
                $handler($e);

                return;
            }
        }

        $this->renderException($e);
    }

    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }

        return false;
    }

    private function renderException(Throwable $e): void
    {
        $statusCode = $this->getStatusCode($e);

        if (! headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }

        $response = [
            'success' => false,
            'message' => $this->debug ? $e->getMessage() : 'Internal Server Error',
        ];

        if ($this->debug) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = explode("\n", $e->getTraceAsString());
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
    }

    public function renderSwoole(Throwable $e, $swooleResponse): void
    {
        $statusCode = $this->getStatusCode($e);

        $swooleResponse->status($statusCode);
        $swooleResponse->header('Content-Type', 'application/json');

        $response = [
            'success' => false,
            'message' => $this->debug ? $e->getMessage() : 'Internal Server Error',
        ];

        if ($this->debug) {
            $response['exception'] = get_class($e);
            $response['file'] = $e->getFile();
            $response['line'] = $e->getLine();
            $response['trace'] = explode("\n", $e->getTraceAsString());
        }

        $swooleResponse->end(json_encode($response, JSON_PRETTY_PRINT));
    }

    public static function capture(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            self::getInstance()->handle($e);

            return null;
        }
    }

    private function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode') && is_callable([$e, 'getStatusCode'])) {
            return (int) call_user_func([$e, 'getStatusCode']);
        }

        return 500;
    }
}
