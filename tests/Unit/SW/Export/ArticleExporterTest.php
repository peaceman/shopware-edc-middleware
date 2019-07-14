<?php
/**
 * lel since 2019-07-14
 */

namespace Tests\Unit\SW\Export;

use App\Domain\ShopwareArticleInfo;
use App\EDC\Import\Parser\ProductFeedPartParser;
use App\EDC\Import\ProductImageLoader;
use App\EDC\Import\ProductXML;
use App\EDCFeed;
use App\EDCFeedPartProduct;
use App\EDCProduct;
use App\EDCProductImage;
use App\ResourceFile\StorageDirector;
use App\SW\Export\PriceCalculator;
use App\SW\ShopwareAPI;
use App\SWArticle;
use App\SWVariant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_path;

class ArticleExporterTest extends TestCase
{
    use DatabaseTransactions;

    /** @var StorageDirector */
    protected $storageDirector;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake(Storage::getDefaultCloudDriver());

        $this->storageDirector = $this->app[StorageDirector::class];
    }

    public function testExport()
    {
        // setup edc product
        $imageLoader = $this->createMock(ProductImageLoader::class);

        /** @var ProductFeedPartParser $productFeedPartParser */
        $productFeedPartParser = $this->app->make(ProductFeedPartParser::class, ['imageLoader' => $imageLoader]);
        $productFeedPartParser->parse($this->createProductFeedPartFromFile(fixture_path('product-1.xml')));

        /** @var EDCProduct $edcProduct */
        $edcProduct = EDCProduct::query()->latest()->first();
        $edcProductImage = factory(EDCProductImage::class)->create(['product_id' => $edcProduct->id]);

        $swAPIMock = $this->getMockBuilder(ShopwareAPI::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'createShopwareArticle',
            ])
            ->getMock();

        $swAPIMock->expects(static::once())
            ->method('createShopwareArticle')
            ->with([
                'active' => true,
                'name' => 'Sex-Strumpfhose haut',
                'tax' => 19.0,
                'supplier' => 'Cottelli Collection',
                'descriptionLong' => 'Haut/Weiß. 100% Polyamid.',
                'configuratorSet' => [
                    'type' => 0,
                    'groups' => [
                        ['name' => 'Size', 'options' => [
                            ['name' => 'S/M'],
                            ['name' => 'L/XL'],
                        ]]
                    ],
                ],
                'variants' => [
                    [
                        'active' => true,
                        'number' => '02308550000',
                        'ean' => '4024144230853',
                        'prices' => [[
                            'price' => 9.95,
                        ]],
                        'configuratorOptions' => [
                            ['group' => 'Size', 'option' => 'S/M'],
                        ],
                    ],
                    [
                        'active' => true,
                        'number' => '230863',
                        'ean' => '4024144230860',
                        'prices' => [[
                            'price' => 9.95,
                        ]],
                        'configuratorOptions' => [
                            ['group' => 'Size', 'option' => 'L/XL'],
                        ],
                    ]
                ],
                'images' => [
                    ['link' => route('product-images', [$edcProductImage->identifier])],
                ],
            ])
            ->willReturn(new ShopwareArticleInfo([
                'data' => [
                    'id' => 23,
                    'details' => [
                        ['number' => '02308550000', 'id' => 24],
                        ['number' => '230863', 'id' => 25],
                    ],
                ],
            ]));

        $priceCalculatorMock = $this->getMockBuilder(PriceCalculator::class)
            ->setMethods(['calcPrice'])
            ->disableOriginalConstructor()
            ->getMock();

        $priceCalculatorMock->expects(static::atLeastOnce())
            ->method('calcPrice')
            ->with($edcProduct, static::isInstanceOf(ProductXML::class))
            ->will(static::returnCallback(function (EDCProduct $ep, ProductXML $productXML) {
                return $productXML->getB2CPrice();
            }));

        // test export
        $exporter = $this->app->make(\App\SW\Export\ArticleExporter::class, [
            'shopwareAPI' => $swAPIMock,
            'priceCalculator' => $priceCalculatorMock,
        ]);
        $exporter->export($edcProduct);

        $swArticle = SWArticle::query()->where('edc_product_id', $edcProduct->id)->first();
        static::assertNotNull($swArticle);
        static::assertEquals(23, $swArticle->sw_id);

        foreach ($edcProduct->variants as $edcPV) {
            /** @var SWVariant $swVariant */
            $swVariant = SWVariant::query()
                ->where([
                    'article_id' => $swArticle->id,
                    'edc_product_variant_id' => $edcPV->id,
                ])
                ->first();

            static::assertNotNull($swVariant);
            static::assertContains($swVariant->sw_id, [24, 25]);
        }
    }

    public function testExportWithInvalidSWArticle()
    {
        // setup edc product
        $imageLoader = $this->createMock(ProductImageLoader::class);

        /** @var ProductFeedPartParser $productFeedPartParser */
        $productFeedPartParser = $this->app->make(ProductFeedPartParser::class, ['imageLoader' => $imageLoader]);
        $productFeedPartParser->parse($this->createProductFeedPartFromFile(fixture_path('product-1.xml')));

        /** @var EDCProduct $edcProduct */
        $edcProduct = EDCProduct::query()->latest()->first();
        $edcProductImage = factory(EDCProductImage::class)->create(['product_id' => $edcProduct->id]);

        // invalid sw article
        $swArticle = new SWArticle(['sw_id' => 35]);
        $swArticle->edcProduct()->associate($edcProduct);
        $swArticle->save();

        $swAPIMock = $this->getMockBuilder(ShopwareAPI::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchShopwareArticleInfoByArticleID',
                'createShopwareArticle',
            ])
            ->getMock();

        $swAPIMock->expects(static::once())
            ->method('fetchShopwareArticleInfoByArticleID')
            ->with(35)
            ->willReturn(null);

        $swAPIMock->expects(static::once())
            ->method('createShopwareArticle')
            ->willReturn(new ShopwareArticleInfo([
                'data' => [
                    'id' => 23,
                    'details' => [
                        ['number' => '02308550000', 'id' => 24],
                        ['number' => '230863', 'id' => 25],
                    ],
                ],
            ]));

        $priceCalculatorMock = $this->getMockBuilder(PriceCalculator::class)
            ->setMethods(['calcPrice'])
            ->disableOriginalConstructor()
            ->getMock();

        $priceCalculatorMock->expects(static::atLeastOnce())
            ->method('calcPrice')
            ->with($edcProduct, static::isInstanceOf(ProductXML::class))
            ->will(static::returnCallback(function (EDCProduct $ep, ProductXML $productXML) {
                return $productXML->getB2CPrice();
            }));

        // test export
        $exporter = $this->app->make(\App\SW\Export\ArticleExporter::class, [
            'shopwareAPI' => $swAPIMock,
            'priceCalculator' => $priceCalculatorMock,
        ]);
        $exporter->export($edcProduct);

        // assert that the invalid sw article got deleted
        static::assertFalse(SWArticle::query()->where('id', $swArticle->id)->exists());

        // assert that a new sw article was created
        $swArticle = SWArticle::query()->where('edc_product_id', $edcProduct->id)->first();
        static::assertNotNull($swArticle);
        static::assertEquals(23, $swArticle->sw_id);

        foreach ($edcProduct->variants as $edcPV) {
            /** @var SWVariant $swVariant */
            $swVariant = SWVariant::query()
                ->where([
                    'article_id' => $swArticle->id,
                    'edc_product_variant_id' => $edcPV->id,
                ])
                ->first();

            static::assertNotNull($swVariant);
            static::assertContains($swVariant->sw_id, [24, 25]);
        }
    }

    public function testExportUpdate()
    {
        // setup edc product
        $imageLoader = $this->createMock(ProductImageLoader::class);

        /** @var ProductFeedPartParser $productFeedPartParser */
        $productFeedPartParser = $this->app->make(ProductFeedPartParser::class, ['imageLoader' => $imageLoader]);
        $productFeedPartParser->parse($this->createProductFeedPartFromFile(fixture_path('product-1.xml')));

        /** @var EDCProduct $edcProduct */
        $edcProduct = EDCProduct::query()->latest()->first();
        $edcProductImage = factory(EDCProductImage::class)->create(['product_id' => $edcProduct->id]);

        // valid sw article
        $swArticle = new SWArticle(['sw_id' => 35]);
        $swArticle->edcProduct()->associate($edcProduct);
        $swArticle->save();

        $swAPIMock = $this->getMockBuilder(ShopwareAPI::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'fetchShopwareArticleInfoByArticleID',
                'updateShopwareArticle',
            ])
            ->getMock();

        $swAPIMock->expects(static::once())
            ->method('fetchShopwareArticleInfoByArticleID')
            ->with(35)
            ->willReturn(new ShopwareArticleInfo([]));

        $swAPIMock->expects(static::once())
            ->method('updateShopwareArticle')
            ->with(35, [
                'active' => true,
                'name' => 'Sex-Strumpfhose haut',
                'tax' => 19.0,
                'supplier' => 'Cottelli Collection',
                'descriptionLong' => 'Haut/Weiß. 100% Polyamid.',
                'configuratorSet' => [
                    'type' => 0,
                    'groups' => [
                        ['name' => 'Size', 'options' => [
                            ['name' => 'S/M'],
                            ['name' => 'L/XL'],
                        ]]
                    ],
                ],
                'variants' => [
                    [
                        'active' => true,
                        'number' => '02308550000',
                        'ean' => '4024144230853',
                        'prices' => [[
                            'price' => 9.95,
                        ]],
                        'configuratorOptions' => [
                            ['group' => 'Size', 'option' => 'S/M'],
                        ],
                    ],
                    [
                        'active' => true,
                        'number' => '230863',
                        'ean' => '4024144230860',
                        'prices' => [[
                            'price' => 9.95,
                        ]],
                        'configuratorOptions' => [
                            ['group' => 'Size', 'option' => 'L/XL'],
                        ],
                    ]
                ],
                'images' => [
                    ['link' => route('product-images', [$edcProductImage->identifier])],
                ],
            ])
            ->willReturn(new ShopwareArticleInfo([]));

        $priceCalculatorMock = $this->getMockBuilder(PriceCalculator::class)
            ->setMethods(['calcPrice'])
            ->disableOriginalConstructor()
            ->getMock();

        $priceCalculatorMock->expects(static::atLeastOnce())
            ->method('calcPrice')
            ->with($edcProduct, static::isInstanceOf(ProductXML::class))
            ->will(static::returnCallback(function (EDCProduct $ep, ProductXML $productXML) {
                return $productXML->getB2CPrice();
            }));

        $exporter = $this->app->make(\App\SW\Export\ArticleExporter::class, [
            'shopwareAPI' => $swAPIMock,
            'priceCalculator' => $priceCalculatorMock,
        ]);
        $exporter->export($edcProduct);
    }

    public function testExportWithSingleVariant()
    {
        // setup edc product
        $imageLoader = $this->createMock(ProductImageLoader::class);

        /** @var ProductFeedPartParser $productFeedPartParser */
        $productFeedPartParser = $this->app->make(ProductFeedPartParser::class, ['imageLoader' => $imageLoader]);
        $productFeedPartParser->parse($this->createProductFeedPartFromFile(fixture_path('product-2.xml')));

        /** @var EDCProduct $edcProduct */
        $edcProduct = EDCProduct::query()->latest()->first();
        $edcProductImage = factory(EDCProductImage::class)->create(['product_id' => $edcProduct->id]);

        $swAPIMock = $this->getMockBuilder(ShopwareAPI::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'createShopwareArticle',
            ])
            ->getMock();

        $swAPIMock->expects(static::once())
            ->method('createShopwareArticle')
            ->with([
                'active' => true,
                'name' => 'Raging Cockstars Großer Penis Ben',
                'tax' => 19.0,
                'supplier' => 'Raging Cock Stars',
                'descriptionLong' => 'Dieser dicke, lange Dildo ist mit besonders realistischen Details versehen und an einem sehr starken Saugnapf befestigt. Dadurch können Sie den Dildo auf jeder gewünschten Oberfläche befestigen und so Vergnügen ganz ohne Hände genießen! Die pinkfarbene Spitze des Dildos ist von Hand gefärbt. Der Dildo ist am Schaft mit einer stimulierenden Textur versehen und hat lebensechte Hoden.',
                'mainDetail' => [
                    'active' => true,
                    'number' => 'ae214',
                    'ean' => '848518017215',
                    'prices' => [[
                        'price' => 62.95,
                    ]],
                ],
                'images' => [
                    ['link' => route('product-images', [$edcProductImage->identifier])],
                ],
            ])
            ->willReturn(new ShopwareArticleInfo([
                'data' => [
                    'id' => 23,
                    'details' => [
                        ['number' => 'ae214', 'id' => 24],
                    ],
                ],
            ]));

        $priceCalculatorMock = $this->getMockBuilder(PriceCalculator::class)
            ->setMethods(['calcPrice'])
            ->disableOriginalConstructor()
            ->getMock();

        $priceCalculatorMock->expects(static::atLeastOnce())
            ->method('calcPrice')
            ->with($edcProduct, static::isInstanceOf(ProductXML::class))
            ->will(static::returnCallback(function (EDCProduct $ep, ProductXML $productXML) {
                return $productXML->getB2CPrice();
            }));

        // test export
        $exporter = $this->app->make(\App\SW\Export\ArticleExporter::class, [
            'shopwareAPI' => $swAPIMock,
            'priceCalculator' => $priceCalculatorMock,
        ]);
        $exporter->export($edcProduct);

        $swArticle = SWArticle::query()->where('edc_product_id', $edcProduct->id)->first();
        static::assertNotNull($swArticle);
        static::assertEquals(23, $swArticle->sw_id);

        foreach ($edcProduct->variants as $edcPV) {
            /** @var SWVariant $swVariant */
            $swVariant = SWVariant::query()
                ->where([
                    'article_id' => $swArticle->id,
                    'edc_product_variant_id' => $edcPV->id,
                ])
                ->first();

            static::assertNotNull($swVariant);
            static::assertEquals($swVariant->sw_id, 24);
        }
    }

    protected function createProductFeedPartFromFile(string $filePath): EDCFeedPartProduct
    {
        $rf = $this->storageDirector->createFileFromPath('product-feed-part.xml', $filePath);

        $fullFeed = factory(EDCFeed::class)->create();

        $feed = new EDCFeedPartProduct();
        $feed->fullFeed()->associate($fullFeed);
        $feed->file()->associate($rf);
        $feed->save();

        return $feed;
    }
}
