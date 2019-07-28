<?php
/**
 * lel since 2019-07-28
 */

namespace Tests\Unit\EDC\Export\Commands;

use App\EDC\Export\OpenOrderExporter;
use Tests\TestCase;

class ExportOrdersTest extends TestCase
{
    public function testCommand()
    {
        $exporter = $this->createMock(OpenOrderExporter::class);
        $exporter->expects(static::once())
            ->method('exportOrders');

        $this->app->instance(OpenOrderExporter::class, $exporter);

        $this->artisan('edc:export-orders');
    }
}
