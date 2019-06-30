<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\HouseKeeping;

use App\ResourceFile\HouseKeeping\Providers\ResourceFileProvider;
use App\ResourceFile\StorageDirector;

class FileForceDeleter
{
    /** @var StorageDirector */
    protected $storageDirector;

    public function __construct(StorageDirector $storageDirector)
    {
        $this->storageDirector = $storageDirector;
    }

    public function __invoke(ResourceFileProvider $rfs)
    {
        foreach ($rfs as $rf) {
            try {
                $this->storageDirector->forceDeleteFile($rf);
            } catch (\Exception $e) {
                report($e);
            }
        }
    }
}
