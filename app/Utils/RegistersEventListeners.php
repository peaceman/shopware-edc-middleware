<?php
/**
 * lel since 2019-07-20
 */

namespace App\Utils;

use Illuminate\Contracts\Events\Dispatcher;

trait RegistersEventListeners
{
    protected function registerEventListeners()
    {
        /** @var Dispatcher $eventDispatcher */
        $eventDispatcher = $this->app[Dispatcher::class];

        foreach ($this->listen ?? [] as $event => $listeners) {
            foreach (array_unique($listeners) as $listener) {
                $eventDispatcher->listen($event, $listener);
            }
        }
    }
}
