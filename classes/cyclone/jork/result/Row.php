<?php

namespace cyclone\jork\result;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class Row extends \ArrayObject {

    private $_mappers;

    public function  __construct($mappers, $db_row) {
        $this->_mappers = $mappers;
    }

    public function  offsetGet($index) {
        
    }
    
}
