<?php
/**
 * lel since 2019-07-01
 */

namespace Tests\Unit\EDC\Import;

use App\EDC\Import\FeedFetcher;
use App\EDCFeed;
use App\ResourceFile\ResourceFile;
use function App\Utils\fixture_path;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedFetcherTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake(Storage::getDefaultCloudDriver());
    }

    public function testFetchesFeed()
    {
        $requestContainer = [];
        $mockHandler = new MockHandler([
            new Response(200, [], fopen(base_path('docs/fixtures/discountoverview.csv'), 'r'))
        ]);

        $handler = HandlerStack::create($mockHandler);
        $handler->push(Middleware::history($requestContainer));

        $client = new Client(['handler' => $handler]);

        /** @var FeedFetcher $feedFetcher */
        $feedFetcher = $this->app->make(FeedFetcher::class, ['httpClient' => $client]);

        $edcFeed = $feedFetcher->fetch('https://example.com/feed.csv', EDCFeed::TYPE_DISCOUNTS);

        static::assertNotNull($edcFeed);
        static::assertInstanceOf(EDCFeed::class, $edcFeed);
        static::assertEquals(EDCFeed::TYPE_DISCOUNTS, $edcFeed->type);
        static::assertNotEmpty($requestContainer);
        static::assertEquals('feed.csv', $edcFeed->file->original_filename);
    }

    public function testDoesntProduceDuplicateFeeds()
    {
        /** @var ResourceFile $rf */
        $rf = factory(ResourceFile::class)
            ->create(['checksum' => md5_file(fixture_path('discountoverview.csv'))]);

        /** @var EDCFeed $edcFeed */
        $edcFeed = factory(EDCFeed::class)
            ->create(['resource_file_id' => $rf->id, 'type' => EDCFeed::TYPE_DISCOUNTS]);

        $mockHandler = new MockHandler([
            new Response(200, [], fopen(fixture_path('discountoverview.csv') ,'r'))
        ]);

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        /** @var FeedFetcher $feedFetcher */
        $feedFetcher = $this->app->make(FeedFetcher::class, ['httpClient' => $client]);
        $edcFeed = $feedFetcher->fetch('https://example.com/feed.csv', EDCFeed::TYPE_DISCOUNTS);

        static::assertNull($edcFeed);
    }

    public function testDuplicateCheckIsFeedTypeScoped()
    {
        /** @var ResourceFile $rf */
        $rf = factory(ResourceFile::class)
            ->create(['checksum' => md5_file(fixture_path('discountoverview.csv'))]);

        /** @var EDCFeed $edcFeed */
        $edcFeed = factory(EDCFeed::class)
            ->create(['resource_file_id' => $rf->id, 'type' => EDCFeed::TYPE_PRODUCTS]);

        $mockHandler = new MockHandler([
            new Response(200, [], fopen(fixture_path('discountoverview.csv') ,'r'))
        ]);

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        /** @var FeedFetcher $feedFetcher */
        $feedFetcher = $this->app->make(FeedFetcher::class, ['httpClient' => $client]);
        $edcFeed = $feedFetcher->fetch('https://example.com/feed.csv', EDCFeed::TYPE_DISCOUNTS);

        static::assertNotNull($edcFeed);
    }

    public function testUnzipsZipArchives()
    {
        $mockHandler = new Mockhandler([
            new Response(200, [], fopen(fixture_path('discountoverview.zip'), 'r')),
        ]);

        $handler = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $handler]);

        /** @var FeedFetcher $feedFetcher */
        $feedFetcher = $this->app->make(FeedFetcher::class, ['httpClient' => $client]);
        $edcFeed = $feedFetcher->fetch('https://example.com/feed.zip', EDCFeed::TYPE_DISCOUNTS);

        static::assertNotNull($edcFeed);
        static::assertEquals('discountoverview.csv', $edcFeed->file->original_filename);
        static::assertEquals(md5_file(fixture_path('discountoverview.csv')), $edcFeed->file->checksum);
    }
}
