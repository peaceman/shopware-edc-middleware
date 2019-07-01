<?php
/**
 * lel since 2019-07-01
 */

namespace App\EDC\Import;

use App\EDCFeed;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use App\Utils\GuessDownloadFilenames;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use ZipArchive;

class FeedFetcher
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Client */
    protected $httpClient;

    /** @var StorageDirector */
    protected $storageDirector;

    public function __construct(
        LoggerInterface $logger,
        Client $httpClient,
        StorageDirector $storageDirector
    )
    {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->storageDirector = $storageDirector;
    }

    public function fetch(string $feedURI, string $feedType): ?EDCFeed
    {
        $this->logger->info('FeedFetcher: Start fetching feed', compact('feedType', 'feedURI'));

        $response = $this->httpClient->get($feedURI);
        $filename = (new GuessDownloadFilenames($feedURI, $response))();

        $rf = $this->isZipArchive($filename)
            ? $this->unzipArchive($response->getBody())
            : $this->storeAsResourceFile($filename, $response->getBody());

        if ($this->wouldBeADuplicateFeed($rf, $feedType)) {
            $rf->delete();
            return null;
        }

        $feed = new EDCFeed(['type' => $feedType]);
        $feed->file()->associate($rf);
        $feed->save();

        $this->logger->info('FeedFetcher: Finished fetching feed', array_merge(
            compact('feedType', 'feedURI'),
            ['feed' => $feed->asLoggingContext()]
        ));

        return $feed;
    }

    protected function isZipArchive(string $filename): bool
    {
        return strtolower((new \SplFileInfo($filename))->getExtension()) === 'zip';
    }

    protected function storeAsResourceFile(string $filename, StreamInterface $stream): ResourceFile
    {
        return $this->storageDirector->createFileFromStream($filename, $stream);
    }

    protected function wouldBeADuplicateFeed(ResourceFile $rf, string $feedType): bool
    {
        /** @var EDCFeed $latestFeedOfType */
        $latestFeedOfType = EDCFeed::withType($feedType)->latest()->first();

        $isDuplicate = $latestFeedOfType ? $latestFeedOfType->file->checksum == $rf->checksum : false;

        if ($isDuplicate) {
            $this->logger->info('FeedFetcher: Detected duplicate feed; abort', [
                'feedType' => $feedType,
                'newRF' => $rf->asLoggingContext(),
                'duplicateOf' => $latestFeedOfType->asLoggingContext(),
            ]);
        }

        return $isDuplicate;
    }

    protected function unzipArchive(StreamInterface $inputStream): ResourceFile
    {
        $this->logger->info('FeedFetcher: unzip archive', ['feedURI' => $inputStream->getMetadata('uri')]);

        try {
            $tmpZipRF = $this->storageDirector->createFileFromStream('feed-fetcher-tmp.zip', $inputStream);
            $tmpZipPath = $this->storageDirector->getLocalPath($tmpZipRF);

            $zipArchive = new ZipArchive;
            if (($errCode = $zipArchive->open($tmpZipPath)) !== true) {
                $message = 'Failed to open zip archive';

                $this->logger->error('FeedFetcher: ' . $message, ['tmpZipRF' => $tmpZipRF->asLoggingContext()]);
                throw new Exceptions\InvalidZipArchive($message);
            }

            if ($zipArchive->numFiles == 0) {
                $message = 'Zip archive is empty';

                $this->logger->error('FeedFetcher: ' . $message, ['tmpZipRF' => $tmpZipRF->asLoggingContext()]);
                throw new Exceptions\InvalidZipArchive($message);
            }

            if (($nameOfFirstFile = $zipArchive->getNameIndex(0)) === false) {
                $message = 'Failed to fetch the first files name from zip archive';

                $this->logger->error('FeedFetcher: ' . $message, ['tmpZipRF' => $tmpZipRF->asLoggingContext()]);
                throw new Exceptions\InvalidZipArchive($message);
            }

            if (($inZipStream = $zipArchive->getStream($nameOfFirstFile)) === false) {
                $message = 'Failed to receive the in zip file stream';

                $this->logger->error('FeedFetcher: ' . $message, ['tmpZipRF' => $tmpZipRF->asLoggingContext()]);
                throw new Exceptions\InvalidZipArchive($message);
            }

            $feedRF = $this->storageDirector->createFileFromStream($nameOfFirstFile, stream_for($inZipStream));
            $zipArchive->close();

            return $feedRF;
        } finally {
            if ($tmpZipRF)
                $tmpZipRF->delete();
        }
    }
}
