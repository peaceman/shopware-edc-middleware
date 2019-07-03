<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\EDCFeed;

class ProductFeedParser extends FeedParser
{
    public function parse(EDCFeed $feed): void
    {
        $this->ensureMatchingFeedType(EDCFeed::TYPE_PRODUCTS, $feed->type);

        // todo implement
    }
}
