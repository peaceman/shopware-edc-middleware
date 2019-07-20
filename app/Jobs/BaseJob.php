<?php
/**
 * lel since 2019-07-20
 */

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

abstract class BaseJob  implements ShouldQueue
{
    use InteractsWithQueue, Queueable;
}
