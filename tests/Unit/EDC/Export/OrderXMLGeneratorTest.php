<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\EDC\Export;

use App\EDC\Export\OrderXMLGenerator;
use App\SW\ShopwareOrderInfo;
use Tests\TestCase;
use function App\Utils\fixture_content;

class OrderXMLGeneratorTest extends TestCase
{
    public function testGeneral()
    {
        $soi = new ShopwareOrderInfo(
            json_decode(fixture_content('shopware-api-order-details-response-59.json'), true)['data']
        );

        $orderXMLGenerator = $this->createOrderXMLGenerator();
        $xml = $orderXMLGenerator->generateXML($soi);
        static::assertEquals(fixture_content('edc-order-export.xml'), $xml);
    }

    protected function createOrderXMLGenerator(): OrderXMLGenerator
    {
        return $this->app->make(OrderXMLGenerator::class);
    }
}
