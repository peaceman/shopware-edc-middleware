<?php
/**
 * lel since 2019-07-06
 */

namespace App\EDC\Import\Jobs;

use App\EDCFeedPartProduct;
use Illuminate\Contracts\Queue\ShouldQueue;

class ParseProductFeedPart implements ShouldQueue
{
    /** @var EDCFeedPartProduct */
    public $feedPart;

    public function __construct(EDCFeedPartProduct $feedPart)
    {
        $this->feedPart = $feedPart;
    }
}
