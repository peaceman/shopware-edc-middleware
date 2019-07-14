<?php

use App\EDCFeed;

return [
    'feedURI' => [
        EDCFeed::TYPE_DISCOUNTS => env('EDC_FEED_DISCOUNTS', 'https://plx-fill.me'),
        EDCFeed::TYPE_PRODUCTS => env('EDC_FEED_PRODUCTS', 'https://plx-fill.me'),
        EDCFeed::TYPE_PRODUCT_STOCKS => env('EDC_FEED_PRODUCT_STOCKS', 'https://plx-fill.me'),
    ],
    'imageBaseURI' => env('EDC_IMAGE_BASE_URI', 'https://plx-fill.me/500'),
];
