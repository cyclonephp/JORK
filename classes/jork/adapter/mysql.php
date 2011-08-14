<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Adapter_Mysql extends JORK_Adapter_Abstract {

    /**
     * @param JORK_Query_Select $select
     * @return DB_Query_Select
     */
    public function  map_select(JORK_Query_Select $jork_select) {
        $db_select = new DB_Query_Select;
        $db_select->columns = array(DB::expr('*'));

        $db_select->tables = self::entity2table($jork_select->entity);

        if ( ! empty($jork_select->joins)) {
            $db_select->joins = self::property_chain2joins($jork_select->entity
                    , $jork_select->joins);
        }
        
        return $db_select;
    }

    /**
     * @param DB_Query_Result $db_result
     * @param JORK_Query_Select $jork_select
     * @return JORK_Query_Result
     */
    public function  map_select_result(DB_Query_Result $db_result
            , JORK_Query_Select $jork_select) {
        ;
    }
}
