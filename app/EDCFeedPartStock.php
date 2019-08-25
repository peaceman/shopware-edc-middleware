<?php
/**
 * lel since 2019-07-06
 */

namespace App;

use App\ResourceFile\ResourceFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class EDCFeedPartStock
 * @package App
 *
 * @property int $id
 * @property int $file_id
 * @property int $full_feed_id
 * @property string $content
 * @property-read string $content_checksum
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read ResourceFile $file
 * @property-read EDCFeed $fullFeed
 * @property-read EDCProductVariantData $variantData
 */
class EDCFeedPartStock extends Model
{
    protected $table = 'edc_feed_part_stocks';
    protected static $unguarded = true;

    public static function boot()
    {
        parent::boot();

        static::deleted(function (self $feedPartStock) {
            if ($feedPartStock->file) $feedPartStock->file->delete();
        });
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ResourceFile::class, 'file_id', 'id');
    }

    public function fullFeed(): BelongsTo
    {
        return $this->belongsTo(EDCFeed::class, 'full_feed_id', 'id');
    }

    public function asLoggingContext(): array
    {
        return $this->only(['id', 'file_id', 'full_feed_id']);
    }

    public function variantData(): HasMany
    {
        return $this->hasMany(EDCProductVariantData::class, 'feed_part_stock_id', 'id');
    }
}
