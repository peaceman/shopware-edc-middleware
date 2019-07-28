<?php
/**
 * lel since 2019-07-28
 */

namespace App;

use App\Utils\ConstantEnumerator;

final class EDCExportStatus
{
    use ConstantEnumerator;

    public const OK = 'OK';
    public const FAIL = 'FAIL';

    private function __construct() { }
}
