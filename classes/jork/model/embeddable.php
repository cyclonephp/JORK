<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
abstract class JORK_Model_Embeddable extends JORK_Model_Abstract {

    private static $_instances = array();

    protected static function _inst($class) {
        if ( ! array_key_exists($class, self::$_instances)) {
            self::$_instances[$class] = new $class;
        }
        return self::$_instances[$class];
    }

}
