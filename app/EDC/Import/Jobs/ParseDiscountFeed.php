<?php
/**
 * lel since 2019-07-03
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\Exceptions\ParserFeedTypeMismatch;
use App\EDC\Import\Parser\DiscountFeedParser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Psr\Log\LoggerInterface;

class ParseDiscountFeed extends ParseFeed
{
    public function handle(
        LoggerInterface $logger,
        DiscountFeedParser $parser
    )
    {
        try {
            $parser->parse($this->feed);
        } catch (ParserFeedTypeMismatch $e) {
            $logger->error('ParseDiscountFeed: ParserFeedTypeMismatch; delete job', [
                'msg' => $e->getMessage(),
            ]);

            report($e);

            $this->delete();
        }
    }
}
