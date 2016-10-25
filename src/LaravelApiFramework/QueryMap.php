<?php

namespace Karellens\LAF;

use Karellens\LAF\ReflectionModel;

class QueryMap
{
    const FIELDS_DELIMETER = ',';
//    const ORFILTERS_DELIMETER = ';';
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

    protected $query;
    protected $modelClass;

    protected $fields;
    protected $filters;
    protected $orders;

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
    }

    /**
     * @param mixed $modelClass
     * @return $this
     */
    public function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;
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
     * @return $this
     */
    public function handleFields($fields)
    {
        if($fields)
        {
            $fields = self::parseFields($fields);

            // leave only specified relations.
            // some of them can have child relations. so we compare them as `starts-with`
            $relations = $this->extractRelations($fields);
            $columns = array_diff($fields, $relations);

            if(!empty($columns)) {
                // do not forget `id`
                array_unshift($columns, 'id');

                $this->query->select($columns);
            }
            elseif(!empty($relations)) {
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
                    $with_map[$subject] = function($query) use ($subject_conditions) {
                        return $this->applyRulesToQuery($query, $subject_conditions);
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
    public function handleOrders()
    {
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

        // append by fields if the same not present in filters
//        foreach ($fields as $rs) {
//            if(!isset($appends[$rs])) {
//                $appends[$rs] = $entity->{$this->scopes[$rs]}()->get();
//            }
//        }

        foreach ($filters as $filter) {
            list($name, $rule) = explode('.', $filter);

            $scoped = call_user_func([$entity, $name]);

            $same_from_field = self::extractFromFields(implode(',', $fields), $name);

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
    public static function parseFields($fields_string)
    {
        return explode(self::FIELDS_DELIMETER, $fields_string);

    }


    /**
     * @param mixed $needles
     * @param string $fields_string
     * @return array
     */
    public static function extractFromFields($fields_string, $needles)
    {
        if(!is_array($needles)) $needles = [$needles];

        $fields = explode(self::FIELDS_DELIMETER, $fields_string);

        return array_filter($fields, function($field) use ($needles) {
            return in_array(explode('.', $field)[0], $needles);
        });
    }


    /**
     * @param string $fields_string
     * @param mixed $needles
     * @return array
     */
    public static function subtractFromFields($fields_string, $needles)
    {
        if(!is_array($needles)) $needles = [$needles];

        $fields = explode(self::FIELDS_DELIMETER, $fields_string);

        return array_filter($fields, function($field) use ($needles) {
            return !in_array(explode('.', $field)[0], $needles);
        });
    }

    /**
     * @param string $filters_string
     * @param mixed $needles
     * @return array
     */
    public static function extractFromFilters($filters_string, $needles)
    {
        if(!is_array($needles)) $needles = [$needles];

        $filters = explode(self::ANDFILTERS_DELIMETER, $filters_string);

        return array_filter($filters, function($filter) use ($needles) {
            return in_array(explode('.', $filter)[0], $needles);
        });
    }

    /**
     * @param string $filters_string
     * @param mixed $needles
     * @return array
     */
    public static function subtractFromFilters($filters_string, $needles)
    {
        if(!is_array($needles)) $needles = [$needles];

        $filters = explode(self::ANDFILTERS_DELIMETER, $filters_string);

        return array_filter($filters, function($filter) use ($needles) {
            return !in_array(explode('.', $filter)[0], $needles);
        });
    }

    /**
     * @param string $filters
     * @return array
     */
    protected function createConditionsMap($filters)
    {
        $filters = explode(self::ANDFILTERS_DELIMETER, $filters);
        sort($filters);

        $conditions = [];

        foreach ($filters as $filter)
        {
            $with_relation = explode('.', $filter, 2);    // allow only nested level 1

            if(count($with_relation) > 1)
            {
                list($relation, $relation_filter) = $with_relation;
                $conditions[$relation][] = $relation_filter;
            }
            else
            {
                $conditions['.'][] = $filter;
            }
        }

        return $conditions;
    }

    protected function applyRulesToQuery($query, $subject_conditions)
    {
        foreach ($subject_conditions as $subject_condition) {
            list($field, $operand, $value) = explode(':', $subject_condition);
            $values = explode(',', $value);

            $query = $this->operators[$operand]($query, $field, $values);
        }

        return $query;
    }

    /**
     * @param $fields
     * @return array
     */
    private function extractRelations($fields)
    {
        $valid_relations = (new ReflectionModel())->getSupportedRelations($this->modelClass);

        return array_filter($fields, function($field) use ($valid_relations) {
            return in_array(explode('.', $field)[0], $valid_relations);
        });
    }

}