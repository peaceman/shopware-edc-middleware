<?php
/**
 * lel since 2019-07-17
 */

namespace App\ResourceFile;

use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Storage;

class JobProcessingSubscriber
{
    /** @var Storage */
    protected $storageDirector;

    public function __construct(StorageDirector $storageDirector)
    {
        $this->storageDirector = $storageDirector;
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(JobProcessed::class, function () {
            $this->storageDirector->flushQueues();
        });
    }
}
