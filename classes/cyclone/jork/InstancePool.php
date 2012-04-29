<?php

namespace cyclone\jork;

use cyclone as cy;
use cyclone\db;

/**
 * The instances of this class can be used as a <primary key -> entity> hashmap.
 * Every entity class has (at most) one dedicated instance managed internally
 * by the InstancePool, this can be accessed using the @c inst() method. It is
 * used mainly by @c\cyclone\jork\EntityMapper to try to ensure that one entity
 * has at most one in-memory representation. During SQL query result processing
 * (mapping it to object graph) if the <code>EntityMapper</code> finds a new entity
 * in a given row of an SQL query, it doesn't instantiate the entity class but obtains
 * reference to it using the @c get_by_pk() method, which will return the already existing
 * instance with the given primary key or <code>NULL<code> if such instance is not present
 * yet (in the latter case the @c \cyclone\jork\mapper\EntityMapper will create the entity
 * and add it to the pool using @c add() )
 *
 * @author Bence Erős <crystal@cyclonephp.org>
 * @package JORK
 * @usedby \cyclone\jork\mapper\EntityMapper
 */
class InstancePool {

    private static $_instances = array();

    public static function inst($class) {
        if ( ! isset(self::$_instances[$class])) {
            self::$_instances[$class] = new InstancePool($class);
        }
        return self::$_instances[$class];
    }

    /**
     * Removes all InstancePool instances obtainable using the @c inst() method.
     * Only used for unit testing.
     *
     */
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

    public function  __construct($class) {
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
     * Removes the entity specified by its primary key <code>$pk</code>
     * from the instance pool. If the entity is not found then it
     * throws an exception.
     *
     * @param array $pk
     * @throws Exception if the entity is not present in the instance pool.
     */
    public function delete_by_pk($pk) {
        $prev_pool = NULL;
        $prev_key = NULL;
        $curr_pool = $this->_pool;
        foreach ($pk as $pk_component) {
            if ( ! isset($curr_pool[$pk_component]))
                throw new Exception("key '$pk_component' not found");

            $prev_pool = $curr_pool;
            $prev_key = $pk_component;
            $curr_pool = $curr_pool[$pk_component];
        }
        unset($prev_pool[$prev_key]);
    }

}
