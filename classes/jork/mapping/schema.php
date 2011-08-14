<?php

/**
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
 class JORK_Mapping_Schema {

    public $db_conn = 'default';

    public $class;

    public $table;

    public $secondary_tables;

    public $atomics;

    public $components;

    public function primary_key() {
        foreach ($this->atomics as $name => $def) {
            if (array_key_exists('primary', $def))
                return $name;
        }
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

    public function table_name_for_column($col_name) {
        return array_key_exists('table', $this->atomics[$col_name])
                ? $this->atomics[$col_name]
                : $this->table;
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
