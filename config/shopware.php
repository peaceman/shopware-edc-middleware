<?php
/**
 * lel since 2019-07-20
 */

return [
    'baseUri' => env('SHOPWARE_BASE_URI'),
    'auth' => [
        'username' => env('SHOPWARE_AUTH_USERNAME'),
        'apiKey' => env('SHOPWARE_AUTH_APIKEY'),
    ],
    'order' => [
        'open' => [
            'requirements' => [
                [
                    'status' => env('SHOPWARE_ORDER_STATUS_OPEN'),
                    'cleared' => env('SHOPWARE_ORDER_CLEARED_COMPLETELY_PAID'),
                ],
                [
                    'status' => env('SHOPWARE_ORDER_STATUS_OPEN'),
                    'cleared' => env('SHOPWARE_ORDER_CLEARED_COMPLETELY_INVOICED'),
                ],
            ],
        ],
    ],
];
