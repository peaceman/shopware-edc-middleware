<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW;

use App\EDC\Import\Events\BrandDiscountTouched;
use App\EDC\Import\Events\ProductTouched;
use App\SW\Export\Commands\ExportArticles;
use App\SW\Export\Listeners\ExportBrandArticles;
use App\SW\Export\Listeners\ExportTouchedArticle;
use App\SW\Import\OrderProviders\OpenOrderProvider;
use App\Utils\RegistersEventListeners;
use GuzzleHttp\Client;
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
    }

    public function register()
    {
        $this->registerCommands();
        $this->registerShopwareAPI();
        $this->registerOpenOrderProvider();
    }

    protected function registerCommands()
    {
        $this->commands([
            ExportArticles::class,
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
            $provider->setRequirements(config('shopware.order.requirements'));
        });
    }
}
