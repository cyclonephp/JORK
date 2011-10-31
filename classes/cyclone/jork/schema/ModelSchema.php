<?php

namespace cyclone\jork\schema;

use cyclone\jork;
use cyclone as cy;

/**
 * @author Bence Erős <crystal@cyclonephp.com>
 * @package JORK
 */
 class ModelSchema {

     /**
      * The name of the database connection.
      *
      * @var string
      */
    public $db_conn = 'default';

    /**
     * The name of the model class which the schema instance provides
     * mapping schema for.
     *
     * @var string
     */
    public $class;

    /**
     * The name of the database table where the primitive properties
     * of the class should be mapped.
     *
     * @var string
     */
    public $table;

    /**
     * @var array<SecondaryTableSchema>
     */
    public $secondary_tables;

    /**
     * @var array<PrimitivePropertySchema>
     */
    public $primitives = array();

    /**
     * @var array<ComponentSchema>
     */
    public $components = array();

    /**
     * Embedded component name => name of EmbeddedComponentSchema subclass
     *
     * @var array<string>
     */
    public $embedded_components = array();

    /**
     * Setter for the <code>$db_conn</code> property.
     *
     * @param string $db_conn
     * @return ModelSchema <code>$this</code>
     */
    public function db_conn($db_conn) {
        $this->db_conn = $db_conn;
        return $this;
    }

    /**
     *
     * @param string $class
     * @return ModelSchema
     */
    public function clazz($class) {
        $this->class = $class;
        return $this;
    }

    /**
     * Setter for the <code>$table</code> property.
     *
     * @param string $table
     * @return ModelSchema
     */
    public function table($table) {
        $this->table = $table;
        return $this;
    }

    public function secondary_table(SecondaryTableSchema $secondary_table) {
        $this->secondary_tables [$secondary_table->name] = $secondary_table;
        return $this;
    }

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
     * @param string $name
     * @param string $classname
     * @return ModelSchema
     */
    public function embedded_component($name, $classname) {
        $this->embedded_components[$name] = $classname;
        return $this;
    }
    

    public function primary_key() {
        foreach ($this->primitives as $name => $def) {
            if ($def->is_primary_key)
                return $name;
        }
    }

    public function get_property_schema($name) {
        if (isset($this->primitives[$name]))
            return $this->primitives[$name];

        if (isset($this->components[$name]))
            return $this->components[$name];
        
        throw new jork\SchemaException("property '$name' of {$this->class} does not exist");
    }

    public function table_name_for_column($col_name) {
        return isset($this->primitives[$col_name]->table)
                ? $this->prmitives[$col_name]->table
                : $this->table;
    }

    public function is_to_many_component($comp_name) {
        $comp_schema = $this->components[$comp_name];
        if ($comp_schema instanceof EmbeddableSchema)
            // embedded components are always to-one components by nature
            return FALSE;
        if ( ! isset($comp_schema->mapped_by))
            return $comp_schema->type == cy\JORK::ONE_TO_MANY
                || $comp_schema->type == cy\JORK::MANY_TO_MANY;

        $remote_comp_schema = jork\model\AbstractModel::schema_by_class($comp_schema['class'])
            ->components[$comp_schema->mapped_by];

        return $remote_comp_schema->type == cy\JORK::MANY_TO_MANY
            || $remote_comp_schema->type == cy\JORK::MANY_TO_ONE;
    }
    
}
