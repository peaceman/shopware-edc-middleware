<?php
/**
 * lel since 2019-07-06
 */

namespace Tests\Unit\EDC\Import\Parser;

use App\EDC\Import\Events\ProductTouched;
use App\EDC\Import\Parser\ProductFeedPartParser;
use App\EDC\Import\ProductImageLoader;
use App\EDCFeed;
use App\EDCFeedPartProduct;
use App\EDCProduct;
use App\EDCProductVariant;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use Tests\TestCase;
use function App\Utils\fixture_path;

class ProductFeedPartParserTest extends TestCase
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

    public function testRegularProductParsing()
    {
        $this->app->instance(ProductImageLoader::class, $this->createProductImageLoaderMock([
            '02308550000.jpg',
            '02308550000_2.jpg',
            '02308550000_3.jpg',
            '02308550000_4.jpg',
        ]));

        Event::fake();
        $feed = $this->createProductFeedPartFromFile(fixture_path('product-1.xml'));
        $parser = $this->getProductFeedPartParser();

        $parser->parse($feed);

        // assert product creation
        /** @var EDCProduct $product */
        $product = EDCProduct::query()->where('edc_id', 1947)->first();
        static::assertNotNull($product);

        $productData = $product->currentData;
        static::assertNotNull($productData);

        static::assertEquals('02308550000', $productData->artnr);
        static::assertEquals($feed->id, $productData->feedPartProduct->id);

        // assert brand creation
        $brand = $product->brand;
        static::assertNotNull($brand);
        static::assertEquals(
            ['edc_brand_id' => 21, 'brand_name' => 'Cottelli Collection'],
            $brand->only(['edc_brand_id', 'brand_name'])
        );

        // assert variant creations
        static::assertEquals(2, $product->variants()->count());

        // first variant
        /** @var EDCProductVariant $variant */
        $variant = $product->variants()->where('edc_id', 1215)->first();
        static::assertNotNull($variant);

        $variantData = $variant->currentData;
        static::assertNotNull($variantData);
        static::assertEquals($feed->id, $variantData->feedPartProduct->id);
        static::assertEquals('02308550000', $variantData->subartnr);
        static::assertNull($variant->currentData->feedPartStock);

        // second variant
        /** @var EDCProductVariant $variant */
        $variant = $product->variants()->where('edc_id', 2300)->first();
        static::assertNotNull($variant);

        $variantData = $variant->currentData;
        static::assertNotNull($variantData);
        static::assertEquals($feed->id, $variantData->feedPartProduct->id);
        static::assertEquals('230863', $variantData->subartnr);
        static::assertNull($variant->currentData->feedPartStock);

        Event::assertDispatched(ProductTouched::class, function (ProductTouched $e) use ($product) {
            return $e->getProduct() instanceof $product && $e->getProduct()->id == $product->id;
        });
    }

    /**
     * @depends testRegularProductParsing
     */
    public function testUpdatingWithDuplicateFeedPartDoesntTriggerTheTouchedEvent()
    {
        // first pass
        $this->app->instance(ProductImageLoader::class, $this->createProductImageLoaderMock());

        Event::fake();
        $parser = $this->getProductFeedPartParser();

        $feed = $this->createProductFeedPartFromFile(fixture_path('product-1.xml'));
        $parser->parse($feed);

        // second pass
        $this->app->instance(ProductImageLoader::class, $this->createProductImageLoaderMock([
            '02308550000.jpg',
            '02308550000_2.jpg',
            '02308550000_3.jpg',
            '02308550000_4.jpg',
        ]));

        Event::fake();
        $parser = $this->getProductFeedPartParser();

        $feed = $this->createProductFeedPartFromFile(fixture_path('product-1.xml'));
        $parser->parse($feed);

        // assertions
        /** @var EDCProduct $product */
        $product = EDCProduct::query()->where('edc_id', 1947)->first();
        static::assertNotNull($product);
        static::assertEquals(2, $product->data()->count());

        foreach ($product->variants as $variant) {
            static::assertEquals(2, $variant->data()->count());
        }

        Event::assertNotDispatched(ProductTouched::class);
    }

    /**
     * @depends testRegularProductParsing
     */
    public function testUpdatingProductParsing()
    {
        // first pass
        $this->app->instance(ProductImageLoader::class, $this->createProductImageLoaderMock());

        Event::fake();
        $parser = $this->getProductFeedPartParser();

        $feed = $this->createProductFeedPartFromFile(fixture_path('product-1.xml'));
        $parser->parse($feed);

        // second pass
        $this->app->instance(ProductImageLoader::class, $this->createProductImageLoaderMock([
            '02308550000.jpg',
            '02308550000_2.jpg',
            '02308550000_3.jpg',
            '02308550000_4.jpg',
        ]));

        Event::fake();
        $parser = $this->getProductFeedPartParser();

        $feed = $this->createProductFeedPartFromFile(fixture_path('product-1-modified.xml'));
        $parser->parse($feed);

        // assertions
        /** @var EDCProduct $product */
        $product = EDCProduct::query()->where('edc_id', 1947)->first();
        static::assertNotNull($product);
        static::assertEquals(2, $product->data()->count());
        static::assertEquals(2, $product->variants()->count());

        foreach ($product->variants as $variant) {
            static::assertEquals(2, $variant->data()->count());
        }

        Event::assertDispatched(ProductTouched::class, function (ProductTouched $e) use ($product) {
            return $e->getProduct() instanceof $product
                && $e->getProduct()->id == $product->id;
        });
    }

    protected function createProductFeedPartFromFile(string $filePath): EDCFeedPartProduct
    {
        $rf = $this->storageDirector->createFileFromPath('product-feed-part.xml', $filePath);

        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartProduct();
        $feed->fullFeed()->associate($fullFeed);
        $feed->file()->associate($rf);
        $feed->save();

        return $feed;
    }

    protected function getProductFeedPartParser(): ProductFeedPartParser
    {
        return $this->app[ProductFeedPartParser::class];
    }

    protected function createProductImageLoaderMock(array $expectedFileNames = []): ProductImageLoader
    {
        $loader = $this->getMockBuilder(ProductImageLoader::class)
            ->setMethods(['loadImages'])
            ->disableOriginalConstructor()
            ->getMock();

        if (empty($expectedFileNames)) {
            $loader->expects(static::any())
                ->method('loadImages')
                ->withAnyParameters();
        } else {
            $loader->expects(static::once())
                ->method('loadImages')
                ->with(new IsInstanceOf(EDCProduct::class), $expectedFileNames);
        }

        return $loader;
    }
}
