<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\Jobs;

use App\ResourceFile\HouseKeeping\FileForceDeleter;
use App\ResourceFile\HouseKeeping\Providers\SoftDeletedResourceFiles;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForceDeleteSoftDeleted implements ShouldQueue
{
    public function handle(FileForceDeleter $forceDeleter, SoftDeletedResourceFiles $rfs): void
    {
        $forceDeleter($rfs);
    }
}
