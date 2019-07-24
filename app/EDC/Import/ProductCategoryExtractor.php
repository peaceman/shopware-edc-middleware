<?php
/**
 * lel since 2019-07-20
 */

namespace App\EDC\Import;

use App\EDCFeed;
use App\EDCProduct;
use App\ResourceFile\StorageDirector;
use Illuminate\Database\Eloquent\Builder;
use Psr\Log\LoggerInterface;

class ProductCategoryExtractor
{
    /** @var StorageDirector */
    protected $storageDirector;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(StorageDirector $storageDirector, LoggerInterface $logger)
    {
        $this->storageDirector = $storageDirector;
        $this->logger = $logger;
    }

    public function extract(): array
    {
        $categories = [];

        $latestProductFeed = EDCFeed::withType(EDCFeed::TYPE_PRODUCTS)->latest()->first();

//        $query = EDCProduct::query();
        $query = EDCProduct::query()
            ->whereHas('currentData.feedPartProduct', function (Builder $q) use ($latestProductFeed) {
                $q->where('full_feed_id', $latestProductFeed->id);
            });

        /** @var EDCProduct $edcProduct */
        foreach ($query->cursor() as $edcProduct) {
            $productCategories = $this->extractCategoriesFromProduct($edcProduct);

            foreach ($productCategories as $productCategory) {
                [$mainCategory, $subCategory] = $productCategory;

                $this->logger->info('CategoryExtractor', [
                    'edcProduct' => $edcProduct->asLoggingContext(),
                    'main' => $mainCategory,
                    'sub' => $subCategory,
                ]);

                $mainCategory['childs'][$subCategory['id']] = $subCategory;

                $categories[] = [$mainCategory['id'] => $mainCategory];
            }
        }

        if (empty($categories)) return [];

        $categories = array_replace_recursive(...$categories);

        return array_values(array_map(
            function (array $mainCategory) {
                return array_merge($mainCategory, [
                    'childs' => array_values($mainCategory['childs']),
                ]);
            },
            $categories
        ));
    }

    protected function extractCategoriesFromProduct(EDCProduct $product): array
    {
        $productXML = ProductXML::fromFilePath(
            $this->storageDirector->getLocalPath($product->currentData->feedPartProduct->file)
        );

        return $productXML->getCategories();
    }
}
