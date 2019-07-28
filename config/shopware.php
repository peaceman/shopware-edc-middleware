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
    'status' => [
        'order' => [
            'open' => env('SHOPWARE_ORDER_STATUS_OPEN'),
            'inProcess' => env('SHOPWARE_ORDER_STATUS_IN_PROCESS'),
            'completed' => env('SHOPWARE_ORDER_STATUS_COMPLETED'),
            'clarificationRequired' => env('SHOPWARE_ORDER_STATUS_CLARIFICATION_REQUIRED'),
        ],
    ],
];
