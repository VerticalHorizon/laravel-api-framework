<?php

namespace Karellens\LAF;
use Illuminate\Support\Facades\Cache;

/**
 * Helper for Eloquent Model reverse engineering
 *
 * Class ReflectionModel
 * @package Karellens\LAF
 */
class ReflectionModel
{
    private static $RELATIONS = [
        // Define a one-to-one relationship.
        'Illuminate\Database\Eloquent\Relations\HasOne',
        // Define a polymorphic one-to-one relationship.
        'Illuminate\Database\Eloquent\Relations\MorphOne',
        // Define an inverse one-to-one or many relationship.
        'Illuminate\Database\Eloquent\Relations\BelongsTo',
        // Define a polymorphic, inverse one-to-one or many relationship.
        'Illuminate\Database\Eloquent\Relations\MorphTo',
        // Define a one-to-many relationship.
        'Illuminate\Database\Eloquent\Relations\HasMany',
        // Define a has-many-through relationship.
        'Illuminate\Database\Eloquent\Relations\HasManyThrough',
        // Define a polymorphic one-to-many relationship.
        'Illuminate\Database\Eloquent\Relations\MorphMany',
        // Define a many-to-many relationship.
        'Illuminate\Database\Eloquent\Relations\BelongsToMany',
        // Define a polymorphic many-to-many relationship.
        'Illuminate\Database\Eloquent\Relations\MorphToMany',
        // Define a polymorphic, inverse many-to-many relationship.
        'Illuminate\Database\Eloquent\Relations\MorphToMany',
    ];

    protected $modelName;

    protected $modelClass;

    protected $relations;

    protected $defaultRelationsClasses;

    /**
     * @param string $alias
     * @return string Class
     */
    public function getClassByAlias($alias)
    {
        $modelName = str_singular(studly_case($alias));

        return $this->getNamespaceClass($modelName);
    }

    /**
     * @param string $modelName
     * @return string Class
     */
    public function getNamespaceClass($modelName)
    {
        foreach (config('api.models_namespaces') as $ns) {
            if(class_exists($ns.$modelName))
            {
                return $ns.$modelName;
            }
        }

        if(class_exists($modelName)) {
            return $modelName;
        }

        // throw 404
        return false;
    }

    /**
     * Get table linked to model
     *
     * @param string $modelClass
     * @return string
     */
    public function getTable($modelClass = null)
    {
        return with(new $modelClass)->getTable();
    }

    /**
     * @return array
     */
    public function getRelations($modelClass = null)
    {
        $relations = [];

        if(Cache::has($modelClass.'_relations')) {
            $relations = Cache::get($modelClass.'_relations');
            return $relations;
        }

        $rc = new \ReflectionClass($modelClass);

        foreach($rc->getMethods() as $method)
        {
            $doc = $method->getDocComment();

            // if doc contains one of relations
            if($doc && strposa($doc, self::$RELATIONS) !== false)
            {
                // check only current class methods without parent relations
                if($method->class == ltrim($modelClass, '\\')) {
                    $relations[$method->getName()] = null;

                    // check for pivot columns
                    preg_match_all('#@pivotColumns\s+(.*?)\n#s', $doc, $pivotColumnsString);
                    preg_match_all('#@foreignModel\s+(.*?)\n#s', $doc, $foreignModel);
                    preg_match_all('#@return\s+(.*?)\n#s', $doc, $return);

                    $relations[$method->getName()]['return'] = ltrim($return[1][0], '\\');

                    if(count($foreignModel[1])) {
                        $relations[$method->getName()]['foreignModel'] = $foreignModel[1][0];
                    }

                    if(count($pivotColumnsString[1])) {
                        // remove spaces and split columns by commas
                        $pivotColumns = explode(',', preg_replace('/\s+/', '', $pivotColumnsString[1][0]));
                        $relations[$method->getName()]['pivotColumns'] = $pivotColumns;
                    }
                }
            }
        }

        Cache::put($modelClass.'_relations', $relations);
        return $relations;
    }

    /**
     * get relations names
     *
     * @return mixed
     */
    public function getRelationsNames($modelClass = null)
    {
        $relationsMap = $this->getRelations($modelClass);

        return array_keys($relationsMap);
    }

    /**
     * return pivot columns of the certain relation if provided
     * or all relations having pivot tables with there pivot columns
     *
     * @param string $modelClass
     * @param string $relationName
     * @return mixed
     */
    public function getPivotColumns($modelClass, $relationName = null)
    {
        $relationsMap = $this->getRelations($modelClass);

        $result = [];
        foreach ($relationsMap as $name => $map) {
            if(isset($map['pivotColumns'])) {
                $result[$name] = $map['pivotColumns'];
            }
        }

        if($relationName && isset($result[$relationName])) {
            return $result[$relationName];
        }
        elseif (!$relationName) {
            return $result;
        }

        return  [];
    }

    /**
     * return foreign model of certain relation if provided
     * or all relations with there foreign models
     *
     * @param string $modelClass
     * @param string $relationName
     * @return mixed
     */
    public function getForeignModel($modelClass, $relationName = null)
    {
        $relationsMap = $this->getRelations($modelClass);

        $result = [];
        foreach ($relationsMap as $name => $map) {
            $result[$name] = isset($map['foreignModel']) ? $map['foreignModel'] : null;
        }

        if($relationName && isset($result[$relationName])) {
            return $result[$relationName];
        }
        elseif (!$relationName) {
            return $result;
        }

        return  [];
    }
}

/**
 * @return bool
 */
function strposa($haystack, $needles, $offset=0) {
    if(!is_array($needles)) $needles = array($needles);

    foreach($needles as $query) {
        if(strpos($haystack, $query, $offset) !== false) return true; // stop on first true result
    }
    return false;
}