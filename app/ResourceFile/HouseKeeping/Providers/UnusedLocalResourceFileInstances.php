<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\HouseKeeping\Providers;

use App\ResourceFile\ResourceFileInstance;
use Illuminate\Database\Eloquent\Builder;

class UnusedLocalResourceFileInstances extends ResourceFileInstanceProvider
{
    /** @var int */
    protected $minDaysSinceLastUsage;

    public function __construct(int $minDaysSinceLastUsage)
    {
        $this->minDaysSinceLastUsage = $minDaysSinceLastUsage;

        parent::__construct();
    }

    protected function get(): \Traversable
    {
        $query = ResourceFileInstance::query()
            ->where('last_access_at', '<', now()->subDays($this->minDaysSinceLastUsage))
            ->where('disk', 'local')
            ->whereHas('file', function (Builder $query) {
                $query->whereHas('cloudInstance');

                return $query;
            });

        return $query->cursor();
    }
}
