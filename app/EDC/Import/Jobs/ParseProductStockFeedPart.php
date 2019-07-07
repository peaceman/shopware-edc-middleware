<?php
/**
 * lel since 2019-07-07
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\Parser\ProductStockFeedPartParser;
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

    public function handle(ProductStockFeedPartParser $parser)
    {
        $parser->parse($this->feedPart);
    }
}
