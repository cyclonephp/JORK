<?php

namespace cyclone\jork\model;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
abstract class EmbeddableModel extends AbstractModel {

    private static $_instances = array();

    protected static function _inst($class) {
        if ( ! isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class;
        }
        return self::$_instances[$class];
    }

}
