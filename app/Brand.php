<?php
/**
 * lel since 2019-07-01
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class Brand
 * @package App
 *
 * @property int $id
 * @property string $edc_brand_id
 * @property string $brand_name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read BrandDiscount|null $currentDiscount
 * @property-read BrandDiscount[] $discounts
 */
class Brand extends Model
{
    protected $table = 'brands';
    protected static $unguarded = true;

    public function currentDiscount(): HasOne
    {
        return $this->hasOne(BrandDiscount::class, 'brand_id', 'id')
            ->whereNull('current_until');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(BrandDiscount::class, 'brand_id', 'id');
    }

    public function saveDiscount(BrandDiscount $discount): void
    {
        $this->retireCurrentDiscount();

        $this->currentDiscount()->save($discount);

        $this->setRelation('currentDiscount', $discount);
    }

    protected function retireCurrentDiscount(): void
    {
        if (!$this->currentDiscount) return;

        $this->currentDiscount->update(['current_until' => now()]);
        $this->unsetRelation('currentDiscount');
    }
}
