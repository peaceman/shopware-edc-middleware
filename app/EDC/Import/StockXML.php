<?php
/**
 * lel since 2019-07-07
 */

namespace App\EDC\Import;

class StockXML
{
    /** @var \SimpleXMLElement */
    protected $xml;

    public static function fromFilePath(string $filePath)
    {
        $o = new static;
        $o->xml = simplexml_load_file($filePath);

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
}
