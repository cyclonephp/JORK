<?php

namespace cyclone\jork\mapper\result;

use cyclone\jork;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class SimpleResult extends AbstractResult {

    private $_db_result;

    private $_mapper;

    public function  __construct(db\query\result\AbstractResult $db_result
            , jork\mapper\RowMapper $mapper) {
        $this->_db_result = $db_result;
        $this->_mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<\cyclone\jork\model\AbstractModel>
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