<?php
/**
 * lel since 2019-07-06
 */

namespace Tests\Unit\EDC\Import\Parser;

use App\EDC\Import\Jobs\ParseProductFeedPart;
use App\EDC\Import\Parser\ProductFeedParser;
use App\EDCFeed;
use App\EDCFeedPartProduct;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_path;

class ProductFeedParserTest extends TestCase
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

        $edcFeed = $this->createEDCFeedFromFile(fixture_path('products.xml'));
        $parser = $this->getProductFeedParser();

        $parser->parse($edcFeed);

        // assert feed part creation
        $productFeedParts = EDCFeedPartProduct::query()->where('full_feed_id', $edcFeed->id)->with('file')->get();
        static::assertEquals(3, $productFeedParts->count());

        $feedPartFixtureChecksums = collect(range(1, 3))
            ->map(function (int $idx) {
                return md5_file(fixture_path("product-$idx.xml"));
            })
            ->all();

        $feedPartsChecksums = $productFeedParts->pluck('file.checksum')->all();

        static::assertEqualsCanonicalizing($feedPartFixtureChecksums, $feedPartsChecksums);

        foreach ($productFeedParts as $productFeedPart) {
            Queue::assertPushed(ParseProductFeedPart::class, function (ParseProductFeedPart $job) use ($productFeedPart) {
                return $job->feedPart instanceof $productFeedPart
                    && $job->feedPart->id == $productFeedPart->id;
            });
        }
    }

    public function testUnexpectedXMLStructure()
    {
        Queue::fake();

        $edcFeed = $this->createEDCFeedFromFile(fixture_path('products-broken.xml'));
        $parser = $this->getProductFeedParser();

        $parser->parse($edcFeed);

        static::assertEquals(0, EDCFeedPartProduct::query()->where('full_feed_id', $edcFeed->id)->count());
        Queue::assertNotPushed(ParseProductFeedPart::class);
    }

    /**
     * @depends testRegularFeedParsing
     */
    public function testParserWontCreateFeedPartDuplicates()
    {
        // first pass
        Queue::fake();

        $edcFeed = $this->createEDCFeedFromFile(fixture_path('products.xml'));
        $parser = $this->getProductFeedParser();

        $parser->parse($edcFeed);

        $feedPartCount = EDCFeedPartProduct::query()->where('full_feed_id', $edcFeed->id)->count();

        // second pass
        Queue::fake();

        $parser->parse($edcFeed);

        static::assertEquals($feedPartCount, EDCFeedPartProduct::query()->where('full_feed_id', $edcFeed->id)->count());
        Queue::assertNotPushed(ParseProductFeedPart::class);
    }

    protected function createEDCFeedFromFile(string $filePath): EDCFeed
    {
        $rf = $this->storageDirector->createFileFromPath('products.xml', $filePath);

        $feed = new EDCFeed(['type' => EDCFeed::TYPE_PRODUCTS]);
        $feed->file()->associate($rf);
        $feed->save();

        return $feed;
    }

    protected function getProductFeedParser(): ProductFeedParser
    {
        return $this->app[ProductFeedParser::class];
    }
}
