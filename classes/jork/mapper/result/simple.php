<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Mapper_Result_Simple extends JORK_Mapper_Result {

    private $_db_result;

    private $_mapper;

    public function  __construct(DB_Query_Result $db_result
            , JORK_Mapper_Row $mapper) {
        $this->_db_result = $db_result;
        $this->_mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<JORK_Model_Abstract>
     */
    public function map() {
        $obj_result = array();
        foreach ($this->_db_result as $row) {
            list($entity, $is_new) = $this->_mapper->map_row($row);
            if ($is_new) {
                $obj_result [] = $entity;
            }
        }
        return $obj_result;
    }
    
}