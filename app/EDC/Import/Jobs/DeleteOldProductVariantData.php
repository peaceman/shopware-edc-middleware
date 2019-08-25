<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\Jobs;

use App\EDC\Import\HouseKeeping\ProductVariantDataDeleter;
use App\EDC\Import\HouseKeeping\Providers\OldProductVariantData;
use App\Jobs\BaseJob;

class DeleteOldProductVariantData extends BaseJob
{
    public $queue = 'long-running';

    public function handle(ProductVariantDataDeleter $deleter, OldProductVariantData $provider)
    {
        $deleter($provider);
    }
}
