<?php
namespace Karellens\LAF;


class Rules
{
    const AVAILABLE_ACTIONS = [
        'index'     => ['controller' => 'ApiController', 'middleware' => '', 'method' => 'get', 'postfix' => ''],
        'store'     => ['controller' => 'ApiController', 'middleware' => '', 'method' => 'post', 'postfix' => ''],
        'show'      => ['controller' => 'ApiController', 'middleware' => '', 'method' => 'get', 'postfix' => '{id}'],
        'update'    => ['controller' => 'ApiController', 'middleware' => '', 'method' => 'put', 'postfix' => '{id}'],
        'destroy'   => ['controller' => 'ApiController', 'middleware' => '', 'method' => 'delete', 'postfix' => '{id}'],
    ];

    protected $rules;

    public function __construct()
    {
        // get user defined rules
        $this->rules = config('rules');
        // merge default rules with user defined
        foreach ($this->rules as $version => &$entities) {
            foreach ($entities as $entity => &$rules) {
                $this->rules[$version][$entity] = array_replace_recursive(self::AVAILABLE_ACTIONS, $rules);
            }
        }

        $this->array_unset_recursive($this->rules, '');
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
    public function getCustomController($name)
    {
        list($version, $entity, $action) = explode('.', $name);

        return isset($this->rules[$version][$entity][$action]['controller'])
            && $this->rules[$version][$entity][$action]['controller'] !== 'ApiController' ?
            $this->rules[$version][$entity][$action]['controller'] :
            false
            ;
    }

    private function array_unset_recursive(&$array, $remove) {
        if (!is_array($remove)) $remove = array($remove);
        foreach ($array as $key => &$value) {
            if (in_array($value, $remove)) unset($array[$key]);
            else if (is_array($value)) {
                $this->array_unset_recursive($value, $remove);
            }
        }
    }
}