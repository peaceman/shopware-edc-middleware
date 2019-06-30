<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\Jobs;

use App\ResourceFile\Jobs\UpdateLastAccessTime;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateLastAccessTimeTest extends TestCase
{
    use DatabaseTransactions;

    public function testJob()
    {
        $rf = factory(ResourceFile::class)->create();
        $rfi = $rf->instances()->create(['disk' => 'local']);

        $storageDirector = $this->getMockBuilder(StorageDirector::class)
            ->setMethods(['updateLastAccessTime'])
            ->disableOriginalConstructor()
            ->getMock();

        $storageDirector->expects(static::once())
            ->method('updateLastAccessTime')
            ->with(static::callback(function ($subject) use ($rfi) {
                return $subject instanceof $rfi
                    && $subject->id == $rfi->id;
            }));

        $job = new UpdateLastAccessTime([$rfi->id, $rfi->id + 23]);
        $job->handle($storageDirector);
    }
}
