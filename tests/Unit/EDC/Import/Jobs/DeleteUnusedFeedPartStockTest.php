<?php
/**
 * lel since 25.08.19
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\HouseKeeping\FeedPartStockDeleter;
use App\EDC\Import\HouseKeeping\Providers\UnusedFeedPartStock;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use Tests\TestCase;

class DeleteUnusedFeedPartStockTest extends TestCase
{
    public function testJob()
    {
        $deleter = $this->createMock(FeedPartStockDeleter::class);
        $deleter->expects(static::once())
            ->method('__invoke')
            ->with(new IsInstanceOf(UnusedFeedPartStock::class));

        $job = new \App\EDC\Import\Jobs\DeleteUnusedFeedPartStock();
        $this->app->call([$job, 'handle'], ['deleter' => $deleter]);
    }
}
