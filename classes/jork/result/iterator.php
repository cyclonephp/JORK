<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Result_Iterator implements Iterator, Countable, ArrayAccess {

    /**
     * @var DB_Query_Result
     */
    private $_result;

    /**
     * @var array<JORK_Mapper_Result>
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
        return array_key_exists($offset, $this->_result);
    }
    
}
