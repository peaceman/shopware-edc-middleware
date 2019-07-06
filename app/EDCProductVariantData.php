<?php
/**
 * lel since 2019-07-06
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EDCProductVariantData
 * @package App
 *
 * @property int $id
 * @property int $product_variant_id
 * @property int $feed_part_product_id
 * @property int|null $feed_part_stock_id
 * @property string $subartnr
 * @property Carbon|null $current_until
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read EDCProductVariant $productVariant
 * @property-read EDCFeedPartProduct $feedPartProduct
 * @property-read EDCFeedPartStock|null $feedPartStock
 */
class EDCProductVariantData extends Model
{
    protected $table = 'edc_product_variant_data';
    protected static $unguarded = true;

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(EDCProductVariant::class, 'product_variant_id', 'id');
    }

    public function feedPartProduct(): BelongsTo
    {
        return $this->belongsTo(EDCFeedPartProduct::class, 'feed_part_product_id', 'id');
    }

    public function feedPartStock(): BelongsTo
    {
        return $this->belongsTo(EDCFeedPartStock::class, 'feed_part_stock_id', 'id');
    }
}
