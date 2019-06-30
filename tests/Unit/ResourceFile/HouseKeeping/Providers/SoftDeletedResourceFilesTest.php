<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\HouseKeeping\Providers;

use App\ResourceFile\HouseKeeping\Providers\SoftDeletedResourceFiles;
use App\ResourceFile\ResourceFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SoftDeletedResourceFilesTest extends TestCase
{
    use DatabaseTransactions;

    public function testNonSoftDeletedAreNotReturned()
    {
        $rf = factory(ResourceFile::class)->create();

        $provider = new SoftDeletedResourceFiles(3);
        foreach ($provider as $prf) {
            static::assertNotEquals($rf->id, $prf->id);
        }

        // prevent failure if we don't receive any results; as expected
        static::assertTrue(true);
    }

    public function testSoftDeletedAreReturned()
    {
        $rf = factory(ResourceFile::class)->create(['deleted_at' => now()->subDays(5)]);

        $provider = new SoftDeletedResourceFiles(3);
        foreach ($provider as $pRF) {
            if ($rf->id == $pRF->id) {
                $receivedExpected = true;
                break;
            }
        }

        static::assertTrue($receivedExpected ?? null);
    }

    public function testNotOldEnoughAreNotReturned()
    {
        $rf = factory(ResourceFile::class)->create(['deleted_at' => now()]);

        $provider = new SoftDeletedResourceFiles(3);

        $receivedExpected = false;
        foreach ($provider as $pRF) {
            if ($rf->id == $pRF->id) {
                $receivedExpected = true;
                break;
            }
        }

        static::assertFalse($receivedExpected);
    }
}
