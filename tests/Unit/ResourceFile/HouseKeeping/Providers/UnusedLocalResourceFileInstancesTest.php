<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile\HouseKeeping\Providers;

use App\ResourceFile\HouseKeeping\Providers\UnusedLocalResourceFileInstances;
use App\ResourceFile\ResourceFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UnusedLocalResourceFileInstancesTest extends TestCase
{
    use DatabaseTransactions;

    /** @var ResourceFile */
    protected $rf;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rf = factory(ResourceFile::class)->create();
    }

    public function testRecentlyUsedAreNotReturned()
    {
        $this->rf->instances()->create(['disk' => 'cloud', 'last_access_at' => now()->subDays(7)]);
        $rfi = $this->rf->instances()->create(['disk' => 'local', 'last_access_at' => now()]);

        $provider = new UnusedLocalResourceFileInstances(1);

        $receivedExpected = false;
        foreach ($provider as $pRFI) {
            if ($pRFI->id != $rfi->id)
                continue;

            $receivedExpected = true;
            break;
        }

        static::assertFalse($receivedExpected);
    }

    public function testLocalOnlyAreNotReturned()
    {
        $rfi = $this->rf->instances()->create(['disk' => 'local', 'last_access_at' => now()->subDays(2)]);

        $provider = new UnusedLocalResourceFileInstances(1);

        $receivedExpected = false;
        foreach ($provider as $pRFI) {
            if ($pRFI->id != $rfi->id)
                continue;

            $receivedExpected = true;
            break;
        }

        static::assertFalse($receivedExpected);
    }

    public function testCloudAreNotReturned()
    {
        $rfi = $this->rf->instances()->create(['disk' => 'cloud', 'last_access_at' => now()->subDays(7)]);
        $provider = new UnusedLocalResourceFileInstances(1);

        $receivedExpected = false;
        foreach ($provider as $pRFI) {
            if ($pRFI->id != $rfi->id)
                continue;

            $receivedExpected = true;
            break;
        }

        static::assertFalse($receivedExpected);
    }

    public function testQualifiedAreReturned()
    {
        $this->rf->instances()->create(['disk' => 'cloud', 'last_access_at' => now()->subDays(7)]);
        $rfi = $this->rf->instances()->create(['disk' => 'local', 'last_access_at' => now()->subDays(7)]);

        $provider = new UnusedLocalResourceFileInstances(1);

        $receivedExpected = false;
        foreach ($provider as $pRFI) {
            if ($pRFI->id != $rfi->id)
                continue;

            $receivedExpected = true;
            break;
        }

        static::assertTrue($receivedExpected);
    }
}
