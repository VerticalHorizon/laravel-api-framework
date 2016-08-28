<?php

namespace Karellens\LAF\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Route;
use Karellens\LAF\ApiResponse;
use Karellens\LAF\Http\Exceptions\DataNotReceivedException;
use Karellens\LAF\Facades\QueryMap;
use Karellens\LAF\ReflectionModel;

class ApiController extends Controller
{
    protected $version;

    protected $alias;

    protected $modelClass;

    /**
     * Setup api version
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $version
     * @return void
     */
    public function __construct(Request $request, $entity = '',  $version = null, $id = null)
    {
        $this->version = $version;
        $this->alias = $entity;

        $this->modelClass = (new ReflectionModel($this->alias))->getClass();
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

        return QueryMap::getQuery()->paginate( $this->getPageSize() );
        // return (new ApiResponse())->paginate(\App\User::query());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = new \App\User($request->all());
        $user->save();

        return (new ApiResponse())->error(200, 'Resource #'.$user->id.' created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try
        {
            dd(get_class( \App\User::where('id', '=', $id) ));
            return \App\User::where('id', '=', $id)->firstOrFail();
        }
        catch (ModelNotFoundException $e)
        {
            return (new ApiResponse())->error(404, $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
            $user = \App\User::findOrFail($id);
        }
        catch (ModelNotFoundException $e)
        {
            return (new ApiResponse())->error(404, $e->getMessage());
        }

        $user->fill($request->all());
        $user->save();

        return (new ApiResponse())->error(200, 'Resource #'.$id.' updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try
        {
            \App\User::findOrFail($id)->delete();
        }
        catch (ModelNotFoundException $e)
        {
            return (new ApiResponse())->error(404, $e->getMessage());
        }

        return (new ApiResponse())->error(200, 'Resource #'.$id.' deleted!');
    }
}
