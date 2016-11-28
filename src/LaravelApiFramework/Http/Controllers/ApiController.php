<?php

namespace Karellens\LAF\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Karellens\LAF\Facades\ApiResponse;
use Karellens\LAF\Facades\QueryMap;
use Karellens\LAF\Facades\ReflectionModel;

class ApiController extends Controller
{
    protected $version;

    protected $alias;

    protected $modelClass;

    /**
     * Setup api version
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function __construct(Request $request)
    {
        $route_name = Route::currentRouteName();

        if($route_name) {
            list($this->version, $this->alias, $action) = explode('.', $route_name);

            $this->modelClass = ReflectionModel::getClassByAlias($this->alias);
        }
    }

    protected function getPageSize()
    {
        $pagesize = (int)request()->input('pagesize', config('api.default_pagesize'));
        return $pagesize > config('api.max_pagesize') ? config('api.max_pagesize') : $pagesize;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        QueryMap
            ::setModelClass($this->modelClass)
            ->handleFields(request()->input('fields'))
            ->handleFilters(request()->input('filter'))
            ->handleOrders(request()->input('order'))
        ;

        return ApiResponse::paginate(QueryMap::getQuery());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $object = new $this->modelClass($request->json()->all());
        $object->save();

        return ApiResponse::message(200, 'Resource #'.$object->id.' created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try
        {
            QueryMap
                ::setModelClass($this->modelClass)
                ->handleFields(request()->input('fields'))
                ->handleRelationsFilters(request()->input('filter'))
            ;

            return QueryMap::getQuery()->where('id', '=', $id)->firstOrFail()->toArray();
        }
        catch (ModelNotFoundException $e)
        {
            return ApiResponse::message(404, $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try
        {
            $object = call_user_func([$this->modelClass, 'findOrFail'], $id);
        }
        catch (ModelNotFoundException $e)
        {
            return ApiResponse::message(404, $e->getMessage());
        }

        $object->fill($request->json()->all());
        $object->save();

        return ApiResponse::message(200, 'Resource #'.$id.' updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try
        {
            $object = call_user_func([$this->modelClass, 'findOrFail'], $id);
            $object->delete();
        }
        catch (ModelNotFoundException $e)
        {
            return ApiResponse::message(404, $e->getMessage());
        }

        return ApiResponse::message(200, 'Resource #'.$id.' deleted!');
    }
}
