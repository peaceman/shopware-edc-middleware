<?php
/**
 * lel since 2019-07-01
 */

namespace App;

use App\ResourceFile\ResourceFile;
use App\Utils\ConstantEnumerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class EDCFeed
 * @package App
 *
 * @property int $id
 * @property string $type
 * @property int $resource_file_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read ResourceFile $file
 * @property-read EDCFeedPartProduct[] $productFeedParts
 */
class EDCFeed extends Model
{
    use ConstantEnumerator;

    public const TYPE_DISCOUNTS = 'discounts';
    public const TYPE_PRODUCTS = 'products';
    public const TYPE_PRODUCT_STOCKS = 'product-stocks';

    protected $table = 'edc_feeds';
    protected static $unguarded = true;

    public function file(): BelongsTo
    {
        return $this->belongsTo(ResourceFile::class, 'resource_file_id', 'id');
    }

    public function scopeWithType(Builder $query, string $feedType): Builder
    {
        return $query->where('type', $feedType);
    }

    public function productFeedParts(): HasMany
    {
        return $this->hasMany(EDCFeedPartProduct::class, 'full_feed_id', 'id');
    }

    public function asLoggingContext(): array
    {
        return $this->only([
            'id',
            'type',
            'created_at',
        ]);
    }
}
