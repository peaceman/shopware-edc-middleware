<?php
/**
 * lel since 2019-07-14
 */

namespace Tests\Unit\SW\Export;

use App\Brand;
use App\BrandDiscount;
use App\EDC\Import\ProductXML;
use App\EDCProduct;
use App\SW\Export\PriceCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function App\Utils\fixture_path;

class PriceCalculatorTest extends TestCase
{
    use DatabaseTransactions;

    /** @var PriceCalculator */
    protected $priceCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->priceCalculator = $this->app[PriceCalculator::class];
    }

    public function priceCalculationData(): array
    {
        return [
            'with active brand discount' => [
                tap(new EDCProduct(), function (EDCProduct $ep) {
                    $brand = new Brand();
                    $brand->setRelation('currentDiscount', new BrandDiscount(['value' => 50]));

                    $ep->setRelation('brand', $brand);

                    return $ep;
                }),
                'product-1.xml',
                ceil(9.95 * .5),
            ],
            'with active brand discount 0' => [
                tap(new EDCProduct(), function (EDCProduct $ep) {
                    $brand = new Brand();
                    $brand->setRelation('currentDiscount', new BrandDiscount(['value' => 0]));

                    $ep->setRelation('brand', $brand);

                    return $ep;
                }),
                'product-1.xml',
                ceil(9.95),
            ],
            'without brand discount' => [
                tap(new EDCProduct(), function (EDCProduct $ep) {
                    $brand = new Brand();
                    $brand->setRelation('currentDiscount', new BrandDiscount(['value' => 0]));

                    $ep->setRelation('brand', $brand);

                    return $ep;
                }),
                'product-1.xml',
                ceil(9.95),
            ],
            'with inactive brand discount' => [
                tap(new EDCProduct(), function (EDCProduct $ep) {
                    $brand = new Brand();
                    $brand->setRelation('currentDiscount', new BrandDiscount(['value' => 50]));

                    $ep->setRelation('brand', $brand);

                    return $ep;
                }),
                'product-1-without-discount.xml',
                ceil(9.95),
            ],
        ];
    }

    /**
     * @dataProvider priceCalculationData
     */
    public function testPriceCalculation(EDCProduct $product, string $productXMLFilePath, float $expected)
    {
        $productXML = ProductXML::fromFilePath(fixture_path($productXMLFilePath));

        static::assertEquals($expected, $this->priceCalculator->calcPrice($product, $productXML));
    }
}
