<?php
/**
 * lel since 2019-07-08
 */

namespace App\Utils;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Trait HasIdentifier
 * @package App\Utils
 *
 * @property string $identifier
 * @method static Builder withIdentifier(string $identifier)
 */
trait HasIdentifier
{
    public static function findByIdentifierOrFail(string $identifier)
    {
        return static::query()->withIdentifier($identifier)->firstOrFail();
    }

    public static function fetchNewIdentifier(): string
    {
        while (true) {
            $identifier = Str::random(8);

            if (!static::isIdentifierAlreadyInUse($identifier))
                return $identifier;
        }
    }

    public static function isIdentifierAlreadyInUse(string $identifier): bool
    {
        $q = static::query()->where('identifier', $identifier);

        if (uses_soft_deletes(static::class)) $q->withTrashed();

        return $q->exists();
    }

    public function scopeWithIdentifier($query, string $identifier)
    {
        return $query->where('identifier', $identifier);
    }

    protected static function bootHasIdentifier(): void
    {
        static::creating(function ($model) {
            if (!empty($model->identifier)) return;

            $model->identifier = static::fetchNewIdentifier();
        });
    }
}
