<?php
/**
 * lel since 2019-07-20
 */

namespace App\ResourceFile\HouseKeeping\Providers;

class PreFilledRFProvider extends ResourceFileProvider
{
    protected $rfs;

    public function __construct(array $rfs)
    {
        $this->rfs = $rfs;

        parent::__construct();
    }

    protected function get(): \Traversable
    {
        return new \ArrayIterator($this->rfs);
    }
}
