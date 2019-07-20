<?php
/**
 * lel since 2019-07-06
 */

namespace App\EDC\Import;

use SimpleXMLElement;

class ProductXML
{
    /** @var \SimpleXMLElement */
    protected $xml;

    public static function fromFilePath(string $filePath): self
    {
        $productXML = new static;
        $productXML->xml = simplexml_load_file($filePath);

        return $productXML;
    }

    public function getEDCID(): string
    {
        return (string)$this->xml->id;
    }

    public function getArtNr(): string
    {
        return (string)$this->xml->artnr;
    }

    /**
     * @return array|VariantXML[]
     */
    public function getVariants(): iterable
    {
        foreach ($this->xml->xpath('variants/variant') as $xml) {
            yield VariantXML::fromSimpleXMLElement($xml);
        }
    }

    public function getBrandID(): string
    {
        return (string)$this->xml->brand->id;
    }

    public function getBrandName(): string
    {
        return (string)$this->xml->brand->title;
    }

    public function getPicNames(): array
    {
        return array_map(function (\SimpleXMLElement $xml) {
            return (string)$xml;
        }, $this->xml->xpath('pics/pic'));
    }

    public function getTitle(): string
    {
        return (string)$this->xml->title;
    }

    public function getVATDE(): float
    {
        return (float)$this->xml->price->vatde;
    }

    public function getDescription(): string
    {
        return (string)$this->xml->description;
    }

    public function getB2CPrice(): float
    {
        return (float)$this->xml->price->b2c;
    }

    public function shouldApplyDiscount(): bool
    {
        return (string)$this->xml->price->discount === 'Y';
    }

    public function getCategories()
    {
        return array_map(
            function (SimpleXMLElement $category): array {
                return array_map(
                    function (SimpleXMLElement $cat) {
                        return ['id' => (string)$cat->id, 'title' => (string)$cat->title];
                    },
                    $category->xpath('cat')
                );
            },
            $this->xml->xpath('categories/category')
        );
    }
}
