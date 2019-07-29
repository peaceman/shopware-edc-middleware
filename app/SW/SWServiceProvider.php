<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW;

use App\EDC\Import\Events\BrandDiscountTouched;
use App\EDC\Import\Events\ProductTouched;
use App\SW\Export\CategoryMapper;
use App\SW\Export\Commands\ExportArticles;
use App\SW\Export\Commands\UpdateOrders;
use App\SW\Export\Listeners\ExportBrandArticles;
use App\SW\Export\Listeners\ExportTouchedArticle;
use App\SW\Import\Commands\FetchOrders;
use App\SW\Import\OrderProviders\OpenOrderProvider;
use App\Utils\RegistersEventListeners;
use GuzzleHttp\Client;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class SWServiceProvider extends ServiceProvider
{
    use RegistersEventListeners;

    protected $listen = [
        BrandDiscountTouched::class => [
            ExportBrandArticles::class,
        ],
        ProductTouched::class => [
            ExportTouchedArticle::class,
        ],
    ];

    public function boot()
    {
        $this->registerEventListeners();
        $this->scheduleCommands();
    }

    public function register()
    {
        $this->registerCommands();
        $this->registerShopwareAPI();
        $this->registerOpenOrderProvider();
        $this->registerCategoryMapper();
    }

    protected function registerCommands()
    {
        $this->commands([
            ExportArticles::class,
            FetchOrders::class,
            UpdateOrders::class,
        ]);
    }

    protected function registerShopwareAPI(): void
    {
        $this->app->bind(ShopwareAPI::class, function () {
            $httpClient = new Client([
                'base_uri' => config('shopware.baseUri'),
                'auth' => [config('shopware.auth.username'), config('shopware.auth.apiKey')],
            ]);

            $api = new ShopwareAPI($this->app[LoggerInterface::class], $httpClient);
            return $api;
        });
    }

    protected function registerOpenOrderProvider(): void
    {
        $this->app->resolving(OpenOrderProvider::class, function (OpenOrderProvider $provider): void {
            $provider->setRequirements(config('shopware.order.open.requirements'));
        });
    }

    protected function registerCategoryMapper(): void
    {
        $this->app->singleton(CategoryMapper::class, function (): CategoryMapper {
            /** @var FilesystemManager $fsm */
            $fsm = $this->app[FilesystemManager::class];

            $filename = config('shopware.categoryMappingFile');
            return new CategoryMapper($fsm->disk(), $filename);
        });
    }

    protected function scheduleCommands(): void
    {
        if (!$this->app->runningInConsole()) return;

        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app[Schedule::class];

            $schedule->command(FetchOrders::class)->everyMinute();
            $schedule->command(UpdateOrders::class)->everyMinute();
        });
    }
}
