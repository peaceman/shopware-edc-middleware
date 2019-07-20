<?php
/**
 * lel since 2019-07-20
 */

namespace App\ResourceFile\Jobs;

use App\Jobs\BaseJob;
use App\ResourceFile\HouseKeeping\FileUploadQueuer;
use App\ResourceFile\HouseKeeping\Providers\NonCloudResourceFiles;

class QueueNonCloudForUpload extends BaseJob
{
    public function handle(FileUploadQueuer $uploadQueuer, NonCloudResourceFiles $rfs): void
    {
        $uploadQueuer($rfs);
    }
}
