<?php

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Karellens\LAF\Facades\Rules;

$available_actions =
    [
        'index'     => ['', 'get'],
        'store'     => ['', 'post'],
        'show'      => ['{id}', 'get'],
        'update'    => ['{id}', 'put'],
        'destroy'   => ['{id}', 'delete'],
    ];

Route::pattern('version', '[0-9]+');
Route::pattern('id', '[0-9]+');
Route::pattern('entity', '[0-9a-z_-]+');    // plural

/* map api routes */
Route::group([
    'middleware' => ['api', 'laf'],
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api',
], function (Router $router) use ($available_actions) {


    $rules_config = Rules::getRules();

    // generate routes
    foreach ($rules_config as $version => &$entities) {
        foreach ($entities as $entity => &$rules) {
            // $entity  ~> users
            // $rules   ~> ['store' => 'auth:api', 'show' => false, 'OtherController@index' => 'smthng',]

            foreach ($available_actions as $name => &$seg_act) {
                // $name    ~> 'index'  ||  'OtherController@index'
                // $seg_act      ~> ['', 'get']

                // skip blocked action ('show' => false)
                if(Rules::isActionBlocked($version.'.'.$entity.'.'.$name)) {
                    continue;
                }

                // return CustomController@action or false
                $custom_controller_action = Rules::getCustomControllerAndAction($version.'.'.$entity.'.'.$name);
                $rule = Rules::getRule($version.'.'.$entity.'.'.$name);
                $default_controller = function (Request $request, $id = null) use ($entity, $name)  {
                    return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity))->{$name}($request, $id);
                };
                $route_attributes = [
                    'as'    => $version.'.'.$entity.'.'.$name,          // '.users.index' if no version
                ];

                // appy middleware
                if(strlen($rule)) {
                    $route_attributes['middleware'] = $rule;
                }

                if($custom_controller_action) {
                    $route_attributes['uses'] = $custom_controller_action;
                }
                else {
                    array_push($route_attributes, $default_controller);
                }

//                var_dump($route_attributes);
                $router->{$seg_act[1]}(                    // http method
                    $version.'/'.$entity.'/'.$seg_act[0],  // uri segment
                    $route_attributes
                );
            }


        }

    }

//    $router->any('{entity}/{id?}', function (Request $request, $version, $entity, $id = null) {
//        $action = '';
//        switch ($request->getMethod()) {
//            // index
//            case 'GET' && $id ===null:
//                $action = 'index';
//                break;
//
//            // store
//            case 'POST' && $id === null:
//                $action = 'store';
//                break;
//
//            //show
//            case 'GET' && $id !== null:
//                $action = 'show';
//                break;
//
//            // update
//            case 'PUT' && $id !== null:
//                $action = 'update';
//                break;
//
//            // destroy
//            case 'DELETE' && $id !== null:
//                $action = 'destroy';
//                break;
//        }
//
//        return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->index();
//    });
//
//        // store
//        Route::post('/', function (Request $request, $version, $entity) {
//            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->store($request);
//        })->name('api.store');
//
//        // show
//        Route::get('/{id}', function (Request $request, $version, $entity, $id) {
//            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->show($id);
//        })->name('api.show');
//
//        // update
//        Route::put('/{id}', function (Request $request, $version, $entity, $id) {
//            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->update($request, $id);
//        })->name('api.update');
//
//        // destroy
//        Route::delete('/{id}', function (Request $request, $version, $entity, $id) {
//            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->destroy($id);
//        })->name('api.destroy');

});