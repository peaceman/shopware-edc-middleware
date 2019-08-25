<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\Jobs;

use App\Jobs\BaseJob;
use App\ResourceFile\HouseKeeping\FileInstanceDeleter;
use App\ResourceFile\HouseKeeping\Providers\UnusedLocalResourceFileInstances;

class DeleteUnusedLocals extends BaseJob
{
    public $queue = 'long-running';

    public function handle(FileInstanceDeleter $fileInstanceDeleter, UnusedLocalResourceFileInstances $rfiProvider)
    {
        $fileInstanceDeleter($rfiProvider);
    }
}
