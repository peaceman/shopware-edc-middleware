<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\HouseKeeping\FeedPartStockDeleter;
use App\EDC\Import\HouseKeeping\Providers\UnusedFeedPartStock;
use App\Jobs\BaseJob;

class DeleteUnusedFeedPartStock extends BaseJob
{
    public $queue = 'long-running';

    public function handle(FeedPartStockDeleter $deleter, UnusedFeedPartStock $provider)
    {
        $deleter($provider);
    }
}
