<?php
/**
 * lel since 2019-07-06
 */

namespace App\EDC\Import\Events;

use App\EDCProduct;

class ProductTouched
{
    protected $product;

    public function __construct(EDCProduct $product)
    {
        $this->product = $product;
    }

    public function getProduct(): EDCProduct
    {
        return $this->product;
    }
}
