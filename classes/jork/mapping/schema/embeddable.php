<?php

/**
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Mapping_Schema_Embeddable {

    public $atomics = array();

    public $components = array();

    public $table;

    public $class;

    /**
     *
     * @var JORK_Mapping_Schema
     */
    protected $_parent_schema;

    public function  __construct(JORK_Mapping_Schema $parent_schema, $class) {
        $this->_parent_schema = $parent_schema;
        $this->class = $class;
    }

    public function primary_key() {
        return $this->_parent_schema->primary_key();
    }

    public function get_property_schema($name) {
        foreach ($this->atomics as $k => $v) {
            if ($k == $name)
                return $v;
        }
        foreach ($this->components as $k => $v) {
            if ($k == $name)
                return $v;
        }
        throw new JORK_Schema_Exception("property '$name' of {$this->class} does not exist");
    }

    public function is_to_many_component($comp_name) {
        $comp_schema = $this->components[$comp_name];
        if ($comp_schema instanceof JORK_Mapping_Schema_Embeddable)
            // embedded components are always to-one components by nature
            return FALSE;
        if ( ! array_key_exists('mapped_by', $comp_schema))
            return $comp_schema['type'] == JORK::ONE_TO_MANY
                || $comp_schema['type'] == JORK::MANY_TO_MANY;

        $remote_comp_schema = JORK_Model_Abstract::schema_by_class($comp_schema['class'])
            ->components[$comp_schema['mapped_by']];

        return $remote_comp_schema['type'] == JORK::MANY_TO_MANY
            || $remote_comp_schema['type'] == JORK::MANY_TO_ONE;
    }

}
