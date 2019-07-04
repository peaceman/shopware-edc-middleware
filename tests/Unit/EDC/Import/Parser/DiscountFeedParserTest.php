<?php
/**
 * lel since 2019-07-04
 */

namespace Tests\Unit\EDC\Import\Parser;

use App\Brand;
use App\EDC\Import\Events\BrandDiscountTouched;
use App\EDC\Import\Parser\DiscountFeedParser;
use App\EDCFeed;
use App\ResourceFile\StorageDirector;
use function App\Utils\fixture_path;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DiscountFeedParserTest extends TestCase
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

    public function testRegularFeedParsing()
    {
        $edcFeed = $this->createEDCFeedFromFile(fixture_path('discountoverview.csv'));
        $parser = $this->getDiscountFeedParser();

        // clean brand tables
        Brand::query()->delete();

        $parser->parse($edcFeed);

        $expectedData = [
            ['brandId' => 171, 'name' => 'Hirthe-Gaylord', 'discount' => 10],
            ['brandId' => 472, 'name' => 'Shanahan-Reinger', 'discount' => 35],
            ['brandId' => 744, 'name' => 'Turcotte, Upton and Hirthe', 'discount' => 35],
            ['brandId' => 542, 'name' => 'Beier and Sons', 'discount' => 35],
            ['brandId' => 250, 'name' => 'Watsica, Schaden and VonRueden', 'discount' => 35],
        ];

        foreach ($expectedData as $expected) {
            /** @var Brand $brand */
            $brand = Brand::query()->withBrandID($expected['brandId'])->first();
            static::assertNotNull($brand);
            static::assertEquals($expected['name'], $brand->brand_name);

            $discount = $brand->currentDiscount;
            static::assertNotNull($discount);
            static::assertEquals($expected['discount'], $discount->value);
            static::assertEquals($edcFeed->id, $discount->edc_feed_id);
        }
    }

    /**
     * @depends testRegularFeedParsing
     */
    public function testPartialFeedParsing()
    {
        Event::fake();
        $parser = $this->getDiscountFeedParser();

        // clean brand tables
        Brand::query()->delete();

        // first pass (initial filling)
        $edcFeed = $this->createEDCFeedFromFile(fixture_path('discountoverview.csv'));
        $parser->parse($edcFeed);

        // second pass
        $edcFeed = $this->createEDCFeedFromFile(fixture_path('discountoverview-partial.csv'));

        $parser->parse($edcFeed);

        // assert brand update where the brand is missing in the partial update
        /** @var Brand $brand */
        $brand = Brand::query()->withBrandID(171)->first();
        static::assertNotNull($brand);
        static::assertEquals(2, $brand->discounts()->count());

        $discount = $brand->currentDiscount;
        static::assertNotNull($discount);
        static::assertEquals(0, $discount->value);
        static::assertEquals($edcFeed->id, $discount->edc_feed_id);

        Event::assertDispatched(BrandDiscountTouched::class, function (BrandDiscountTouched $e) use ($brand) {
            return $e->brand->id == $brand->id;
        });

        // assert brand update where the brand is in the partial update
        $brand = Brand::query()->withBrandID(472)->first();
        static::assertNotNull($brand);
        static::assertEquals(2, $brand->discounts()->count());

        $discount = $brand->currentDiscount;
        static::assertNotNull($discount);
        static::assertEquals(23, $discount->value);
        static::assertEquals($edcFeed->id, $discount->edc_feed_id);

        Event::assertDispatched(BrandDiscountTouched::class, function (BrandDiscountTouched $e) use ($brand) {
            return $e->brand->id == $brand->id;
        });
    }

    /**
     * @depends testRegularFeedParsing
     */
    public function testEmptyOrBrokenFeedParsing()
    {
        $parser = $this->getDiscountFeedParser();

        // clean brand tables
        Brand::query()->delete();

        // first pass (initial filling)
        $edcFeed = $this->createEDCFeedFromFile(fixture_path('discountoverview.csv'));
        $parser->parse($edcFeed);

        // second pass
        $edcFeed = $this->createEDCFeedFromFile(fixture_path('discountoverview-broken.csv'));
        $parser->parse($edcFeed);

        static::assertGreaterThan(0, Brand::query()->count());

        /** @var Brand $brand */
        foreach (Brand::query()->with('discounts')->get() as $brand) {
            static::assertEquals(1, $brand->discounts->count());
        }
    }

    protected function createEDCFeedFromFile(string $filePath): EDCFeed
    {
        $rf = $this->storageDirector->createFileFromPath('discountoverview.csv', $filePath);

        $feed = new EDCFeed(['type' => EDCFeed::TYPE_DISCOUNTS]);
        $feed->file()->associate($rf);
        $feed->save();

        return $feed;
    }

    protected function getDiscountFeedParser(): DiscountFeedParser
    {
        return $this->app[DiscountFeedParser::class];
    }
}
