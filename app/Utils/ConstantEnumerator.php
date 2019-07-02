<?php
/**
 * lel since 2019-07-02
 */

namespace App\Utils;

use Illuminate\Support\Str;

trait ConstantEnumerator
{
    public static function getConstants(): array
    {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        return $reflectionClass->getConstants();
    }

    public static function getConstantsWithPrefix(string $prefix): array
    {
        $constants = static::getConstants();

        $filteredConstants = [];

        foreach ($constants as $constantKey => $constantValue) {
            if (!Str::startsWith($constantKey, $prefix)) continue;
            $filteredConstants[$constantKey] = $constantValue;
        }

        return $filteredConstants;
    }
}
