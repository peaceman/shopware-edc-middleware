<?php
/**
 * lel since 25.08.19
 */

namespace Tests\Unit\EDC\Import\HouseKeeping\Providers;

use App\EDC\Import\HouseKeeping\Providers\OldProductVariantData;
use App\EDCProduct;
use App\EDCProductVariant;
use App\EDCProductVariantData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OldProductVariantDataTest extends TestCase
{
    use DatabaseTransactions;

    /** @var EDCProduct */
    protected $ep;

    /** @var EDCProductVariant */
    protected $epv;

    /** @var EDCProductVariantData */
    protected $epvd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ep = factory(EDCProduct::class)->create();
        $this->epv = factory(EDCProductVariant::class)->create(['product_id' => $this->ep->id]);
        $this->epvd = factory(EDCProductVariantData::class)->create(['product_variant_id' => $this->epv->id]);
    }

    public function testCurrentAreNotReturned()
    {
        $provider = $this->createProvider();

        $receivedExpected = false;

        /** @var EDCProductVariantData $epvd */
        foreach ($provider as $epvd) {
            if ($epvd->id === $this->epvd->id) $receivedExpected = true;
        }

        static::assertFalse($receivedExpected);
    }

    public function testOldThatDontExceedKeepDaysAreNotReturned()
    {
        $provider = $this->createProvider();
        $this->epvd->update(['current_until' => now()]);

        $receivedExpected = false;

        /** @var EDCProductVariantData $epvd */
        foreach ($provider as $epvd) {
            if ($epvd->id === $this->epvd->id) $receivedExpected = true;
        }

        static::assertFalse($receivedExpected);
    }

    public function testOldThatExceedKeepDaysAreReturned()
    {
        $keepDays = 5;
        $provider = $this->createProvider($keepDays);
        $this->epvd->update(['current_until' => now()->subDays($keepDays)]);

        $receivedExpected = false;

        /** @var EDCProductVariantData $epvd */
        foreach ($provider as $epvd) {
            if ($epvd->id === $this->epvd->id) $receivedExpected = true;
        }

        static::assertTrue($receivedExpected);
    }

    protected function createProvider(int $keepDays = 3): OldProductVariantData
    {
        return new OldProductVariantData($keepDays);
    }

}
