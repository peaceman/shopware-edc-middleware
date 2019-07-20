<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\ResourceFile\HouseKeeping\Providers;

use App\ResourceFile\HouseKeeping\Providers\NonCloudResourceFiles;
use App\ResourceFile\ResourceFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class NonCloudResourceFilesTest extends TestCase
{
    use DatabaseTransactions;

    /** @var ResourceFile */
    protected $rf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rf = factory(ResourceFile::class)->create();
    }

    public function testResourceFilesInCloudAreNotReturned()
    {
        $rfi = $this->rf->instances()->create(['disk' => 'cloud']);

        $provider = $this->createProvider();

        $receivedExpected = false;
        foreach ($provider as $pRF) {
            if ($pRF->id != $this->rf->id)
                continue;

            $receivedExpected = true;
            break;
        }

        static::assertFalse($receivedExpected);
    }

    public function testResourceFilesOnlyLocalAreReturned()
    {
        $rfi = $this->rf->instances()->create(['disk' => 'local']);

        $provider = $this->createProvider();

        $receivedExpected = false;
        foreach ($provider as $pRF) {
            if ($pRF->id != $this->rf->id)
                continue;

            $receivedExpected = true;
            break;
        }

        static::assertTrue($receivedExpected);
    }

    public function testResourceFilesLocalAndCloudAreNotReturned()
    {
        $rfi = $this->rf->instances()->create(['disk' => 'local']);
        $rfi = $this->rf->instances()->create(['disk' => 'cloud']);

        $provider = $this->createProvider();

        $receivedExpected = false;
        foreach ($provider as $pRF) {
            if ($pRF->id != $this->rf->id)
                continue;

            $receivedExpected = true;
            break;
        }

        static::assertFalse($receivedExpected);
    }

    protected function createProvider(): NonCloudResourceFiles
    {
        return new NonCloudResourceFiles();
    }
}
