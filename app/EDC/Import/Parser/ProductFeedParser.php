<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\EDC\Import\Jobs\ParseProductFeedPart;
use App\EDCFeed;
use App\EDCFeedPartProduct;
use App\ResourceFile\StorageDirector;
use Illuminate\Contracts\Bus\Dispatcher as JobDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class ProductFeedParser extends FeedParser
{
    /** @var JobDispatcher */
    protected $jobDispatcher;

    /** @var \SplObjectStorage */
    protected $feedPartChecksumCache;

    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $dbConnection,
        EventDispatcher $eventDispatcher,
        JobDispatcher $jobDispatcher,
        StorageDirector $storageDirector
    )
    {
        parent::__construct($logger, $dbConnection, $eventDispatcher, $storageDirector);

        $this->jobDispatcher = $jobDispatcher;
        $this->feedPartChecksumCache = new \SplObjectStorage();
    }

    public function parse(EDCFeed $feed): void
    {
        $this->ensureMatchingFeedType(EDCFeed::TYPE_PRODUCTS, $feed->type);

        $startTime = microtime(true);
        $this->logger->info('ProductFeedParser: Start parsing feed', [
            'feed' => $feed->asLoggingContext(),
        ]);

        $filePath = $this->storageDirector->getLocalPath($feed->file);

        $counter = 0;
        foreach ($this->readFeed($filePath) as $productXML) {
            $feedPart = $this->createFeedPart($feed, $productXML);
            if (!$feedPart) continue;

            $this->dispatchFeedPartParsingJob($feedPart);
            $counter++;
        }

        $elapsed = microtime(true) - $startTime;
        $this->logger->info('ProductFeedParser: Finished parsing feed', [
            'feed' => $feed->asLoggingContext(),
            'producedFeedParts' => $counter,
            'elapsed' => $elapsed,
        ]);

        $this->feedPartChecksumCache->detach($feed);
    }

    protected function readFeed(string $filePath): \Generator
    {
        $xmlReader = new \XMLReader();
        $xmlReader->open($filePath);

        // move to the first product node
        while ($xmlReader->read() && $xmlReader->name !== 'product') ;

        while ($xmlReader->name === 'product') {
            $productXML = $xmlReader->readOuterXml();

            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->loadXML($productXML);
            $dom->formatOutput = true;
            $dom->encoding = 'UTF-8';

            yield $dom->saveXML(null, LIBXML_NOEMPTYTAG);

            $xmlReader->next('product');
        }
    }

    protected function createFeedPart(EDCFeed $feed, string $xml): ?EDCFeedPartProduct
    {
        $checksum = md5($xml);
        if ($this->feedPartExistsAlready($feed, $checksum)) {
            $this->logger->info('ProductFeedParser: skip feed creation; found already existing with same checksum', [
                'feed' => $feed->asLoggingContext(),
                'checksum' => $checksum
            ]);

            return null;
        }

        $file = $this->storageDirector->createFileFromString('product-feed-part.xml', $xml);

        $feedPart = new EDCFeedPartProduct();
        $feedPart->file()->associate($file);
        $feedPart->fullFeed()->associate($feed);
        $feedPart->save();

        return $feedPart;
    }

    protected function feedPartExistsAlready(EDCFeed $feed, string $checksum): bool
    {
        $checksums = $this->getFeedPartChecksums($feed);
        $result = $checksums->has($checksum);

        $checksums->put($checksum, true);

        return $result;
    }

    protected function getFeedPartChecksums(EDCFeed $feed): Collection
    {
        if (!($checksums = $this->feedPartChecksumCache[$feed] ?? null)) {
            $checksums = collect(array_fill_keys($this->determineFeedPartChecksums($feed)->all(), true));
            $this->feedPartChecksumCache[$feed] = $checksums;
        }

        return $checksums;
    }

    protected function determineFeedPartChecksums(EDCFeed $feed): Collection
    {
        return $feed->productFeedParts()->with('file')->get()->pluck('file.checksum');
    }

    protected function dispatchFeedPartParsingJob(EDCFeedPartProduct $feedPart): void
    {
        $job = new ParseProductFeedPart($feedPart);
        $this->jobDispatcher->dispatch($job);
    }
}
