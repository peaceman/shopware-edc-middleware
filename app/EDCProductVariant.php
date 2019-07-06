<?php
/**
 * lel since 2019-07-06
 */

namespace App;

use App\Utils\RetiringRelation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Class EDCProductVariant
 * @package App
 *
 * @property int $id
 * @property int $product_id
 * @property string $edc_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read EDCProduct $product
 * @property-read EDCProductVariantData $currentData
 * @property-read EDCProductVariantData[] $data
 */
class EDCProductVariant extends Model
{
    protected $table = 'edc_product_variants';
    protected static $unguarded = true;

    public function product(): BelongsTo
    {
        return $this->belongsTo(EDCProduct::class, 'product_id', 'id');
    }

    public function currentData(): HasOne
    {
        return $this->hasOne(EDCProductVariantData::class, 'product_variant_id', 'id')
            ->whereNull('current_until');
    }

    public function data(): HasMany
    {
        return $this->hasMany(EDCProductVariantData::class, 'product_variant_id', 'id');
    }

    public function saveData(EDCProductVariantData $data)
    {
        (new RetiringRelation($this, 'currentData'))->save($data);
    }

    public function asLoggingContext(): array
    {
        return $this->only(['id', 'product_id', 'edc_id']);
    }
}
