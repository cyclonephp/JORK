<?php

namespace cyclone\jork\model;

/**
 * Iterator for implementation used both to
 * - iterate through model collections
 * - iterate through the properties of a model instance
 */
class Iterator extends \ArrayIterator {

    public function  current() {
        $rval = parent::current();
        return $rval['value'];
    }

}