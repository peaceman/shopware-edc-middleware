<?php
/**
 * lel since 25.08.19
 */

namespace Tests\Unit\EDC\Import\HouseKeeping;

use App\EDC\Import\HouseKeeping\ProductVariantDataDeleter;
use App\EDC\Import\HouseKeeping\Providers\ProductVariantData;
use App\EDCProductVariantData;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductVariantDataDeleterTest extends TestCase
{
    use DatabaseTransactions;

    public function testDeleter()
    {
        $epvd = $this->createMock(EDCProductVariantData::class);
        $epvd->expects(static::once())
            ->method('delete');

        $provider = $this->createPreFilledProvider([$epvd]);

        $deleter = $this->app->make(ProductVariantDataDeleter::class);
        $deleter($provider);
    }

    protected function createPreFilledProvider(iterable $filling): ProductVariantData
    {
        return new class($filling) extends ProductVariantData {
            protected $filling;

            public function __construct(iterable $filling)
            {
                $this->filling = $filling;

                parent::__construct();
            }

            protected function get(): \Traversable
            {
                return yield from $this->filling;
            }
        };
    }
}
