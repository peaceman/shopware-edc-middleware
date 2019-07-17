<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDC\Import\Parser\ProductFeedParser;
use Psr\Log\LoggerInterface;

class ParseProductFeed extends ParseFeed
{
    public $queue = 'long-running';

    public function handle(
        LoggerInterface $logger,
        ProductFeedParser $parser
    )
    {
        try {
            $parser->parse($this->feed);
        } catch (ParserFeedTypeMismatch $e) {
            $logger->error('ParseProductsFeed: ParserFeedTypeMismatch; delete job', [
                'msg' => $e->getMessage(),
            ]);

            report($e);

            $this->delete();
        }
    }
}
