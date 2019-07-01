<?php
/**
 * lel since 2019-06-27
 */

namespace App\ResourceFile;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Ramsey\Uuid\Uuid;

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
 * @property-read ResourceFileInstance|null $localInstance
 * @property-read ResourceFileInstance|null $cloudInstance
 */
class ResourceFile extends Model
{
    use SoftDeletes;

    protected $table = 'resource_files';
    protected $dates = ['deleted_at'];
    protected static $unguarded = true;

    public function instances(): HasMany
    {
        return $this->hasMany(ResourceFileInstance::class, 'file_id', 'id');
    }

    public function localInstance(): HasOne
    {
        return $this->hasOne(ResourceFileInstance::class, 'file_id', 'id')
            ->where('disk', 'local');
    }

    public function cloudInstance(): HasOne
    {
        return $this->hasOne(ResourceFileInstance::class, 'file_id', 'id')
            ->where('disk', 'cloud');
    }

    public static function newWithUUID(): ResourceFile
    {
        $rf = new static;
        $rf->uuid = static::getNewUUID();

        return $rf;
    }

    public static function getNewUUID(): string
    {
        $uuid = Uuid::uuid4();

        return $uuid->toString();
    }

    public function ensureValidUUID(): void
    {
        if (!is_string($this->uuid) || empty($this->uuid)) {
            throw new \LogicException('Invalid UUID');
        }
    }

    public function asLoggingContext(): array
    {
        return $this->only([
            'id', 'original_filename', 'mime_type', 'size',
        ]);
    }
}
