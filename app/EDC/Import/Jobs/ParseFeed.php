<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Jobs;

use App\EDCFeed;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class ParseFeed implements ShouldQueue
{
    /** @var EDCFeed */
    public $feed;

    final public function __construct(EDCFeed $feed)
    {
        $this->feed = $feed;
    }
}
