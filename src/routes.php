<?php

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Karellens\PrettyApi\ApiResponse;
use Karellens\PrettyApi\Http\Exceptions\DataNotReceivedException;


Route::pattern('id', '[0-9]+');
Route::pattern('alias', '[0-9a-z_-]+');

/* map api routes */
Route::group([
    'middleware' => 'api',
    'namespace' => 'App\Http\Controllers',
    'prefix' => 'api',
], function ($router) {


    Route::group(['prefix' => 'v1'], function () {

        // index
        Route::get('/users', function (Request $request) {
            return App\User::all();
        });

        // store
        Route::post('/users', function (Request $request) {
            try
            {
                if($request->isJson())
                {
                    $user = new App\User($request->all());
                }
                else
                {
                    throw new DataNotReceivedException('Bad request! No data received.');
                }

                $user->save();

                return (new ApiResponse())->error(200, 'Resource #'.$user->id.' created!');
            }
            catch (DataNotReceivedException $e)
            {
                return (new ApiResponse())->error(400, $e->getMessage());
            }
        });

        // show
        Route::get('/users/{id}', function (Request $request, $id) {
            try
            {
                return App\User::findOrFail($id);
            }
            catch (ModelNotFoundException $e)
            {
                return (new ApiResponse())->error(404, $e->getMessage());
            }
        });

        // update
        /**
         * url: resource/id
         * body: data: {...}
         * */
        Route::put('/users/{id}', function (Request $request, $id) {
            try
            {
                $user = App\User::findOrFail($id);
            }
            catch (ModelNotFoundException $e)
            {
                return (new ApiResponse())->error(404, $e->getMessage());
            }

            try
            {
                if($request->isJson())
                {
                    $user->fill($request->all());
                }
                else
                {
                    throw new DataNotReceivedException('Bad request! No data received.');
                }
            }
            catch (DataNotReceivedException $e)
            {
                return (new ApiResponse())->error(400, $e->getMessage());
            }

            $user->save();

            return (new ApiResponse())->error(200, 'Resource #'.$id.' updated!');
        });

        // destroy
        Route::delete('/users/{id}', function (Request $request, $id) {
            try
            {
                App\User::findOrFail($id)->delete();
                return (new ApiResponse())->error(200, 'Resource #'.$id.' deleted!');
            }
            catch (ModelNotFoundException $e)
            {
                return (new ApiResponse())->error(404, $e->getMessage());
            }
        });
    });

});