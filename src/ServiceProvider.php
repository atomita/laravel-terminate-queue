<?php

namespace atomita\LaravelTerminateQueue;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Arr;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('terminate-queue.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->app->singleton('terminate-queue.connector', fn () => new Connector($this->app));

        $this->app->singleton('terminate-queue.listener', fn () => new Listener(
            $this->app->make('queue'),
            $this->app->make('events'),
            $this->app->make(ExceptionHandler::class),
        ));

        $this->app->bindIf('terminate-queue.queue', Queue::class);

        $this->app->extend('queue', function ($queue) {
            $queue->addConnector('terminate', fn () => $this->app->make('terminate-queue.connector'));

            return $queue;
        });

        $this->mergeQueueConfig();

        $queues = config('terminate-queue.queues');
        if (is_array($queues) && ! empty($queues)) {
            $this->app->terminating(fn () => $this->app->make('terminate-queue.listener')->run($queues));
        }
    }

    public function mergeQueueConfig(): void
    {
        $config = require __DIR__.'/../config/queue.php';

        foreach (Arr::dot($config, 'queue.') as $key => $value) {
            if (! $this->app->config->has($key)) {
                $this->app->config->set($key, $value);
            }
        }
    }
}
