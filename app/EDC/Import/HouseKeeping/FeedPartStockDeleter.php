<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\HouseKeeping;

use App\EDCFeedPartStock;

class FeedPartStockDeleter
{
    public function __invoke(Providers\FeedPartStock $provider): void
    {
        foreach ($provider as $fps) {
            $this->handle($fps);
        }
    }

    public function handle(EDCFeedPartStock $feedPartStock): void
    {
        $feedPartStock->delete();
    }
}
