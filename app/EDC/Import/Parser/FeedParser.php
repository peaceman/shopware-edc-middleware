<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;

abstract class FeedParser
{

    protected function ensureMatchingFeedType(string $expected, string $actual): void
    {
        if ($actual === $expected) return;

        throw new ParserFeedTypeMismatch("Expected {$expected} got {$actual}'");
    }
}
