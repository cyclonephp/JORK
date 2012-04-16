<?php

namespace cyclone\jork\model;

use cyclone\jork;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
abstract class EmbeddableModel extends AbstractModel {

    private static $_instances = array();

    public final static function setup() {

    }

    public static function setup_embeddable(jork\schema\EmbeddableSchema $schema) {

    }

    protected static function _inst($class) {
        if ( ! isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class;
        }
        return self::$_instances[$class];
    }

    public function set_schema(jork\schema\EmbeddableSchema $schema) {
        $this->_schema = $schema;
    }

}
