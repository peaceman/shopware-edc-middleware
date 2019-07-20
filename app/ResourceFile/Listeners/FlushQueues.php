<?php
/**
 * lel since 2019-07-20
 */

namespace App\ResourceFile\Listeners;

use App\ResourceFile\StorageDirector;

class FlushQueues
{
    /** @var StorageDirector */
    protected $storageDirector;

    public function __construct(StorageDirector $storageDirector)
    {
        $this->storageDirector = $storageDirector;
    }

    public function handle($event): void
    {
        $this->storageDirector->flushQueues();
    }
}
