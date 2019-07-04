<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\ResourceFile\StorageDirector;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;

abstract class FeedParser
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var StorageDirector */
    protected $storageDirector;

    /** @var ConnectionInterface */
    protected $dbConnection;

    /** @var EventDispatcher */
    protected $eventDispatcher;

    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $dbConnection,
        EventDispatcher $eventDispatcher,
        StorageDirector $storageDirector
    )
    {
        $this->logger = $logger;
        $this->dbConnection = $dbConnection;
        $this->eventDispatcher = $eventDispatcher;
        $this->storageDirector = $storageDirector;
    }

    protected function ensureMatchingFeedType(string $expected, string $actual): void
    {
        if ($actual === $expected) return;

        throw new ParserFeedTypeMismatch("Expected {$expected} got {$actual}'");
    }
}
