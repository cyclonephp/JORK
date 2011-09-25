<?php

namespace cyclone\jork\schema;

use cyclone\jork;
use cyclone as cy;

/**
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
 class ModelSchema {

    public $db_conn = 'default';

    public $class;

    public $table;

    public $secondary_tables;

    public $atomics;

    public $components;

    public function primary_key() {
        foreach ($this->atomics as $name => $def) {
            if (isset($def['primary']))
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
        throw new jork\SchemaException("property '$name' of {$this->class} does not exist");
    }

    public function table_name_for_column($col_name) {
        return array_key_exists('table', $this->atomics[$col_name])
                ? $this->atomics[$col_name]
                : $this->table;
    }

    public function is_to_many_component($comp_name) {
        $comp_schema = $this->components[$comp_name];
        if ($comp_schema instanceof EmbeddableSchema)
            // embedded components are always to-one components by nature
            return FALSE;
        if ( ! array_key_exists('mapped_by', $comp_schema))
            return $comp_schema['type'] == cy\JORK::ONE_TO_MANY
                || $comp_schema['type'] == cy\JORK::MANY_TO_MANY;

        $remote_comp_schema = jork\model\AbstractModel::schema_by_class($comp_schema['class'])
            ->components[$comp_schema['mapped_by']];

        return $remote_comp_schema['type'] == cy\JORK::MANY_TO_MANY
            || $remote_comp_schema['type'] == cy\JORK::MANY_TO_ONE;
    }
    
}
