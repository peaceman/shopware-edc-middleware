<?php
/**
 * lel since 2019-07-17
 */

namespace Tests\Unit\ResourceFile;

use App\ResourceFile\StorageDirector;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobProcessed;
use Tests\TestCase;

class JobProcessingSubscriberTest extends TestCase
{
    public function testJobProcessingSubscriber()
    {
        $storageDirector = $this->getMockBuilder(StorageDirector::class)
            ->disableOriginalConstructor()
            ->setMethods(['flushQueues'])
            ->getMock();

        $storageDirector->expects(static::once())
            ->method('flushQueues');

        $eventDispatcher = new Dispatcher();

        $subscriber = new \App\ResourceFile\JobProcessingSubscriber($storageDirector);
        $subscriber->subscribe($eventDispatcher);

        $eventDispatcher->dispatch(new JobProcessed('lul', $this->createMock(Job::class)));
    }
}
