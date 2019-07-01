<?php
/**
 * lel since 2019-07-01
 */

namespace App\Utils;

function fixture_path(string $path): string {
    return base_path("docs/fixtures/$path");
};
