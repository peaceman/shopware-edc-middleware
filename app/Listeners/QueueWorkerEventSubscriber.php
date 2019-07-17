<?php
/**
 * lel since 2019-07-17
 */

namespace App\Listeners;

use Illuminate\Events\Dispatcher;
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
}