<?php
/**
 * lel since 25.08.19
 */

namespace Tests\Unit\EDC\Import\HouseKeeping;

use App\EDC\Import\HouseKeeping\FeedPartStockDeleter;
use App\EDC\Import\HouseKeeping\Providers\FeedPartStock;
use App\EDCFeed;
use App\EDCFeedPartStock;
use App\EDCProduct;
use App\EDCProductVariant;
use App\EDCProductVariantData;
use App\ResourceFile\ResourceFile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FeedPartStockDeleterTest extends TestCase
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

    public function testDeletionWithFile(): void
    {
        $rf = factory(ResourceFile::class)->create();

        $feedPart = $this->createFeedPart($rf);

        $provider = $this->createPreFilledProvider([$feedPart]);
        $deleter = $this->createDeleter();

        $deleter($provider);

        $rf->refresh();

        static::assertNotNull($rf->deleted_at);
        static::assertNull(EDCFeedPartStock::query()->find($feedPart->id));
    }

    public function testDeletionWithoutFile(): void
    {
        $feedPart = $this->createFeedPart();

        $provider = $this->createPreFilledProvider([$feedPart]);
        $deleter = $this->createDeleter();

        $deleter($provider);

        static::assertNull(EDCFeedPartStock::query()->find($feedPart->id));
    }

    protected function createDeleter(): FeedPartStockDeleter
    {
        return $this->app->make(FeedPartStockDeleter::class);
    }

    protected function createFeedPart(?ResourceFile $file = null): EDCFeedPartStock
    {
        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartStock();
        $feed->fullFeed()->associate($fullFeed);
        $feed->file()->associate($file);
        $feed->save();

        return $feed;
    }

    protected function createPreFilledProvider(iterable $filling): FeedPartStock
    {
        return new class($filling) extends FeedPartStock {
            protected $filling;

            public function __construct(iterable $filling)
            {
                $this->filling = $filling;

                parent::__construct();
            }

            protected function get(): \Traversable
            {
                return yield from $this->filling;
            }
        };
    }
}
