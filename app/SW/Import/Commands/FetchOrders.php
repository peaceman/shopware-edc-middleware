<?php
/**
 * lel since 2019-07-28
 */

namespace App\SW\Import\Commands;

use App\SW\Import\OrderFetcher;
use App\SW\Import\OrderProviders\OpenOrderProvider;
use Illuminate\Console\Command;

class FetchOrders extends Command
{
    protected $signature = 'sw:fetch-orders';

    public function handle(OrderFetcher $orderFetcher, OpenOrderProvider $openOrderProvider): void
    {
        $orderFetcher->fetchOrders($openOrderProvider);
    }
}
