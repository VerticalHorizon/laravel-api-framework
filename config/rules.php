<?php

return [

    /**
     * version can be empty
     */
    'v1' => [

        /**
         * only allowable routes
         */
        'users' => [

            /**
             * by default we have 5 actions belong to ApiController: index, show, store, update, destroy.
             * now we declare only restricted of them
             * and add any custom actions from other Controller that can extends ApiController
             * detailed example:
             *
             * 'descendants_of' => {
             *      'controller'    => 'AreaController',
             *      'middleware'    => 'auth:api',
             *      'method'        => 'get',
             *      'postfix'       => 'descendants_of/{id}', // or it generate by action name
             * },
             */
            'show'      => false,       // blocked action
            'store'     => ['middleware' => 'auth:api'],
            'update'    => ['middleware' => 'auth:api'],
            'destroy'   => ['middleware' => 'auth:api'],
        ],
    ],
];
