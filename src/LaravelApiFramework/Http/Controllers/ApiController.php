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
    public function index(Request $request)
    {
        $order = request()->input('order');
        $order = $order ==  ':order' ? null : $order;

        QueryMap
            ::setModelClass($this->modelClass)
            ->handleFields(request()->input('fields'))
            ->handleFilters(request()->input('filter'))
            ->handleOrders($order)
        ;

        $pagesize = $this->getPageSize();

        if(strpos($request->header('Accept'), 'application/hal+json') !== false)  {
            return QueryMap::getQuery()->paginate( $pagesize )->toArray();
        } else {
            $page = (int)$request->input('page', 1);
            return QueryMap::getQuery()->skip( ($page-1)*$pagesize )->take( $pagesize )->get()->toArray();
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $object = new $this->modelClass();

        $qm = QueryMap
            ::setModelClass($this->modelClass);

        // get own fields
        $fields = $request->only(
            QueryMap::extractFields(
                array_keys($request->json()->all())
            )
        );

        $object->fill($fields);

        // get relations
        $relations = $request->only(
            QueryMap::extractRelations(
                array_keys($request->json()->all())
            )
        );

        // first apply BelongsTo relations. before saving main entity
        if(count($relations)) {
            foreach ($relations as $name => $values) {
                $relation_class = get_class($object->{$name}());
                $foreignModel = ReflectionModel::getForeignModel($this->modelClass, $name);

                if($relation_class == 'Illuminate\Database\Eloquent\Relations\BelongsTo') {

                    // SiteString hack
                    if('SiteString' == $foreignModel) {

                        $foreignModel = ReflectionModel::getClassByAlias($foreignModel);

                        $newForeignModel = new $foreignModel();
                        $newForeignModel->fill(['text' => $values]);
                        $newForeignModel->save();

                        $object->{$name}()->associate($newForeignModel);
                    }
                    else {
                        // other belongsTo
                        $object->{$name}()->associate((int) $values);
                    }

                }
            }
        }

        $object->save();

        // belongsToMany
        if(count($relations)) {
            foreach ($relations as $name => $values) {

                $relation_class = get_class($object->{$name}());

                if($relation_class == 'Illuminate\Database\Eloquent\Relations\BelongsToMany') {
                    $ids = array_column($values, 'id');
                    $this->delete_column($values, 'id');
                    $values = array_combine($ids, $values);

                    $object->{$name}()->sync($values);
                }

            }
        }

        return with(new $this->modelClass())
            ->with(array_keys($relations))
            ->findOrFail((int) $object->id)->toArray();
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
            return ApiResponse::error($e->getMessage(), 404);
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

        $qm = QueryMap
            ::setModelClass($this->modelClass);

        // get own fields
        $fields = $request->only(
            QueryMap::extractFields(
                array_keys($request->json()->all())
            )
        );

        // get relations
        $relations = $request->only(
            QueryMap::extractRelations(
                array_keys($request->json()->all())
            )
        );

        try
        {
            $object = with(new $this->modelClass())
                ->with(array_keys($relations))
                ->findOrFail((int) $id);
        }
        catch (ModelNotFoundException $e)
        {
            return ApiResponse::error($e->getMessage(), 404);
        }

        $object->fill($fields);

        // first apply BelongsTo relations. before saving main entity
        if(count($relations)) {
            foreach ($relations as $name => $values) {
                $relation_class = get_class($object->{$name}());
                $foreignModel = ReflectionModel::getForeignModel($this->modelClass, $name);

                if($relation_class == 'Illuminate\Database\Eloquent\Relations\BelongsTo') {

                    // SiteString hack
                    if('SiteString' == $foreignModel) {
                        if($object->{$name}) {
                            $object->{$name}->fill(['text' => $values])->save();
                        } else {
                            $foreignModel = ReflectionModel::getClassByAlias($foreignModel);

                            $newForeignModel = new $foreignModel();
                            $newForeignModel->fill(['text' => $values]);
                            $newForeignModel->save();

                            $object->{$name}()->associate($newForeignModel);
                        }

                    }
                    else {
                        // other belongsTo
                        $object->{$name}()->associate((int) $values);
                    }

                }
            }
        }

        $object->save();

        // belongsToMany
        if(count($relations)) {
            foreach ($relations as $name => $values) {

                $relation_class = get_class($object->{$name}());
                $foreignModel = ReflectionModel::getForeignModel($this->modelClass, $name);

                if($relation_class == 'Illuminate\Database\Eloquent\Relations\BelongsToMany' && 'Userfilegroup' == $foreignModel) {
                    $ids = array_column($values, 'id');
                    $object->{$name}()->sync($ids);
                }
                elseif($relation_class == 'Illuminate\Database\Eloquent\Relations\BelongsToMany') {
                    $ids = array_column($values, 'id');
                    $this->delete_column($values, 'id');
                    $values = array_combine($ids, $values);

                    $object->{$name}()->sync($values);
                }

            }
        }

        return with(new $this->modelClass())
            ->with(array_keys($relations))
            ->findOrFail((int) $id)->toArray();
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
            return ApiResponse::error($e->getMessage(), 404);
        }

        return ApiResponse::success('Resource #'.$id.' deleted!', 200);
    }

    private function delete_column(&$array, $key) {
        return array_walk($array, function (&$v) use ($key) {
            unset($v[$key]);
        });
    }
}
