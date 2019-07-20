<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\ResourceFile\HouseKeeping;

use App\ResourceFile\HouseKeeping\FileUploadQueuer;
use App\ResourceFile\HouseKeeping\Providers\PreFilledRFProvider;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use Tests\TestCase;

class FileUploadQueuerTest extends TestCase
{
    public function testUploadQueuer()
    {
        $rf = factory(ResourceFile::class)->create();

        $storageDirector = $this->getMockBuilder(StorageDirector::class)
            ->disableOriginalConstructor()
            ->setMethods(['addToUploadQueue'])
            ->getMock();

        $storageDirector->expects(static::once())
            ->method('addToUploadQueue')
            ->with($rf);

        $rfProvider = new PreFilledRFProvider([$rf]);

        $fileUploadQueuer = $this->app->make(FileUploadQueuer::class, [
            'storageDirector' => $storageDirector,
        ]);

        $fileUploadQueuer($rfProvider);
    }
}
