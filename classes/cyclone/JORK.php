<?php

namespace cyclone;

use cyclone\jork\schema;

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

    const AUTO = 'auto';

    const ASSIGN = 'assign';

    const SINGLE_TABLE = 'single-table';

    const JOINED_SUBCLASS = 'joined-subclass';

    const TABLE_PER_CLASS = 'table-per-class';

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

    /**
     *
     * @param <type> $name
     * @param <type> $type
     * @return schema\PrimitivePropertySchema 
     */
    public static function primitive($name, $type) {
        return new schema\PrimitivePropertySchema($name, $type);
    }

    /**
     *
     * @param string $name
     * @param string $class
     * @return schema\ComponentSchema
     */
    public static function component($name, $class) {
        return new schema\ComponentSchema($name, $class);
    }

    /**
     *
     * @param string $name
     * @return schema\SecondaryTableSchema
     */
    public static function secondary_table($name, $join_col, $inverse_join_col) {
        return new schema\SecondaryTableSchema($name, $join_col, $inverse_join_col);
    }

    /**
     *
     * @param string $join_column
     * @param string $inverse_join_column
     * @return schema\JoinTableSchema
     */
    public static function join_table($name, $join_column, $inverse_join_column) {
        return new schema\JoinTableSchema($name, $join_column, $inverse_join_column);
    }

    /**
     *
     * @param string $discriminator_column
     * @return schema\SingleTableInheritance
     */
    public static function single_table($discriminator_column) {
        return new schema\SingleTableInheritance($discriminator_column);
    }

    /**
     * @return schema\JoinedSubclassInheritance
     */
    public static function joined_subclass() {
        return new schema\JoinedSubclassInheritance;
    }

    /**
     * @return schema\TablePerClassInheritance
     */
    public static function table_per_class() {
        return new schema\TablePerClassInheritance;
    }

}
