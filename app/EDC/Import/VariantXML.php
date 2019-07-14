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

    public function isInStock(): bool
    {
        return strtolower($this->getStock()) === 'y';
    }

    public function getStock(): string
    {
        return (string)$this->xml->stock;
    }

    public function getSize(): ?string
    {
        if ($this->getType() !== 'S') return null;

        $title = trim((string)$this->xml->title);

        return empty($title) ? null : $title;
    }

    public function getType(): string
    {
        return (string)$this->xml->type;
    }

    public function getEAN(): string
    {
        return (string)$this->xml->ean;
    }
}
