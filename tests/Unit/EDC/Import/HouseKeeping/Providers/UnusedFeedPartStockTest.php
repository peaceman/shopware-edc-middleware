<?php
/**
 * lel since 25.08.19
 */

namespace Tests\Unit\EDC\Import\HouseKeeping\Providers;

use App\EDC\Import\HouseKeeping\Providers\UnusedFeedPartStock;
use App\EDCFeed;
use App\EDCFeedPartStock;
use App\EDCProduct;
use App\EDCProductVariant;
use App\EDCProductVariantData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UnusedFeedPartStockTest extends TestCase
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

        Event::fake();
    }

    public function testUsedAreNotReturned()
    {
        $provider = $this->createProvider();

        $feedPart = $this->createFeedPart();
        $this->epvd->feedPartStock()->associate($feedPart);
        $this->epvd->save();

        $receivedExpected = false;
        foreach ($provider as $fps) {
            if ($fps->id === $feedPart->id) {
                $receivedExpected = true;
                break;
            }
        }

        static::assertFalse($receivedExpected);
    }

    public function testUnusedAreReturned()
    {
        $provider = $this->createProvider();

        $feedPart = $this->createFeedPart();

        $receivedExpected = false;
        foreach ($provider as $fps) {
            if ($fps->id === $feedPart->id) {
                $receivedExpected = true;
                break;
            }
        }

        static::assertTrue($receivedExpected);
    }

    protected function createFeedPart(): EDCFeedPartStock
    {
        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartStock();
        $feed->fullFeed()->associate($fullFeed);
        $feed->save();

        return $feed;
    }

    protected function createProvider(): UnusedFeedPartStock
    {
        return new UnusedFeedPartStock();
    }

}
