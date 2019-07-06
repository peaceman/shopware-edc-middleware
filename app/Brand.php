<?php
/**
 * lel since 2019-07-01
 */

namespace App;

use App\Utils\RetiringRelation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        (new RetiringRelation($this, 'currentDiscount'))->save($discount);
    }

    public function scopeWithBrandID(Builder $query, string $brandID): Builder
    {
        return $query->where('edc_brand_id', $brandID);
    }

    public function asLoggingContext(): array
    {
        return $this->only([
            'id', 'edc_brand_id', 'brand_name'
        ]);
    }
}
