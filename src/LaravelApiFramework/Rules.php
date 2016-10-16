<?php
/**
 * Created by PhpStorm.
 * User: karellen
 * Date: 10/13/16
 * Time: 4:33 PM
 */

namespace Karellens\LAF;


class Rules
{
    protected $rules;

    public function __construct()
    {
        $this->rules = config('rules');
    }

    /**
     * @return mixed
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @param mixed
     * @return array
     */
    public function getVersions()
    {
        return array_keys($this->rules);
    }

    /**
     * @param mixed
     * @return array
     */
    public function getEntities($version)
    {
        return array_keys($this->rules[$version]);
    }

    /**
     * @param $name
     * @return string
     */
    public function getRule($name) {
        list($version, $entity, $action) = explode('.', $name);

        if (isset($this->rules[$version][$entity][$action])) {
            return $this->rules[$version][$entity][$action];
        }
        else {
            foreach ($this->rules[$version][$entity] as $rule_action => &$rule) {
                if(strpos($rule_action, '@'.$action) !== false) {
                    return $rule;
                }
            }
        }

        return false;
    }

    /**
     * @param string
     * @return boolean
     */
    public function isActionBlocked($name)
    {
        list($version, $entity, $action) = explode('.', $name);
        return isset($this->rules[$version][$entity][$action])
                && $this->rules[$version][$entity][$action] === false;
    }

    /**
     * @param string
     * @return string
     */
    public function getCustomControllerAndAction($name)
    {
        list($version, $entity, $action) = explode('.', $name);
        $result = false;

        foreach ($this->rules[$version][$entity] as $rule_action => &$rule) {
            $result = $result || strpos($rule_action, '@'.$action) !== false;
            if($result) {
//                $result = explode('@', $rule_action);
                $result = $rule_action;
                break;
            }
        }

        return $result;
    }
}