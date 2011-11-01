<?php

namespace cyclone\jork\schema;
use cyclone\jork;

/**
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
class EmbeddableSchema {

    public $primitives = array();

    public $components = array();

    public $table;

    public $class;

    /**
     * @param PrimitivePropertySchema $schema
     * @return ModelSchema
     */
    public function primitive(PrimitivePropertySchema $schema) {
        $this->primitives[$schema->name] = $schema;
        return $this;
    }

    /**
     *
     * @param <type> $name
     * @param ComponentSchema $schema
     * @return ModelSchema
     */
    public function component(ComponentSchema $schema) {
        $this->components[$schema->name] = $schema;
        return $this;
    }

    /**
     *
     * @var JORK_Mapping_Schema
     */
    protected $_parent_schema;

    public function  __construct(ModelSchema $parent_schema, $class) {
        $this->_parent_schema = $parent_schema;
        $this->class = $class;
    }

    public function primary_key() {
        return $this->_parent_schema->primary_key();
    }

    public function get_property_schema($name) {
        if (isset($this->primitives[$name]))
            return $this->primitives[$name];

        if (isset($this->components[$name]))
            return $this->components[$name];

        throw new jork\SchemaException("property '$name' of {$this->class} does not exist");
    }

    public function is_to_many_component($comp_name) {
        $comp_schema = $this->components[$comp_name];
        
        if ( ! isset($comp_schema->mapped_by))
            return $comp_schema->type == JORK::ONE_TO_MANY
                || $comp_schema->type == JORK::MANY_TO_MANY;

        $remote_comp_schema = jork\AbstractModel::schema_by_class($comp_schema->class)
            ->components[$comp_schema->mapped_by];

        return $remote_comp_schema->type == JORK::MANY_TO_MANY
            || $remote_comp_schema->type == JORK::MANY_TO_ONE;
    }

}
