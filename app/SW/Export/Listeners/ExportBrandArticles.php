<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW\Export\Listeners;

use App\Brand;
use App\EDC\Import\Events\BrandDiscountTouched;
use App\EDCProduct;
use App\SW\Export\Jobs\ExportArticle;
use Illuminate\Bus\Dispatcher as JobDispatcher;
use Psr\Log\LoggerInterface;

class ExportBrandArticles
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var JobDispatcher */
    protected $jobDispatcher;

    public function __construct(LoggerInterface $logger, JobDispatcher $jobDispatcher)
    {
        $this->logger = $logger;
        $this->jobDispatcher = $jobDispatcher;
    }

    public function handle(BrandDiscountTouched $e): void
    {
        $brand = $e->brand;
        $this->logger->info('ExportBrandArticles', [
            'brand' => $brand->asLoggingContext(),
        ]);

        /** @var EDCProduct $product */
        foreach ($this->fetchAffectedProducts($brand) as $product) {
            $this->logger->info('ExportBrandArticles: dispatch export article', [
                'brand' => $brand->asLoggingContext(),
                'product' => $product->asLoggingContext(),
            ]);

            $this->jobDispatcher->dispatch(new ExportArticle($product));
        }
    }

    protected function fetchAffectedProducts(Brand $brand): iterable
    {
        return EDCProduct::withBrand($brand)->cursor();
    }
}
