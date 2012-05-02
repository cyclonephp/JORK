<?php

namespace cyclone\jork\model;

/**
 * Iterator for implementation used both to iterate through the properties of a model instance
 *
 * @author Bence Erős <crystal@cyclonephp.org>
 * @package JORK
 */
class Iterator extends \ArrayIterator {

    public function  current() {
        $rval = parent::current();
        return $rval['value'];
    }

}