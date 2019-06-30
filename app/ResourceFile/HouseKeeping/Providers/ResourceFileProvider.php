<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile\HouseKeeping\Providers;

use App\ResourceFile\ResourceFile;

abstract class ResourceFileProvider extends \IteratorIterator
{
    public function __construct()
    {
        parent::__construct($this->get());
    }

    public function current(): ResourceFile
    {
        return parent::current();
    }

    abstract protected function get(): \Traversable;
}
