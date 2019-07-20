<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\ResourceFile\Jobs;

use App\ResourceFile\HouseKeeping\FileUploadQueuer;
use App\ResourceFile\HouseKeeping\Providers\NonCloudResourceFiles;
use App\ResourceFile\Jobs\QueueNonCloudForUpload;
use Tests\TestCase;

class QueueNonCloudForUploadTest extends TestCase
{
    public function testJob()
    {
        $uploadQueuer = $this->getMockBuilder(FileUploadQueuer::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $uploadQueuer->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function ($subject) {
                return $subject instanceof NonCloudResourceFiles;
            }));

        $job = new QueueNonCloudForUpload();
        $this->app->call(
            [$job, 'handle'],
            ['uploadQueuer' => $uploadQueuer]
        );
    }
}
