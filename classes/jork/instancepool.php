<?php

/**
 * This class is responsible for storing only one entity instance with a
 * given primary key. Entity instantiations during the database query result
 * mapping process should be done using this class
 * 
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_InstancePool {

    private static $_instances = array();

    public static function inst($class) {
        if ( !array_key_exists($class, self::$_instances)) {
            self::$_instances[$class] = new JORK_InstancePool($class);
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

    private $_pool = array();

    private function  __construct($class) {
        $this->_class = $class;
    }

    public function get_by_pk($primary_key) {
        return array_key_exists($primary_key, $this->_pool)
                ? $this->_pool[$primary_key]
                : NULL;
    }

    public function for_pk($primary_key) {
        return array_key_exists($primary_key, $this->_pool)
                ? $this->_pool[$primary_key]
                : ($this->_pool[$primary_key] = new $this->_class);
    }

    public function add(JORK_Model_Abstract $instance) {
        $this->_pool[$instance->pk()] = $instance;
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
    public function add_or_get(JORK_Model_Abstract $instance) {
        $pk = $instance->pk();
        if (array_key_exists($pk, $this->_pool)) {
            return array($this->_pool[$pk], FALSE);
        }
        $this->_pool[$pk] = $instance;
        return array($instance, TRUE);
    }

}
