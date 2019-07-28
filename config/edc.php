<?php

use App\EDCFeed;

return [
    'feedURI' => [
        EDCFeed::TYPE_DISCOUNTS => env('EDC_FEED_DISCOUNTS', 'https://plx-fill.me'),
        EDCFeed::TYPE_PRODUCTS => env('EDC_FEED_PRODUCTS', 'https://plx-fill.me'),
        EDCFeed::TYPE_PRODUCT_STOCKS => env('EDC_FEED_PRODUCT_STOCKS', 'https://plx-fill.me'),
    ],
    'imageBaseURI' => env('EDC_IMAGE_BASE_URI', 'https://plx-fill.me/500'),
    'orderExportURI' => env('EDC_ORDER_EXPORT_URI', 'https://www.erotikgrosshandel.de/ao/'),
    'orderUpdateAuthToken' => env('EDC_ORDER_UPDATE_AUTH_TOKEN', 'this should be random'),
    'api' => [
        'email' => env('EDC_API_EMAIL', 'testaccount@edc-internet.nl'),
        'key' => env('EDC_API_KEY', '7651320RK8RD972HR966Z40752DDKZKK'),
    ],
    'countryMap' => [
        'NL' => 1,
        'BE' => 2,
        'DE' => 3,
        'UK' => 4,
        'GB' => 4,
        'FR' => 5,
        'LU' => 6,
        'AT' => 7,
        'PT' => 8,
        'ES' => 9,
        'CH' => 10,
        'SE' => 11,
        'IT' => 12,
        'AD' => 13,
        'AR' => 14,
        'AW' => 15,
        'BA' => 16,
        'BR' => 17,
        'BG' => 18,
        'CA' => 19,
        'HR' => 20,
        'CY' => 21,
        'CZ' => 22,
        'DK' => 23,
        'EE' => 24,
        'GR' => 25,
    ]
];
