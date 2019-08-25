<?php
/**
 * lel since 2019-07-07
 */

namespace App\EDC\Import\Parser;

use App\EDC\Import\Events\ProductTouched;
use App\EDC\Import\StockProductXML;
use App\EDC\Import\StockXML;
use App\EDC\Import\StockXMLFactory;
use App\EDCFeedPartStock;
use App\EDCProduct;
use App\EDCProductVariant;
use App\ResourceFile\StorageDirector;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Psr\Log\LoggerInterface;

class ProductStockFeedPartParser extends FeedParser
{
    protected $touchedProducts = [];

    /** @var StockXMLFactory */
    protected $stockXMLFactory;

    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $dbConnection,
        EventDispatcher $eventDispatcher,
        StorageDirector $storageDirector,
        StockXMLFactory $stockXMLFactory
    ) {
        parent::__construct($logger, $dbConnection, $eventDispatcher, $storageDirector);

        $this->stockXMLFactory = $stockXMLFactory;
    }

    public function parse(EDCFeedPartStock $feed): void
    {
        $this->touchedProducts = [];
        $stockXML = $this->createStockXML($feed);

        foreach ($stockXML->getProducts() as $stockProduct) {
            $this->dbConnection->transaction(function () use ($feed, $stockProduct) {
                $this->updateVariantWithStockProduct($feed, $stockProduct);
            });
        }

        $this->dispatchProductTouchedEvents();
    }

    protected function createStockXML(EDCFeedPartStock $feed): StockXML
    {
        return $this->stockXMLFactory->create($feed);
    }

    protected function updateVariantWithStockProduct(
        EDCFeedPartStock $feed,
        StockProductXML $stockProduct
    ): void
    {
        /** @var EDCProduct $product */
        $product = EDCProduct::withEDCID($stockProduct->getProductEDCID())->first();
        if (!$product) {
            $this->logger->info('ProductStockFeedPartParser: couldnt find product to assign stock feed part', [
                'feed' => $feed->asLoggingContext(),
                'productEDCID' => $stockProduct->getProductEDCID(),
            ]);

            return;
        }

        /** @var EDCProductVariant $variant */
        $variant = $product->variants()->withEDCID($stockProduct->getVariantEDCID())->first();
        if (!$variant) {
            $this->logger->info('ProductStockFeedPartParser: couldnt find variant to assign stock feed part', [
                'feed' => $feed->asLoggingContext(),
                'product' => $product->asLoggingContext(),
                'variantEDCID' => $stockProduct->getVariantEDCID(),
            ]);

            return;
        }

        $feedIsAlreadyKnown = $this->isFeedAlreadyKnown($feed, $variant);

        $newData = $variant->currentData->replicate();
        $newData->feedPartStock()->associate($feed);
        $variant->saveData($newData);

        if (!$feedIsAlreadyKnown)
            $this->touchedProducts[] = $product;
    }

    protected function dispatchProductTouchedEvents(): void
    {
        $products = collect($this->touchedProducts)->unique('id');
        $this->touchedProducts = [];

        foreach ($products as $product) {
            $this->eventDispatcher->dispatch(new ProductTouched($product));
        }
    }

    protected function isFeedAlreadyKnown(EDCFeedPartStock $feed, EDCProductVariant $variant): bool
    {
        $feedPartStock = $variant->currentData->feedPartStock;

        return $feedPartStock && $feedPartStock->content_checksum === $feed->content_checksum;
    }
}
