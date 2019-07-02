<?php
/**
 * lel since 2019-07-02
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\Events\FeedFetched;
use App\EDC\Import\FeedFetcher;
use App\EDC\Import\Jobs\FetchFeed;
use App\EDCFeed;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FetchFeedTest extends TestCase
{
    public function testFetchFeedDispatchesFeedFetchedEvent()
    {
        Event::fake();

        $edcFeed = new EDCFeed();

        $feedFetcher = $this->getMockBuilder(FeedFetcher::class)
            ->setMethods(['fetch'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedFetcher->expects(static::once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn($edcFeed);

        $job = new FetchFeed(EDCFeed::TYPE_DISCOUNTS);
        $this->app->call([$job, 'handle'], ['feedFetcher' => $feedFetcher]);

        Event::assertDispatched(FeedFetched::class, function (FeedFetched $e) use ($edcFeed) {
            return $edcFeed === $e->getEDCFeed();
        });
    }

    public function testFetchFeedDispatchesFeedFetchedEventOnlyIfANewFeedWasFetched()
    {
        Event::fake();

        $feedFetcher = $this->getMockBuilder(FeedFetcher::class)
            ->setMethods(['fetch'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedFetcher->expects(static::once())
            ->method('fetch')
            ->withAnyParameters()
            ->willReturn(null);

        $job = new FetchFeed(EDCFeed::TYPE_DISCOUNTS);
        $this->app->call([$job, 'handle'], ['feedFetcher' => $feedFetcher]);

        Event::assertNotDispatched(FeedFetched::class);
    }

    public function testFeedFetcherIsCalledWithCorrectTypeAndURI()
    {
        Event::fake();

        $feedType = EDCFeed::TYPE_DISCOUNTS;
        $feedURI = 'https://multilevel.example.com';

        config()->set("edc.feedURI.{$feedType}", $feedURI);

        $feedFetcher = $this->getMockBuilder(FeedFetcher::class)
            ->setMethods(['fetch'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedURI, $feedType)
            ->willReturn(null);

        $job = new FetchFeed($feedType);
        $this->app->call([$job, 'handle'], ['feedFetcher' => $feedFetcher]);
    }

    public function testRequestExceptionWontBubbleUpAndTriggerARetry()
    {
        Event::fake();

        $feedType = EDCFeed::TYPE_DISCOUNTS;
        $feedURI = 'https://multilevel.example.com';

        config()->set("edc.feedURI.{$feedType}", $feedURI);

        $feedFetcher = $this->getMockBuilder(FeedFetcher::class)
            ->setMethods(['fetch'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedFetcher->expects(static::once())
            ->method('fetch')
            ->with($feedURI, $feedType)
            ->willThrowException(new RequestException('test', new Request('POST', 'topkek.com')));

        $job = new FetchFeed($feedType);
        $this->app->call([$job, 'handle'], ['feedFetcher' => $feedFetcher]);
        static::addToAssertionCount(1);
    }
}
