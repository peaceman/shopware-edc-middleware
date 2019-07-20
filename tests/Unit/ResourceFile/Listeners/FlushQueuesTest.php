<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\ResourceFile\Listeners;

use App\ResourceFile\StorageDirector;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Tests\TestCase;

class FlushQueuesTest extends TestCase
{
    public function testFlushQueues()
    {
        $storageDirector = $this->getMockBuilder(StorageDirector::class)
            ->disableOriginalConstructor()
            ->setMethods(['flushQueues'])
            ->getMock();

        $storageDirector->expects(static::once())
            ->method('flushQueues');

        $this->app[StorageDirector::class] = $storageDirector;

        $this->app[Dispatcher::class]->dispatch(new JobProcessed('lul', $this->createMock(Job::class)));
    }
}
