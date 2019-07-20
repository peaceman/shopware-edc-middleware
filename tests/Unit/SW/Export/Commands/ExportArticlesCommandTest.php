<?php
/**
 * lel since 2019-07-20
 */

namespace Tests\Unit\SW\Export\Commands;

use App\EDCProduct;
use App\SW\Export\Jobs\ExportArticle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportArticlesCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function testWithRegularIDs()
    {
        factory(EDCProduct::class, 5)->create();
        $products = factory(EDCProduct::class, 5)->create();
        $productIDs = collect($products)->pluck('id');

        Queue::fake();
        $this->artisan('sw:export-articles ' . $productIDs->join(' '));

        Queue::assertPushed(ExportArticle::class, 5);
        Queue::assertPushed(ExportArticle::class, function (ExportArticle $job) use ($productIDs) {
            return $productIDs->contains($job->edcProduct->id);
        });
    }

    public function testWithEDCIDs()
    {
        factory(EDCProduct::class, 5)->create();
        $products = factory(EDCProduct::class, 5)->create();
        $productIDs = collect($products)->pluck('edc_id');

        Queue::fake();
        $this->artisan('sw:export-articles --edc-id ' . $productIDs->join(' '));

        Queue::assertPushed(ExportArticle::class, 5);
        Queue::assertPushed(ExportArticle::class, function (ExportArticle $job) use ($productIDs) {
            return $productIDs->contains($job->edcProduct->edc_id);
        });
    }

    public function testAll()
    {
        factory(EDCProduct::class, 5)->create();
        $products = factory(EDCProduct::class, 5)->create();
        $productIDs = collect($products)->pluck('edc_id');

        Queue::fake();
        $this->artisan('sw:export-articles');

        Queue::assertPushed(ExportArticle::class, 10);
        Queue::assertPushed(ExportArticle::class, function (ExportArticle $job) use ($productIDs) {
            return $productIDs->contains($job->edcProduct->edc_id);
        });
    }
}
