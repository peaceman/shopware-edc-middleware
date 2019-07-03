<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import;

use App\EDC\Import\Events\FeedFetched;
use App\EDCFeed;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Log\LoggerInterface;

class DispatchParseFeed
{
    /** @var Dispatcher */
    protected $jobDispatcher;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * feed type -> parse feed job class
     *
     * @var array
     */
    protected $parseFeedJobs = [];

    public function __construct(Dispatcher $jobDispatcher, LoggerInterface $logger)
    {
        $this->jobDispatcher = $jobDispatcher;
        $this->logger = $logger;
    }

    public function handle(FeedFetched $feedFetched): void
    {
        $job = $this->createParseFeedJob($feedFetched->getEDCFeed());

        $this->logger->info('DispatchParseFeed: Dispatch parse feed job', [
            'feed' => $feedFetched->getEDCFeed()->asLoggingContext(),
        ]);

        $this->jobDispatcher->dispatch($job);
    }

    protected function createParseFeedJob(EDCFeed $feed): Jobs\ParseFeed
    {
        if (!($jobClass = $this->parseFeedJobs[$feed->type] ?? null)) {
            $this->logger->error("DispatchParseFeed: Couldn't determine job class for feed type", [
                'feed' => $feed->asLoggingContext(),
            ]);

            throw new Exceptions\UnknownFeedType($feed->type);
        }

        return new $jobClass($feed);
    }

    public function registerParseFeedJob(string $jobType, string $jobClass): void
    {
        $this->parseFeedJobs[$jobType] = $jobClass;
    }
}
