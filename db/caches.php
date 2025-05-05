<?php
$definitions = [
    'api_responses' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => false,
        'simpledata' => false,
        'ttl' => 1800, // 30 minutes
        'staticacceleration' => true,
        'staticaccelerationsize' => 50
    ],
    'category_structure' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => false,
        'ttl' => 3600, // 1 hour
        'staticacceleration' => true
    ],
    'filter_results' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => false,
        'simpledata' => false,
        'ttl' => 900, // 15 minutes
        'staticacceleration' => true
    ],
    'event_mapping' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 86400, // 1 day
        'staticacceleration' => true
    ],
    'user_mapping' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 86400, // 1 day
        'staticacceleration' => true
    ]
];