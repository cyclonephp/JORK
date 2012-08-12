<?php

namespace cyclone\jork\mapper\result;

use cyclone\jork;
use cyclone\db;

/**
 * Abstract class that is able to map a database query result to an object query result.
 * 
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
abstract class AbstractResult {

    /**
     * Static factory method for result mapper implementations.
     *
     * If the JORK SELECT query has an implicit root and it's select list is empty,
     * (it's typically the case when the JORK SELECT was created using JORK::from())
     * then it returns a @c SimpleResult instance. Otherwise it returns
     * a @c DefaultResult instance.
     *
     * @param \cyclone\jork\query\SelectQuery $query the JORK SELECT query that's result wil be mapped
     * @param \cyclone\db\query\result\AbstractResult $db_result the database query result to map
     * @param boolean $has_implicit_root
     * @param array $mappers
     * @return AbstractResult
     * @usedby \cyclone\jork\query\SelectQuery::exec()
     */
    public static function for_query(jork\query\SelectQuery $query
            , db\query\result\AbstractResult $db_result
            , $has_implicit_root, $mappers) {
        if ($has_implicit_root && empty($query->select_list)) 
            return new SimpleResult($db_result, $mappers[NULL]);
        
        return new DefaultResult($query, $db_result, $has_implicit_root, $mappers);
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
