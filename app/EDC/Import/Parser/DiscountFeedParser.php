<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Parser;

use App\Brand;
use App\BrandDiscount;
use App\EDC\Import\Events\BrandDiscountTouched;
use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDCFeed;
use App\Utils\ConstantEnumerator;
use League\Csv\Exception as CSVException;
use League\Csv\Reader;

class DiscountFeedParser extends FeedParser
{
    use ConstantEnumerator;

    protected const COL_BRAND_ID = 'brandid';
    protected const COL_BRAND_NAME = 'brandname';
    protected const COL_DISCOUNT = 'discount';

    /** @var array */
    protected $eventQueue = [];

    public function parse(EDCFeed $feed): void
    {
        $this->ensureMatchingFeedType(EDCFeed::TYPE_DISCOUNTS, $feed->type);

        try {
            $this->dbConnection->transaction(function () use ($feed) {
                $reader = $this->openReaderForFeed($feed);
                $touchedBrandIDs = $this->updateBrandsFromFeed($feed, $reader);

                if (empty($touchedBrandIDs)) {
                    $this->logger->info(
                        'DiscountFeedParser: skip updating untouched brands, there were no touched ones'
                    );
                    return;
                }

                $this->removeDiscountFromUntouchedBrands($feed, $touchedBrandIDs);
            });

            $this->dispatchQueuedEvents();
        } catch (CSVException $e) {
            report($e);
        }
    }

    protected function openReaderForFeed(EDCFeed $feed): Reader
    {
        $reader = Reader::createFromPath($this->storageDirector->getLocalPath($feed->file));
        $reader->setHeaderOffset(0);
        $reader->setDelimiter(';');

        return $reader;
    }

    protected function updateBrandDiscount(EDCFeed $feed, Brand $brand, array $row)
    {
        $discountPercentage = (int)$row[self::COL_DISCOUNT];

        if (($currentDiscount = $brand->currentDiscount) && $currentDiscount->value === $discountPercentage) {
            $this->logger->info('DiscountFeedParser: skip updating brand discount; same value', [
                'brand' => $brand->asLoggingContext(),
                'feed' => $feed->asLoggingContext(),
                'currentDiscount' => $currentDiscount->asLoggingContext(),
                'row' => $row,
            ]);

            return;
        }

        $discount = new BrandDiscount();
        $discount->edcFeed()->associate($feed);
        $discount->value = $discountPercentage;

        $brand->saveDiscount($discount);

        $this->eventQueue[] = new BrandDiscountTouched($brand);
    }

    protected function isRowComplete(array $row): bool
    {
        $expectedCols = static::getConstantsWithPrefix('COL_');
        $cols = array_keys($row);

        return count($expectedCols) === count(array_intersect($expectedCols, $cols));
    }

    protected function updateBrandsFromFeed(EDCFeed $feed, Reader $reader): array
    {
        $touchedBrandIDs = [];

        foreach ($reader->getRecords() as $row) {
            if (!$this->isRowComplete($row)) continue;

            /** @var Brand $brand */
            $brand = Brand::query()->firstOrCreate(
                ['edc_brand_id' => $row[self::COL_BRAND_ID]],
                ['brand_name' => $row[self::COL_BRAND_NAME]]
            );

            $this->updateBrandDiscount($feed, $brand, $row);

            $touchedBrandIDs[] = $brand->id;
        }

        return $touchedBrandIDs;
    }

    protected function removeDiscountFromUntouchedBrands(EDCFeed $feed, array $touchedBrandIDs): void
    {
        $untouchedBrands = Brand::query()->whereNotIn('id', $touchedBrandIDs)->cursor();

        /** @var Brand $brand */
        foreach ($untouchedBrands as $brand) {
            $this->logger->info('DiscountFeedParser: brand was not found in latest discount feed, set discount to 0', [
                'brand' => $brand->asLoggingContext(),
                'feed' => $feed->asLoggingContext(),
            ]);

            $discount = new BrandDiscount();
            $discount->edcFeed()->associate($feed);
            $discount->value = 0;

            $brand->saveDiscount($discount);
        }
    }

    protected function dispatchQueuedEvents(): void
    {
        while ($e = array_shift($this->eventQueue)) {
            $this->eventDispatcher->dispatch($e);
        }
    }
}
