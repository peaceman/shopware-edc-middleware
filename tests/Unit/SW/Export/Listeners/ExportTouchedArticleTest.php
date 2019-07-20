<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\SW\Export\Listeners;

use App\EDC\Import\Events\ProductTouched;
use App\EDCProduct;
use App\SW\Export\Jobs\ExportArticle;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportTouchedArticleTest extends TestCase
{
    use DatabaseTransactions;

    public function testListener()
    {
        $product = factory(EDCProduct::class)->create();

        Queue::fake();

        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->app[EventDispatcher::class];
        $eventDispatcher->dispatch(new ProductTouched($product));

        Queue::assertPushed(ExportArticle::class, function (ExportArticle $job) use ($product) {
            return $job->edcProduct instanceof $product && $job->edcProduct->id == $product->id;
        });
    }
}
