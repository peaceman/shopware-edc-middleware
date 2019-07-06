<?php
/**
 * lel since 2019-07-06
 */

namespace Tests\Unit\EDC\Import\Jobs;

use App\EDC\Import\Jobs\ParseProductFeedPart;
use App\EDC\Import\Parser\ProductFeedPartParser;
use App\EDCFeedPartProduct;
use Tests\TestCase;

class ParseProductFeedPartTest extends TestCase
{
    public function testJob()
    {
        $feed = new EDCFeedPartProduct();

        $feedParser = $this->getMockBuilder(ProductFeedPartParser::class)
            ->setMethods(['parse'])
            ->disableOriginalConstructor()
            ->getMock();

        $feedParser->expects(static::once())
            ->method('parse')
            ->with($feed);

        $job = new ParseProductFeedPart($feed);
        $this->app->call([$job, 'handle'], ['parser' => $feedParser]);
    }
}
