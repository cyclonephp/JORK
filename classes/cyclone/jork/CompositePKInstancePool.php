<?php

namespace cyclone\jork;

/**
 * The instances of this class are used as <code>&lt;primary key -> entity&gt;</code> hashtables
 * where the <code>primary key</code> is an array of scalar values (representing a composite
 * primary key). Internally the data structure is represented as an <code>N</code>-depth tree build of
 * <code>ArrayObject</code> instances where <code>N</code> is the same as te number of (scalar) values
 * in the primary keys. The @c key() method (see <code>Iterator</code> interface in the PHP documentation)
 * will always return an array of scalar values (the composite primary key of an entity) which will result
 * in PHP warnings. As a workaround it is recommended to iterate on <code>CompositePKInstancePool</code>
 * instances this way: @code
 * foreach ($pool as $entity) {
 *      $primary_key = $pool->key();
 *      // the body of the loop
 * } @endcode
 *
 * @package JORK
 * @author Bence Erős <crystal@cyclonephp.org>
 */
class CompositePKInstancePool extends InstancePool {

    private $_count = 0;

    private $_current_iterables;

    private $_pk_property_cnt;

    private $_curr_key;

    private $_curr_val;

    private $_curr_count;

    protected function  __construct($class) {
        parent::__construct($class);
        $this->_pk_property_cnt = count(schema\SchemaPool::inst()->get_schema($class)->primary_keys());
    }


    public function append($instance) {
        if ( ! ($instance instanceof $this->_class))
            throw new Exception("unable to add an instance of class '"
                . get_class($instance)
                . " to InstancePool of class '{$this->_class}'");

        $prev_pool = NULL;
        $curr_pool = $this->_pool;
        $last_key = NULL;
        $last_is_new_entry = FALSE;
        foreach ($instance->pk() as $pk_component) {
            if ($pk_component === NULL) {
                $pk_component = '';
            }
            if ( ! array_key_exists($pk_component, $curr_pool)) {
                $curr_pool[$pk_component] = new \ArrayObject();
                $last_is_new_entry = TRUE;
            }
            $prev_pool = $curr_pool;
            $curr_pool = $curr_pool[$pk_component];
            $last_key = $pk_component;
        }
        if ($last_is_new_entry) {
            $this->_count++;
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
        $this->_count--;
    }

    public function offsetExists($primary_key) {
        $curr_pool = $this->_pool;
        foreach ($primary_key as $prim_key_val) {
            if (isset($curr_pool[$prim_key_val])) {
                $curr_pool = $curr_pool[$prim_key_val];
            } else
                return FALSE;
        }
        return TRUE;
    }

    public function count() {
        return $this->_count;
    }

    public function rewind() {
        $curr_iterables = array();
        $curr_pool = $this->_pool;
        for ($i = 0; $i < $this->_pk_property_cnt; ++$i) {
            $iter = $curr_pool->getIterator();
            $curr_iterables []= $iter;
            $iter->rewind();
            if ( ! $iter->valid())
                //throw new CycloneException('invalid state');
                return;

            $curr_pool = $iter->current();
        }
        /*$iter = $curr_pool->getIterator();
        $curr_iterables []= $iter;*/
        $this->_current_iterables = $curr_iterables;
        $curr_key = array();
        foreach ($this->_current_iterables as $iter) {
            $curr_key []= $iter->key();
        }
        $this->_curr_key = $curr_key;
        $this->_curr_val = $curr_iterables[$this->_pk_property_cnt - 1]->current();
        $this->_curr_count = -1;
    }

    public function valid() {
        if ($this->_curr_count == -1) {
            $this->_curr_count = 0;
            return $this->_count > 0;
        } else {
            if ($this->_count > 0 && $this->_curr_count < $this->_count - 1) {
                $this->create_current();
                return TRUE;
            }
            return FALSE;
        }
    }

    private function create_current() {
        $curr_key = array();

        $last_iter = $this->_current_iterables[$this->_pk_property_cnt - 1];
        $last_iter->next();
        if ( ! $last_iter->valid()) {
            for ($idx = $this->_pk_property_cnt - 2
                ; $this->_current_iterables[$idx]->next(), ( ! $this->_current_iterables[$idx]->valid())
                ; --$idx);
            $valid_iter = $this->_current_iterables[$idx];

            $this->_current_iterables[$idx] = $valid_iter;

            $curr_iter = $valid_iter->current()->getIterator();
            for ($i = $idx + 1; $i < $this->_pk_property_cnt; ++$i) {
                $curr_iter->rewind();
                $this->_current_iterables[$i] = $curr_iter;
                $curr_iter = $curr_iter->current()->getIterator();
            }
        }

        for ($i = 0; $i < $this->_pk_property_cnt; ++$i) {
            $curr_key []= $this->_current_iterables[$i]->key();
        }
        $this->_curr_key = $curr_key;
        $this->_curr_val = $this->_current_iterables[$this->_pk_property_cnt - 1]->current();
        ++$this->_curr_count;
    }

    public function next() {}

    public function key() {
        return $this->_curr_key;
    }

    public function current() {
        return $this->_curr_val;
    }

}