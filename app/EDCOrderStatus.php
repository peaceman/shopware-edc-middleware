<?php
/**
 * lel since 2019-07-28
 */

namespace App;

use App\Utils\ConstantEnumerator;

final class EDCOrderStatus
{
    use ConstantEnumerator;

    public const SHIPPED = 'shipped';
    public const BACKORDER = 'backorder';

    private function __construct() { }
}
