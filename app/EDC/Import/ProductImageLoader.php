<?php
/**
 * lel since 2019-07-08
 */

namespace App\EDC\Import;

use App\EDCProduct;
use App\EDCProductImage;
use App\ResourceFile\StorageDirector;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProductImageLoader
{
    /** @var LoggerInterface */
    protected $logger;

    /** @var Client */
    protected $httpClient;

    /** @var StorageDirector */
    protected $storageDirector;

    /** @var string */
    protected $baseURI;

    public function __construct(LoggerInterface $logger, Client $httpClient, StorageDirector $storageDirector)
    {
        $this->logger = $logger;
        $this->httpClient = $httpClient;
        $this->storageDirector = $storageDirector;
    }

    public function setBaseURI(string $baseURI): void
    {
        $this->baseURI = $baseURI;
    }

    public function loadImages(EDCProduct $product, array $filenames): void
    {
        $startTime = microtime(true);
        $loggingContext = [
            'product' => $product->asLoggingContext(),
            'filenames' => $filenames,
            'baseURI' => $this->baseURI,
        ];
        $this->logger->info('ProductImageLoader: start loading images', $loggingContext);

        foreach ($filenames as $filename) {
            $this->loadImage($product, $filename);
        }

        $this->logger->info('ProductImageLoader: finished loading images', array_merge($loggingContext, [
            'elapsed' => microtime(true) - $startTime,
        ]));
    }

    protected function loadImage(EDCProduct $product, string $filename): void
    {
        /** @var EDCProductImage $image */
        if ($image = $product->images->firstWhere('filename', $filename)) {
            $this->tryUpdatingImage($image);
            return;
        }

        try {
            $imageURI = "{$this->baseURI}/$filename";

            $this->logger->info('ProductImageLoader: load image', [
                'imageURI' => $imageURI,
            ]);

            retry(3, function () use ($product, $filename, $imageURI) {
                $response = $this->httpClient->get($imageURI);

                $image = $this->makeImage($filename, $response);
                $product->images()->save($image);
            }, .5);
        } catch (\Exception $e) {
            report($e);
        }
    }

    protected function makeImage(string $filename, ResponseInterface $response): EDCProductImage
    {
        $imageRF = $this->storageDirector->createFileFromStream($filename, $response->getBody());

        $image = new EDCProductImage();
        $image->filename = $filename;
        $image->file()->associate($imageRF);
        $image->etag = head($response->getHeader('etag') ?? ['unknown']);

        return $image;
    }

    protected function tryUpdatingImage(EDCProductImage $image): void
    {
        try {
            $imageURI = "{$this->baseURI}/{$image->filename}";

            $this->logger->info('ProductImageLoader: try updating image', [
                'image' => $image->asLoggingContext(),
                'imageURI' => $imageURI,
            ]);

            $newImageAvailable = retry(3, function () use ($image, $imageURI) {
                $response = $this->httpClient->head($imageURI);
                $etagHeader = current($response->getHeader('etag'));

                return $etagHeader !== $image->etag;
            }, .5);

            if (!$newImageAvailable) return;

            retry(3, function () use ($image, $imageURI) {
                $response = $this->httpClient->get($imageURI);
                $newImage = $this->makeImage($image->filename, $response);

                $image->setRawAttributes($newImage->only('file_id', 'etag'));
                $image->save();
            }, .5);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
