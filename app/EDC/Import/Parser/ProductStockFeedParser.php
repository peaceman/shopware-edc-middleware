<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\EDC\Import\Exceptions\MissingProductIDInStockFeed;
use App\EDC\Import\Jobs\ParseProductStockFeedPart;
use App\EDCFeed;
use App\EDCFeedPartStock;
use App\ResourceFile\StorageDirector;
use DOMDocument;
use DOMNode;
use Illuminate\Contracts\Bus\Dispatcher as JobDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;
use XMLReader;

class ProductStockFeedParser extends FeedParser
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
        $this->ensureMatchingFeedType(EDCFeed::TYPE_PRODUCT_STOCKS, $feed->type);

        $startTime = microtime(true);
        $this->logger->info('ProductStockFeedParser: Start parsing feed', [
            'feed' => $feed->asLoggingContext(),
        ]);

        $filePath = $this->storageDirector->getLocalPath($feed->file);

        $producedFeedParts = 0;
        $lastProductID = null;
        $currentProductNodes = [];
        $domDoc = new DOMDocument('1.0', 'UTF-8');

        /** @var DOMNode $productNode */
        foreach ($this->readFeed($filePath) as $productNode) {
            try {
                $productID = $this->determineProductIDFromNode($productNode);

                if (!is_null($lastProductID) && $productID != $lastProductID) {
                    if ($this->handleNewFeedPart($feed, $currentProductNodes))
                        $producedFeedParts++;

                    $currentProductNodes = [];
                }

                $currentProductNodes[] = $productNode;
                $lastProductID = $productID;
            } catch (MissingProductIDInStockFeed $e) {
                $this->logger->warning('ProductStockFeedParser: missing product id in stock feed node', [
                    'feed' => $feed->asLoggingContext(),
                    'xml' => $domDoc->saveXML($domDoc->importNode($productNode)),
                ]);

                report($e);
            }
        }

        if (!empty($currentProductNodes)) {
            $this->handleNewFeedPart($feed, $currentProductNodes);
            $producedFeedParts++;
        }

        $elapsed = microtime(true) - $startTime;
        $this->logger->info('ProductStockFeedParser: Finished parsing feed', [
            'feed' => $feed->asLoggingContext(),
            'producedFeedParts' => $producedFeedParts,
            'elapsed' => $elapsed,
        ]);
    }

    protected function readFeed(string $filePath): \Generator
    {
        $xmlReader = new XMLReader();
        $xmlReader->open($filePath);

        // move to the first product node
        while ($xmlReader->read() && $xmlReader->name !== 'product');

        while ($xmlReader->name === 'product') {
            yield $xmlReader->expand();

            $xmlReader->next('product');
        }
    }

    protected function determineProductIDFromNode(DOMNode $node): string
    {
        /** @var DOMNode $childNode */
        foreach ($node->childNodes as $childNode) {
            if ($childNode->nodeName !== 'productid') continue;

            return $childNode->nodeValue;
        }

        throw new MissingProductIDInStockFeed();
    }

    protected function handleNewFeedPart(EDCFeed $feed, array $domNodes): bool
    {
        $feedPartXML = $this->createFeedPartXML($domNodes);
        if ($this->feedPartExistsAlready($feed, md5($feedPartXML)))
            return false;
        
        $feedPart = $this->createFeedPart($feed, $feedPartXML);

        $job = new ParseProductStockFeedPart($feedPart);
        $this->jobDispatcher->dispatch($job);

        return true;
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
        return $feed->stockFeedParts()->with('file')->get()->pluck('file.checksum');
    }

    protected function createFeedPart(EDCFeed $feed, string $feedPartXML): EDCFeedPartStock
    {
        $rf = $this->storageDirector->createFileFromString('product-stock-feed-part.xml', $feedPartXML);

        $feedPart = new EDCFeedPartStock();
        $feedPart->fullFeed()->associate($feed);
        $feedPart->file()->associate($rf);
        $feedPart->save();

        return $feedPart;
    }

    protected function createFeedPartXML(array $domNodes): string
    {
        $domDoc = new DOMDocument('1.0', 'UTF-8');
        $domDoc->formatOutput = true;

        $domDoc->appendChild($products = $domDoc->createElement('producten'));

        foreach ($domNodes as $domNode) {
            $products->appendChild($domNode);
        }

        return $domDoc->saveXML(null, LIBXML_NOEMPTYTAG);
    }
}
