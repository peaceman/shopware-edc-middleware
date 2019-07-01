<?php
/**
 * lel since 2019-07-01
 */

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class BrandDiscount
 * @package App
 *
 * @property int $id
 * @property int $brand_id
 * @property int $edc_feed_id
 * @property int $value
 * @property Carbon|null $current_until
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Brand $brand
 * @property-read EDCFeed $edcFeed
 */
class BrandDiscount extends Model
{
    protected $table = 'brand_discounts';
    protected static $unguarded = true;

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function edcFeed(): BelongsTo
    {
        return $this->belongsTo(EDCFeed::class, 'edc_feed_id', 'id');
    }
}
