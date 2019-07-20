<?php
/**
 * lel since 2019-07-20
 */

namespace App\SW\Export;

use App\EDC\Import\Events\BrandDiscountTouched;
use App\SW\Export\Listeners\ExportBrandArticles;
use App\Utils\RegistersEventListeners;
use Illuminate\Support\ServiceProvider;

class SWExportServiceProvider extends ServiceProvider
{
    use RegistersEventListeners;

    protected $listen = [
        BrandDiscountTouched::class => [
            ExportBrandArticles::class,
        ],
    ];

    public function boot()
    {
        $this->registerEventListeners();
    }

    public function register()
    {

    }
}
