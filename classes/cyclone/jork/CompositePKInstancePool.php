<?php

namespace cyclone\jork;

/**
 * @package JORK
 * @author Bence Erős <crystal@cyclonephp.org>
 */
class CompositePKInstancePool extends InstancePool {

    public function append(model\AbstractModel $instance) {
        if ( ! ($instance instanceof $this->_class))
            throw new Exception("unable to add an instance of class '"
                . get_class($instance)
                . " to InstancePool of class '{$this->_class}'");

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
    }

    public function offsetGet($primary_key) {
        $curr_pool = $this->_pool;
        foreach ($primary_key as $prim_key_val) {
            if (array_key_exists($prim_key_val, $curr_pool)) {
                $curr_pool = $curr_pool[$prim_key_val];
            } else
                return NULL;
        }
        return $curr_pool;
    }

    public function offsetUnset($primary_key) {
        $prev_pool = NULL;
        $prev_key = NULL;
        $curr_pool = $this->_pool;
        foreach ($primary_key as $pk_component) {
            if ( ! isset($curr_pool[$pk_component]))
                throw new Exception("key '$pk_component' not found");

            $prev_pool = $curr_pool;
            $prev_key = $pk_component;
            $curr_pool = $curr_pool[$pk_component];
        }
        unset($prev_pool[$prev_key]);
    }

    public function offsetExists($primary_key) {

    }

}