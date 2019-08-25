<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\HouseKeeping\Providers;

use App\EDCFeedPartStock;
use IteratorIterator;

abstract class FeedPartStock extends IteratorIterator
{
    public function __construct()
    {
        parent::__construct($this->get());
    }

    public function current(): EDCFeedPartStock
    {
        return parent::current();
    }

    abstract protected function get(): \Traversable;
}
