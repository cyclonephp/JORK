<?php

/**
 * Abstract class that is able to map a database query result to an object query result.
 * 
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
abstract class JORK_Mapper_Result {

    /**
     * Static factory method for result mapper implementations.
     *
     * If the JORK SELECT query has an implicit root and it's select list is empty,
     * (it's typically the case when the JORK SELECT was created using JORK::from())
     * then it returns a JORK_Mapper_Result_Simple instance. Otherwise it returns
     * a JORK_Mapper_Result_Default instance.
     *
     * @param JORK_Query_Select $query the JORK SELECT query that's result wil be mapped
     * @param DB_Query_Result $db_result the database query result to map
     * @param boolean $has_implicit_root
     * @param array $mappers
     * @return JORK_Mapper_Result
     * @usedby JORK_Query_Select::exec()
     */
    public static function for_query(JORK_Query_Select $query
            , DB_Query_Result $db_result
            , $has_implicit_root, $mappers) {
        if ($has_implicit_root && empty($query->select_list)) 
            return new JORK_Mapper_Result_Simple($db_result, $mappers[NULL]);
        
        return new JORK_Mapper_Result_Default($query, $db_result, $has_implicit_root, $mappers);
    }

    /**
     * Maps the database query result to an object query result.
     *
     * An object query result is an array of entities if the object query
     * has an implicit root entity, otherwise an array of arrays (rows) where
     * every item is something with a type indicated by the select items of
     * the query.
     *
     * @return array
     */
    public abstract function map();

}
