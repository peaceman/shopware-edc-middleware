<?php
/**
 * lel since 2019-07-01
 */

namespace App;

use App\ResourceFile\ResourceFile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 */
class EDCFeed extends Model
{
    protected $table = 'edc_feeds';
    protected static $unguarded = true;

    public function file(): BelongsTo
    {
        $this->belongsTo(ResourceFile::class, 'resource_file_id', 'id');
    }
}
