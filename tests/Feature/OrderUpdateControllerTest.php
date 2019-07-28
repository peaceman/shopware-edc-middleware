<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Feature;

use App\EDCOrderStatus;
use App\EDCTransferStatus;
use App\SWOrder;
use App\SWOrderDetail;
use App\SWTransferStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;
use function App\Utils\fixture_content;

class OrderUpdateControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testUnauthenticated()
    {
        $response = $this->post(route('order-update', ['data' => '']));
        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function testShippedOrder()
    {
        $this->withoutExceptionHandling();

        // prepare order
        $swOrder = new SWOrder(['sw_order_number' => '9393920209']);
        $swOrder->save();

        $swOrderDetail = new SWOrderDetail(['order_id' => $swOrder->id, 'data' => []]);
        $swOrderDetail->save();

        $swOrder->data()->create([
            'order_detail_id' => $swOrderDetail->id,
            'sw_transfer_status' => SWTransferStatus::COMPLETED,
            'edc_transfer_status' => EDCTransferStatus::WAITING,
        ]);

        // request
        $orderShippedData = fixture_content('order-update-shipped.xml');
        $response = $this->post(
            route('order-update', ['auth' => config('edc.orderUpdateAuthToken')]),
            ['data' => $orderShippedData]
        );

        // assertions
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $swOrder->refresh();
        $orderData = $swOrder->currentData;
        static::assertEquals('1Z57W468DL53020674', $orderData->tracking_number);
        static::assertEquals(SWTransferStatus::OPEN, $orderData->sw_transfer_status);
        static::assertEquals(EDCTransferStatus::COMPLETED, $orderData->edc_transfer_status);

        $edcOrderUpdate = $swOrder->updates()->latest()->first();
        static::assertNotNull($edcOrderUpdate);
        static::assertEquals(EDCOrderStatus::SHIPPED, $edcOrderUpdate->status);
        static::assertEquals($orderShippedData, $edcOrderUpdate->received);
    }

    public function testBackOrder()
    {
        $this->withoutExceptionHandling();

        // prepare order
        $swOrder = new SWOrder(['sw_order_number' => 'R9393JKF93']);
        $swOrder->save();

        $swOrderDetail = new SWOrderDetail(['order_id' => $swOrder->id, 'data' => []]);
        $swOrderDetail->save();

        $swOrder->data()->create([
            'order_detail_id' => $swOrderDetail->id,
            'sw_transfer_status' => SWTransferStatus::COMPLETED,
            'edc_transfer_status' => EDCTransferStatus::WAITING,
        ]);

        // request
        $backorderData = fixture_content('order-update-backorder.xml');
        $response = $this->post(
            route('order-update', ['auth' => config('edc.orderUpdateAuthToken')]),
            ['data' => $backorderData]
        );

        // assertions
        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $swOrder->refresh();
        static::assertEquals(1, $swOrder->data()->count());

        $edcOrderUpdate = $swOrder->updates()->latest()->first();
        static::assertNotNull($edcOrderUpdate);
        static::assertEquals(EDCOrderStatus::BACKORDER, $edcOrderUpdate->status);
        static::assertEquals($backorderData, $edcOrderUpdate->received);
    }
}
