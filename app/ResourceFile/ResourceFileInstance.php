<?php
/**
 * lel since 2019-06-27
 */

namespace App\ResourceFile;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ResourceFileInstance
 * @package App\ResourceFile
 *
 * @property int $id
 * @property int $file_id
 * @property string $disk
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property null|Carbon $last_access_at
 *
 * @property-read ResourceFile $file
 */
class ResourceFileInstance extends Model
{
    protected $table = 'resource_file_instances';
    protected $dates = ['last_access_at'];
    protected static $unguarded = true;

    public function file(): BelongsTo
    {
        return $this->belongsTo(ResourceFile::class, 'file_id', 'id');
    }
}
