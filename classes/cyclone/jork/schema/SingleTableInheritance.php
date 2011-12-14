<?php

namespace cyclone\jork\schema;

/**
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.org>
 */
class SingleTableInheritance implements Inheritance {

    public $discriminator_column;

    public function __construct($discriminator_column) {
        $this->discriminator_column = $discriminator_column;
    }
    
}