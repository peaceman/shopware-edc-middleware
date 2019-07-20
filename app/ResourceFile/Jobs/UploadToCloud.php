<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\Jobs;

use App\Jobs\BaseJob;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use Assert\Assert;

class UploadToCloud extends BaseJob
{
    public $queue = 'long-running';

    protected $rfIDs;

    public function __construct(array $rfIDs)
    {
        Assert::thatAll($rfIDs)->integer();

        $this->rfIDs = $rfIDs;
    }

    public function getRFIDs(): array
    {
        return $this->rfIDs;
    }

    public function handle(StorageDirector $storageDirector): void
    {
        $rfCursor = ResourceFile::query()->whereIn('id', $this->rfIDs)->cursor();

        foreach ($rfCursor as $rf) {
            $storageDirector->uploadToCloud($rf);
        }
    }
}
