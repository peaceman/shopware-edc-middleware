<?php
/**
 * lel since 2019-07-08
 */

namespace Tests\Feature;

use App\EDCProductImage;
use App\ResourceFile\StorageDirector;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use function App\Utils\fixture_path;

class ProductImageControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @var StorageDirector */
    protected $storageDirector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->localFS = Storage::fake('local');
        $this->cloudFS = Storage::fake(Storage::getDefaultCloudDriver());

        $this->storageDirector = $this->app->make(StorageDirector::class);
    }

    public function testImageRetrieval()
    {
        $imageRF = $this->storageDirector->createFileFromPath(
            'topkek.png', fixture_path('Screen Shot 2019-06-25 at 18.40.45 PM.png')
        );

        /** @var EDCProductImage $epi */
        $epi = factory(EDCProductImage::class)->create(['file_id' => $imageRF]);

        $response = $this->get(route('product-images', [$epi->identifier]));
        $response->assertOk();
        $response->assertHeader('content-type', $imageRF->mime_type);
        $response->assertHeader('etag', $epi->etag);
        $response->assertHeader('content-length', $imageRF->size);
    }
}
