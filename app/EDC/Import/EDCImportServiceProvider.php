<?php
/**
 * lel since 2019-07-02
 */

namespace App\EDC\Import;

use App\EDC\Import\Events\FeedFetched;
use App\EDC\Import\Jobs\FetchFeed;
use App\EDC\Import\Jobs\ParseDiscountFeed;
use App\EDC\Import\Jobs\ParseProductFeed;
use App\EDC\Import\Jobs\ParseProductStockFeed;
use App\EDCFeed;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher as JobDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\ServiceProvider;

class EDCImportServiceProvider extends ServiceProvider
{
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
        if ($this->app->runningInConsole()) $this->scheduleJobs();

        $this->registerEventListeners();
    }

    public function register()
    {
        $this->registerParseFeedJobs();
        $this->registerProductImageLoader();

        if ($this->app->runningInConsole()) $this->registerCommands();
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
    }

    protected function scheduleJobs()
    {
        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app[Schedule::class];

            $schedule->job(new FetchFeed(EDCFeed::TYPE_DISCOUNTS))->dailyAt('04:23');
            $schedule->job(new FetchFeed(EDCFeed::TYPE_PRODUCTS))->cron('23 5 */3 * *');
            $schedule->job(new FetchFeed(EDCFeed::TYPE_PRODUCT_STOCKS))->hourlyAt(5);
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

    protected function registerEventListeners()
    {
        /** @var Dispatcher $eventDispatcher */
        $eventDispatcher = $this->app[Dispatcher::class];

        foreach ($this->listen as $event => $listeners) {
            foreach (array_unique($listeners) as $listener) {
                $eventDispatcher->listen($event, $listener);
            }
        }
    }

    protected function registerProductImageLoader(): void
    {
        $this->app->extend(ProductImageLoader::class, function (ProductImageLoader $loader): ProductImageLoader {
            $loader->setBaseURI(config('edc.imageBaseURI'));

            return $loader;
        });
    }
}
