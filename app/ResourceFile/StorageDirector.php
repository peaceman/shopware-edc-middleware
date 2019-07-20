<?php
/**
 * lel since 2019-06-27
 */

namespace App\ResourceFile;

use App\ResourceFile\Jobs\UpdateLastAccessTime;
use App\ResourceFile\Jobs\UploadToCloud;
use Assert\Assert;
use Illuminate\Contracts\Bus\Dispatcher as JobDispatcher;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use League\Flysystem\Adapter\Local;
use Psr\Http\Message\StreamInterface;
use SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use function GuzzleHttp\Psr7\copy_to_stream;
use function GuzzleHttp\Psr7\stream_for;
use function GuzzleHttp\Psr7\try_fopen;

class StorageDirector
{
    /** @var Filesystem */
    protected $localFS;

    /** @var Filesystem */
    protected $cloudFS;

    /** @var JobDispatcher */
    protected $jobDispatcher;

    /** @var int[] */
    protected $uploadQueue = [];

    /** @var int[] */
    protected $updateLastAccessTimeQueue = [];

    public function __construct(Filesystem $localFS, Filesystem $cloudFS, JobDispatcher $jobDispatcher)
    {
        $this->setLocalFS($localFS);
        $this->setCloudFS($cloudFS);

        $this->jobDispatcher = $jobDispatcher;
    }

    protected function setLocalFS(Filesystem $filesystem): void
    {
        $this->ensureLocalAdapter($filesystem);
        $this->localFS = $filesystem;
    }

    protected function ensureLocalAdapter(Filesystem $filesystem): void
    {
        if (!$filesystem->getAdapter() instanceof Local) {
            $adapterClass = get_class($filesystem);

            $message = "Got non local filesystem adapter to use as local filesystem '{$adapterClass}''";
            throw new Exceptions\NonLocalFilesystem($message);
        }
    }

    protected function setCloudFS(Filesystem $filesystem): void
    {
        $this->cloudFS = $filesystem;
    }

    public function createFileFromString(string $filename, string $content): ResourceFile
    {
        $rf = $this->newFileWithName($filename);
        file_put_contents($this->genLocalPath($rf), $content);
        $this->persistFile($rf);

        return $rf;
    }

    public function createFileFromStream(string $filename, StreamInterface $inputStream): ResourceFile
    {
        $rf = $this->newFileWithName($filename);
        $outputSteam = stream_for(try_fopen($this->genLocalPath($rf), 'w'));
        copy_to_stream($inputStream, $outputSteam);
        $outputSteam->close();

        $this->persistFile($rf);

        return $rf;
    }

    public function createFileFromPath(string $filename, string $filePath): ResourceFile
    {
        $rf = $this->newFileWithName($filename);
        copy($filePath, $this->genLocalPath($rf));

        $this->persistFile($rf);

        return $rf;
    }

    public function newFileWithName(string $filename): ResourceFile
    {
        $rf = ResourceFile::newWithUUID();
        $rf->original_filename = $filename;
        $rf->path = $this->generateStoragePath($rf, $filename);

        return $rf;
    }

    protected function generateStoragePath(ResourceFile $rf, string $filename): string
    {
        $rf->ensureValidUUID();

        $fileInfo = new SplFileInfo($filename);
        $fileExtension = $fileInfo->getExtension();
        $fileExtensionWithDot = empty($fileExtension) ? '' : ".$fileExtension";
        $fileBasename = $fileInfo->getBasename($fileExtensionWithDot);

        $folder = 'rfs/' . $this->generateDirectoryStructureFromUUID($rf->uuid);

        return sprintf(
            '%s/%s-%s%s',
            $folder,
            Str::slug($fileBasename),
            $rf->uuid,
            $fileExtensionWithDot
        );
    }

    protected function generateDirectoryStructureFromUUID(string $uuid): string
    {
        [$firstUUIDPart,] = explode('-', $uuid);
        $pathParts = str_split($firstUUIDPart, 2);

        return implode('/', $pathParts);
    }

    public function genLocalPath(ResourceFile $rf): string
    {
        Assert::that($rf->path)->notEmpty()->string();

        $this->localFS->makeDirectory(dirname($rf->path));
        $path = $this->localFS->path($rf->path);

        return $path;
    }

    public function persistFile(ResourceFile $rf): void
    {
        Assert::that($this->localFS->exists($rf->path))->true();

        $this->setMetadataFromFilePath($rf, $this->localFS->path($rf->path));
        $rf->save();

        $rf->instances()->firstOrCreate(['disk' => 'local']);

        $this->addToUploadQueue($rf);
    }

    protected function setMetadataFromFilePath(ResourceFile $rf, string $localFilePath): void
    {
        $fileInfo = new File($localFilePath);
        $rf->size = $fileInfo->getSize();
        $rf->mime_type = $fileInfo->getMimeType();
        $rf->checksum = md5_file($localFilePath);
    }

    public function addToUploadQueue(ResourceFile $rf): void
    {
        $this->uploadQueue[] = $rf->id;
    }

    public function getLocalPath(ResourceFile $rf): string
    {
        if (!$localRFI = $rf->localInstance) {
            $this->downloadFromCloud($rf);
            $localRFI = $rf->localInstance;
        }

        $path = $this->localFS->path($rf->path);
        $this->addToUpdateLastAccessTimeQueue($localRFI);

        return $path;
    }

    protected function addToUpdateLastAccessTimeQueue(ResourceFileInstance $rfi): void
    {
        $this->updateLastAccessTimeQueue[] = $rfi->id;
    }

    public function flushQueues(): void
    {
        $this->flushUploadQueue();
        $this->flushUpdateLastAccessTimeQueue();
    }

    protected function flushUploadQueue(): void
    {
        $queue = array_unique($this->uploadQueue);
        if (empty($queue)) return;

        $job = new UploadToCloud($queue);
        $this->uploadQueue = [];

        $this->jobDispatcher->dispatch($job);
    }

    protected function flushUpdateLastAccessTimeQueue(): void
    {
        $queue = array_unique($this->updateLastAccessTimeQueue);
        if (empty($queue)) return;

        $job = new UpdateLastAccessTime($queue);
        $this->updateLastAccessTimeQueue = [];

        $this->jobDispatcher->dispatch($job);
    }

    public function uploadToCloud(ResourceFile $rf): void
    {
        Assert::that($this->localFS->exists($rf->path))->true();

        $this->cloudFS->put($rf->path, $this->localFS->readStream($rf->path));

        $rf->instances()->firstOrCreate(['disk' => 'cloud']);
    }

    public function updateLastAccessTime(ResourceFileInstance $rfi): void
    {
        $rfi->last_access_at = now();
        $rfi->save();
    }

    protected function downloadFromCloud(ResourceFile $rf): void
    {
        Assert::that($this->cloudFS->exists($rf->path))->true();

        $this->localFS->put($rf->path, $this->cloudFS->readStream($rf->path));

        $rfi = $rf->instances()->firstOrCreate(['disk' => 'local']);
        $rf->setRelation('localInstance', $rfi);
    }

    public function forceDeleteFile(ResourceFile $rf): void
    {
        if ($rf->localInstance) {
            $this->deleteFileInstance($rf->localInstance);

            $rf->unsetRelation('localInstance');
        }

        if ($rf->cloudInstance) {
            $this->deleteFileInstance($rf->cloudInstance);

            $rf->unsetRelation('cloudInstance');
        }

        $rf->forceDelete();
    }

    public function deleteFileInstance(ResourceFileInstance $rfi): void
    {
        $rfi->delete();
        $rf = $rfi->file;

        switch ($rfi->disk) {
            case 'local':
                $this->localFS->delete($rf->path);
                break;
            case 'cloud':
                $this->cloudFS->delete($rf->path);
                break;
            default:
                throw new Exceptions\UnknownDisk($rfi->disk);
        }
    }
}
