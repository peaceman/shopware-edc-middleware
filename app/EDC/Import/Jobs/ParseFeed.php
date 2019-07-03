<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Jobs;

use App\EDCFeed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

abstract class ParseFeed implements ShouldQueue
{
    use InteractsWithQueue;

    /** @var EDCFeed */
    public $feed;

    final public function __construct(EDCFeed $feed)
    {
        $this->feed = $feed;
    }
}
