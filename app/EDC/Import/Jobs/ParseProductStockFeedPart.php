<?php
/**
 * lel since 2019-07-07
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\Parser\ProductStockFeedPartParser;
use App\EDCFeedPartStock;
use App\Jobs\BaseJob;

class ParseProductStockFeedPart extends BaseJob
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
