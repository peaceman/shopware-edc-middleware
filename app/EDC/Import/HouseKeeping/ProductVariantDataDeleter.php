<?php
/**
 * lel since 25.08.19
 */

namespace App\EDC\Import\HouseKeeping;

use App\EDCProductVariantData;

class ProductVariantDataDeleter
{
    public function __invoke(Providers\ProductVariantData $provider): void
    {
        foreach ($provider as $pvd) {
            $this->handle($pvd);
        }
    }

    protected function handle(EDCProductVariantData $pvd): void
    {
        $pvd->delete();
    }
}
