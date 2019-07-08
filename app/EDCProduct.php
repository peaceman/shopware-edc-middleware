<?php
/**
 * lel since 2019-07-06
 */

namespace App;

use App\Utils\RetiringRelation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class EDCProduct
 * @package App
 *
 * @property int $id
 * @property int $brand_id
 * @property string $edc_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Brand $brand
 * @property-read EDCProductData $currentData
 * @property-read EDCProductData[] $data
 * @property-read EDCProductVariant[] $variants
 * @property-read EDCProductImage[] $images;
 *
 * @method static Builder withEDCID(string $edcID)
 */
class EDCProduct extends Model
{
    protected $table = 'edc_products';
    protected static $unguarded = true;

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function currentData(): HasOne
    {
        return $this->hasOne(EDCProductData::class, 'product_id', 'id')
            ->whereNull('current_until');
    }

    public function data(): HasMany
    {
        return $this->hasMany(EDCProductData::class, 'product_id', 'id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(EDCProductVariant::class, 'product_id', 'id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(EDCProductImage::class, 'product_id', 'id');
    }

    public function saveData(EDCProductData $data): void
    {
        (new RetiringRelation($this, 'currentData'))->save($data);
    }

    public function scopeWithEDCID(Builder $query, string $edcID): Builder
    {
        return $query->where('edc_id', $edcID);
    }

    public function asLoggingContext(): array
    {
        return $this->only([
            'id', 'brand_id', 'edc_id',
        ]);
    }
}
