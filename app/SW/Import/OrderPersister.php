<?php
/**
 * lel since 2019-07-28
 */

namespace App\SW\Import;

use App\EDCTransferStatus;
use App\SW\ShopwareOrderInfo;
use App\SWOrder;
use App\SWOrderData;
use App\SWOrderDetail;
use App\SWTransferStatus;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;

class OrderPersister
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ConnectionInterface */
    protected $db;

    public function __construct(LoggerInterface $logger, ConnectionInterface $db)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function persistOrder(ShopwareOrderInfo $shopwareOrderInfo): void
    {
        $swOrder = $this->fetchExistingOrder($shopwareOrderInfo->getOrderNumber());

        if (!$swOrder) {
            $this->db->transaction(function () use ($shopwareOrderInfo) {
                $this->createOrder($shopwareOrderInfo);
            });

            return;
        }

        $this->db->transaction(function () use ($swOrder, $shopwareOrderInfo) {
            $this->updateOrder($swOrder, $shopwareOrderInfo);
        });
    }

    protected function fetchExistingOrder(string $orderNumber): ?SWOrder
    {
        return SWOrder::withOrderNumber($orderNumber)->first();
    }

    protected function createOrder(ShopwareOrderInfo $shopwareOrderInfo): void
    {
        $this->logger->info('OrderPersister: create new order', [
            'orderInfo' => $shopwareOrderInfo->asLoggingContext(),
        ]);

        $swOrder = $this->createSWOrder($shopwareOrderInfo);
        $swOrderDetails = $this->createSWOrderDetails($swOrder, $shopwareOrderInfo);

        $swOrderData = new SWOrderData();
        $swOrderData->order()->associate($swOrder);
        $swOrderData->orderDetail()->associate($swOrderDetails);
        $swOrderData->sw_transfer_status = SWTransferStatus::OPEN;
        $swOrderData->edc_transfer_status = EDCTransferStatus::OPEN;

        $swOrder->saveData($swOrderData);
    }

    protected function createSWOrder(ShopwareOrderInfo $shopwareOrderInfo): SWOrder
    {
        $swOrder = new SWOrder([
            'sw_order_number' => $shopwareOrderInfo->getOrderNumber(),
        ]);
        $swOrder->save();

        return $swOrder;
    }

    protected function createSWOrderDetails(SWOrder $swOrder, ShopwareOrderInfo $shopwareOrderInfo): SWOrderDetail
    {
        $swOD = $swOrder->details()
            ->save(new SWOrderDetail([
                'data' => $shopwareOrderInfo->getData(),
            ]));

        return $swOD;
    }

    protected function updateOrder(SWOrder $swOrder, ShopwareOrderInfo $shopwareOrderInfo): void
    {
        $currentData = $swOrder->currentData;
        $this->logger->info('OrderPersister: updating order', [
            'order' => $swOrder->asLoggingContext(),
            'orderData' => $currentData->asLoggingContext(),
            'orderInfo' => $shopwareOrderInfo->asLoggingContext(),
        ]);

        $swOrderData = $currentData->replicate();
        $swOrderData->sw_transfer_status = SWTransferStatus::OPEN;

        if ($currentData->edc_transfer_status === EDCTransferStatus::ERROR) {
            $swOrderData->edc_transfer_status = EDCTransferStatus::OPEN;
        }

        if (!in_array($currentData->edc_transfer_status, [EDCTransferStatus::COMPLETED, EDCTransferStatus::WAITING])) {
            $swOrderDetails = $this->createSWOrderDetails($swOrder, $shopwareOrderInfo);
            $swOrderData->orderDetail()->associate($swOrderDetails);
        }

        $swOrder->saveData($swOrderData);

        $this->logger->info('OrderPersister: updated order', [
            'order' => $swOrder->asLoggingContext(),
            'orderData' => $currentData->asLoggingContext(),
        ]);
    }
}
