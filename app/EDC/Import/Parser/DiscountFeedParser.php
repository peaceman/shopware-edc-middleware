<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDCFeed;

class DiscountFeedParser extends FeedParser
{
    public function parse(EDCFeed $feed): void
    {
        $this->ensureMatchingFeedType(EDCFeed::TYPE_DISCOUNTS, $feed->type);

        // todo implement
    }

}
