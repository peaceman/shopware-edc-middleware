<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\EDC\Export;

use App\EDC\EDCAPI;
use App\EDC\EDCOrderExportInfo;
use App\EDC\Export\OrderExporter;
use App\EDC\Export\OrderXMLGenerator;
use App\EDCExportStatus;
use App\EDCTransferStatus;
use App\SW\ShopwareOrderInfo;
use App\SWOrder;
use App\SWOrderData;
use App\SWOrderDetail;
use App\SWTransferStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use InvalidArgumentException;
use Tests\TestCase;
use function App\Utils\fixture_content;

class OrderExporterTest extends TestCase
{
    use DatabaseTransactions;

    public function testSuccessfulExport()
    {
        // prepare order
        /** @var SWOrder $swOrder */
        $swOrder = factory(SWOrder::class)->create();
        $swOrderDetails = new SWOrderDetail(['data' => ['number' => 'this is test']]);
        $swOrder->details()->save($swOrderDetails);

        $swOrderData = new SWOrderData();
        $swOrderData->order()->associate($swOrder);
        $swOrderData->orderDetail()->associate($swOrderDetails);
        $swOrderData->sw_transfer_status = SWTransferStatus::COMPLETED;
        $swOrderData->edc_transfer_status = EDCTransferStatus::OPEN;

        $swOrder->saveData($swOrderData);

        // prepare mocks
        $orderXMLGenerator = $this->createMock(OrderXMLGenerator::class);
        $orderXML = 'dis is order xml';
        $orderXMLGenerator->expects(static::once())
            ->method('generateXML')
            ->with(static::logicalAnd(
                static::isInstanceOf(ShopwareOrderInfo::class),
                static::callback(function (ShopwareOrderInfo $soi) {
                    return $soi->getOrderNumber() === 'this is test';
                })
            ))
            ->willReturn($orderXML);

        $edcAPI = $this->createMock(EDCAPI::class);
        $edcOrderExportResponse = json_decode(fixture_content('edc-order-export-response-success.json'), true);
        $edcAPI->expects(static::once())
            ->method('exportOrder')
            ->with($orderXML)
            ->willReturn(new EDCOrderExportInfo($edcOrderExportResponse));

        // export
        $orderExporter = $this->createOrderExporter([
            'orderXMLGenerator' => $orderXMLGenerator,
            'edcAPI' => $edcAPI,
        ]);

        $orderExporter->exportOrder($swOrder);

        // assert
        $swOrder->refresh();

        // order export
        static::assertEquals(1, $swOrder->exports()->count());
        $orderExport = $swOrder->exports()->latest()->first();
        static::assertNotNull($orderExport);
        static::assertEquals($orderXML, $orderExport->sent);
        static::assertEquals($edcOrderExportResponse, $orderExport->received);
        static::assertEquals(EDCExportStatus::OK, $orderExport->status);

        // order data
        $orderData = $swOrder->currentData;
        static::assertNotEquals($swOrderData->id, $orderData->id);
        static::assertEquals(EDCTransferStatus::WAITING, $orderData->edc_transfer_status);
        static::assertEquals(SWTransferStatus::OPEN, $orderData->sw_transfer_status);
        static::assertEquals('DR19072814488521', $orderData->edc_order_number);
        static::assertNull($orderData->tracking_number);
    }

    public function testFailingExportWithValidResponse()
    {
        // prepare order
        /** @var SWOrder $swOrder */
        $swOrder = factory(SWOrder::class)->create();
        $swOrderDetails = new SWOrderDetail(['data' => ['number' => 'this is test']]);
        $swOrder->details()->save($swOrderDetails);

        $swOrderData = new SWOrderData();
        $swOrderData->order()->associate($swOrder);
        $swOrderData->orderDetail()->associate($swOrderDetails);
        $swOrderData->sw_transfer_status = SWTransferStatus::COMPLETED;
        $swOrderData->edc_transfer_status = EDCTransferStatus::OPEN;

        $swOrder->saveData($swOrderData);

        // prepare mocks
        $orderXMLGenerator = $this->createMock(OrderXMLGenerator::class);
        $orderXML = 'dis is order xml';
        $orderXMLGenerator->expects(static::once())
            ->method('generateXML')
            ->with(static::anything())
            ->willReturn($orderXML);

        $edcAPI = $this->createMock(EDCAPI::class);
        $edcOrderExportResponse = json_decode(fixture_content('edc-order-export-response-failure.json'), true);
        $edcAPI->expects(static::once())
            ->method('exportOrder')
            ->with($orderXML)
            ->willReturn(new EDCOrderExportInfo($edcOrderExportResponse));

        // export
        $orderExporter = $this->createOrderExporter([
            'orderXMLGenerator' => $orderXMLGenerator,
            'edcAPI' => $edcAPI,
        ]);

        $orderExporter->exportOrder($swOrder);

        // assert
        $swOrder->refresh();

        // order export
        static::assertEquals(1, $swOrder->exports()->count());
        $orderExport = $swOrder->exports()->latest()->first();
        static::assertNotNull($orderExport);
        static::assertEquals($orderXML, $orderExport->sent);
        static::assertEquals($edcOrderExportResponse, $orderExport->received);
        static::assertEquals(EDCExportStatus::FAIL, $orderExport->status);

        // order data
        $orderData = $swOrder->currentData;
        static::assertNotEquals($swOrderData->id, $orderData->id);
        static::assertEquals(EDCTransferStatus::ERROR, $orderData->edc_transfer_status);
        static::assertEquals(SWTransferStatus::OPEN, $orderData->sw_transfer_status);
        static::assertNull($orderData->edc_order_number);
        static::assertNull($orderData->tracking_number);
    }

    public function testFailingExportWithInvalidResponse()
    {
        // prepare order
        /** @var SWOrder $swOrder */
        $swOrder = factory(SWOrder::class)->create();
        $swOrderDetails = new SWOrderDetail(['data' => ['number' => 'this is test']]);
        $swOrder->details()->save($swOrderDetails);

        $swOrderData = new SWOrderData();
        $swOrderData->order()->associate($swOrder);
        $swOrderData->orderDetail()->associate($swOrderDetails);
        $swOrderData->sw_transfer_status = SWTransferStatus::COMPLETED;
        $swOrderData->edc_transfer_status = EDCTransferStatus::OPEN;

        $swOrder->saveData($swOrderData);

        // prepare mocks
        $orderXMLGenerator = $this->createMock(OrderXMLGenerator::class);
        $orderXML = 'dis is order xml';
        $orderXMLGenerator->expects(static::once())
            ->method('generateXML')
            ->with(static::anything())
            ->willReturn($orderXML);

        $edcAPI = $this->createMock(EDCAPI::class);
        $edcAPI->expects(static::once())
            ->method('exportOrder')
            ->with($orderXML)
            ->will(static::throwException(new \InvalidArgumentException('json_decode_error')));

        // export
        $orderExporter = $this->createOrderExporter([
            'orderXMLGenerator' => $orderXMLGenerator,
            'edcAPI' => $edcAPI,
        ]);

        try {
            $orderExporter->exportOrder($swOrder);
            static::fail('The expected exception was not thrown');
        } catch (InvalidArgumentException $e) {
            static::addToAssertionCount(1);
        }

        // assert
        $swOrder->refresh();

        // order export
        static::assertEquals(0, $swOrder->exports()->count());

        // order data
        $orderData = $swOrder->currentData;
        static::assertEquals($swOrderData->id, $orderData->id);
        static::assertEquals(EDCTransferStatus::OPEN, $orderData->edc_transfer_status);
        static::assertEquals(SWTransferStatus::COMPLETED, $orderData->sw_transfer_status);
        static::assertNull($orderData->edc_order_number);
        static::assertNull($orderData->tracking_number);
    }

    protected function createOrderExporter(array $params = []): OrderExporter
    {
        return $this->app->make(OrderExporter::class, $params);
    }
}
