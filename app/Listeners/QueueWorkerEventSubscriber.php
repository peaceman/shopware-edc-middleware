<?php
/**
 * lel since 2019-07-17
 */

namespace App\Listeners;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\WorkerStopping;
use Psr\Log\LoggerInterface;

class QueueWorkerEventSubscriber
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    public function __construct(LoggerInterface $logger)
    {
        $this->log = $logger;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(WorkerStopping::class, [$this, 'onWorkerStopping']);
        $events->listen(JobProcessing::class, [$this, 'logJobEvent']);
        $events->listen(JobProcessed::class, [$this, 'logJobEvent']);
    }

    public function onWorkerStopping(WorkerStopping $e)
    {
        $loggingContext = ['pid' => getmypid(), 'status' => $e->status];

        switch ($e->status) {
            case 0:
                $this->log->debug('WorkerStopping: regular', $loggingContext);
                break;
            case 1:
                $this->log->alert('WorkerStopping: exceeded timeout', $loggingContext);
                break;
            case 12:
                $this->log->info('WorkerStopping: exceeded memory limit', $loggingContext);
                break;
        }
    }

    public function logJobEvent($e)
    {
        $loggingContext = [
            'pid' => getmypid(),
            'class' => get_class($e),
            'jobClass' => $this->getJobClassFromEvent($e),
        ];

        $this->log->debug('JobEvent', $loggingContext);
    }

    protected function getJobInstanceFromEvent($event)
    {
        /** @var Job $job */
        $job = $event->job;
        $payload = $job->payload();

        $command = data_get($payload, 'data.command', false);

        return $command ? unserialize($command) : null;
    }

    protected function getJobClassFromEvent($e): ?string
    {
        $job = $this->getJobInstanceFromEvent($e);

        return $job ? get_class($job) : null;
    }
}
