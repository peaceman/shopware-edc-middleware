<?php
/**
 * lel since 2019-07-28
 */

namespace App\EDC\Export\Commands;

use App\EDC\Export\OpenOrderExporter;
use Illuminate\Console\Command;

class ExportOrders extends Command
{
    protected $signature = 'edc:export-orders';

    public function handle(OpenOrderExporter $exporter): void
    {
        $exporter->exportOrders();
    }
}
