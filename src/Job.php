<?php

namespace atomita\LaravelTerminateQueue;

use Illuminate\Container\Container;

class Job extends \Illuminate\Queue\Jobs\Job implements \Illuminate\Contracts\Queue\Job
{
    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  string  $payload
     * @param  string  $connectionName
     * @param  string  $queue
     * @return void
     */
    public function __construct(Container $container, string $payload, string $connectionName, string $queue)
    {
        $this->connectionName = $connectionName;
        $this->container = $container;
        $this->payload = $payload;
        $this->queue = $queue;
    }

    /**
     * @return string
     */
    public function getJobId()
    {
        return json_encode([
            'payload' => $this->getRawBody(),
            'queue' => $this->queue,
            'connection' => $this->connectionName,
        ], JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    /**
     * @return string
     */
    public function getRawBody()
    {
        return $this->payload;
    }

    /**
     * @return int
     */
    public function attempts()
    {
        return 1;
    }
}
