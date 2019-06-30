<?php
/**
 * lel since 2019-06-30
 */

namespace Tests\Unit\ResourceFile;

use App\ResourceFile\Jobs\UpdateLastAccessTime;
use App\ResourceFile\Jobs\UploadToCloud;
use App\ResourceFile\ResourceFile;
use App\ResourceFile\StorageDirector;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageDirectorTest extends TestCase
{
    use DatabaseTransactions;

    /** @var Filesystem */
    protected $localFS;

    /** @var Filesystem */
    protected $cloudFS;

    /** @var StorageDirector */
    protected $storageDirector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localFS = Storage::fake('local');
        $this->cloudFS = Storage::fake(Storage::getDefaultCloudDriver());

        $this->storageDirector = $this->app->make(StorageDirector::class);
    }

    public function testNewFileFlow()
    {
        $rf = $this->storageDirector->newFileWithName('foo-the-bar.txt');
        static::assertNotEmpty($rf->path);

        $localPath = $this->storageDirector->genLocalPath($rf);
        static::assertFileNotExists($localPath);

        file_put_contents($localPath, 'hi');
        $this->storageDirector->persistFile($rf);
        static::assertTrue($rf->exists);
        static::assertEquals(['local'], $rf->instances->pluck('disk')->all());

        $this->localFS->assertExists($rf->path);
        $this->cloudFS->assertMissing($rf->path);

        static::assertNotEmpty($rf->size);
        static::assertNotEmpty($rf->mime_type);
        static::assertNotEmpty($rf->checksum);

        static::assertFileExists($this->storageDirector->getLocalPath($rf));
    }

    public function testPersistedFilesWillBeQueuedForUpload()
    {
        $rf = $this->storageDirector->newFileWithName('foo-the-bar.txt');
        $localPath = $this->storageDirector->genLocalPath($rf);
        file_put_contents($localPath, 'hi');
        $this->storageDirector->persistFile($rf);

        Queue::fake();
        $this->storageDirector->flushQueues();
        Queue::assertPushed(UploadToCloud::class, function (UploadToCloud $job) use ($rf) {
            return in_array($rf->id, $job->getRFIDs());
        });
    }

    public function testAccessedFilesWillBeQueueForUpdateLastAccessTime()
    {
        $rf = $this->storageDirector->newFileWithName('foo-the-bar.txt');
        $localPath = $this->storageDirector->genLocalPath($rf);
        file_put_contents($localPath, 'hi');
        $this->storageDirector->persistFile($rf);

        $this->storageDirector->getLocalPath($rf);

        $localRFI = $rf->instances()->where('disk', 'local')->firstOrFail();

        Queue::fake();
        $this->storageDirector->flushQueues();
        Queue::assertPushed(UpdateLastAccessTime::class, function (UpdateLastAccessTime $job) use ($localRFI) {
            return in_array($localRFI->id, $job->getRFIIDs());
        });
    }

    public function testUploadToCloud()
    {
        $rf = $this->storageDirector->createFileFromString('foo-the-bar.txt', 'topkek');
        static::assertTrue($this->localFS->exists($rf->path));
        static::assertFalse($this->cloudFS->exists($rf->path));

        $this->storageDirector->uploadToCloud($rf);
        static::assertTrue($this->localFS->exists($rf->path));
        static::assertTrue($this->cloudFS->exists($rf->path));

        static::assertTrue($rf->instances()->where('disk', 'cloud')->exists());
    }

    public function testUpdateLastAccessTime()
    {
        $rf = $this->storageDirector->createFileFromString('foo-the-bar.txt', 'topkek');
        $localRFI = $rf->localInstance;

        static::assertNull($localRFI->last_access_at);
        $this->storageDirector->updateLastAccessTime($localRFI);
        static::assertNotNull($localRFI->last_access_at);
    }

    public function testOnlyRemotelyAvailableFileWillBeDownloaded()
    {
        $rf = factory(ResourceFile::class)->create();
        $rf->instances()->create(['disk' => 'cloud']);
        $this->cloudFS->put($rf->path, 'aloha');

        $localPath = $this->storageDirector->getLocalPath($rf);
        static::assertFileExists($localPath);
        static::assertTrue($this->localFS->exists($rf->path));
    }

    public function testForceDelete()
    {
        $rf = $this->storageDirector->createFileFromString('foo-the-bar.txt', 'topkek');
        $this->storageDirector->uploadToCloud($rf);

        $this->storageDirector->forceDeleteFile($rf);
        static::assertFalse($this->localFS->exists($rf->path));
        static::assertFalse($this->cloudFS->exists($rf->path));

        static::assertFalse($rf->exists);
        static::assertFalse(ResourceFile::query()->where('id', $rf->id)->exists());
    }

    public function testDeleteFileInstance()
    {
        $rf = $this->storageDirector->createFileFromString('foo-the-bar.txt', 'topkek');
        $this->storageDirector->deleteFileInstance($rf->localInstance);

        $rf->refresh();

        static::assertFalse($this->localFS->exists($rf->path));
        static::assertNull($rf->localInstance);
    }
}
