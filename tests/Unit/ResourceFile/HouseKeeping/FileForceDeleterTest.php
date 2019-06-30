<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\HouseKeeping;

use App\ResourceFile\HouseKeeping\FileForceDeleter;
use App\ResourceFile\HouseKeeping\Providers\ResourceFileProvider;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FileForceDeleterTest extends TestCase
{
    use DatabaseTransactions;

    public function testFileForceDeleter()
    {
        $rf = factory(ResourceFile::class)->create();

        $resourceFileProvider = new class([$rf]) extends ResourceFileProvider {
            protected $rfs;

            public function __construct(iterable $rfs)
            {
                $this->rfs = $rfs;

                parent::__construct();
            }

            protected function get(): \Traversable
            {
                return yield from $this->rfs;
            }
        };

        $storageDirector = $this->getMockBuilder(StorageDirector::class)
            ->disableOriginalConstructor()
            ->setMethods(['forceDeleteFile'])
            ->getMock();

        $storageDirector->expects(static::once())
            ->method('forceDeleteFile')
            ->with(static::callback(function ($subject) use ($rf) {
                return $subject instanceof $rf
                    && $subject->id == $rf->id;
            }));

        /** @var FileForceDeleter $forceDeleter */
        $forceDeleter = $this->app->make(FileForceDeleter::class, [
            'storageDirector' => $storageDirector,
        ]);

        $forceDeleter($resourceFileProvider);
    }
}
