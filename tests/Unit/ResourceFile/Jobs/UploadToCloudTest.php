<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\Jobs;

use App\ResourceFile\Jobs\UpdateLastAccessTime;
use App\ResourceFile\Jobs\UploadToCloud;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UploadToCloudTest extends TestCase
{
    use DatabaseTransactions;

    public function testJob()
    {
        $rf = factory(ResourceFile::class)->create();

        $storageDirector = $this->getMockBuilder(StorageDirector::class)
            ->setMethods(['uploadToCloud'])
            ->disableOriginalConstructor()
            ->getMock();

        $storageDirector->expects(static::once())
            ->method('uploadToCloud')
            ->with(static::callback(function ($subject) use ($rf) {
                return $subject instanceof $rf
                    && $subject->id == $rf->id;
            }));

        $job = new UploadToCloud([$rf->id, $rf->id + 23]);
        $job->handle($storageDirector);
    }
}
