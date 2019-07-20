<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW\Export\Listeners;

use App\EDC\Import\Events\ProductTouched;
use App\SW\Export\Jobs\ExportArticle;
use Illuminate\Bus\Dispatcher as JobDispatcher;
use Psr\Log\LoggerInterface;

class ExportTouchedArticle
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

    public function handle(ProductTouched $e): void
    {
        $product = $e->getProduct();

        $this->logger->info('ExportTouchedArticle: dispatch export article', [
            'product' => $product->asLoggingContext(),
        ]);

        $this->jobDispatcher->dispatch(new ExportArticle($product));
    }
}
