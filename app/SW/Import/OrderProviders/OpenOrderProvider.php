<?php
/**
 * lel since 2019-07-27
 */

namespace App\SW\Import\OrderProviders;

class OpenOrderProvider extends OrderProvider
{
    /** @var array */
    protected $requirements;

    public function generateFilters(): array
    {
        return array_map(function (array $reqs): array {
            return array_map(function ($reqVal, $reqKey): array {
                return ['property' => $reqKey, 'value' => $reqVal];
            }, $reqs, array_keys($reqs));
        }, $this->requirements);
    }

    public function setRequirements(array $requirements): void
    {
        $this->requirements = $requirements;
    }
}
