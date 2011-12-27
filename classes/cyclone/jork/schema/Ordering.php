<?php

namespace cyclone\jork\schema;

use cyclone\jork;

/**
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
class Ordering {

    public $property;

    public $direction;

    function __construct($property, $direction = 'asc') {
        $direction = strtolower($direction);
        if ( ! ($direction == 'asc' || $direction == 'desc'))
            throw new jork\SchemaException("invalid ordering direction: '$direction'");
        
        $this->property = $property;
        $this->direction = $direction;
    }

}