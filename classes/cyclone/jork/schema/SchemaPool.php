<?php

namespace cyclone\jork\schema;

use cyclone as cy;
use cyclone\autoloader;

use cyclone\jork;

/**
 * This class is a singleton which is responsible for the in-memory management of the
 * mapping schemas of the model classes.
 *
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.com>
 */
class SchemaPool {

    /**
     * @var ShemaPool the singleton instance.
     */
    private static $_inst;

    /**
     * Singleton accessor method.
     *
     * @return SchemaPool singleton instance
     * @throws \cyclone\jork\SchemaException if any of the
     *  <code>modelclass::inst()</code> methods are not or not properly implemented
     */
    public static function inst() {
        if (NULL === self::$_inst) {
            self::$_inst = new SchemaPool;
        }
        return self::$_inst;
    }

    private $_pool = array();

    private function __construct() {return;
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

    /**
     * Returns the names of the classes which a mapping schema belongs to.
     *
     * @return array<string>
     */
    public function get_mapped_classes() {
        return array_keys($this->_pool);
    }

    /**
     * Returns the mapping schema belonging to the class <code>$class_name</code>.
     *
     * @param $class_name string
     * @return \cyclone\jork\schema\ModelSchema
     * @throws \cyclone\jork\SchemaException
     */
    public function get_schema($class_name) {
        if ( ! isset($this->_pool[$class_name]))
            throw new jork\SchemaException("unknown model class: '$class_name'");

        return $this->_pool[$class_name];
    }

    public function schema_exists($classname) {
        return isset($this->_pool[$classname]);
    }

    public function add_schema($classname, $schema) {
        $this->_pool[$classname] = $schema;
        if (NULL === $schema->class) {
            $schema->class = $classname;
        } elseif ($schema->class !== $classname)
            throw new jork\SchemaException("\$schema->class ({$schema->class}) should be NULL or equal to \$classname ($classname)");
        if ($schema instanceof jork\schema\ModelSchema) {
            $this->load_embedded_schemas($schema);
        }
    }

    private function load_embedded_schemas(jork\schema\ModelSchema $schema) {
        foreach ($schema->embedded_components as $k => &$v) {
            $emb_schema = new EmbeddableSchema($schema, $v);
            call_user_func(array($v, 'setup_embeddable'), $emb_schema);
            $emb_schema->table = $schema->table;
            $v = $emb_schema;
        }
    }

    /**
     * Returns the currently loaded schemas. Array keys are classnames (strings),
     * values are @c \cyclone\jork\schema\ModelSchema or @c \cyclone\jork\schema\EmbeddableSchema
     * instances.
     *
     * @return array
     */
    public function get_schemas() {
        return $this->_pool;
    }

    /**
     * Replaces the currently loaded mapping schemas with <code>$schemas</code>. Only
     * for unit testing purposes.
     *
     * <p>
     * <strong>Do NOT use this method!</strong>
     * </p>
     *
     * @param $schemas array<\cyclone\jork\schema\ModelSchema> array keys must be
     *  class names, values should be @v \cyclone\jork\schema\ModelSchema instances.
     * @throws \cyclone\jork\SchemaException
     */
    public function set_schemas($schemas) {
        foreach ($schemas as $classname => $schema) {
            if (NULL === $schema->class) {
                $schema->class = $classname;
            } elseif ($schema->class !== $classname)
                throw new jork\SchemaException("array key for schema '{$schema->class}' is '{$classname}'");

            $this->load_embedded_schemas($schema);
        }
        $this->_pool = $schemas;
    }

}