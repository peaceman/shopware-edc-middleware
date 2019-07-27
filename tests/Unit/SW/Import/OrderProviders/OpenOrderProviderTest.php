<?php
/**
 * lel since 2019-07-27
 */

namespace Tests\Unit\SW\Import\OrderProviders;

use App\SW\Import\OrderProviders\OpenOrderProvider;
use App\SW\ShopwareAPI;
use App\SW\ShopwareOrderInfo;
use Tests\TestCase;
use function App\Utils\fixture_content;

class OpenOrderProviderTest extends TestCase
{
    public function testFilters(): void
    {
        $shopwareAPI = $this->getMockBuilder(ShopwareAPI::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchOrders', 'fetchOrderDetails'])
            ->getMock();

        $shopwareAPI->expects(static::exactly(2))
            ->method('fetchOrders')
            ->withConsecutive(...[
                [[
                    ['property' => 'status', 'value' => 23],
                    ['property' => 'cleared', 'value' => 42],
                ]],
                [[
                    ['property' => 'status', 'value' => 23],
                    ['property' => 'cleared', 'value' => 5],
                ]]
            ])
            ->willReturnOnConsecutiveCalls(...[
                ['data' => []],
                json_decode(fixture_content('shopware-api-orders-response.json'), true),
            ]);


        $shopwareAPI->expects(static::exactly(3))
            ->method('fetchOrderDetails')
            ->withConsecutive([55], [59], [61])
            ->willReturnOnConsecutiveCalls(...[
                json_decode(fixture_content('shopware-api-order-details-response-55.json'), true),
                json_decode(fixture_content('shopware-api-order-details-response-59.json'), true),
                json_decode(fixture_content('shopware-api-order-details-response-61.json'), true),
            ]);

        $orderProvider = new OpenOrderProvider($shopwareAPI);
        $orderProvider->setRequirements([
            ['status' => 23, 'cleared' => 42],
            ['status' => 23, 'cleared' => 5],
        ]);

        $orders = iterator_to_array($orderProvider->getOrders());
        static::assertCount(3, $orders);
        static::assertContainsOnlyInstancesOf(ShopwareOrderInfo::class, $orders);

        $orderNumbers = collect($orders)
            ->map(function (ShopwareOrderInfo $orderInfo) {
                return $orderInfo->getOrderNumber();
            })
            ->all();

        static::assertEqualsCanonicalizing(['20002', '20003', '20004'], $orderNumbers);
    }

}
