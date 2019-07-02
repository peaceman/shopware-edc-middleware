<?php
/**
 * lel since 2019-07-02
 */

namespace App\EDC\Import;

use App\EDC\Import\Jobs\FetchFeed;
use App\EDCFeed;
use App\ResourceFile\Jobs\DeleteUnusedLocals;
use App\ResourceFile\Jobs\ForceDeleteSoftDeleted;
use Carbon\Laravel\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class EDCImportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) $this->scheduleJobs();
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
}
