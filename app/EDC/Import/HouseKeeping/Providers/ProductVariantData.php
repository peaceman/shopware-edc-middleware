<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\HouseKeeping\Providers;

use App\EDCProductVariantData;

abstract class ProductVariantData extends \IteratorIterator
{
    public function __construct()
    {
        parent::__construct($this->get());
    }

    public function current(): EDCProductVariantData
    {
        return parent::current();
    }

    abstract protected function get(): \Traversable;
}
