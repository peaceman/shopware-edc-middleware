<?php
/**
 * lel since 2019-07-08
 */

namespace App\Http\Controllers;

use App\EDCProductImage;
use App\ResourceFile\StorageDirector;

class ProductImageController extends Controller
{
    public function __invoke(StorageDirector $storageDirector, string $identifier)
    {
        /** @var EDCProductImage $epi */
        $epi = EDCProductImage::withIdentifier($identifier)->firstOrFail();

        return response()->download(
            $storageDirector->getLocalPath($epi->file),
            $epi->file->original_filename,
            [
                'content-type' => $epi->file->mime_type,
                'etag' => $epi->etag
            ]
        );
    }
}
