<?php
/**
 * lel since 2019-07-07
 */

namespace Tests\Unit\EDC\Import\Parser;

use App\EDCFeedPartStock;
use Illuminate\Contracts\Queue\ShouldQueue;

class ParseProductStockFeedPart implements ShouldQueue
{
    /** @var EDCFeedPartStock */
    public $feedPart;

    public function __construct(EDCFeedPartStock $feedPart)
    {
        $this->feedPart = $feedPart;
    }
}
