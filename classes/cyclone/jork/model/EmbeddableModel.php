<?php

namespace cyclone\jork\model;

use cyclone\jork;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
abstract class EmbeddableModel extends AbstractModel {

    private static $_instances = array();

    public final static function setup() {

    }

    public static function setup_embeddable(jork\schema\EmbeddableSchema $schema) {

    }

    public function __construct(AbstractModel $owner_model, $comp_name) {
        if (NULL === self::$_cfg) {
            self::$_cfg = cy\Config::inst()->get('jork');
        }
        $schema_pool = jork\schema\SchemaPool::inst();
        if ( ! $schema_pool->schema_exists(get_class($this))) {
            $schema = new jork\schema\EmbeddableSchema($owner_model->schema(), get_class($this));
            static::setup_embeddable($schema);
            $schema_pool->add_schema(get_class($this), $schema);
        }
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
