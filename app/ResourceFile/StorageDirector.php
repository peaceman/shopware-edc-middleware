<?php
/**
 * lel since 2019-06-27
 */

namespace App\ResourceFile;

use Illuminate\Contracts\Filesystem\Filesystem;
use League\Flysystem\Adapter\Local;

class StorageDirector
{
    protected $localFS;
    protected $cloudFS;

    public function __construct(Filesystem $localFS, Filesystem $cloudFS)
    {
        $this->setLocalFS($localFS);
        $this->setCloudFS($cloudFS);
    }

    protected function setLocalFS(Filesystem $filesystem): void
    {
        $this->ensureLocalAdapter($filesystem);
        $this->localFS = $filesystem;
    }

    protected function ensureLocalAdapter(Filesystem $filesystem): void
    {
        if (!$filesystem->getAdapter() instanceof Local) {
            $adapterClass = get_class($filesystem);

            $message = "Got non local filesystem adapter to use as local filesystem '{$adapterClass}''";
            throw new Exceptions\NonLocalFilesystem($message);
        }
    }

    protected function setCloudFS(Filesystem $filesystem): void
    {
        $this->cloudFS = $filesystem;
    }

    
}
