<?php
/**
 * lel since 2019-07-02
 */

namespace App\EDC\Import;

use App\EDC\Import\Events\FeedFetched;
use App\EDC\Import\Jobs\FetchFeed;
use App\EDC\Import\Jobs\ParseDiscountFeed;
use App\EDC\Import\Jobs\ParseProductsFeed;
use App\EDC\Import\Jobs\ParseProductStocksFeed;
use App\EDCFeed;
use App\ResourceFile\Jobs\DeleteUnusedLocals;
use App\ResourceFile\Jobs\ForceDeleteSoftDeleted;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class EDCImportServiceProvider extends ServiceProvider
{
    protected $listen = [
        FeedFetched::class => [
            DispatchParseFeed::class,
        ],
    ];

    protected $parseFeedJobMapping = [
        EDCFeed::TYPE_PRODUCTS => ParseProductsFeed::class,
        EDCFeed::TYPE_PRODUCT_STOCKS => ParseProductStocksFeed::class,
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
}
