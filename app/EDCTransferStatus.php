<?php
/**
 * lel since 2019-07-28
 */

namespace App;

final class EDCTransferStatus
{
    public const OPEN = 'open';
    public const ERROR = 'error';
    public const COMPLETED = 'completed';
    public const WAITING = 'waiting';

    private function __construct() { }
}
