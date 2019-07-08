<?php
/**
 * lel since 2019-07-01
 */

namespace App\Utils;

use Illuminate\Database\Eloquent\SoftDeletes;

function fixture_path(string $path): string {
    return base_path("docs/fixtures/$path");
};

function uses_soft_deletes(string $class): bool {
    $traits = class_uses_recursive($class);

    return isset($traits[SoftDeletes::class]);
}
