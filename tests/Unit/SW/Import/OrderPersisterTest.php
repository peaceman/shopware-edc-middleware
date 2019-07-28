<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\SW\Import;

use App\EDCTransferStatus;
use App\SW\Import\OrderPersister;
use App\SW\ShopwareOrderInfo;
use App\SWOrder;
use App\SWOrderData;
use App\SWOrderDetail;
use App\SWTransferStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderPersisterTest extends TestCase
{
    use DatabaseTransactions;

    public function testNewOrder(): void
    {
        $orderData = [
            'number' => '2323',
            'foo' => 'bar',
        ];
        $shopareOrderInfo = new ShopwareOrderInfo($orderData);

        $orderPersister = $this->createOrderPersister();
        $orderPersister->persistOrder($shopareOrderInfo);

        /** @var SWOrder $swOrder */
        $swOrder = SWOrder::withOrderNumber('2323')->first();
        static::assertNotNull($swOrder);

        $swOrderData = $swOrder->currentData;
        static::assertNotNull($swOrderData);
        static::assertEquals(SWTransferStatus::OPEN, $swOrderData->sw_transfer_status);
        static::assertEquals(EDCTransferStatus::OPEN, $swOrderData->edc_transfer_status);
        static::assertNull($swOrderData->edc_order_number);
        static::assertNull($swOrderData->tracking_number);

        $swOrderDetail = $swOrderData->orderDetail;
        static::assertNotNull($swOrderDetail);
        static::assertEqualsCanonicalizing($orderData, $swOrderDetail->data);
    }

    public function existingOrderDataProvider(): array
    {
        return [
            'error' => [
                EDCTransferStatus::ERROR,
                function (SWOrder $swOrder, SWOrderData $swOD, array $orderData) {
                    static::assertEquals(SWTransferStatus::OPEN, $swOD->sw_transfer_status);
                    static::assertEquals(EDCTransferStatus::OPEN, $swOD->edc_transfer_status);

                    static::assertEquals(2, $swOrder->data()->count());
                    static::assertEquals(2, $swOrder->details()->count());
                    static::assertEquals($orderData, $swOD->orderDetail->data);
                }
            ],
            'open' => [
                EDCTransferStatus::OPEN,
                function (SWOrder $swOrder, SWOrderData $swOD, array $orderData) {
                    static::assertEquals(SWTransferStatus::OPEN, $swOD->sw_transfer_status);
                    static::assertEquals(EDCTransferStatus::OPEN, $swOD->edc_transfer_status);

                    static::assertEquals(2, $swOrder->data()->count());
                    static::assertEquals(2, $swOrder->details()->count());
                    static::assertEquals($orderData, $swOD->orderDetail->data);
                }
            ],
            'completed' => [
                EDCTransferStatus::COMPLETED,
                function (SWOrder $swOrder, SWOrderData $swOD, array $orderData) {
                    static::assertEquals(SWTransferStatus::OPEN, $swOD->sw_transfer_status);
                    static::assertEquals(EDCTransferStatus::COMPLETED, $swOD->edc_transfer_status);

                    static::assertEquals(2, $swOrder->data()->count());
                    static::assertEquals(1, $swOrder->details()->count());
                    static::assertNotEquals($orderData, $swOD->orderDetail->data);
                }
            ],
            'waiting' => [
                EDCTransferStatus::WAITING,
                function (SWOrder $swOrder, SWOrderData $swOD, array $orderData) {
                    static::assertEquals(SWTransferStatus::OPEN, $swOD->sw_transfer_status);
                    static::assertEquals(EDCTransferStatus::WAITING, $swOD->edc_transfer_status);

                    static::assertEquals(2, $swOrder->data()->count());
                    static::assertEquals(1, $swOrder->details()->count());
                    static::assertNotEquals($orderData, $swOD->orderDetail->data);
                }
            ],
        ];
    }

    /**
     * @dataProvider existingOrderDataProvider
     */
    public function testExistingOrder(string $edcTransferStatus, \Closure $assertions)
    {
        // prepare order
        $swOrderNumber = '2323';
        $swOrder = new SWOrder(['sw_order_number' => $swOrderNumber]);
        $swOrder->save();

        $swOrderDetails = new SWOrderDetail(['order_id' => $swOrder->id]);
        $swOrderDetails->data = ['foo' => 'bar'];
        $swOrderDetails->save();

        $swOrderData = new SWOrderData(['order_id' => $swOrder->id]);
        $swOrderData->orderDetail()->associate($swOrderDetails);
        $swOrderData->sw_transfer_status = SWTransferStatus::COMPLETED;
        $swOrderData->edc_transfer_status = $edcTransferStatus;
        $swOrderData->edc_order_number = '42';

        $swOrder->saveData($swOrderData);

        // persist order
        $orderData = [
            'number' => $swOrderNumber,
            'foo' => 'topkek',
        ];

        $orderPersister = $this->createOrderPersister();
        $orderPersister->persistOrder(new ShopwareOrderInfo($orderData));

        // assert
        $swOrder = $swOrder->refresh();

        static::assertEquals($swOrderData->edc_order_number, $swOrder->currentData->edc_order_number);
        $assertions->call($this, $swOrder, $swOrder->currentData, $orderData);
    }

    protected function createOrderPersister(array $params = []): OrderPersister
    {
        return $this->app->make(OrderPersister::class, $params);
    }
}
