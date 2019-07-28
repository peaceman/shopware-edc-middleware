<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\SW\Export;

use App\EDCExportStatus;
use App\EDCTransferStatus;
use App\SW\Export\OrderUpdater;
use App\SW\ShopwareAPI;
use App\SWOrder;
use App\SWOrderData;
use App\SWOrderDetail;
use App\SWTransferStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use RuntimeException;
use Tests\TestCase;

class OrderUpdaterTest extends TestCase
{
    use DatabaseTransactions;

    public function testRegularUpdate()
    {
        // prepare order
        $swOrder = $this->createOrder();
        $swOrder->exports()->create([
            'status' => EDCExportStatus::FAIL,
            'received' => ['result' => 'FAIL', 'message' => 'error msg one', 'errorcode' => '23'],
            'sent' => 'lul',
            'created_at' => Carbon::parse('1980-05-23T23:05:42+00:00'),
        ]);

        $swOrder->exports()->create([
            'status' => EDCExportStatus::FAIL,
            'received' => ['result' => 'FAIL', 'message' => 'error msg two', 'errorcode' => '23'],
            'sent' => 'lul',
            'created_at' => Carbon::parse('1999-05-23T23:05:42+00:00'),
        ]);

        // prepare sw api mock
        $swAPI = $this->createMock(ShopwareAPI::class);
        $swAPI->expects(static::once())
            ->method('updateOrder')
            ->with($swOrder->sw_order_number, [
                'orderStatusId' => config('shopware.status.order.inProcess'),
                'trackingCode' => 'abc123',
                'attribute' => [
                    'edcOrderNumber' => '42',
                    'edcErrors' => implode(PHP_EOL, [
                        '1999-05-23T23:05:42+00:00 error msg two (23)',
                        '1980-05-23T23:05:42+00:00 error msg one (23)',
                    ])
                ],
            ]);

        // update order
        $orderUpdater = $this->createOrderUpdater(['shopwareAPI' => $swAPI]);
        $orderUpdater->updateOrder($swOrder);

        // assertions
        $swOrder->refresh();
        static::assertEquals(SWTransferStatus::COMPLETED, $swOrder->currentData->sw_transfer_status);
    }

    public function testFailingUpdate()
    {
        // prepare order
        $swOrder = $this->createOrder();

        // prepare sw api mock
        $swAPI = $this->createMock(ShopwareAPI::class);
        $swAPI->expects(static::once())
            ->method('updateOrder')
            ->will(static::throwException(new RuntimeException()));

        // update order
        $orderUpdater = $this->createOrderUpdater(['shopwareAPI' => $swAPI]);

        try {
            $orderUpdater->updateOrder($swOrder);
            static::fail('The expected exception was not thrown');
        } catch (\Exception $e) {
            static::addToAssertionCount(1);
        }

        // assertions
        $swOrder->refresh();
        static::assertEquals(SWTransferStatus::OPEN, $swOrder->currentData->sw_transfer_status);
    }

    protected function createOrder(): SWOrder
    {
        $swOrderNumber = '2323';
        $swOrder = new SWOrder(['sw_order_number' => $swOrderNumber]);
        $swOrder->save();

        $swOrderDetails = new SWOrderDetail(['order_id' => $swOrder->id]);
        $swOrderDetails->data = ['foo' => 'bar'];
        $swOrderDetails->save();

        $swOrderData = new SWOrderData(['order_id' => $swOrder->id]);
        $swOrderData->orderDetail()->associate($swOrderDetails);
        $swOrderData->sw_transfer_status = SWTransferStatus::OPEN;
        $swOrderData->edc_transfer_status = EDCTransferStatus::WAITING;
        $swOrderData->edc_order_number = '42';
        $swOrderData->tracking_number = 'abc123';

        $swOrder->saveData($swOrderData);

        return $swOrder;
    }

    protected function createOrderUpdater(array $params = []): OrderUpdater
    {
        return $this->app->make(OrderUpdater::class, $params);
    }
}
