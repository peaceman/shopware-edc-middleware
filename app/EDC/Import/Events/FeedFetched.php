<?php
/**
 * lel since 2019-07-02
 */

namespace App\EDC\Import\Events;

use App\EDCFeed;

class FeedFetched
{
    /** @var EDCFeed */
    protected $edcFeed;

    public function __construct(EDCFeed $edcFeed)
    {
        $this->edcFeed = $edcFeed;
    }

    public function getEDCFeed(): EDCFeed
    {
        return $this->edcFeed;
    }
}
