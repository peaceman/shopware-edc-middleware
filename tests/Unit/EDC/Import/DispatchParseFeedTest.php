<?php
/**
 * lel since 2019-07-03
 */

namespace Tests\Unit\EDC\Import;

use App\EDC\Import\DispatchParseFeed;
use App\EDC\Import\Events\FeedFetched;
use App\EDC\Import\Exceptions\UnknownFeedType;
use App\EDC\Import\Jobs\ParseDiscountFeed;
use App\EDC\Import\Jobs\ParseProductFeed;
use App\EDC\Import\Jobs\ParseProductStockFeed;
use App\EDCFeed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DispatchParseFeedTest extends TestCase
{
    public function dispatchedRegisteredParseFeedJobForTypeProvider(): array
    {
        return [
            EDCFeed::TYPE_DISCOUNTS => [
                EDCFeed::TYPE_DISCOUNTS,
                ParseDiscountFeed::class,
            ],
            EDCFeed::TYPE_PRODUCT_STOCKS => [
                EDCFeed::TYPE_PRODUCT_STOCKS,
                ParseProductStockFeed::class,
            ],
            EDCFeed::TYPE_PRODUCTS => [
                EDCFeed::TYPE_PRODUCTS,
                ParseProductFeed::class,
            ],
        ];
    }

    /**
     * @dataProvider dispatchedRegisteredParseFeedJobForTypeProvider
     */
    public function testDispatchesRegisteredParseFeedJobForType(string $edcFeedType, string $jobClass)
    {
        Queue::fake();

        $edcFeed = new EDCFeed([
            'type' => $edcFeedType,
        ]);

        Event::dispatch(new FeedFetched($edcFeed));

        Queue::assertPushed($jobClass, function ($job) use ($edcFeed) {
            return $job->feed === $edcFeed;
        });
    }

    public function testUnknownFeedTypeThrowsException()
    {
        $edcFeed = new EDCFeed(['type' => 'invalid type']);

        /** @var DispatchParseFeed $listener */
        $listener = $this->app[DispatchParseFeed::class];

        try {
            $listener->handle(new FeedFetched($edcFeed));
            static::fail('Didnt throw the expected exception');
        } catch (UnknownFeedType $e) {
            static::addToAssertionCount(1);
        }
    }
}
