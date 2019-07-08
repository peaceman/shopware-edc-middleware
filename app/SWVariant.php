<?php
/**
 * lel since 2019-07-08
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class SWVariant
 * @package App
 *
 * @property int $id
 * @property int $article_id
 * @property int $edc_product_variant_id
 * @property string $sw_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read SWArticle $article
 * @property-read EDCProductVariant $edcProductVariant
 */
class SWVariant extends Model
{
    protected $table = 'sw_variants';
    protected static $unguarded = true;

    public function article(): BelongsTo
    {
        return $this->belongsTo(SWArticle::class, 'article_id', 'id');
    }

    public function edcProductVariant(): BelongsTo
    {
        return $this->belongsTo(EDCProductVariant::class, 'edc_product_variant_id', 'id');
    }
}
