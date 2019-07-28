<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\SW\Export;

use App\EDCTransferStatus;
use App\SW\Export\OpenOrderUpdater;
use App\SW\Export\OrderUpdater;
use App\SWOrder;
use App\SWOrderData;
use App\SWOrderDetail;
use App\SWTransferStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OpenOrderUpdaterTest extends TestCase
{
    use DatabaseTransactions;

    public function testOnlyOpenOrdersWillBeExported()
    {
        $orderA = $this->createSWOrderWithTransferStatus(SWTransferStatus::OPEN);
        $orderB = $this->createSWOrderWithTransferStatus(SWTransferStatus::COMPLETED);

        $orderUpdater = $this->createMock(OrderUpdater::class);
        $orderUpdater->expects(static::once())
            ->method('updateOrder')
            ->with(static::callback(function ($arg) use ($orderA) {
                return $arg instanceof $orderA
                    && $arg->id == $orderA->id;
            }));

        $openOrderUpdater = $this->createOpenOrderUpdater(['orderUpdater' => $orderUpdater]);
        $openOrderUpdater->updateOrders();
    }

    public function testAFailedUpdateWontCrashTheWholeThing()
    {
        $orderA = $this->createSWOrderWithTransferStatus(SWTransferStatus::OPEN);
        $orderB = $this->createSWOrderWithTransferStatus(SWTransferStatus::OPEN);

        $orderUpdater = $this->createMock(OrderUpdater::class);
        $orderUpdater->expects(static::exactly(2))
            ->method('updateOrder')
            ->willReturn(
                static::throwException(new \Exception()),
                true
            );

        $openOrderUpdater = $this->createOpenOrderUpdater(['orderUpdater' => $orderUpdater]);
        $openOrderUpdater->updateOrders();
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
        $orderData->sw_transfer_status = $transferStatus;
        $orderData->edc_transfer_status = EDCTransferStatus::ERROR;
        $orderData->save();

        return $order;
    }

    protected function createOpenOrderUpdater(array $params = []): OpenOrderUpdater
    {
        return $this->app->make(OpenOrderUpdater::class, $params);
    }
}
