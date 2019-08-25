<?php
/**
 * lel since 2019-07-07
 */

namespace Tests\Unit\EDC\Import\Parser;

use App\EDC\Import\Events\ProductTouched;
use App\EDC\Import\Parser\ProductStockFeedPartParser;
use App\EDCFeed;
use App\EDCFeedPartStock;
use App\EDCProduct;
use App\EDCProductVariant;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_path;

class ProductStockFeedPartParserTest extends TestCase
{
    use DatabaseTransactions;

    /** @var StorageDirector */
    protected $storageDirector;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake(Storage::getDefaultCloudDriver());

        $this->storageDirector = $this->app[StorageDirector::class];
    }

    public function testRegularParsing()
    {
        $product = factory(EDCProduct::class)->create(['edc_id' => 1947]);
        $firstVariant = factory(EDCProductVariant::class)->create(['product_id' => $product->id, 'edc_id' => 2300]);
        $secondVariant = factory(EDCProductVariant::class)->create(['product_id' => $product->id, 'edc_id' => 1215]);

        $feed = $this->createStockFeedPartFromFile(fixture_path('product-stocks-2.xml'));

        Event::fake();
        $parser = $this->createStockFeedPartParser();
        $parser->parse($feed);

        // assert feed part stock assignment
        /** @var EDCProductVariant $variant */
        $variant = $firstVariant;
        $variant->refresh();
        static::assertEquals(2, $variant->data()->count());
        static::assertEquals($feed->id, $variant->currentData->feedPartStock->id);

        $variant = $secondVariant;
        $variant->refresh();
        static::assertEquals(2, $variant->data()->count());
        static::assertEquals($feed->id, $variant->currentData->feedPartStock->id);

        Event::assertDispatchedTimes(ProductTouched::class, 1);
        Event::assertDispatched(ProductTouched::class, function (ProductTouched $e) use ($product) {
            return $e->getProduct() instanceof $product && $e->getProduct()->id == $product->id;
        });
    }

    /**
     * @depends testRegularParsing
     */
    public function testUpdatingWithDuplicateFeedPartDoesntTriggerTheTouchedEvent()
    {
        $product = factory(EDCProduct::class)->create(['edc_id' => 1947]);
        $firstVariant = factory(EDCProductVariant::class)->create(['product_id' => $product->id, 'edc_id' => 2300]);
        $secondVariant = factory(EDCProductVariant::class)->create(['product_id' => $product->id, 'edc_id' => 1215]);

        $feed = $this->createStockFeedPartFromFile(fixture_path('product-stocks-2.xml'));

        // first pass
        Event::fake();
        $parser = $this->createStockFeedPartParser();
        $parser->parse($feed);

        // second pass
        Event::fake();
        $parser = $this->createStockFeedPartParser();
        $parser->parse($feed);

        // assert feed part stock assignment
        /** @var EDCProductVariant $variant */
        $variant = $firstVariant;
        $variant->refresh();
        static::assertEquals(3, $variant->data()->count());
        static::assertEquals($feed->id, $variant->currentData->feedPartStock->id);

        $variant = $secondVariant;
        $variant->refresh();
        static::assertEquals(3, $variant->data()->count());
        static::assertEquals($feed->id, $variant->currentData->feedPartStock->id);

        Event::assertNotDispatched(ProductTouched::class);
    }

    /**
     * @depends testRegularParsing
     */
    public function testParsingWithUnknownProduct()
    {
        $product = factory(EDCProduct::class)->create(['edc_id' => 23]);

        $feed = $this->createStockFeedPartFromFile(fixture_path('product-stocks-2.xml'));

        Event::fake();
        $parser = $this->createStockFeedPartParser();
        $parser->parse($feed);

        Event::assertNotDispatched(ProductTouched::class);
    }

    public function testParsingWithUnknownVariant()
    {
        $product = factory(EDCProduct::class)->create(['edc_id' => 1947]);
        $firstVariant = factory(EDCProductVariant::class)->create(['product_id' => $product->id, 'edc_id' => 2300]);

        $feed = $this->createStockFeedPartFromFile(fixture_path('product-stocks-2.xml'));

        Event::fake();
        $parser = $this->createStockFeedPartParser();
        $parser->parse($feed);

        // assert feed part stock assignment
        /** @var EDCProductVariant $variant */
        $variant = $firstVariant;
        $variant->refresh();
        static::assertEquals(2, $variant->data()->count());
        static::assertEquals($feed->id, $variant->currentData->feedPartStock->id);

        Event::assertDispatchedTimes(ProductTouched::class, 1);
        Event::assertDispatched(ProductTouched::class, function (ProductTouched $e) use ($product) {
            return $e->getProduct() instanceof $product && $e->getProduct()->id == $product->id;
        });
    }

    protected function createStockFeedPartFromFile(string $filePath): EDCFeedPartStock
    {
        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartStock();
        $feed->fullFeed()->associate($fullFeed);
        $feed->content = file_get_contents($filePath);
        $feed->save();

        return $feed->fresh();
    }

    protected function createStockFeedPartParser(): ProductStockFeedPartParser
    {
        return $this->app[ProductStockFeedPartParser::class];
    }
}
