<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\SW\Export\Listeners;

use App\Brand;
use App\EDC\Import\Events\BrandDiscountTouched;
use App\EDCProduct;
use App\SW\Export\Jobs\ExportArticle;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportBrandArticlesTest extends TestCase
{
    use DatabaseTransactions;

    public function testListener()
    {
        $brandA = factory(Brand::class)->create();
        $brandB = factory(Brand::class)->create();

        factory(EDCProduct::class, 5)->create(['brand_id' => $brandA->id]);
        factory(EDCProduct::class, 5)->create(['brand_id' => $brandB->id]);

        Queue::fake();

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->app[EventDispatcher::class];
        $eventDispatcher->dispatch(new BrandDiscountTouched($brandA));

        Queue::assertPushed(ExportArticle::class, 5);
        Queue::assertNotPushed(ExportArticle::class, function (ExportArticle $job) use ($brandB) {
            return $job->edcProduct->brand_id == $brandB->id;
        });
    }
}
