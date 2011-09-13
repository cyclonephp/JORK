<?php

namespace cyclone\jork\query;


use cyclone\jork;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class PropChain {

    /**
     * Internal string representation of the property chain
     * @var string
     */
    private $str;

    /**
    * Internal array representation of the chain
    *
    * @var array
    */
    private $arr;
    
    /**
     * @param string $str
     * @return PropChain
     */
    public static function from_string($str) {
        $rval = new PropChain;
        $rval->str = $str;
        return $rval;
    }

    /**
     * @param array $arr
     * @return PropChain
     */
    public static function from_array($arr) {
        $rval = new PropChain;
        $rval->arr = $arr;
        return $rval;
    }

    private function  __construct() {
        
    }

    public function as_array() {
        if (null === $this->arr) {
            $this->arr = explode('.', $this->str);
        }
        return $this->arr;
    }

    public function as_string() {
        if (null === $this->str) {
            $this->str = implode('.', $this->arr);
        }
        return $this->str;
    }

    public function __toString() {
        return $this->as_string();
    }

}
