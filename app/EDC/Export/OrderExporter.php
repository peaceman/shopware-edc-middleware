<?php
/**
 * lel since 2019-07-28
 */

namespace App\EDC\Export;

use App\EDC\EDCAPI;
use App\EDCExportStatus;
use App\EDCOrderExport;
use App\EDCTransferStatus;
use App\SWOrder;
use App\SWTransferStatus;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;

class OrderExporter
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ConnectionInterface */
    protected $db;

    /** @var OrderXMLGenerator */
    protected $orderXMLGenerator;

    /** @var EDCAPI */
    protected $edcAPI;

    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $db,
        OrderXMLGenerator $orderXMLGenerator,
        EDCAPI $edcAPI
    ) {
        $this->logger = $logger;
        $this->db = $db;
        $this->orderXMLGenerator = $orderXMLGenerator;
        $this->edcAPI = $edcAPI;
    }

    public function exportOrder(SWOrder $order): void
    {
        $startTime = microtime(true);
        $this->logger->info('OrderExporter: start exporting order', [
            'order' => $order->asLoggingContext(),
        ]);

        $orderData = $order->currentData;
        $orderXML = $this->orderXMLGenerator->generateXML($orderData->asShopwareOrderInfo());

        $exportResult = $this->edcAPI->exportOrder($orderXML);

        $this->db->transaction(function () use ($order, $exportResult, $orderXML, $orderData, $startTime) {
            $orderExport = new EDCOrderExport();
            $orderExport->order()->associate($order);
            $orderExport->status = $exportResult->getStatus();
            $orderExport->sent = $orderXML;
            $orderExport->received = $exportResult->getData();
            $orderExport->save();

            $newOrderData = $orderData->replicate();

            switch ($exportResult->getStatus()) {
                case EDCExportStatus::OK:
                    $newOrderData->edc_transfer_status = EDCTransferStatus::WAITING;
                    $newOrderData->sw_transfer_status = SWTransferStatus::OPEN;
                    $newOrderData->edc_order_number = $exportResult->getOrderNumber();
                    break;
                case EDCExportStatus::FAIL:
                    $newOrderData->edc_transfer_status = EDCTransferStatus::ERROR;
                    $newOrderData->sw_transfer_status = SWTransferStatus::OPEN;
                    break;
            }

            $order->saveData($newOrderData);

            $this->logger->info('OrderExporter: finished exporting order', [
                'order' => $order->asLoggingContext(),
                'elapsed' => microtime(true) - $startTime,
            ]);
        });
    }
}
