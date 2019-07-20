<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW\Export;

use App\EDC\Import\Events\BrandDiscountTouched;
use App\EDC\Import\Events\ProductTouched;
use App\SW\Export\Commands\ExportArticles;
use App\SW\Export\Listeners\ExportBrandArticles;
use App\SW\Export\Listeners\ExportTouchedArticle;
use App\Utils\RegistersEventListeners;
use Illuminate\Support\ServiceProvider;

class SWExportServiceProvider extends ServiceProvider
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
    }

    protected function registerCommands()
    {
        $this->commands([
            ExportArticles::class,
        ]);
    }
}
