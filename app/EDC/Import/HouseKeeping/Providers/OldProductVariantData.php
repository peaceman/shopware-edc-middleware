<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\HouseKeeping\Providers;

use App\EDCProductVariantData;

class OldProductVariantData extends ProductVariantData
{
    /** @var int */
    protected $keepDays;

    public function __construct(int $keepDays)
    {
        $this->keepDays = $keepDays;

        parent::__construct();
    }

    protected function get(): \Traversable
    {
        $query = EDCProductVariantData::query()
            ->whereNotNull('current_until')
            ->whereDate('current_until', '<=', now()->subDays($this->keepDays));

        return $query->cursor();
    }
}
