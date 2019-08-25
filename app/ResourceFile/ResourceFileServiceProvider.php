<?php
/**
 * lel since 2019-06-30
 */

namespace App\ResourceFile;

use App\ResourceFile\HouseKeeping\Providers\SoftDeletedResourceFiles;
use App\ResourceFile\HouseKeeping\Providers\UnusedLocalResourceFileInstances;
use App\ResourceFile\Jobs\DeleteUnusedLocals;
use App\ResourceFile\Jobs\ForceDeleteSoftDeleted;
use App\ResourceFile\Jobs\QueueNonCloudForUpload;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Console\Kernel;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class ResourceFileServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) $this->scheduleJobs();

        $this->registerJobProcessingSubscriber();
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
        $this->app->singleton(StorageDirector::class, function (): StorageDirector {
            /** @var FilesystemManager $fsm */
            $fsm = $this->app[FilesystemManager::class];

            return new StorageDirector(
                $fsm->disk(),
                $fsm->cloud(),
                $this->app[Dispatcher::class],
                $this->app[LoggerInterface::class]
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
        /** @var Kernel $consoleKernel */
        $consoleKernel = $this->app[Kernel::class];

        $consoleKernel
            ->command('rf:house-keeping:delete-unused-locals', function (Dispatcher $dispatcher) {
                $dispatcher->dispatch(new DeleteUnusedLocals());
            })
            ->describe('Delete unused local resource file instances');

        $consoleKernel
            ->command('rf:house-keeping:force-delete-soft-deleted', function (Dispatcher $dispatcher) {
                $dispatcher->dispatch(new ForceDeleteSoftDeleted());
            })
            ->describe('Remove files and database records of soft deleted resource files');

        $consoleKernel
            ->command('rf:house-keeping:queue-non-cloud-for-upload', function (Dispatcher $dispatcher) {
                $dispatcher->dispatch(new QueueNonCloudForUpload());
            })
            ->describe('Queue non cloud files for upload');
    }

    protected function scheduleJobs()
    {
        $this->app->booted(function () {
            /** @var Schedule $schedule */
            $schedule = $this->app[Schedule::class];

            $schedule->job(DeleteUnusedLocals::class)->daily();
            $schedule->job(ForceDeleteSoftDeleted::class)->daily();
            $schedule->job(QueueNonCloudForUpload::class)->everyFiveMinutes();
        });
    }

    protected function registerJobProcessingSubscriber()
    {
        /** @var \Illuminate\Contracts\Events\Dispatcher $eventDispatcher */
        $eventDispatcher = $this->app[\Illuminate\Events\Dispatcher::class];

        $eventDispatcher->subscribe($this->app[JobProcessingSubscriber::class]);
    }
}
