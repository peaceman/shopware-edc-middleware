<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\EDC\Export;

use App\EDC\Export\OpenOrderExporter;
use App\EDC\Export\OrderExporter;
use App\EDCTransferStatus;
use App\SWOrder;
use App\SWOrderData;
use App\SWOrderDetail;
use App\SWTransferStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OpenOrderExporterTest extends TestCase
{
    use DatabaseTransactions;

    public function testOnlyOpenOrdersWillBeExported()
    {
        $orderA = $this->createSWOrderWithTransferStatus(EDCTransferStatus::OPEN);
        $orderB = $this->createSWOrderwithTransferStatus(EDCTransferStatus::WAITING);
        $orderC = $this->createSWOrderwithTransferStatus(EDCTransferStatus::ERROR);
        $orderD = $this->createSWOrderwithTransferStatus(EDCTransferStatus::COMPLETED);

        $orderExporter = $this->createMock(OrderExporter::class);
        $orderExporter->expects(static::once())
            ->method('exportOrder')
            ->with(static::callback(function ($arg) use ($orderA) {
                return $arg instanceof $orderA
                    && $arg->id == $orderA->id;
            }));

        $openOrderExporter = $this->createOpenOrderExporter(['orderExporter' => $orderExporter]);
        $openOrderExporter->exportOrders();
    }

    public function testAFailedExportWontCrashTheWholeThing()
    {
        $orderA = $this->createSWOrderWithTransferStatus(EDCTransferStatus::OPEN);
        $orderB = $this->createSWOrderWithTransferStatus(EDCTransferStatus::OPEN);

        $orderExporter = $this->createMock(OrderExporter::class);
        $orderExporter->expects(static::exactly(2))
            ->method('exportOrder')
            ->willReturn(
                static::throwException(new \Exception()),
                true
            );

        $openOrderExporter = $this->createOpenOrderExporter(['orderExporter' => $orderExporter]);
        $openOrderExporter->exportOrders();
    }

    protected function createSWOrderWithTransferStatus(string $transferStatus): SWOrder
    {
        /** @var SWOrder $order */
        $order = factory(SWOrder::class)->create();
        $orderDetail = new SWOrderDetail();
        $orderDetail->order()->associate($order);
        $orderDetail->data = ['top' => 'kek'];
        $orderDetail->save();

        $orderData = new SWOrderData();
        $orderData->order()->associate($order);
        $orderData->orderDetail()->associate($orderDetail);
        $orderData->sw_transfer_status = SWTransferStatus::COMPLETED;
        $orderData->edc_transfer_status = $transferStatus;
        $orderData->save();

        return $order;
    }

    protected function createOpenOrderExporter(array $params = []): OpenOrderExporter
    {
        return $this->app->make(OpenOrderExporter::class, $params);
    }
}
