<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\Jobs;

use App\ResourceFile\HouseKeeping\FileInstanceDeleter;
use App\ResourceFile\HouseKeeping\Providers\UnusedLocalResourceFileInstances;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeleteUnusedLocals implements ShouldQueue
{
    public function handle(FileInstanceDeleter $fileInstanceDeleter, UnusedLocalResourceFileInstances $rfiProvider)
    {
        $fileInstanceDeleter($rfiProvider);
    }
}
