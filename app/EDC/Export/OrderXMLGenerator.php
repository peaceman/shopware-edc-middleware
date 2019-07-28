<?php
/**
 * lel since 2019-07-28
 */

namespace App\EDC\Export;

use App\SW\ShopwareOrderInfo;
use Assert\Assert;
use Illuminate\Support\Arr;

class OrderXMLGenerator
{
    /** @var string */
    protected $apiEmail;

    /** @var string */
    protected $apiKey;

    /**
     * iso code -> edc id
     *
     * @var array
     */
    protected $countryMap = [];

    public function setAPIEmail(string $apiEmail): void
    {
        $this->apiEmail = $apiEmail;
    }

    public function setAPIKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function setCountryMap(array $countryMap): void
    {
        $this->countryMap = $countryMap;
    }

    public function generateXML(ShopwareOrderInfo $soi): string
    {
        $doc = $this->createDOMDocument();
        $doc->appendChild($orderDetails = $doc->createElement('orderdetails'));
        $orderDetails->appendChild($this->createCustomerDetailsNode($doc));
        $orderDetails->appendChild($this->createReceiverNode($doc, $soi));
        $orderDetails->appendChild($this->createProductsNode($doc, $soi));

        return $doc->saveXML();
    }

    protected function createDOMDocument(): \DOMDocument
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        return $doc;
    }

    protected function createCustomerDetailsNode(\DOMDocument $doc): \DOMElement
    {
        $node = $doc->createElement('customerdetails');

        $childs = [
            $doc->createElement('email', $this->apiEmail),
            $doc->createElement('apikey', $this->apiKey),
            $doc->createElement('output', 'advanced')
        ];

        foreach ($childs as $child) $node->appendChild($child);

        return $node;
    }

    protected function createReceiverNode(\DOMDocument $doc, ShopwareOrderInfo $soi): \DOMElement
    {
        $node = $doc->createElement('receiver');

        $streetParts = $this->splitStreetParts($soi->getStreet());

        $childs = [
            $doc->createElement('name', $this->generateReceiverName($soi)),
            $doc->createElement('street', $streetParts['street']),
            $doc->createElement('house_nr', $streetParts['house_nr']),
            $doc->createElement('house_nr_ext', $streetParts['house_nr_ext']),
            $doc->createElement('postalcode', $soi->getZIPCode()),
            $doc->createElement('city', $soi->getCity()),
            $doc->createElement('country', $this->mapCountry($soi->getCountry())),
            $doc->createElement('own_ordernumber', $soi->getOrderNumber()),
        ];

        foreach ($childs as $child) $node->appendChild($child);

        return $node;
    }

    protected function splitStreetParts(string $street): array
    {
        $matched = preg_match('/^(?<street>[^\d]+)(?<house_nr>[\d]+)(?<house_nr_ext>.*)$/', $street, $matches);
        Assert::that($matched)->eq(1);

        return array_map('trim', Arr::only($matches, ['street', 'house_nr', 'house_nr_ext']));
    }

    protected function generateReceiverName(ShopwareOrderInfo $soi): string
    {
        return implode(' ', [
            $soi->getFirstName(),
            $soi->getLastName(),
        ]);
    }

    protected function mapCountry(string $countryISO): int
    {
        $countryID = $this->countryMap[$countryISO] ?? null;
        Assert::that($countryID)->notNull();

        return $countryID;
    }

    protected function createProductsNode(\DOMDocument $doc, ShopwareOrderInfo $soi): \DOMElement
    {
        $node = $doc->createElement('products');

        $articleNumbersWithQuantity = $soi->getArticleNumbersWithQuantity();

        foreach ($articleNumbersWithQuantity as $articleInfo) {
            for ($i = 0; $i < $articleInfo['quantity']; $i++) {
                $node->appendChild($doc->createElement('artnr', $articleInfo['articleNumber']));
            }
        }

        return $node;
    }
}
