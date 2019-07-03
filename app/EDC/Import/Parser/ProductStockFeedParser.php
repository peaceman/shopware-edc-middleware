<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\EDC\Import\Events\FeedFetched;
use App\EDCFeed;

class ProductStockFeedParser extends FeedParser
{
    public function parse(EDCFeed $feed): void
    {
        $this->ensureMatchingFeedType(EDCFeed::TYPE_PRODUCT_STOCKS, $feed->type);

        // todo implement
    }
}
