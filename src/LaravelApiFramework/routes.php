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

                // return CustomController or false
                $custom_controller = Rules::getCustomController($version.'.'.$entity.'.'.$action);

                $default_controller = function (Request $request, $id = null) use ($entity, $action)  {
                    return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity))->{$action}($request, $id);
                };

                $route_attributes = [
                    'as'    => $version.'.'.$entity.'.'.$action,          // '.users.index' if no version
                ];

                // apply middleware
                if(isset($rules['middleware'])) {
                    $route_attributes['middleware'] = $rules['middleware'];
                }

                if($custom_controller) {
                    $route_attributes['uses'] = $custom_controller.'@'.$action;
                }
                else {
                    array_push($route_attributes, $default_controller);
                }

                $router->match(
                    $rules['method'],
                    $version.'/'.$entity.'/'. (isset($rules['postfix']) ? $rules['postfix'] : $action),  // uri segment
                    $route_attributes
                );
            }

        }
    }
});