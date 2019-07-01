<?php
/**
 * lel since 2019-07-01
 */

namespace Tests\Unit;

use App\Brand;
use App\BrandDiscount;
use App\EDCFeed;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BrandTest extends TestCase
{
    use DatabaseTransactions;

    public function testThatTheActiveDiscountIsReturned()
    {
        /** @var Brand $brand */
        $brand = factory(Brand::class)->create();
        /** @var EDCFeed $edcFeed */
        $edcFeed = factory(EDCFeed::class)->create();

        $brand->discounts()->saveMany([
            new BrandDiscount(['edc_feed_id' => $edcFeed->id, 'value' => 23, 'current_until' => now()]),
            new BrandDiscount(['edc_feed_id' => $edcFeed->id, 'value' => 23, 'current_until' => null]),
            new BrandDiscount(['edc_feed_id' => $edcFeed->id, 'value' => 23, 'current_until' => now()]),
        ]);

        $discount = $brand->currentDiscount;

        static::assertNull($discount->current_until);
        static::assertEquals(3, $brand->discounts()->count());
    }

    public function testSaveDiscount()
    {
        /** @var Brand $brand */
        $brand = factory(Brand::class)->create();

        /** @var BrandDiscount $discount */
        $discount = factory(BrandDiscount::class)->make();
        $brand->saveDiscount($discount);

        $discount->refresh();
        static::assertEquals($brand->id, $discount->brand_id);
        static::assertNull($discount->current_until);
        static::assertEquals($discount->id, $brand->currentDiscount->id);

        /** @var BrandDiscount $secondDiscount */
        $secondDiscount = factory(BrandDiscount::class)->make();
        $brand->saveDiscount($secondDiscount);

        static::assertEquals($brand->id, $secondDiscount->brand_id);
        static::assertNull($secondDiscount->current_until);
        static::assertEquals($secondDiscount->id, $brand->currentDiscount->id);

        $discount->refresh();
        static::assertNotNull($discount->current_until);
    }
}
