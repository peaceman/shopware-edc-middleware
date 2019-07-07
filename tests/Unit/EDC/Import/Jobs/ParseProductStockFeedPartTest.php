<?php
/**
 * lel since 2019-07-07
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\Jobs\ParseProductStockFeedPart;
use App\EDC\Import\Parser\ProductStockFeedPartParser;
use App\EDCFeedPartStock;
use Tests\TestCase;

class ParseProductStockFeedPartTest extends TestCase
{
    public function testJob()
    {
        $feed = new EDCFeedPartStock();

        $feedParser = $this->getMockBuilder(ProductStockFeedPartParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedParser->expects(static::once())
            ->method('parse')
            ->with($feed);

        $job = new ParseProductStockFeedPart($feed);
        $this->app->call([$job, 'handle'], ['parser' => $feedParser]);
    }
}
