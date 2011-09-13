<?php

namespace cyclone;



/**
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK {

    const ONE_TO_ONE = 0;

    const ONE_TO_MANY = 1;

    const MANY_TO_MANY = 2;

    const MANY_TO_ONE = 3;

    const CASCADE = 5;

    const SET_NULL = 6;

    private static $_instance;

    /**
     * @return JORK
     */
    public static function inst() {
        if (null === self::$_instance) {
            self::$_instance = new JORK;
        }
        return self::$_instance;
    }

    private function  __construct() {
        //empty private constructor
    }

    /**
     * @return cyclone\jork\query\Select
     */
    public static function select() {
        $query = new jork\query\SelectQuery;
        $args = func_get_args();
        $query->select_array($args);
        return $query;
    }

    /**
     * @return cyclone\jork\querySelect
     */
    public static function from() {
        $query = new jork\query\SelectQuery;
        $args = func_get_args();
        $query->from_array($args);
        return $query;
    }

}
