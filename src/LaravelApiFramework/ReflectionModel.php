<?php

namespace Karellens\LAF;

/**
 * Helper for Eloquent Model reverse engineering
 *
 * Class ReflectionModel
 * @package Karellens\LAF
 */
class ReflectionModel
{
    protected $modelName;

    public function __construct($alias = null)
    {
        if($alias)
        {
            $this->modelName = ucfirst(str_singular($alias));
        }
    }

    /**
     * @return string Class
     */
    public function getClass()
    {
        $modelClass = null;

        foreach (config('api.models_namespaces') as $ns) {
            if(class_exists($ns.$this->modelName))
            {
                $modelClass = $ns.$this->modelName;
                break;
            }
        }

        if(!$modelClass)
        {
            // throw 404
        }

        return $modelClass;
    }

    /**
     * Get table linked to model
     *
     * @param string $modelClass
     * @return string
     */
    public function getTable($modelClass = null)
    {
        $modelClass = $modelClass ? $modelClass : $this->getClass();

        return with(new $modelClass)->getTable();
    }

    /**
     * @return array
     */
    public function getSupportedRelations($modelClass = null)
    {
        $relations = [];
        $modelClass = $modelClass ? $modelClass : $this->getClass();
        $rc = new \ReflectionClass($modelClass);

        foreach($rc->getMethods() as $method)
        {
            $doc = $method->getDocComment();

            if($doc && strpos($doc, '@Relation') !== false)
            {
                $relations[] = $method->getName();
            }
        }

        return $relations;
    }
}