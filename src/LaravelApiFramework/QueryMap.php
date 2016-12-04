<?php

namespace Karellens\LAF;

use Karellens\LAF\Facades\ReflectionModel as RM;

class QueryMap
{
    const FIELDS_DELIMETER = ',';
    const ANDFILTERS_DELIMETER = ';';

    const COMPARATORS = [
        'not'       => '<>',
        'gt'        => '>',
        'gte'       => '>=',
        'lt'        => '<',
        'lte'       => '<=',
        'eq'        => '=',
        'in'        => 'in',
        'between'   => 'between',
        'notin'     => 'not in',
        'like'      => 'like',
        'notlike'   => 'not like',
    ];

    private $operators;
    private $pivot_operators;

    protected $query;
    protected $modelClass;

    protected $fields;
    protected $filters;
    protected $orders;

    protected $availableRelations;
    protected $relationsWithPivots;

    protected $available_scopes;

    public function __construct()
    {
        $this->operators = [
            'not'         => function($query, $field, $values){ return $query->where($field, '<>', $values[0]); },
            'gt'          => function($query, $field, $values){ return $query->where($field, '>', $values[0]); },
            'gte'         => function($query, $field, $values){ return $query->where($field, '>=', $values[0]); },
            'lt'          => function($query, $field, $values){ return $query->where($field, '<', $values[0]); },
            'lte'         => function($query, $field, $values){ return $query->where($field, '<=', $values[0]); },
            'eq'          => function($query, $field, $values){ return $query->where($field, '=', $values[0]); },
            'in'          => function($query, $field, $values){ return $query->whereIn($field, $values); },
            'notin'       => function($query, $field, $values){ return $query->whereNotIn($field, $values); },
            'between'     => function($query, $field, $values){ return $query->whereBetween($field, $values); },
            'notbetween'  => function($query, $field, $values){ return $query->whereNotBetween($field, $values); },
            'like'        => function($query, $field, $values){ return $query->where($field, 'like', '%'.$values[0].'%'); },
            'notlike'     => function($query, $field, $values){ return $query->where($field, 'not like', '%'.$values[0].'%'); },
        ];

        $this->pivot_operators = [
            'not'         => function($query, $field, $values){ return $query->wherePivot($field, '<>', $values[0]); },
            'gt'          => function($query, $field, $values){ return $query->wherePivot($field, '>', $values[0]); },
            'gte'         => function($query, $field, $values){ return $query->wherePivot($field, '>=', $values[0]); },
            'lt'          => function($query, $field, $values){ return $query->wherePivot($field, '<', $values[0]); },
            'lte'         => function($query, $field, $values){ return $query->wherePivot($field, '<=', $values[0]); },
            'eq'          => function($query, $field, $values){ return $query->wherePivot($field, '=', $values[0]); },
            'in'          => function($query, $field, $values){ return $query->wherePivotIn($field, $values); },
            'notin'       => function($query, $field, $values){ return $query->wherePivotIn($field, $values, 'and', true); },
        ];
    }

    /**
     * @param mixed $modelClass
     * @return $this
     */
    public function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;

        $this->availableRelations = RM::getRelations($this->modelClass);
        $this->relationsWithPivots = RM::getPivotColumns($this->modelClass);

        $this->setQuery( (new $this->modelClass())->query());

        return $this;
    }

    /**
     * @param mixed $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return mixed
     */
    public function getAvailableRelations()
    {
        return array_keys($this->availableRelations);
    }

    /**
     * @return mixed
     */
    public function getPivotColumns($relation_name)
    {
        return isset($this->relationsWithPivots[$relation_name]) ?
            $this->relationsWithPivots[$relation_name] :
            []
            ;
    }

    /**
     * @return $this
     */
    public function handleFields($fields)
    {
        if($fields)
        {
            $fields = self::explodeFields($fields);

            // leave only specified relations.
            // some of them can have child relations. so we compare them as `starts-with`
            $relations = self::extractFrom($fields, $this->getAvailableRelations());

            if(!empty($relations)) {
                $this->query->with($relations);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function handleFilters($filters)
    {
        if($filters)
        {
            $conditions = $this->createConditionsMap($filters);

            foreach ($conditions as $subject => $subject_conditions)
            {
                if($subject === '.')
                {
                    $this->query = $this->applyRulesToQuery($this->query, $subject_conditions);
                }
                else
                {
                    $this->query->whereHas($subject, function($query) use ($subject_conditions) {
                        return $this->applyRulesToQuery($query, $subject_conditions);
                    });
                }
            }
        }

        return $this;
    }

    /**
     * Filter just relations
     *
     * @return $this
     */
    public function handleRelationsFilters($filters)
    {
        if($filters)
        {
            $conditions = $this->createConditionsMap($filters);
            $with_map = [];

            foreach ($conditions as $subject => $subject_conditions)
            {
                if($subject !== '.')
                {
                    // $subject needed for filtering by pivot columns
                    $with_map[$subject] = function($query) use ($subject_conditions, $subject) {
                        return $this->applyRulesToQuery($query, $subject_conditions, $subject);
                    };
                }
            }

            if(!empty($with_map)) {
                $this->query->with($with_map);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function handleOrders($orders)
    {
        $orders = self::explodeFilters($orders);

        foreach ($orders as $order) {
            list($field, $direction) = explode(':', $order);

            $this->query->orderBy($field, $direction);
        }

        return $this;
    }

    /**
     * @param object $entity
     * @param array $fields
     * @param array $filters
     * @return array
     */
    public function callScopes($entity, $fields, $filters)
    {
        $appends = [];
        $fields = self::explodeFields($fields);
        $fields_scopes = self::extractFrom($fields, $this->available_scopes);
        $filters_scopes = self::extractFrom($filters, $this->available_scopes);
dd($fields_scopes);

        // append by fields if the same not present in filters
//        foreach ($fields as $rs) {
//            if(!isset($appends[$rs])) {
//                $appends[$rs] = $entity->{$this->scopes[$rs]}()->get();
//            }
//        }

        foreach ($filters as $filter) {
            list($name, $rule) = explode('.', $filter);

            $scoped = call_user_func([$entity, $name]);

            $same_from_field = self::extractFrom($fields, $name);

            $same_with_rels = array_map(function($e){
                $scope_rel = explode('.', $e);
                if(isset($scope_rel[1])) {
                    return $scope_rel[1];
                }
            }, $same_from_field);

            if(!empty($same_with_rels)) {
                $scoped = $scoped->with($same_with_rels);
            }

            $appends[$name] = $this->applyRulesToQuery($scoped, (array)$rule)->get();
        }

        return $appends;
    }

    /**
     * @param string $fields_string
     * @return array
     */
    public static function explodeFields($fields_string)
    {
        return $fields_string ? explode(self::FIELDS_DELIMETER, $fields_string) : [];
    }

    /**
     * @param string $filters_string
     * @return array
     */
    public static function explodeFilters($filters_string)
    {
        return $filters_string ? explode(self::ANDFILTERS_DELIMETER, $filters_string) : [];
    }

    /**
     * @param mixed $needles
     * @param array $fields
     * @return array
     */
    public static function extractFrom($fields, $needles)
    {
        if(!is_array($needles)) $needles = [$needles];
        if(!is_array($fields)) $fields = [$fields];

        return array_filter($fields, function($field) use ($needles) {
            return in_array(preg_split( "/[.|:]/", $field)[0], $needles);
        });
    }

    /**
     * @param mixed $needles
     * @param array $fields
     * @return array
     */
    public static function subtractFrom($fields, $needles)
    {
        if(!is_array($needles)) $needles = [$needles];
        if(!is_array($fields)) $fields = [$fields];

        return array_filter($fields, function($field) use ($needles) {
            return !in_array(preg_split( "/[.|:]/", $field)[0], $needles);
        });
    }


    /**
     * Retrieve own model's columns from given array
     *
     * @param array $fields
     * @return array
     */
    public function extractFields($fields)
    {
        return self::subtractFrom($fields, $this->getAvailableRelations());
    }


    /**
     * Retrieve model's relations from given array
     *
     * @param array $fields
     * @return array
     */
    public function extractRelations($fields)
    {
        return self::extractFrom($fields, $this->getAvailableRelations());
    }

    /**
     * @param string $filters
     * @return array
     */
    protected function createConditionsMap($filters)
    {
        $filters = self::explodeFilters($filters);

        $filters_by_relations = self::extractFrom($filters, $this->getAvailableRelations());
        $filters_own = self::subtractFrom($filters, $this->getAvailableRelations());
        sort($filters_by_relations);

        $conditions = [];

        foreach ($filters_own as $fo) {
            $conditions['.'][] = $fo;
        }

        foreach ($filters_by_relations as $fbr)
        {
            $with_relation = explode('.', $fbr, 2);    // allow only nested level 1

            // filter by ID if "id" keyword not porvided
            if(count($with_relation) > 1) {
                list($relation, $relation_filter) = $with_relation;
            }
            else {
                $with_relation = explode(':', $fbr, 2);
                list($relation, $relation_filter) = $with_relation;
                $relation_filter = 'id:'.$relation_filter;
            }

            $conditions[$relation][] = $relation_filter;
        }

        return $conditions;
    }

    protected function applyRulesToQuery($query, $subject_conditions, $subject = null)
    {
        foreach ($subject_conditions as $subject_condition) {
            list($field, $operand, $value) = explode(':', $subject_condition);
            $values = explode(',', $value);
            // if column belongs to pivot table
            $pivot = $subject ? self::extractFrom($subject_condition, $this->getPivotColumns($subject)) : [];

            $query = count($pivot) ?
                $this->pivot_operators[$operand]($query, $field, $values) :
                $this->operators[$operand]($query, $field, $values);
        }

        return $query;
    }

}