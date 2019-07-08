<?php
/**
 * lel since 2019-07-08
 */

namespace Tests\Unit\EDC\Import;

use App\EDC\Import\ProductImageLoader;
use App\EDCProduct;
use App\EDCProductImage;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageLoaderTest extends TestCase
{
    use DatabaseTransactions;

    /** @var EDCProduct */
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake(Storage::getDefaultCloudDriver());

        $this->product = factory(EDCProduct::class)->create();
    }

    public function testNewImage()
    {
        $etag = '5abb083f-b88e';
        $filename = 'foobar.png';
        $baseURI = 'https://schmoogle.com/lel';

        config()->set('edc.imageBaseURI', $baseURI);

        $requestContainer = [];
        $mockHandler = new MockHandler([
            new Response(200, ['ETag' => $etag], fopen(base_path('docs/fixtures/discountoverview.csv'), 'r'))
        ]);

        $handler = HandlerStack::create($mockHandler);
        $handler->push(Middleware::history($requestContainer));

        $client = new Client(['handler' => $handler]);

        $loader = $this->app->make(ProductImageLoader::class, ['httpClient' => $client]);
        $loader->loadImages($this->product, [$filename]);

        // assert requests
        static::assertCount(1, $requestContainer);
        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        static::assertEquals("$baseURI/$filename", (string)$request->getUri());

        // assert image models
        $this->product->refresh();
        $images = $this->product->images;
        static::assertCount(1, $images);

        /** @var EDCProductImage $image */
        $image = $images[0];
        static::assertInstanceOf(EDCProductImage::class, $image);
        static::assertEquals($this->product->id, $image->product_id);
        static::assertEquals($etag, $image->etag);
        static::assertEquals($filename, $image->filename);
        static::assertNotNull($image->file);
    }

    public function testExistingUnchangedImage()
    {
        $etag = '5abb083f-b88e';
        $filename = 'foobar.png';
        $baseURI = 'https://schmoogle.com/lel';

        /** @var EDCProductImage $image */
        $image = factory(EDCProductImage::class)->create([
            'etag' => $etag, 'product_id' => $this->product->id, 'filename' => $filename,
        ]);

        $imageRFID = $image->file_id;

        config()->set('edc.imageBaseURI', $baseURI);

        $requestContainer = [];
        $mockHandler = new MockHandler([
            new Response(200, ['ETag' => $etag], fopen(base_path('docs/fixtures/discountoverview.csv'), 'r'))
        ]);

        $handler = HandlerStack::create($mockHandler);
        $handler->push(Middleware::history($requestContainer));

        $client = new Client(['handler' => $handler]);

        $loader = $this->app->make(ProductImageLoader::class, ['httpClient' => $client]);
        $loader->loadImages($this->product, [$filename]);

        // assert requests
        static::assertCount(1, $requestContainer);
        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        static::assertEquals("$baseURI/$filename", (string)$request->getUri());
        static::assertEqualsIgnoringCase('head', $request->getMethod());

        // assert image models
        $this->product->refresh();
        $images = $this->product->images;
        static::assertCount(1, $images);

        /** @var EDCProductImage $image */
        $image = $images[0];
        static::assertEquals($imageRFID, $image->file_id);
    }

    public function testExistingChangedImage()
    {
        $etag = '5abb083f-b88e';
        $newEtag = 'neu-b88e';
        $filename = 'foobar.png';
        $baseURI = 'https://schmoogle.com/lel';

        /** @var EDCProductImage $image */
        $image = factory(EDCProductImage::class)->create([
            'etag' => $etag, 'product_id' => $this->product->id, 'filename' => $filename,
        ]);

        $imageRFID = $image->file_id;

        config()->set('edc.imageBaseURI', $baseURI);

        $requestContainer = [];
        $mockHandler = new MockHandler([
            new Response(200, ['ETag' => $newEtag], fopen(base_path('docs/fixtures/discountoverview.csv'), 'r')),
            new Response(200, ['ETag' => $newEtag], fopen(base_path('docs/fixtures/discountoverview.csv'), 'r'))
        ]);

        $handler = HandlerStack::create($mockHandler);
        $handler->push(Middleware::history($requestContainer));

        $client = new Client(['handler' => $handler]);

        $loader = $this->app->make(ProductImageLoader::class, ['httpClient' => $client]);
        $loader->loadImages($this->product, [$filename]);

        // assert requests
        static::assertCount(2, $requestContainer);
        /** @var Request $request */
        $request = $requestContainer[0]['request'];
        static::assertEquals("$baseURI/$filename", (string)$request->getUri());
        static::assertEqualsIgnoringCase('head', $request->getMethod());

        $request = $requestContainer[1]['request'];
        static::assertEquals("$baseURI/$filename", (string)$request->getUri());
        static::assertEqualsIgnoringCase('get', $request->getMethod());

        // assert image models
        $this->product->refresh();
        $images = $this->product->images;
        static::assertCount(1, $images);

        /** @var EDCProductImage $image */
        $image = $images[0];
        static::assertNotEquals($imageRFID, $image->file_id);
        static::assertEquals($newEtag, $image->etag);
    }
}
