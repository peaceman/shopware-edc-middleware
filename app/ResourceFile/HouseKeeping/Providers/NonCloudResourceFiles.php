<?php
/**
 * lel since 2019-07-20
 */

namespace App\ResourceFile\HouseKeeping\Providers;

use App\ResourceFile\ResourceFile;

class NonCloudResourceFiles extends ResourceFileProvider
{
    protected function get(): \Traversable
    {
        return ResourceFile::query()
            ->whereHas('localInstance')
            ->whereDoesntHave('cloudInstance')
            ->cursor();
    }
}
