<?php
/**
 * lel since 2019-07-28
 */

namespace App\EDC\Export;

use App\EDCTransferStatus;
use App\SWOrder;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

class OpenOrderExporter
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var OrderExporter */
    protected $orderExporter;

    public function __construct(LoggerInterface $logger, OrderExporter $orderExporter)
    {
        $this->logger = $logger;
        $this->orderExporter = $orderExporter;
    }

    public function exportOrders(): void
    {
        $startTime = microtime(true);
        $this->logger->info('OpenOrderExporter: start exporting orders to edc');

        $counter = 0;

        /** @var SWOrder $order */
        foreach ($this->provideOrders() as $order) {
            try {
                $this->orderExporter->exportOrder($order);
                $counter++;
            } catch (\Exception $e) {
                $this->logger->error('OpenOrderExporter: failed to export order', [
                    'order' => $order->asLoggingContext(),
                ]);

                report($e);
            }
        }

        $this->logger->info('OpenOrderExporter: finished exporting orders to edc', [
            'elapsed' => microtime(true) - $startTime,
            'counter' => $counter,
        ]);
    }

    protected function provideOrders(): iterable
    {
        return SWOrder::query()
            ->whereHas('currentData', function (Builder $query) {
                $query->where('edc_transfer_status', EDCTransferStatus::OPEN);
            })
            ->cursor();
    }
}
