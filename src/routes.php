<?php

use Illuminate\Http\Request;

$available_versions = '(1|2|3)';

Route::pattern('version', '[0-9]+');
Route::pattern('id', '[0-9]+');
Route::pattern('entity', '[0-9a-z_-]+');    // plural

/* map api routes */
Route::group([
    'middleware' => ['api', 'laf'],
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/v{version}/{entity}',
], function ($router) {

        // index
        Route::get('/', function (Request $request, $version, $entity) {
//            dd($entity);
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->index();
        })->name('api.index');

        // store
        Route::post('/', function (Request $request, $version, $entity) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->store($request);
        })->name('api.store');

        // show
        Route::get('/{id}', function (Request $request, $version, $entity, $id) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->show($id);
        })->name('api.show');

        // update
        Route::put('/{id}', function (Request $request, $version, $entity, $id) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->update($request, $id);
        })->name('api.update');

        // destroy
        Route::delete('/{id}', function (Request $request, $version, $entity, $id) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $entity, $version))->destroy($id);
        })->name('api.destroy');

});