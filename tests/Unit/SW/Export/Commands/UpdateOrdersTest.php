<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\SW\Export\Commands;

use App\SW\Export\OpenOrderUpdater;
use Tests\TestCase;

class UpdateOrdersTest extends TestCase
{
    public function testCommand()
    {
        $updater = $this->createMock(OpenOrderUpdater::class);
        $updater->expects(static::once())
            ->method('updateOrders');

        $this->app->instance(OpenOrderUpdater::class, $updater);

        $this->artisan('sw:update-orders');
    }
}
