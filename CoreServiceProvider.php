<?php

namespace Alphavel\Core;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();
        $this->registerRequest();
        $this->registerRouter();
        $this->registerPipeline();
        $this->registerExceptionHandler();
    }

    private function registerConfig(): void
    {
        $this->app->singleton('config', fn () => Config::getInstance());
    }

    private function registerRequest(): void
    {
        $this->app->bind('request', fn () => new Request());
    }

    private function registerRouter(): void
    {
        $this->app->singleton('router', fn () => new Router());
    }

    private function registerPipeline(): void
    {
        $this->app->bind('pipeline', fn () => new Pipeline());
    }

    private function registerExceptionHandler(): void
    {
        $this->app->singleton('exception', function () {
            $handler = ExceptionHandler::getInstance();
            $handler->setDebug($this->app->config('app.debug', true));
            $handler->register();

            return $handler;
        });
    }
}
