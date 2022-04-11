<?php

namespace atomita\LaravelTerminateQueue;

use Illuminate\Contracts\Container\Container;

class Connector implements \Illuminate\Queue\Connectors\ConnectorInterface
{
    protected Container $container;
    protected array $queues = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        $this->queues[] = $queue = $this->container->make('terminate-queue.queue');

        return $queue;
    }

    public function getQueues(): array
    {
        return $this->queues;
    }
}
