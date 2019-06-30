<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\Jobs;

use App\ResourceFile\HouseKeeping\FileInstanceDeleter;
use App\ResourceFile\HouseKeeping\Providers\UnusedLocalResourceFileInstances;
use Tests\TestCase;

class DeleteUnusedLocalsTest extends TestCase
{
    public function testJob()
    {
        $fileInstanceDeleter = $this->getMockBuilder(FileInstanceDeleter::class)
            ->disableOriginalConstructor()
            ->setMethods(['__invoke'])
            ->getMock();

        $fileInstanceDeleter->expects(static::once())
            ->method('__invoke')
            ->with(static::callback(function ($subject) {
                return $subject instanceof UnusedLocalResourceFileInstances;
            }));

        $job = new \App\ResourceFile\Jobs\DeleteUnusedLocals();
        $this->app->call([$job, 'handle'], ['fileInstanceDeleter' => $fileInstanceDeleter]);
    }
}
