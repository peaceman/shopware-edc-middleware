<?php
/**
 * lel since 2019-07-04
 */

namespace App\EDC\Import\Events;

use App\Brand;

class BrandDiscountTouched
{
    /** @var Brand */
    public $brand;

    public function __construct(Brand $brand)
    {
        $this->brand = $brand;
    }
}
