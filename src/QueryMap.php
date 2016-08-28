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
        $relations = (new ReflectionModel())->getSupportedRelations($this->modelClass);

        if($fields)
        {
            $fields = explode(self::FIELDS_DELIMETER, $fields);

            // leave only specified relations.
            // some of them can have child relations. so we compare them as `starts-with`
            $relations = $this->extractRelations($fields, $relations);

            // do not forget `id`
            $columns = array_diff($fields, $relations);
            array_unshift($columns, 'id');

            $this->query->select($columns);

        }

        $this->query->with($relations);

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
                    foreach ($subject_conditions as $subject_condition) {
                        list($field, $operand, $value) = explode(':', $subject_condition);
                        $values = explode(',', $value);

                        $this->query = $this->operators[$operand]($this->query, $field, $values);
                    }
                }
                else
                {
                    $relation_table = (new ReflectionModel($subject))->getTable();

                    $this->query->whereHas($subject, function($query) use ($subject_conditions, $relation_table) {
                        foreach ($subject_conditions as $subject_condition) {
                            list($field, $operand, $value) = explode(':', $subject_condition);
                            $values = explode(',', $value);

                            $query = $this->operators[$operand]($query, $relation_table.'.'.$field, $values);
                        }

                        return $query;
                    });
                }
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

    /**
     * @param $fields
     * @param $valid_relations
     * @return array
     */
    private function extractRelations($fields, $valid_relations)
    {
        return array_filter($fields, function($field) use ($valid_relations) {
            $flag = false;
            foreach ($valid_relations as $valid_relation) {
                $flag = strpos($field, $valid_relation.'.') === 0;
            }
            return $flag;
        });
    }

}