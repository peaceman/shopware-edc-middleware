<?php
/**
 * lel since 2019-07-28
 */

namespace App\SW\Export\Commands;

use App\SW\Export\OpenOrderUpdater;
use Illuminate\Console\Command;

class UpdateOrders extends Command
{
    protected $signature = 'sw:update-orders';

    public function handle(OpenOrderUpdater $updater)
    {
        $updater->updateOrders();
    }
}
