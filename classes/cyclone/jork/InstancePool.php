<?php

namespace cyclone\jork;

use cyclone as cy;
use cyclone\db;

/**
 * This class is responsible for storing only one entity instance with a
 * given primary key. Entity instantiations during the database query result
 * mapping process should be done using this class
 * 
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
class InstancePool {

    private static $_instances = array();

    public static function inst($class) {
        if ( ! isset(self::$_instances[$class])) {
            self::$_instances[$class] = new InstancePool($class);
        }
        return self::$_instances[$class];
    }

    public static function clear() {
        self::$_instances = array();
    }

    /**
     * the class which' instances should be stored
     *
     * @var string
     */
    private $_class;

    private $_pool;

    private function  __construct($class) {
        $this->_class = $class;
        $this->_pool = new \ArrayObject();
    }

    public function get_by_pk($primary_key) {
        $curr_pool = $this->_pool;
        foreach ($primary_key as $prim_key_val) {
            if (array_key_exists($prim_key_val, $curr_pool)) {
                $curr_pool = $curr_pool[$prim_key_val];
            } else
                return NULL;
        }
        return $curr_pool;
        /*return array_key_exists($primary_key, $this->_pool)
                ? $this->_pool[$primary_key]
                : NULL;*/
    }

    public function for_pk($primary_key) {
        if (isset($this->_pool[$primary_key])) {
            return $this->_pool[$primary_key];
        }
        return $this->_pool[$primary_key] = new $this->_class;
    }


    public function add(model\AbstractModel $instance) {
        $prev_pool = NULL;
        $curr_pool = $this->_pool;
        $last_key = NULL;
        foreach ($instance->pk() as $pk_component) {
            if ($pk_component === NULL) {
                $pk_component = '';
            }
            if ( ! array_key_exists($pk_component, $curr_pool)) {
                $curr_pool[$pk_component] = new \ArrayObject();
            }
            $prev_pool = $curr_pool;
            $curr_pool = $curr_pool[$pk_component];
            $last_key = $pk_component;
        }
        $prev_pool[$last_key] = $instance;
        //$this->_pool[$instance->pk()] = $instance;
    }

    /**
     * Gets the instance from the instance pool if there is one with the
     * primary key of $instance, or if not found then puts it into the pool
     * and returns the parameter instance.
     *
     * @param JORK_Model_Abstract $instance
     * @return array 1th item the instance with the primary key of $instance
     *  , 2nd item is FALSE if the instance already existed, otherwise TRUE.
     */
    public function add_or_get(model\AbstractModel $instance) {
        $pk = $instance->pk();
        if (isset($this->_pool[$pk])) {
            return array($this->_pool[$pk], FALSE);
        }
        $this->_pool[$pk] = $instance;
        return array($instance, TRUE);
    }

}
