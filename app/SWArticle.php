<?php
/**
 * lel since 2019-07-08
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class SWArticle
 * @package App
 *
 * @property int $id
 * @property int $edc_product_id
 * @property string $sw_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read EDCProduct $edcProduct
 * @property-read SWVariant[] $variants
 */
class SWArticle extends Model
{
    protected $table = 'sw_articles';
    protected static $unguarded = true;

    public function edcProduct(): BelongsTo
    {
        return $this->belongsTo(EDCProduct::class, 'edc_product_id', 'id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(SWVariant::class, 'article_id', 'id');
    }
}
