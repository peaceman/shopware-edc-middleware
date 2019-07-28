<?php
/**
 * lel since 2019-07-28
 */

namespace App\SW\Import;

use App\SW\Import\OrderProviders\OrderProvider;
use App\SW\ShopwareOrderInfo;
use Exception;
use Psr\Log\LoggerInterface;

class OrderFetcher
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var OrderPersister */
    protected $orderPersister;

    public function __construct(LoggerInterface $logger, OrderPersister $orderPersister)
    {
        $this->logger = $logger;
        $this->orderPersister = $orderPersister;
    }

    public function fetchOrders(OrderProvider $orderProvider): void
    {
        $startTime = microtime(true);
        $counter = 0;

        $this->logger->info('OrderFetcher: start fetching orders');

        foreach ($orderProvider->getOrders() as $order) {
            $this->persistOrder($order);

            $counter++;
        }

        $this->logger->info('OrderFetcher: finished fetching orders', [
            'elapsed' => microtime(true) - $startTime,
            'counter' => $counter,
        ]);
    }

    protected function persistOrder(ShopwareOrderInfo $shopwareOrderInfo): void
    {
        try {
            $this->orderPersister->persistOrder($shopwareOrderInfo);
        } catch (Exception $e) {
            $this->logger->error('OrderFetcher: failed to persist order', [
                'shopwareOrderInfo' => $shopwareOrderInfo->asLoggingContext(),
            ]);

            report($e);
        }
    }
}
