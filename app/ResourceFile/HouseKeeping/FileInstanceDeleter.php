<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\HouseKeeping;

use App\ResourceFile\HouseKeeping\Providers\ResourceFileInstanceProvider;
use App\ResourceFile\StorageDirector;

class FileInstanceDeleter
{
    /** @var StorageDirector */
    protected $storageDirector;

    public function __construct(StorageDirector $storageDirector)
    {
        $this->storageDirector = $storageDirector;
    }

    public function __invoke(ResourceFileInstanceProvider $rfiProvider)
    {
        foreach ($rfiProvider as $rfi) {
            $this->storageDirector->deleteFileInstance($rfi);
        }
    }
}
