<?php
/**
 * lel since 2019-07-20
 */

namespace App\EDC\Import;

use App\EDCProduct;
use App\ResourceFile\StorageDirector;

class ProductCategoryExtractor
{
    /** @var StorageDirector */
    protected $storageDirector;

    public function __construct(StorageDirector $storageDirector)
    {
        $this->storageDirector = $storageDirector;
    }

    public function extract(): array
    {
        $categories = [];

        /** @var EDCProduct $edcProduct */
        foreach (EDCProduct::query()->cursor() as $edcProduct) {
            $productCategories = $this->extractCategoriesFromProduct($edcProduct);

            foreach ($productCategories as $productCategory) {
                [$mainCategory, $subCategory] = $productCategory;

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
