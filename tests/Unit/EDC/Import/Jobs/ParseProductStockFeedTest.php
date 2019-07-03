<?php
/**
 * lel since 2019-07-03
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDC\Import\Jobs\ParseProductStockFeed;
use App\EDC\Import\Parser\ProductStockFeedParser;
use App\EDCFeed;
use Tests\TestCase;

class ParseProductStockFeedTest extends TestCase
{
    public function testJobWithValidFeed()
    {
        $edcFeed = new EDCFeed(['type' => EDCFeed::TYPE_PRODUCT_STOCKS]);

        $feedParser = $this->getMockBuilder(ProductStockFeedParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedParser->expects(static::once())
            ->method('parse')
            ->with($edcFeed);

        $job = new ParseProductStockFeed($edcFeed);
        $this->app->call([$job, 'handle'], ['parser' => $feedParser]);
    }

    public function testJobWithInvalidFeed()
    {
        $edcFeed = new EDCFeed(['type' => EDCFeed::TYPE_DISCOUNTS]);

        $feedParser = $this->getMockBuilder(ProductStockFeedParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedParser->expects(static::once())
            ->method('parse')
            ->with($edcFeed)
            ->willThrowException(new ParserFeedTypeMismatch(''));

        $job = new ParseProductStockFeed($edcFeed);
        $this->app->call([$job, 'handle'], ['parser' => $feedParser]);
    }
}
