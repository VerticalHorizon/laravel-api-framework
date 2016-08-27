<?php

use Illuminate\Http\Request;

$available_versions = '(1|2|3)';

Route::pattern('id', '[0-9]+');
Route::pattern('version', '[0-9]+');
Route::pattern('alias', '[0-9a-z_-]+');

/* map api routes */
Route::group([
    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api/v{version}',
], function ($router) {

        // index
        Route::get('/users', function (Request $request, $version) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $version))->index();
        })->name('api.index');

        // store
        Route::post('/users', function (Request $request, $version) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $version))->store($request);
        })->name('api.store');

        // show
        Route::get('/users/{id}', function (Request $request, $version, $id) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $version))->show($id);
        })->name('api.show');

        // update
        Route::put('/users/{id}', function (Request $request, $version, $id) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $version))->update($request, $id);
        })->name('api.update');

        // destroy
        Route::delete('/users/{id}', function (Request $request, $version, $id) {
            return (new \Karellens\LAF\Http\Controllers\ApiController($request, $version))->destroy($id);
        })->name('api.destroy');

});