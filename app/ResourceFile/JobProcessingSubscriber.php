<?php
/**
 * lel since 2019-07-17
 */

namespace App\ResourceFile;

use App\ResourceFile\Listeners\FlushQueues;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;

class JobProcessingSubscriber
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(JobProcessed::class, FlushQueues::class);
    }
}
