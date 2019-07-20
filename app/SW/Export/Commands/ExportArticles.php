<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW\Export\Commands;

use App\EDCProduct;
use App\SW\Export\Jobs\ExportArticle;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher as JobDispatcher;

class ExportArticles extends Command
{
    protected $signature = 'sw:export-articles {--edc-id : Are the given ids edc ids?} {products?*}';

    public function handle(JobDispatcher $jobDispatcher): void
    {
        $productIDs = $this->argument('products');

        /** @var EDCProduct $product */
        foreach ($this->fetchProducts($productIDs, $this->option('edc-id')) as $product) {
            $jobDispatcher->dispatch(new ExportArticle($product));
        }
    }

    protected function fetchProducts(array $ids, bool $areEDCIDs): iterable
    {
        $query = EDCProduct::query();

        if (!empty($ids)) {
            $idAttr = $areEDCIDs
                ? 'edc_id'
                : 'id';

            $query->whereIn($idAttr, $ids);
        }

        return $query->cursor();
    }
}
