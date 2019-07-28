<?php
/**
 * lel since 2019-07-28
 */

namespace App\SW\Export;

use App\EDCOrderExport;
use App\EDCTransferStatus;
use App\SW\ShopwareAPI;
use App\SWOrder;
use App\SWTransferStatus;
use Assert\Assert;
use Psr\Log\LoggerInterface;

class OrderUpdater
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ShopwareAPI */
    protected $shopwareAPI;

    public function __construct(LoggerInterface $logger, ShopwareAPI $shopwareAPI)
    {
        $this->logger = $logger;
        $this->shopwareAPI = $shopwareAPI;
    }

    public function updateOrder(SWOrder $order): void
    {
        $startTime = microtime(true);
        $this->logger->info('OrderUpdater: start updating order', [
            'order' => $order->asLoggingContext(),
        ]);

        $this->shopwareAPI->updateOrder($order->sw_order_number, array_merge(...array_filter([
            $this->genOrderState($order),
            $this->genTrackingCode($order),
            $this->genAttributes($order),
        ])));

        $orderData = $order->currentData->replicate();
        $orderData->sw_transfer_status = SWTransferStatus::COMPLETED;
        $order->saveData($orderData);

        $this->logger->info('OrderUpdater: finished updating order', [
            'order' => $order->asLoggingContext(),
            'elapsed' => microtime(true) - $startTime,
        ]);
    }

    protected function genOrderState(SWOrder $order): array
    {
        $mapping = [
            EDCTransferStatus::OPEN => config('shopware.status.order.inProcess'),
            EDCTransferStatus::COMPLETED => config('shopware.status.order.completed'),
            EDCTransferStatus::WAITING => config('shopware.status.order.inProcess'),
            EDCTransferStatus::ERROR => config('shopware.status.order.clarificationRequired'),
        ];

        $orderData = $order->currentData;
        Assert::that($orderData->edc_transfer_status)->inArray(array_keys($mapping));

        return [
            'orderStatusId' => $mapping[$orderData->edc_transfer_status],
        ];
    }

    protected function genTrackingCode(SWOrder $order): ?array
    {
        $orderData = $order->currentData;
        if (!$orderData->tracking_number) return null;

        return ['trackingCode' => $orderData->tracking_number];
    }

    protected function genAttributes(SWOrder $order): array
    {
        return [
            'attribute' => array_merge(...array_filter([
                $this->genEDCOrderNumber($order),
                $this->genEDCErrors($order),
            ]))
        ];
    }

    protected function genEDCOrderNumber(SWOrder $order): ?array
    {
        $orderData = $order->currentData;
        if (!$orderData->edc_order_number) return null;

        return [
            'edcOrderNumber' => $orderData->edc_order_number,
        ];
    }

    protected function genEDCErrors(SWOrder $order): ?array
    {
        if ($order->failedOrderExports->count() === 0) return null;

        $errors = $order->failedOrderExports
            ->sortByDesc('created_at')
            ->map(function (EDCOrderExport $orderExport): string {
                $exportInfo = $orderExport->asExportInfo();

                return sprintf(
                    '%s %s (%s)',
                    $orderExport->created_at->toAtomString(),
                    $exportInfo->getMessage(),
                    $exportInfo->getErrorCode()
                );
            })
            ->implode(PHP_EOL);

        return ['edcErrors' => $errors];
    }
}
