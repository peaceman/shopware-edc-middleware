<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\Jobs;

use App\ResourceFile\HouseKeeping\FileForceDeleter;
use App\ResourceFile\HouseKeeping\Providers\SoftDeletedResourceFiles;
use App\ResourceFile\Jobs\ForceDeleteSoftDeleted;
use Tests\TestCase;

class ForceDeleteSoftDeletedTest extends TestCase
{
    public function testJob()
    {
        $forceDeleter = $this->getMockBuilder(FileForceDeleter::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $forceDeleter->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function ($subject) {
                return $subject instanceof SoftDeletedResourceFiles;
            }));

        $job = new ForceDeleteSoftDeleted();
        $this->app->call([$job, 'handle'], ['forceDeleter' => $forceDeleter]);
    }
}
