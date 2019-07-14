<?php
/**
 * lel since 2019-07-07
 */

namespace App\EDC\Import;

class StockProductXML
{
    /** @var \SimpleXMLElement */
    protected $xml;

    public static function fromSimpleXMLElement(\SimpleXMLElement $xml): self
    {
        $variantXML = new static;
        $variantXML->xml = $xml;

        return $variantXML;
    }

    public function getProductEDCID(): string
    {
        return (string)$this->xml->productid;
    }

    public function getVariantEDCID(): string
    {
        return (string)$this->xml->variantid;
    }

    public function isInStock(): bool
    {
        return strtolower($this->getStock()) === 'j';
    }

    public function getStock(): string
    {
        return (string)$this->xml->stock;
    }
}
