<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDC\Import\Parser\ProductStockFeedParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Psr\Log\LoggerInterface;

class ParseProductStockFeed extends ParseFeed
{
    public function handle(
        LoggerInterface $logger,
        ProductStockFeedParser $parser
    )
    {
        try {
            $parser->parse($this->feed);
        } catch (ParserFeedTypeMismatch $e) {
            $logger->error('ParseProductStockFeed: ParserFeedTypeMismatch; delete job', [
                'msg' => $e->getMessage(),
            ]);

            report($e);

            $this->delete();
        }
    }
}
