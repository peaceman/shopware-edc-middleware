<?php
/**
 * lel since 2019-07-27
 */

namespace App\SW;

class ShopwareOrderInfo
{
    /** @var array */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getOrderNumber(): string
    {
        return (string)$this->data['number'];
    }
}