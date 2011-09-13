<?php

namespace cyclone\jork\mapper;

use cyclone\jork;

/**
 * This class is reponsible for mapping custom database expressions of
 * the JORK query.
 * 
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class ExpressionMapper implements RowMapper {

    private $_db_expr;

    public $col_name;

    private $_last_value;

    public function  __construct($resolved_db_expr) {
        $this->_db_expr = $resolved_db_expr;
        $this->col_name = substr($this->_db_expr, strrpos($this->_db_expr, ' ') + 1);
    }

    public function  map_row(&$row) {
        if ( ! isset($row[$this->col_name]))
                throw new jork\Exception('failed to detect column name for database expression "'
                        .$this->_db_expr.'"');
        
        return array($this->_last_value = $row[$this->col_name], TRUE);
    }

    public function  get_last_entity() {
        return $this->_last_value;
    }
}
