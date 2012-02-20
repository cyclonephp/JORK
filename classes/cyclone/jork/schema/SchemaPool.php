<?php

namespace cyclone\jork\schema;

use cyclone as cy;
use cyclone\autoloader;

use cyclone\jork;

/**
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.com>
 */
class SchemaPool {

    private static $_inst;

    /**
     *
     * @return SchemaPool
     * @throws cyclone\jork\SchemaException if any of the
     *  <code>modelclass::inst()</code> methods are not or not properly implemented
     */
    public static function inst() {
        if (NULL === self::$_inst) {
            self::$_inst = new SchemaPool;
        }
        return self::$_inst;
    }

    private $_pool = array();

    private function __construct() {
        $cfg = cy\Config::inst()->get('jork');
        if ( ! (isset($cfg['mapped_classes']) || isset($cfg['mapped_namespaces'])))
            throw new jork\Exception("neither 'jork.mapped_classes' nor 'jork.mapped_namespaces' configuration key has been found");;

        $classnames = array();
        if (isset($cfg['mapped_classes'])) {
            $classnames = $cfg['mapped_classes'];
        }
        if (isset($cfg['mapped_namespaces'])) {
            foreach ($cfg['mapped_namespaces'] as $ns) {
                $classnames = cy\Arr::merge($classnames
                        , autoloader\AbstractAutoloader::get_classnames($ns));
            }
        }
        foreach ($classnames as $classname) {
            $ref_class = new \ReflectionClass($classname);
            try  {
                $ref_method = $ref_class->getMethod('inst');
            } catch (\ReflectionException $ex) {
                throw new jork\SchemaException("$classname::inst() method doesn't exist");
            }
            if ( ! $ref_method->isStatic())
                throw new jork\SchemaException("$classname::inst() is not a static method");

            if ( ! $ref_method->isPublic())
                throw new jork\SchemaException("$classname::inst() is not a public method");
            
            $instance = call_user_func(array($classname, 'inst'));
            if ( ! ($instance instanceof  $classname)) {
                $actualtype = is_object($instance)
                    ? get_class($instance) . ' instance'
                    : gettype($instance);
                throw new jork\SchemaException("$classname::inst() returned a(n) "
                        . $actualtype . " instead of a $classname instance" );
            }
            $this->_pool[$classname] = $instance->schema();
        }
    }

    public function get_mapped_classes() {
        return array_keys($this->_pool);
    }

    public function get_schemas() {
        return $this->_pool;
    }

}