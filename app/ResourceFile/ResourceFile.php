<?php
/**
 * lel since 2019-06-27
 */

namespace App\ResourceFile;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ResourceFile
 * @package App\ResourceFile
 *
 * @property int $id
 * @property string $uuid
 * @property string $original_filename
 * @property string $path
 * @property int $size
 * @property string $mime_type
 * @property string $checksum
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * @property-read ResourceFileInstance[]|Collection $instances
 */
class ResourceFile extends Model
{
    use SoftDeletes;

    protected $table = 'resource_files';
    protected $dates = ['deleted_at'];

    public function instances(): HasMany
    {
        return $this->hasMany(ResourceFileInstance::class, 'file_id', 'id');
    }
}
