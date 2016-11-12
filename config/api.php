<?php

return [
    'acceptable_headers'   =>
    [
        'application/json',
        'application/hal+json',
    ],

    'models_namespaces' => [
        '\App\\', '\App\Models\\'
    ],

    'custom_relations'  => [],

    'default_pagesize'  => 9,
    'max_pagesize'		=> 100,
];
