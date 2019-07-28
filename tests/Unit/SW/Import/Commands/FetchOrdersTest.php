<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\SW\Import\Commands;

use App\SW\Import\OrderFetcher;
use App\SW\Import\OrderProviders\OpenOrderProvider;
use Tests\TestCase;

class FetchOrdersTest extends TestCase
{
    public function testCommand()
    {
        $orderFetcher = $this->createMock(OrderFetcher::class);
        $orderProvider = $this->createMock(OpenOrderProvider::class);

        $orderFetcher->expects(static::once())
            ->method('fetchOrders')
            ->with($orderProvider);

        $this->app->instance(OrderFetcher::class, $orderFetcher);
        $this->app->instance(OpenOrderProvider::class, $orderProvider);

        $this->artisan('sw:fetch-orders');
    }
}
