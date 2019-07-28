<?php
/**
 * lel since 2019-07-28
 */

namespace App\Http\Controllers;

use App\EDC\Import\OrderUpdateXML;
use App\EDCOrderStatus;
use App\EDCOrderUpdate;
use App\EDCTransferStatus;
use App\SWOrder;
use App\SWTransferStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;

class OrderUpdateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, callable $next) {
            $authToken = $request->get('auth');

            if ($authToken !== config('edc.orderUpdateAuthToken'))
                return response()->make('Unauthorized', Response::HTTP_UNAUTHORIZED);

            return $next($request);
        });
    }

    public function __invoke(LoggerInterface $logger, Request $request)
    {
        $this->validate($request, [
            'data' => ['required', 'string'],
        ]);

        $orderUpdateXML = OrderUpdateXML::fromString($request->input('data'));
        $swOrder = $this->fetchSWOrder($orderUpdateXML);

        $logger->info('OrderUpdateController: received order update', [
            'order' => $swOrder->asLoggingContext(),
            'status' => $orderUpdateXML->getStatus(),
        ]);

        $this->storeOrderUpdate($swOrder, $orderUpdateXML);

        if ($orderUpdateXML->getStatus() === EDCOrderStatus::SHIPPED) {
            $swOrderData = $swOrder->currentData->replicate();
            $swOrderData->tracking_number = $orderUpdateXML->getTrackingNumber();
            $swOrderData->edc_transfer_status = EDCTransferStatus::COMPLETED;
            $swOrderData->sw_transfer_status = SWTransferStatus::OPEN;
            $swOrder->saveData($swOrderData);
        }

        return response()->noContent();
    }

    protected function fetchSWOrder(OrderUpdateXML $orderUpdateXML): SWOrder
    {
        return SWOrder::withOrderNumber($orderUpdateXML->getOwnOrderNumber())->firstOrFail();
    }

    protected function storeOrderUpdate(SWOrder $swOrder, OrderUpdateXML $orderUpdateXML): void
    {
        $orderUpdate = new EDCOrderUpdate([
            'status' => $orderUpdateXML->getStatus(),
            'received' => $orderUpdateXML->asXML(),
        ]);

        $swOrder->updates()->save($orderUpdate);
    }
}
