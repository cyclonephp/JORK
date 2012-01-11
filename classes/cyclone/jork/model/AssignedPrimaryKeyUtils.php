<?php

namespace cyclone\jork\model;

use cyclone\jork;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class AssignedPrimaryKeyUtils {

    private static $_inst;

    /**
     * @return cyclone\jork\model\AssignedPrimaryKeyUtils
     */
    public static function inst() {
        if (NULL === self::$_inst) {
            self::$_inst = new AssignedPrimaryKeyUtils;
        }
        return self::$_inst;
    }

    private $_pk_registry = array();

    private function __construct() {
        // empty private constructor
    }

    
    public function register_old_pk($class, $old_pk, $new_pk) {
        $pk_registry = &$this->_pk_registry;
        if ( ! isset($pk_registry[$class])) {
            $pk_registry[$class] = array();
        }

        $pk_registry[$class][$new_pk] = $old_pk;
    }

    /**
     * @param string $class the name of the model class
     * @param scalar $new_pk
     */
    public function old_pk_exists($class, $new_pk) {
        $pk_registry = &$this->_pk_registry;
        if ( ! isset($pk_registry[$class]))
            return FALSE;

        if ( ! isset($pk_registry[$class][$new_pk]))
            return FALSE;

        return TRUE;
    }

    /**
     *
     * @param string $class
     * @param scalar $new_pk
     * @return scalar
     */
    public function get_old_pk($class, $new_pk) {
        $pk_registry = &$this->_pk_registry;
        if ( ! isset($pk_registry[$class]))
            throw new jork\Exception("failed to retrieve old primary key of entity '" . $class . "' #" . $new_pk);

        if ( ! isset($pk_registry[$class][$new_pk]))
            throw new jork\Exception("failed to retrieve old primary key of entity '" . $class . "' #" . $new_pk);

        return $pk_registry[$class][$new_pk];
    }
}