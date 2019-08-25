<?php
/**
 * lel since 2019-07-14
 */

namespace App\SW\Export;

use App\Domain\ShopwareArticleInfo;
use App\EDC\Import\ProductXML;
use App\EDC\Import\StockXMLFactory;
use App\EDC\Import\VariantXML;
use App\EDCProduct;
use App\EDCProductImage;
use App\EDCProductVariant;
use App\ResourceFile\StorageDirector;
use App\SW\ShopwareAPI;
use App\SWArticle;
use Assert\Assert;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class ArticleExporter
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var ShopwareAPI */
    protected $shopwareAPI;

    /** @var StorageDirector */
    protected $storageDirector;

    /** @var PriceCalculator */
    protected $priceCalculator;

    /** @var CategoryMapper */
    protected $categoryMapper;

    /** @var StockXMLFactory */
    protected $stockXMLFactory;

    public function __construct(
        LoggerInterface $logger,
        ShopwareAPI $shopwareAPI,
        StorageDirector $storageDirector,
        PriceCalculator $priceCalculator,
        CategoryMapper $categoryMapper,
        StockXMLFactory $stockXMLFactory
    )
    {
        $this->logger = $logger;
        $this->shopwareAPI = $shopwareAPI;
        $this->storageDirector = $storageDirector;
        $this->priceCalculator = $priceCalculator;
        $this->categoryMapper = $categoryMapper;
        $this->stockXMLFactory = $stockXMLFactory;
    }

    public function export(EDCProduct $edcProduct): void
    {
        $swArticle = $this->fetchLocalSWArticleAndValidate($edcProduct);

        // create or update
        $swArticle
            ? $this->update($edcProduct, $swArticle)
            : $this->create($edcProduct);
    }

    protected function fetchLocalSWArticleAndValidate(EDCProduct $edcProduct): ?SWArticle
    {
        $swArticle = $edcProduct->swArticle;
        if (!$swArticle) return null;

        $this->logger->info('ArticleExporter: found local sw article; check validity', [
            'edcProduct' => $edcProduct->asLoggingContext(),
            'swArticle' => $swArticle->asLoggingContext(),
        ]);

        if ($this->shopwareAPI->fetchShopwareArticleInfoByArticleID($swArticle->sw_id))
            return $swArticle;

        $this->logger->info('ArticleExporter: local sw article is invalid; deleting', [
            'edcProduct' => $edcProduct->asLoggingContext(),
            'swArticle' => $swArticle->asLoggingContext(),
        ]);

        $swArticle->delete();
        return null;
    }

    protected function update(EDCProduct $edcProduct, SWArticle $swArticle): void
    {
        $sourceFeed = $edcProduct->currentData->feedPartProduct;
        $this->logger->info('ArticleExporter: start update', [
            'edcProduct' => $edcProduct->asLoggingContext(),
            'swArticle' => $swArticle->asLoggingContext(),
            'feedPartProduct' => $sourceFeed->asLoggingContext(),
        ]);

        $productXML = ProductXML::fromFilePath($this->storageDirector->getLocalPath($sourceFeed->file));
        $articleData = $this->generateArticleData($edcProduct, $productXML);

        $this->shopwareAPI->updateShopwareArticle($swArticle->sw_id, $articleData);

        $this->logger->info('ArticleExporter: finished update', [
            'edcProduct' => $edcProduct->asLoggingContext(),
            'swArticle' => $swArticle->asLoggingContext(),
            'feedPartProduct' => $sourceFeed->asLoggingContext(),
        ]);
    }

    protected function create(EDCProduct $edcProduct): void
    {
        $sourceFeed = $edcProduct->currentData->feedPartProduct;
        $this->logger->info('ArticleExporter: start creation', [
            'edcProduct' => $edcProduct->asLoggingContext(),
            'feedPartProduct' => $sourceFeed->asLoggingContext(),
        ]);

        $productXML = ProductXML::fromFilePath($this->storageDirector->getLocalPath($sourceFeed->file));
        $articleData = $this->generateArticleData($edcProduct, $productXML);

        $swArticleInfo = $this->shopwareAPI->createShopwareArticle($articleData);

        $this->persistLocalShopwareModels($edcProduct, $swArticleInfo);

        $this->logger->info('ArticleExporter: finished creation', [
            'edcProduct' => $edcProduct->asLoggingContext(),
            'feedPartProduct' => $sourceFeed->asLoggingContext(),
        ]);
    }

    protected function generateVariantDataList(EDCProduct $edcProduct, ProductXML $productXML): array
    {
        return collect($productXML->getVariants())
            ->map(function (VariantXML $variantXML) use ($edcProduct, $productXML) {
                return $this->generateVariantData($edcProduct, $productXML, $variantXML);
            })
            ->values()
            ->all();
    }

    protected function generateVariantData(
        EDCProduct $edcProduct,
        ProductXML $productXML,
        VariantXML $variantXML
    ): array
    {
        /** @var EDCProductVariant $epv */
        $epv = $edcProduct->variants()->where('edc_id', $variantXML->getEDCID())->firstOrFail();

        $isActive = $this->determineIsActiveFromStock($epv, $variantXML);

        $variantData = [
            'active' => $isActive,
            'number' => $variantXML->getSubArtNr(),
            'ean' => $variantXML->getEAN(),
            'prices' => [[
                'price' => $this->calculatePrice($edcProduct, $productXML),
            ]],
        ];

        if ($size = $variantXML->getSize()) {
            $variantData['configuratorOptions'] = [
                ['group' => 'Size', 'option' => $size],
            ];
        }

        return $variantData;
    }

    protected function determineIsActiveFromStock(EDCProductVariant $epv, VariantXML $variantXML): bool
    {
        if ($feedPartStock = $epv->currentData->feedPartStock) {
            $stockXML = $this->stockXMLFactory->create($feedPartStock);

            $stockProductXML = $stockXML->getStockProductWithVariantEDCID($epv->edc_id);
            return $stockProductXML->isInStock();
        }

        return $variantXML->isInStock();
    }

    protected function generateConfiguratorSetFromVariants(array $variants): array
    {
        return collect($variants)
            ->pluck('configuratorOptions')
            ->filter()
            ->reduce(function (Collection $acc, $configuratorOptions) {
                foreach ($configuratorOptions as $configuratorOption) {
                    $group = $configuratorOption['group'];
                    $option = $configuratorOption['option'];

                    if (!$acc->has($group)) $acc->put($group, collect());
                    $acc->get($group)->push($option);
                }

                return $acc;
            }, collect())
            ->map(function ($groupOptions, $groupName) {
                return [
                    'name' => $groupName,
                    'options' => $groupOptions->unique()
                        ->map(function ($option) {
                            return ['name' => $option];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    protected function calculatePrice(EDCProduct $edcProduct, ProductXML $productXML): float
    {
        return $this->priceCalculator->calcPrice($edcProduct, $productXML);
    }

    protected function generateImageURIs(EDCProduct $edcProduct): array
    {
        return $edcProduct->images
            ->map(function (EDCProductImage $epi) {
                return route('product-images', $epi->identifier);
            })
            ->values()
            ->all();
    }

    protected function persistLocalShopwareModels(
        EDCProduct $edcProduct,
        ShopwareArticleInfo $swArticleInfo
    ): void
    {
        $swArticle = new SWArticle([
            'edc_product_id' => $edcProduct->id,
            'sw_id' => $swArticleInfo->getArticleID(),
        ]);

        $swArticle->save();

        foreach ($edcProduct->variants as $variant) {
            $swDetailInfo = $swArticleInfo->getDetailByNumber($variant->currentData->subartnr);
            if (!$swDetailInfo) continue;

            $swArticle->variants()->create([
                'edc_product_variant_id' => $variant->id,
                'sw_id' => $swDetailInfo['id'],
            ]);
        }
    }

    protected function generateArticleData(EDCProduct $edcProduct, ProductXML $productXML): array
    {
        $variants = $this->generateVariantDataList($edcProduct, $productXML);
        Assert::that($variants)->minCount(1);

        // general data
        $articleData = [
            'active' => true,
            'name' => $productXML->getTitle(),
            'tax' => $productXML->getVATDE(),
            'supplier' => $edcProduct->brand->brand_name,
            'descriptionLong' => $productXML->getDescription(),
        ];

        // categories
        $subCategoryIDs = $this->determineShopwareSubCategoryIDs($productXML);
        if (!empty($subCategoryIDs)) {
            $articleData['categories'] = array_map(function (string $id): array {
                return ['id' => $id];
            }, $subCategoryIDs);
        }

        // configurator sets
        $configuratorSetGroups = $this->generateConfiguratorSetFromVariants($variants);
        if (!empty($configuratorSetGroups)) {
            $articleData['configuratorSet'] = [
                'type' => 0,
                'groups' => $configuratorSetGroups,
            ];
        }

        // variant or main detail data
        if (count($variants) > 1) {
            $articleData['mainDetail'] = array_shift($variants);
            $articleData['variants'] = $variants;
        } else {
            $articleData['mainDetail'] = $variants[0];
        }

        // image uris
        $imageURIs = $this->generateImageURIs($edcProduct);
        if (!empty($imageURIs)) {
            $articleData['images'] = array_map(function (string $imageURI) {
                return ['link' => $imageURI];
            }, $imageURIs);
        }

        return $articleData;
    }

    protected function determineShopwareSubCategoryIDs(ProductXML $productXML): array
    {
        $categories = $productXML->getCategories();
        if (empty($categories)) return [];

        return collect($categories)
            ->map(function ($categories) {
                return Arr::last($categories);
            })
            ->filter()
            ->pluck('id')
            ->map(function (string $subCategoryID) {
                return $this->categoryMapper->map($subCategoryID);
            })
            ->filter()
            ->all();
    }
}
