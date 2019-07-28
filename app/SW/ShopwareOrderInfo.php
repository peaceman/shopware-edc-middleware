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

    public function asLoggingContext(): array
    {
        return [
            'orderNumber' => $this->data['number'] ?? null,
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getStreet(): string
    {
        return (string)$this->get('shipping.street');
    }

    public function getFirstName(): string
    {
        return (string)$this->get('shipping.firstName');
    }

    public function getLastName(): string
    {
        return (string)$this->get('shipping.lastName');
    }

    public function getZIPCode(): string
    {
        return (string)$this->get('shipping.zipCode');
    }

    public function getCity(): string
    {
        return (string)$this->get('shipping.city');
    }

    public function getCountry(): string
    {
        return (string)$this->get('shipping.country.iso');
    }

    public function getArticleNumbersWithQuantity(): array
    {
        return collect($this->get('details'))
            ->map(function (array $detail) {
                return [
                    'articleNumber' => $detail['articleNumber'],
                    'quantity' => $detail['quantity'],
                ];
            })
            ->all();
    }

    protected function get(string $key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }
}
