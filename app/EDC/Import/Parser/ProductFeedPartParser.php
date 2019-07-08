<?php
/**
 * lel since 2019-07-06
 */

namespace App\EDC\Import\Parser;

use App\Brand;
use App\EDC\Import\Events\ProductTouched;
use App\EDC\Import\ProductImageLoader;
use App\EDC\Import\ProductXML;
use App\EDC\Import\VariantXML;
use App\EDCFeedPartProduct;
use App\EDCProduct;
use App\EDCProductData;
use App\EDCProductVariant;
use App\EDCProductVariantData;
use App\ResourceFile\StorageDirector;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;

class ProductFeedPartParser extends FeedParser
{
    /** @var ProductImageLoader */
    protected $imageLoader;

    /** @var array */
    protected $eventQueue = [];

    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $dbConnection,
        EventDispatcher $eventDispatcher,
        StorageDirector $storageDirector,
        ProductImageLoader $imageLoader
    )
    {
        parent::__construct($logger, $dbConnection, $eventDispatcher, $storageDirector);

        $this->imageLoader = $imageLoader;
    }

    public function parse(EDCFeedPartProduct $feed): void
    {
        $productXML = $this->createProductXML($feed);

        /** @var EDCProduct $product */
        $product = $this->dbConnection->transaction(function () use ($feed, $productXML): EDCProduct {
            $product = $this->tryToFetchExistingProduct($productXML);

            if ($product) {
                $this->updateProduct($feed, $productXML, $product);
            } else {
                $product = $this->createProduct($feed, $productXML);
            }

            return $product;
        });

        $this->loadImages($product);

        $this->dispatchQueuedEvents();
    }

    protected function createProductXML(EDCFeedPartProduct $feed): ProductXML
    {
        $filePath = $this->storageDirector->getLocalPath($feed->file);

        return ProductXML::fromFilePath($filePath);
    }

    protected function tryToFetchExistingProduct(ProductXML $productXML): ?EDCProduct
    {
        $edcID = $productXML->getEDCID();

        return EDCProduct::query()->where('edc_id', $edcID)->first();
    }

    protected function createProduct(EDCFeedPartProduct $feed, ProductXML $productXML): EDCProduct
    {
        $product = new EDCProduct();
        $product->edc_id = $productXML->getEDCID();
        $product->brand()->associate($this->fetchOrCreateBrand($productXML));
        $product->save();

        $this->createProductData($feed, $productXML, $product);
        $this->createOrUpdateVariants($feed, $productXML, $product);

        $this->logger->info('ProductFeedParser: created product', [
            'product' => $product->asLoggingContext(),
        ]);

        $this->eventQueue[] = new ProductTouched($product);

        return $product;
    }

    protected function updateProduct(EDCFeedPartProduct $feed, ProductXML $productXML, EDCProduct $product): void
    {
        $feedIsAlreadyKnown = $this->isFeedAlreadyKnown($feed, $product);

        $this->createProductData($feed, $productXML, $product);
        $this->createOrUpdateVariants($feed, $productXML, $product);

        $this->logger->info('ProductFeedPartParser: updated product', [
            'feed' => $feed->asLoggingContext(),
            'product' => $product->asLoggingContext(),
            'feedWasAlreadyKnown' => $feedIsAlreadyKnown,
        ]);

        if (!$feedIsAlreadyKnown) $this->eventQueue[] = new ProductTouched($product);;
    }

    protected function createProductData(EDCFeedPartProduct $feed, ProductXML $productXML, EDCProduct $product): void
    {
        $productData = new EDCProductData();
        $productData->product()->associate($product);
        $productData->feedPartProduct()->associate($feed);
        $productData->artnr = $productXML->getArtNr();

        $product->saveData($productData);
    }

    protected function createOrUpdateVariants(
        EDCFeedPartProduct $feed,
        ProductXML $productXML,
        EDCProduct $product
    ): void
    {
        foreach ($productXML->getVariants() as $variant) {
            $this->createOrUpdateVariant($feed, $variant, $product);
        }
    }

    protected function createOrUpdateVariant(
        EDCFeedPartProduct $feed,
        VariantXML $variantXML,
        EDCProduct $product
    ): void
    {
        $variant = $this->tryToFetchExistingVariant($product, $variantXML);

        $variant
            ? $this->updateVariant($feed, $variantXML, $variant)
            : $this->createVariant($feed, $variantXML, $product);
    }

    protected function tryToFetchExistingVariant(EDCProduct $product, VariantXML $variantXML): ?EDCProductVariant
    {
        return $product->variants()->where('edc_id', $variantXML->getEDCID())->first();
    }

    protected function createVariant(EDCFeedPartProduct $feed, VariantXML $variantXML, EDCProduct $product): void
    {
        $variant = new EDCProductVariant();
        $variant->product()->associate($product);
        $variant->edc_id = $variantXML->getEDCID();
        $variant->save();

        $this->createVariantData($feed, $variantXML, $variant);

        $this->logger->info('ProductFeedPartParser: created variant', [
            'feed' => $feed->asLoggingContext(),
            'product' => $product->asLoggingContext(),
            'variant' => $variant->asLoggingContext(),
        ]);
    }

    protected function updateVariant(EDCFeedPartProduct $feed, VariantXML $variantXML, EDCProductVariant $variant): void
    {
        $this->createVariantData($feed, $variantXML, $variant);

        $this->logger->info('ProductFeedPartParser: updated variant', [
            'feed' => $feed->asLoggingContext(),
            'product' => $variant->product->asLoggingContext(),
            'variant' => $variant->asLoggingContext(),
        ]);
    }

    protected function createVariantData(
        EDCFeedPartProduct $feed,
        VariantXML $variantXML,
        EDCProductVariant $variant
    ): void
    {
        $variantData = new EDCProductVariantData();
        $variantData->productVariant()->associate($variant);
        $variantData->feedPartProduct()->associate($feed);
        $variantData->subartnr = $variantXML->getSubArtNr();

        $variant->saveData($variantData);
    }

    protected function fetchOrCreateBrand(ProductXML $productXML): Brand
    {
        if (!($brand = Brand::withBrandID($productXML->getBrandID())->first())) {
            $brand = new Brand();
            $brand->edc_brand_id = $productXML->getBrandID();
            $brand->brand_name = $productXML->getBrandName();
            $brand->save();
        }

        return $brand;
    }

    protected function isFeedAlreadyKnown(EDCFeedPartProduct $feed, EDCProduct $product): bool
    {
        return $product->currentData->feedPartProduct->file->checksum === $feed->file->checksum;
    }

    protected function dispatchQueuedEvents(): void
    {
        while ($e = array_shift($this->eventQueue)) {
            $this->eventDispatcher->dispatch($e);
        }
    }

    protected function loadImages(EDCProduct $product): void
    {
        $feedPart = $product->currentData->feedPartProduct;
        $productXML = ProductXML::fromFilePath($this->storageDirector->getLocalPath($feedPart->file));

        $this->imageLoader->loadImages($productXML->getPicNames());
    }
}
