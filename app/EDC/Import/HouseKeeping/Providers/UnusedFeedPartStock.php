<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\HouseKeeping\Providers;

use App\EDCFeedPartStock;

class UnusedFeedPartStock extends FeedPartStock
{
    protected function get(): \Traversable
    {
        $query = EDCFeedPartStock::query();

        $query->whereDoesntHave('variantData');

        return $query->cursor();
    }
}
