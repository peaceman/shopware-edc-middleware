<?php
/**
 * lel since 2019-07-02
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\Events\FeedFetched;
use App\EDC\Import\FeedFetcher;
use App\EDCFeed;
use Assert\Assert;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class FetchFeed implements ShouldQueue
{
    use InteractsWithQueue;

    /** @var string */
    public $feedType;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $retryAfter = 30;

    public function __construct(string $feedType)
    {
        Assert::that($feedType)->inArray(EDCFeed::getConstantsWithPrefix('TYPE'));

        $this->feedType = $feedType;
    }

    public function handle(FeedFetcher $feedFetcher, EventDispatcher $eventDispatcher)
    {
        $feedURI = $this->getFeedURI();

        try {
            $edcFeed = $feedFetcher->fetch($feedURI, $this->feedType);
            if (!$edcFeed) return;

            $eventDispatcher->dispatch(new FeedFetched($edcFeed));
        } catch (RequestException $e) {
            report($e);

            $this->delete();
        }
    }

    protected function getFeedURI(): string
    {
        $feedURI = config("edc.feedURI.{$this->feedType}");
        Assert::that($feedURI)->notBlank();

        return $feedURI;
    }
}
