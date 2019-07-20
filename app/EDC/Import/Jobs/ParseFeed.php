<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Jobs;

use App\EDCFeed;
use App\Jobs\BaseJob;

abstract class ParseFeed extends BaseJob
{
    /** @var EDCFeed */
    public $feed;

    public $queue = 'long-running';

    final public function __construct(EDCFeed $feed)
    {
        $this->feed = $feed;
    }
}
