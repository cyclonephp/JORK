<?php

namespace cyclone\jork\result;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class Iterator implements \Iterator, \Countable, \ArrayAccess {

    /**
     * @var cyclone\db\query\Result
     */
    private $_result;

    /**
     * @var array<cyclone\jork\mapper\Result>
     */
    private $_mappers;

    /**
     * @var int the index of the current element
     */
    private $_idx;

    /**
     * @var int the total count of items in the result
     */
    private $_count;

    public function  __construct($object_result) {
        $this->_result = $object_result;
        $this->_count = count($object_result);
    }
    
    public function  rewind() {
        $this->_idx = 0;
    }

    public function  next() {
        ++$this->_idx;
    }

    public function  valid() {
        return $this->_idx < $this->_count;
    }

    public function  current() {
        return $this->_result[$this->_idx];
    }

    public function  key() {
        return $this->_idx;
    }

    public function count() {
        return $this->_count;
    }

    public function  offsetGet($offset) {
        return $this->_result[$offset];
    }

    public function  offsetSet($offset, $value) {
        $this->_result[$offset] = $value;
    }

    public function  offsetUnset($offset) {
        unset($this->_result[$offset]);
    }

    public function  offsetExists($offset) {
        return isset($this->_result[$offset]);
    }

    public function as_array() {
        $rval = array();
        foreach ($this as $k => $v) {
            $rval[$k] = method_exists($v, 'as_array') ? $v->as_array() : $v;
        }
        return $rval;
    }

    public function jsonSerializable() {
        return $this->as_array();
    }

}
