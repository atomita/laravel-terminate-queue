<?php

namespace atomita\LaravelTerminateQueue;

class Queue extends \Illuminate\Queue\Queue implements \Illuminate\Contracts\Queue\Queue
{
    protected $queues = [];

    /**
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        return count($this->queues[$queue] ?? []);
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        $this->pushRaw($this->createPayload($job, $queue ?? '', $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $jobs = $this->queues[$queue] ?? [];

        $jobs[] = [$payload, $options];

        $this->queues[$queue] = $jobs;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string|object  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        if (! is_array($this->queues[$queue]) || empty($this->queues[$queue])) {
            return null;
        }

        [$payload, $options] = array_pop($this->queues[$queue]);

        return new Job($this->container, $payload, $queue ?? '', $this->connectionName);
    }
}
