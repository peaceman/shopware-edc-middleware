<?php
/**
 * lel since 2019-07-06
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EDCProductData
 * @package App
 *
 * @property int $id
 * @property int $product_id
 * @property int $feed_part_product_id
 * @property string $artnr
 * @property Carbon|null $current_until
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read EDCProduct $product
 * @property-read EDCFeedPartProduct $feedPartProduct
 */
class EDCProductData extends Model
{
    protected $table = 'edc_product_data';
    protected static $unguarded = true;

    public function product(): BelongsTo
    {
        return $this->belongsTo(EDCProduct::class, 'product_id', 'id');
    }

    public function feedPartProduct(): BelongsTo
    {
        return $this->belongsTo(EDCFeedPartProduct::class, 'feed_part_product_id', 'id');
    }
}
