<?php
/**
 * lel since 2019-07-28
 */

namespace App\EDC\Import;

use App\EDCOrderStatus;
use Assert\Assert;

class OrderUpdateXML
{
    /** @var \SimpleXMLElement */
    protected $xml;

    public static function fromString(string $xml): self
    {
        $orderUpdateXML = new static;
        $orderUpdateXML->xml = simplexml_load_string($xml);

        return $orderUpdateXML;
    }

    public function getOwnOrderNumber(): string
    {
        $result = (string)$this->xml->own_ordernumber;
        Assert::that($result)->notEmpty();

        return $result;
    }

    public function getTrackingNumber(): string
    {
        $result = (string)$this->xml->tracktrace;
        Assert::that($result)->notEmpty();

        return $result;
    }

    public function getStatus(): string
    {
        $result = (string)$this->xml->status;
        Assert::that($result)->inArray(EDCOrderStatus::getConstants());

        return $result;
    }

    public function asXML(): string
    {
        return $this->xml->asXML();
    }
}
