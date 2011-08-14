<?php

/**
 * Base class for all JORK adapters
 * 
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
abstract class JORK_Adapter_Abstract implements JORK_Adapter {

    protected $db;

    public function  __construct(DB_Adapter $db) {
        $this->db = $db;
    }

    /**
     *
     * @param JORK_Query_Select $jork_select
     * @return JORK_Query_Result
     */
    public function  exec_select(JORK_Query_Select $jork_select) {
        $db_select = $this->map_select($jork_select);
        $db_result = $this->db->exec_select($db_select);
        return $this->map_select_result($db_result, $jork_select);
    }

    /**
     * @return DB_Query_Select
     */
    abstract function map_select(JORK_Query_Select $select);

    /**
     * @return JORK_Query_Result
     */
    abstract function map_select_result(DB_Query_Result $db_result
            , JORK_Query_Select $jork_select);
    

}
