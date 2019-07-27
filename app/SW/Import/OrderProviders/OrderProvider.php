<?php
/**
 * lel since 2019-07-27
 */

namespace App\SW\Import\OrderProviders;

use App\Domain\Export\Order;
use App\Domain\Export\OrderArticle;
use App\Domain\Export\OrderFetched;
use App\SW\ShopwareAPI;
use App\SW\ShopwareOrderInfo;

abstract class OrderProvider
{
    /** @var ShopwareAPI */
    private $shopwareAPI;

    public function __construct(ShopwareAPI $shopwareAPI)
    {
        $this->shopwareAPI = $shopwareAPI;
    }

    public function getOrders(): iterable
    {
        $filters = $this->generateFilters();

        foreach ($filters as $subFilter) {
            $jsonResponse = $this->shopwareAPI->fetchOrders($subFilter);

            $apiOrders = data_get($jsonResponse, 'data', []);

            foreach ($apiOrders as $apiOrder) {
                $swOrderID = data_get($apiOrder, 'id');
                if (!$swOrderID) continue;

                yield $this->fetchOrderDetails($swOrderID);
            }
        }
    }

    public function generateFilters(): array
    {
        return [];
    }

    protected function fetchOrderDetails(int $swOrderID): ?ShopwareOrderInfo
    {
        $jsonResponse = $this->shopwareAPI->fetchOrderDetails($swOrderID);

        $data = $jsonResponse['data'] ?? null;
        if (empty($data)) return null;

        return new ShopwareOrderInfo($data);
    }
}
