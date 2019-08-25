<?php
/**
 * lel since 2019-07-02
 */

namespace App\EDC;

use App\EDC\Export\Commands\ExportOrders;
use App\EDC\Export\OrderXMLGenerator;
use App\EDC\Import\Events\FeedFetched;
use App\EDC\Import\HouseKeeping\Providers\OldProductVariantData;
use App\EDC\Import\Jobs\DeleteOldProductVariantData;
use App\EDC\Import\Jobs\FetchFeed;
use App\EDC\Import\Jobs\ParseDiscountFeed;
use App\EDC\Import\Jobs\ParseProductFeed;
use App\EDC\Import\Jobs\ParseProductStockFeed;
use App\EDC\Import\Listeners\DispatchParseFeed;
use App\EDC\Import\ProductCategoryExtractor;
use App\EDC\Import\ProductImageLoader;
use App\EDCFeed;
use App\Utils\RegistersEventListeners;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher as JobDispatcher;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\ServiceProvider;

class EDCServiceProvider extends ServiceProvider
{
    use RegistersEventListeners;

    protected $listen = [
        FeedFetched::class => [
            DispatchParseFeed::class,
        ],
    ];

    protected $parseFeedJobMapping = [
        EDCFeed::TYPE_PRODUCTS => ParseProductFeed::class,
        EDCFeed::TYPE_PRODUCT_STOCKS => ParseProductStockFeed::class,
        EDCFeed::TYPE_DISCOUNTS => ParseDiscountFeed::class,
    ];

    public function boot()
    {
        $this->schedule();

        $this->registerEventListeners();
    }

    public function register()
    {
        $this->registerParseFeedJobs();
        $this->registerProductImageLoader();
        $this->registerOrderXMLGenerator();
        $this->registerEDCAPI();

        $this->registerOldProductVariantData();

        $this->registerCommands();
    }

    protected function registerCommands()
    {
        /** @var Kernel $consoleKernel */
        $consoleKernel = $this->app[Kernel::class];

        $consoleKernel
            ->command(
                'edc:fetch-feed {type : discounts, products, product-stocks}',
                function (JobDispatcher $jobDispatcher, string $type) {
                    $jobDispatcher->dispatch(new FetchFeed($type));
                }
            )
            ->describe('Dispatch job to fetch the specified edc feed');

        $consoleKernel
            ->command(
                'edc:extract-categories',
                function (ProductCategoryExtractor $extractor) {
                    $categories = $extractor->extract();

                    fwrite(STDOUT, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            );

        $this->commands([
            ExportOrders::class,
        ]);
    }

    protected function registerOldProductVariantData()
    {
        $this->app->bind(OldProductVariantData::class, function (): OldProductVariantData {
            return new OldProductVariantData(14);
        });
    }

    protected function schedule()
    {
        if (!$this->app->runningInConsole()) return;

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app[Schedule::class];

            $schedule->job(new FetchFeed(EDCFeed::TYPE_DISCOUNTS))->dailyAt('04:23');
            $schedule->job(new FetchFeed(EDCFeed::TYPE_PRODUCTS))->cron('23 5 */3 * *');
            $schedule->job(new FetchFeed(EDCFeed::TYPE_PRODUCT_STOCKS))->hourlyAt(5);
            $schedule->job(new DeleteOldProductVariantData())->daily();

            $schedule->command(ExportOrders::class)->everyMinute();
        });
    }

    protected function registerParseFeedJobs()
    {
        $this->app->extend(DispatchParseFeed::class, function (DispatchParseFeed $dispatchParseFeed) {
            foreach ($this->parseFeedJobMapping as $feedType => $jobClass) {
                $dispatchParseFeed->registerParseFeedJob($feedType, $jobClass);
            }

            return $dispatchParseFeed;
        });
    }

    protected function registerProductImageLoader(): void
    {
        $this->app->extend(ProductImageLoader::class, function (ProductImageLoader $loader): ProductImageLoader {
            $loader->setBaseURI(config('edc.imageBaseURI'));

            return $loader;
        });
    }

    protected function registerOrderXMLGenerator(): void
    {
        $this->app->resolving(OrderXMLGenerator::class, function (OrderXMLGenerator $xmlGen): void {
            $xmlGen->setAPIEmail(config('edc.api.email'));
            $xmlGen->setAPIKey(config('edc.api.key'));
            $xmlGen->setCountryMap(config('edc.countryMap'));
        });
    }

    protected function registerEDCAPI(): void
    {
        $this->app->resolving(EDCAPI::class, function (EDCAPI $edcAPI): void {
            $edcAPI->setOrderExportURI(config('edc.orderExportURI'));
        });
    }
}
