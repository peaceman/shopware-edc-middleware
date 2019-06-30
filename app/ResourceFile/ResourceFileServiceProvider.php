<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile;

use App\ResourceFile\HouseKeeping\Providers\SoftDeletedResourceFiles;
use App\ResourceFile\HouseKeeping\Providers\UnusedLocalResourceFileInstances;
use App\ResourceFile\Jobs\DeleteUnusedLocals;
use App\ResourceFile\Jobs\ForceDeleteSoftDeleted;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Support\ServiceProvider;

class ResourceFileServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) $this->scheduleJobs();
    }

    public function register()
    {
        $this->registerStorageDirector();
        $this->registerResourceFileProviders();
        $this->registerResourceFileInstanceProviders();

        if ($this->app->runningInConsole()) $this->registerCommands();
    }

    protected function registerStorageDirector()
    {
        $this->app->bind(StorageDirector::class, function (): StorageDirector {
            /** @var FilesystemManager $fsm */
            $fsm = $this->app[FilesystemManager::class];

            return new StorageDirector(
                $fsm->disk(),
                $fsm->cloud(),
                $this->app[Dispatcher::class]
            );
        });
    }

    protected function registerResourceFileProviders(): void
    {
        $this->app->bind(SoftDeletedResourceFiles::class, function (): SoftDeletedResourceFiles {
            return new SoftDeletedResourceFiles(7);
        });
    }

    protected function registerResourceFileInstanceProviders(): void
    {
        $this->app->bind(UnusedLocalResourceFileInstances::class, function (): UnusedLocalResourceFileInstances {
            return new UnusedLocalResourceFileInstances(1);
        });
    }

    protected function registerCommands()
    {
        $this->commands([
            new ClosureCommand('rf:house-keeping:delete-unused-locals', function (Dispatcher $dispatcher) {
                $dispatcher->dispatch(new DeleteUnusedLocals());
            }),
            new ClosureCommand('rf:house-keeping:force-delete-soft-deleted', function (Dispatcher $dispatcher) {
                $dispatcher->dispatch(new ForceDeleteSoftDeleted());
            }),
        ]);
    }

    protected function scheduleJobs()
    {
        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app[Schedule::class];

            $schedule->job(DeleteUnusedLocals::class)->daily();
            $schedule->job(ForceDeleteSoftDeleted::class)->daily();
        });
    }
}
