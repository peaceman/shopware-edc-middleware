<?php
/**
 * lel since 2019-07-14
 */

namespace App\SW\Export;

use App\EDC\Import\ProductXML;
use App\EDCProduct;

class PriceCalculator
{
    public function calcPrice(EDCProduct $edcProduct, ProductXML $productXML): float
    {
        $nonRoundedPrice = $this->calcNonRoundedPrice($edcProduct, $productXML);

        return ceil($nonRoundedPrice);
    }

    protected function calcNonRoundedPrice(EDCProduct $edcProduct, ProductXML $productXML): float
    {
        $b2cPrice = $productXML->getB2CPrice();

        if (!$productXML->shouldApplyDiscount()) return $b2cPrice;
        if (!($brand = $edcProduct->brand)) return $b2cPrice;
        if (!($currentDiscount = $brand->currentDiscount)) return $b2cPrice;

        return $b2cPrice - ($b2cPrice * ($currentDiscount->value / 100));
    }
}
