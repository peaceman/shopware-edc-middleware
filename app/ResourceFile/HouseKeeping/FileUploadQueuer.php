<?php
/**
 * lel since 2019-07-20
 */

namespace App\ResourceFile\HouseKeeping;

use App\ResourceFile\HouseKeeping\Providers\ResourceFileProvider;
use App\ResourceFile\StorageDirector;
use Psr\Log\LoggerInterface;

class FileUploadQueuer
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var StorageDirector */
    protected $storageDirector;

    public function __construct(LoggerInterface $logger, StorageDirector $storageDirector)
    {
        $this->logger = $logger;
        $this->storageDirector = $storageDirector;
    }

    public function __invoke(ResourceFileProvider $rfs)
    {
        foreach ($rfs as $rf) {
            $this->logger->info('FileUploadQueuer: add to upload queue', [
                'rf' => $rf->asLoggingContext(),
            ]);

            $this->storageDirector->addToUploadQueue($rf);
        }
    }
}
