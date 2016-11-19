<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Karellens\LAF\Facades\Rules;

Route::pattern('id', '[0-9]+');

/* map api routes */
Route::group([
    'middleware' => ['api', 'laf'],
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api',
], function (Router $router) {


    $rules_config = Rules::getRules();

    // generate routes
    foreach ($rules_config as $version => &$entities) {
        foreach ($entities as $entity => &$actions) {
            // $entity  ~> users
            // $actions   ~> ['store' => ['middleware' => 'auth:api'], 'show' => false,]

            foreach ($actions as $action => &$rules) {
                // skip blocked action ('show' => false)
                if(Rules::isActionBlocked($version.'.'.$entity.'.'.$action)) {
                    continue;
                }

                // assign Custom Controller or Default Controller
                if(!$controller = Rules::getCustomController($version.'.'.$entity.'.'.$action)) {
                    $controller = '\Karellens\LAF\Http\Controllers\ApiController';
                }

                // set route name
                $route_attributes = [
                    'as'    => $version.'.'.$entity.'.'.$action,          // '.users.index' if no version
                ];

                // apply middleware
                if(isset($rules['middleware'])) {
                    $route_attributes['middleware'] = $rules['middleware'];
                }

                $route_attributes['uses'] = $controller.'@'.$action;

                $router->match(
                    $rules['method'],
                    $version.'/'.$entity.'/'. (isset($rules['postfix']) ? $rules['postfix'] : $action),  // uri segment
                    $route_attributes
                );
            }

        }
    }
});