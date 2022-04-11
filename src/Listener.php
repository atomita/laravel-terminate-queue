<?php

namespace atomita\LaravelTerminateQueue;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Factory;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class Listener
{
    protected Factory $manager;

    protected Dispatcher $dispatcher;

    protected ExceptionHandler $exceptionHandler;

    public function __construct(
        Factory $manager,
        Dispatcher $dispatcher,
        ExceptionHandler $exceptionHandler
    ) {
        $this->dispatcher = $dispatcher;
        $this->exceptionHandler = $exceptionHandler;
        $this->manager = $manager;
    }

    public function run(array $queues): void
    {
        foreach ($queues as $queue) {
            foreach ($this->getJobs($queue) as $job) {
                try {
                    $this->raiseBeforeJobEvent($job);

                    $job->fire();

                    $this->raiseAfterJobEvent($job);
                } catch (\Throwable $e) {
                    $this->handleException($job, $e);
                }
            }
        }
    }

    protected function getJobs(string $queueName): \Generator
    {
        $connector = (fn () => $this->getConnector('terminate'))->bindTo($this->manager, $this->manager)();

        foreach ($connector->getQueues() as $queue) {
            while (true) {
                $job = $queue->pop($queueName);
                if (is_null($job)) {
                    break;
                }
                yield $job;
            }
        }
    }

    protected function raiseBeforeJobEvent(Job $job): void
    {
        $this->dispatcher->dispatch(new JobProcessing(
            $job->getConnectionName(), $job
        ));
    }

    protected function raiseAfterJobEvent(Job $job): void
    {
        $this->dispatcher->dispatch(new JobProcessed(
            $job->getConnectionName(), $job
        ));
    }

    protected function raiseExceptionOccurredJobEvent(Job $job, \Throwable $e): void
    {
        $this->dispatcher->dispatch(new JobExceptionOccurred($job->getConnectionName(), $job, $e));
    }

    protected function handleException(Job $queueJob, \Throwable $e): void
    {
        $this->raiseExceptionOccurredJobEvent($queueJob, $e);

        $queueJob->fail($e);
    }
}
