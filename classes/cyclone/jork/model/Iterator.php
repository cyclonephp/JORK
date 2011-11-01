<?php

namespace cyclone\jork\model;

/**
 * Iterator for implementation used both to
 * <ul>
 *  <li> iterate through model collections</li>
 *  <li> iterate through the properties of a model instance</li>
 * </ul>
 */
class Iterator extends \ArrayIterator {

    public function  current() {
        $rval = parent::current();
        return $rval['value'];
    }

}