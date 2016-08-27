<?php

namespace Karellens\LAF\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\Route;
use Karellens\LAF\ApiResponse;
use Karellens\LAF\Http\Exceptions\DataNotReceivedException;

class ApiController extends Controller
{
    protected $version;
    /**
     * Setup api version
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $version
     * @return void
     */
    public function __construct(Request $request, $version = null, $id = null)
    {
        $this->version = $version;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        dd(get_class(\App\User::get()));
        return \App\User::get();
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
        try
        {
            if($request->isJson())
            {
                $user = new \App\User($request->all());
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
            dd(get_class( \App\User::where('id', '=', $id)->firstOrFail() ));
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
            return (new ApiResponse())->error(200, 'Resource #'.$id.' deleted!');
        }
        catch (ModelNotFoundException $e)
        {
            return (new ApiResponse())->error(404, $e->getMessage());
        }
    }
}
