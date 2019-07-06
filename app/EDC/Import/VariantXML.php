<?php
/**
 * lel since 2019-07-06
 */

namespace App\EDC\Import;

class VariantXML
{
    /** @var \SimpleXMLElement */
    protected $xml;

    public static function fromSimpleXMLElement(\SimpleXMLElement $xml): self
    {
        $variantXML = new static;
        $variantXML->xml = $xml;

        return $variantXML;
    }

    public function getEDCID(): string
    {
        return (string)$this->xml->id;
    }

    public function getSubArtNr(): string
    {
        return (string)$this->xml->subartnr;
    }
}
