<?php

return [
    'v1' => [
        // only allowable routes
        'users' => [
            // only restricted methods
            'show'      => false,
            'store'     => 'auth:api',
            'update'    => 'auth:api',
            'destroy'   => 'auth:api',
        ],
    ],
];
