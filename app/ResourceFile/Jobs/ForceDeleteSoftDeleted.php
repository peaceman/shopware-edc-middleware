<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\Jobs;

use App\Jobs\BaseJob;
use App\ResourceFile\HouseKeeping\FileForceDeleter;
use App\ResourceFile\HouseKeeping\Providers\SoftDeletedResourceFiles;

class ForceDeleteSoftDeleted extends BaseJob
{
    public function handle(FileForceDeleter $forceDeleter, SoftDeletedResourceFiles $rfs): void
    {
        $forceDeleter($rfs);
    }
}
