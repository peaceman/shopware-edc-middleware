<?php
/**
 * lel since 2019-07-27
 */

namespace Tests\Unit\SW\Import;

use App\SW\Import\OrderFetcher;
use App\SW\Import\OrderPersister;
use App\SW\Import\OrderProviders\OpenOrderProvider;
use App\SW\ShopwareOrderInfo;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrderFetcherTest extends TestCase
{
    use DatabaseTransactions;

    public function testRegular(): void
    {
        $orderA = $this->createMock(ShopwareOrderInfo::class);
        $orderB = $this->createMock(ShopwareOrderInfo::class);

        $orderProvider = $this->createMock(OpenOrderProvider::class);
        $orderProvider->expects(static::once())
            ->method('getOrders')
            ->willReturn([$orderA, $orderB]);

        $orderPersister = $this->createMock(OrderPersister::class);
        $orderPersister->expects(static::exactly(2))
            ->method('persistOrder')
            ->withConsecutive([$orderA], [$orderB]);

        $orderFetcher = $this->createOrderFetcher(['orderPersister' => $orderPersister]);
        $orderFetcher->fetchOrders($orderProvider);
    }

    public function testFailingPersist(): void
    {
        $order = $this->createMock(ShopwareOrderInfo::class);

        $orderProvider = $this->createMock(OpenOrderProvider::class);
        $orderProvider->expects(static::once())
            ->method('getOrders')
            ->willReturn([$order]);

        $orderPersister = $this->createMock(OrderPersister::class);
        $orderPersister->expects(static::once())
            ->method('persistOrder')
            ->with($order)
            ->willThrowException(new Exception('topkek'));

        $orderFetcher = $this->createOrderFetcher(['orderPersister' => $orderPersister]);
        $orderFetcher->fetchOrders($orderProvider);
    }

    protected function createOrderFetcher(array $params = []): OrderFetcher
    {
        return $this->app->make(OrderFetcher::class, $params);
    }
}
