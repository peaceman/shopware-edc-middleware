<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\Jobs;

use App\ResourceFile\ResourceFileInstance;
use App\ResourceFile\StorageDirector;
use Assert\Assert;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateLastAccessTime implements ShouldQueue
{
    /** @var int[] */
    protected $rfiIDs;

    public function __construct(array $rfiIDs)
    {
        Assert::thatAll($rfiIDs)->integer();

        $this->rfiIDs = $rfiIDs;
    }

    public function getRFIIDs(): array
    {
        return $this->rfiIDs;
    }

    public function handle(StorageDirector $storageDirector): void
    {
        $rfiCursor = ResourceFileInstance::query()->whereIn('id', $this->rfiIDs)->cursor();

        foreach ($rfiCursor as $rfi) {
            $storageDirector->updateLastAccessTime($rfi);
        }
    }
}
