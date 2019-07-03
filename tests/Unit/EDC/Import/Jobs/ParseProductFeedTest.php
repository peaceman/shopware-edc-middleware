<?php
/**
 * lel since 2019-07-03
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDC\Import\Jobs\ParseProductFeed;
use App\EDC\Import\Parser\ProductFeedParser;
use App\EDCFeed;
use Tests\TestCase;

class ParseProductFeedTest extends TestCase
{
    public function testJobWithValidFeed()
    {
        $edcFeed = new EDCFeed(['type' => EDCFeed::TYPE_PRODUCTS]);

        $feedParser = $this->getMockBuilder(ProductFeedParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedParser->expects(static::once())
            ->method('parse')
            ->with($edcFeed);

        $job = new ParseProductFeed($edcFeed);
        $this->app->call([$job, 'handle'], ['parser' => $feedParser]);
    }

    public function testJobWithInvalidFeed()
    {
        $edcFeed = new EDCFeed(['type' => EDCFeed::TYPE_DISCOUNTS]);

        $feedParser = $this->getMockBuilder(ProductFeedParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedParser->expects(static::once())
            ->method('parse')
            ->with($edcFeed)
            ->willThrowException(new ParserFeedTypeMismatch(''));

        $job = new ParseProductFeed($edcFeed);
        $this->app->call([$job, 'handle'], ['parser' => $feedParser]);
    }
}
