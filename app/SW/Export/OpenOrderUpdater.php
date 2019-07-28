<?php
/**
 * lel since 2019-07-28
 */

namespace App\SW\Export;

use App\SWOrder;
use App\SWTransferStatus;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

class OpenOrderUpdater
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var \App\SW\Export\OrderUpdater */
    protected $orderUpdater;

    public function __construct(LoggerInterface $logger, OrderUpdater $orderUpdater)
    {
        $this->logger = $logger;
        $this->orderUpdater = $orderUpdater;
    }

    public function updateOrders(): void
    {
        $startTime = microtime(true);
        $this->logger->info('OpenOrderUpdater: start updating orders to sw');

        $counter = 0;

        foreach ($this->provideOrders() as $order) {
            try {
                $this->orderUpdater->updateOrder($order);
                $counter++;
            } catch (\Exception $e) {
                $this->logger->error('OpenOrderUpdater: failed to update order', [
                    'order' => $order->asLoggingContext(),
                ]);

                report($e);
            }
        }

        $this->logger->info('OpenOrderUpdater: finished updating orders to sw', [
            'elapsed' => microtime(true) - $startTime,
            'counter' => $counter,
        ]);
    }

    protected function provideOrders(): iterable
    {
        return SWOrder::query()
            ->whereHas('currentData', function (Builder $query) {
                $query->where('sw_transfer_status', SWTransferStatus::OPEN);
            })
            ->cursor();
    }
}
