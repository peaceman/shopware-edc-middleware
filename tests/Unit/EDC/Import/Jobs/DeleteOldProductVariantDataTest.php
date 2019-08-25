<?php
/**
 * lel since 25.08.19
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\HouseKeeping\ProductVariantDataDeleter;
use App\EDC\Import\HouseKeeping\Providers\OldProductVariantData;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use Tests\TestCase;

class DeleteOldProductVariantDataTest extends TestCase
{
    public function testJob()
    {
        $deleter = $this->createMock(ProductVariantDataDeleter::class);
        $deleter->expects(static::once())
            ->method('__invoke')
            ->with(new IsInstanceOf(OldProductVariantData::class));

        $job = new \App\EDC\Import\Jobs\DeleteOldProductVariantData();
        $this->app->call([$job, 'handle'], ['deleter' => $deleter]);
    }
}
