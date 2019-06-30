<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\HouseKeeping\Providers;

use App\ResourceFile\ResourceFile;

class SoftDeletedResourceFiles extends ResourceFileProvider
{
    /** @var int */
    protected $minDaysSinceDeletion;

    public function __construct(int $minDaysSinceDeletion)
    {
        $this->minDaysSinceDeletion = $minDaysSinceDeletion;

        parent::__construct();
    }

    protected function get(): \Traversable
    {
        return ResourceFile::query()
            ->onlyTrashed()
            ->where('deleted_at', '<', now()->subDays($this->minDaysSinceDeletion))
            ->cursor();
    }
}
