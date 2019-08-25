<?php
/**
 * lel since 2019-07-07
 */

namespace Tests\Unit\EDC\Import\Parser;

use App\EDC\Import\Jobs\ParseProductStockFeedPart;
use App\EDC\Import\Parser\ProductStockFeedParser;
use App\EDCFeed;
use App\EDCFeedPartStock;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_path;

class ProductStockFeedParserTest extends TestCase
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
        Queue::fake();

        $feed = $this->createEDCFeedFromFile(fixture_path('product-stocks.xml'));
        $parser = $this->getProductStockFeedParser();

        $parser->parse($feed);

        // assert feed part creation
        $stockFeedParts = EDCFeedPartStock::query()->where('full_feed_id', $feed->id)->with('file')->get();
        static::assertEquals(2, $stockFeedParts->count());

        $feedPartFixtureChecksums = collect(range(1, 2))
            ->map(function (int $idx) {
                return md5_file(fixture_path("product-stocks-$idx.xml"));
            })
            ->all();

        $feedPartsChecksums = $stockFeedParts->map(function (EDCFeedPartStock $feedPart) {
            return md5($feedPart->content);
        })->all();

        static::assertEqualsCanonicalizing($feedPartFixtureChecksums, $feedPartsChecksums);

        foreach ($stockFeedParts as $productFeedPart) {
            Queue::assertPushed(
                ParseProductStockFeedPart::class,
                function (ParseProductStockFeedPart $job) use ($productFeedPart) {
                    return $job->feedPart instanceof $productFeedPart
                        && $job->feedPart->id == $productFeedPart->id;
                }
            );
        }
    }

    public function testBrokenFeed()
    {
        Queue::fake();

        $feed = $this->createEDCFeedFromFile(fixture_path('product-stocks-broken.xml'));
        $parser = $this->getProductStockFeedParser();

        $parser->parse($feed);

        static::assertEquals(0, EDCFeedPartStock::query()->where('full_feed_id', $feed->id)->count());
        Queue::assertNotPushed(ParseProductStockFeedPart::class);
    }

    public function testParserWontCreateFeedPartDuplicates()
    {
        // first pass
        Queue::fake();

        $feed = $this->createEDCFeedFromFile(fixture_path('product-stocks.xml'));
        $parser = $this->getProductStockFeedParser();

        $parser->parse($feed);

        $feedPartCount = $feed->stockFeedParts()->count();

        // second pass
        Queue::fake();

        $parser->parse($feed);

        static::assertEquals($feedPartCount, $feed->stockFeedParts()->count());
        Queue::assertNotPushed(ParseProductStockFeedPart::class);

        // third pass with fresh parser
        Queue::fake();

        $parser = $this->getProductStockFeedParser();
        $parser->parse($feed);

        static::assertEquals($feedPartCount, $feed->stockFeedParts()->count());
        Queue::assertNotPushed(ParseProductStockFeedPart::class);
    }

    protected function createEDCFeedFromFile(string $filePath): EDCFeed
    {
        $rf = $this->storageDirector->createFileFromPath('product-stocks.xml', $filePath);

        $feed = new EDCFeed(['type' => EDCFeed::TYPE_PRODUCT_STOCKS]);
        $feed->file()->associate($rf);
        $feed->save();

        return $feed;
    }

    protected function getProductStockFeedParser(): ProductStockFeedParser
    {
        return $this->app[ProductStockFeedParser::class];
    }
}
