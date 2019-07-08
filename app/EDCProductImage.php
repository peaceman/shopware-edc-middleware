<?php
/**
 * lel since 2019-07-08
 */

namespace App;

use App\ResourceFile\ResourceFile;
use App\Utils\HasIdentifier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class EDCProductImage
 * @package App
 *
 * @property int $id
 * @property int $product_id
 * @property string $identifier
 * @property string $filename
 * @property string $etag
 * @property int $file_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read EDCProduct $product
 * @property-read ResourceFile $file
 */
class EDCProductImage extends Model
{
    use HasIdentifier;

    protected $table = 'edc_product_images';
    protected static $unguarded = true;

    public function product(): BelongsTo
    {
        return $this->belongsTo(EDCProduct::class, 'product_id', 'id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ResourceFile::class, 'file_id', 'id');
    }

    public function asLoggingContext(): array
    {
        return $this->only([
            'id', 'product_id', 'identifier', 'filename', 'etag', 'file_id',
        ]);
    }
}
