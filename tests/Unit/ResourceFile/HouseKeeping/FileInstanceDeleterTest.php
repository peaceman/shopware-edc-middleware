<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\HouseKeeping;

use App\ResourceFile\HouseKeeping\FileInstanceDeleter;
use App\ResourceFile\HouseKeeping\Providers\ResourceFileInstanceProvider;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FileInstanceDeleterTest extends TestCase
{
    use DatabaseTransactions;

    public function testUnusedLocalDeleter()
    {
        /** @var ResourceFile $rf */
        $rf = factory(ResourceFile::class)->create();
        $rfi = $rf->instances()->create(['disk' => 'local']);

        $rfiProvider = new class([$rfi]) extends ResourceFileInstanceProvider {
            protected $rfis;

            public function __construct(iterable $rfis)
            {
                $this->rfis = $rfis;

                parent::__construct();
            }

            protected function get(): \Traversable
            {
                return yield from $this->rfis;
            }
        };

        $storageDirector = $this->getMockBuilder(StorageDirector::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteFileInstance'])
            ->getMock();

        $storageDirector->expects(static::once())
            ->method('deleteFileInstance')
            ->with(static::callback(function ($subject) use ($rfi) {
                return $subject instanceof $rfi
                    && $subject->id == $rfi->id;
            }));

        /** @var FileInstanceDeleter $fileInstanceDeleter */
        $fileInstanceDeleter = $this->app->make(FileInstanceDeleter::class, [
            'storageDirector' => $storageDirector,
        ]);

        $fileInstanceDeleter($rfiProvider);
    }
}
