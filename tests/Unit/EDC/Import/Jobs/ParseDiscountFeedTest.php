<?php
/**
 * lel since 2019-07-03
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDC\Import\Jobs\ParseDiscountFeed;
use App\EDC\Import\Parser\DiscountFeedParser;
use App\EDCFeed;
use Tests\TestCase;

class ParseDiscountFeedTest extends TestCase
{
    public function testJobWithValidFeed()
    {
        $edcFeed = new EDCFeed(['type' => EDCFeed::TYPE_DISCOUNTS]);

        $discountFeedParser = $this->getMockBuilder(DiscountFeedParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $discountFeedParser->expects(static::once())
            ->method('parse')
            ->with($edcFeed);

        $job = new ParseDiscountFeed($edcFeed);
        $this->app->call([$job, 'handle'], ['parser' => $discountFeedParser]);
    }

    public function testJobWithInvalidFeed()
    {
        $edcFeed = new EDCFeed(['type' => EDCFeed::TYPE_PRODUCTS]);

        $discountFeedParser = $this->getMockBuilder(DiscountFeedParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $discountFeedParser->expects(static::once())
            ->method('parse')
            ->with($edcFeed)
            ->willThrowException(new ParserFeedTypeMismatch(''));

        $job = new ParseDiscountFeed($edcFeed);
        $this->app->call([$job, 'handle'], ['parser' => $discountFeedParser]);
    }
}
