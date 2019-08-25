<?php
/**
 * lel since 2019-07-07
 */

namespace App\EDC\Import;

use Assert\Assert;

class StockXML
{
    /** @var \SimpleXMLElement */
    protected $xml;

    public static function fromFilePath(string $filePath): self
    {
        $o = new static;
        $o->xml = simplexml_load_file($filePath);

        return $o;
    }

    public static function fromString(string $xml): self
    {
        $o = new static;
        $o->xml = simplexml_load_string($xml);

        return $o;
    }

    /**
     * @return iterable|StockProductXML[]
     */
    public function getProducts(): iterable
    {
        foreach ($this->xml->xpath('product') as $xml) {
            yield StockProductXML::fromSimpleXMLElement($xml);
        }
    }

    public function getStockProductWithVariantEDCID(string $edcID): StockProductXML
    {
        $matchingElements = $this->xml->xpath("product[variantid=$edcID]");
        Assert::that($matchingElements)->minCount(1);

        $matchingElement = head($matchingElements);
        return StockProductXML::fromSimpleXMLElement($matchingElement);
    }
}
