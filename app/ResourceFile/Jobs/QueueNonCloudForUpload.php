<?php
/**
 * lel since 2019-07-20
 */

namespace App\ResourceFile\Jobs;

use App\ResourceFile\HouseKeeping\FileUploadQueuer;
use App\ResourceFile\HouseKeeping\Providers\NonCloudResourceFiles;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueueNonCloudForUpload implements ShouldQueue
{
    public function handle(FileUploadQueuer $uploadQueuer, NonCloudResourceFiles $rfs): void
    {
        $uploadQueuer($rfs);
    }
}
