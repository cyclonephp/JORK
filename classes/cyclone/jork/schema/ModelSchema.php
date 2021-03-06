<?php

namespace cyclone\jork\schema;

use cyclone\jork;
use cyclone as cy;
use cyclone\jork\schema;
use cyclone\jork\schema\SchemaPool;

/**
 * @author Bence Erős <crystal@cyclonephp.org>
 * @package JORK
 */
 class ModelSchema {

     /**
      * @return ModelSchema
      */
     public static function factory() {
         return new ModelSchema;
     }

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
     * The object representing the inheritance mapping strategy.
     * It is recommended to create it using one of the following static
     * factory methods:
     * <ul>
     *  <li>@c cyclone\JORK::single_table()</li>
     *  <li>@c cyclone\JORK::joined_subclass()</li>
     *  <li>@c cyclone\JORK::table_per_class()</li>
     * </ul>
     *
     * @var cyclone\jork\schema\Inheritance
     */
    public $inheritance_strategy;

    /**
     *
     * @var 
     */
    public $discriminator_value;

    /**
     * Stores the natural ordering of the entity. Every @c Ordering instance
     * defines a property name / direction pair.
     *
     * These ordering will be applied on JORK queries which' root entity (explicit
     * or implicit) is @c $class .
     *
     * @var array<Ordering>
     */
    public $natural_ordering = array();

    private $_pk_primitives;

    private $_pk_strategies;

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


    /**
     * @param schema\Inheritance $inheritance_strategy
     * @return ModelSchema 
     */
    public function inheritance_strategy(schema\Inheritance $inheritance_strategy) {
        $this->inheritance_strategy = $inheritance_strategy;
        return $this;
    }

    /**
     *
     * @param string $property a valid property name of the entity class
     *  which exists as an array key in @c $primitives
     * @param string $direction the direction of the ordering. It's possible
     *  values are <code>asc</code> and <code>desc</code>
     * @return ModelSchema
     */
    public function natural_ordering($property, $direction = 'asc') {
        $this->natural_ordering []= new Ordering($property, $direction);
        return $this;
    }

    public function primary_key() {
        throw new \Exception("deprecated");
    }

    public function primary_keys() {
        if (count($this->_pk_primitives) > 0)
            return $this->_pk_primitives;
        
        foreach ($this->primitives as $name => $def) {
            if ( ! is_null($def->primary_key_strategy)) {
                $this->_pk_primitives []= $name;
            }
        }
        if (empty($this->_pk_primitives))
            throw new jork\Exception("no primary key found for schema " . $this->class);

        return $this->_pk_primitives;
    }

    public function primary_key_strategy() {
        if (count($this->_pk_strategies) > 0)
            return $this->_pk_strategies;

        $this->_pk_strategies = array();
        foreach ($this->primitives as $name => $def) {
            $candidate = $def->primary_key_strategy;
            if ( ! is_null($candidate))
                $this->_pk_strategies []= $candidate;
        }
        if (count($this->_pk_strategies) == 0)
            throw new jork\Exception("no primary key found for schema " . $this->class);

        return $this->_pk_strategies;
    }

    public function primary_key_info() {
        if ( ! (empty($this->_pk_primitives) || empty($this->_pk_strategies)))
            return array($this->_pk_primitives, $this->_pk_strategies);

        $this->_pk_primitives = array();
        $this->_pk_strategies = array();
        foreach ($this->primitives as $name => $def) {
            if ( ! is_null($def->primary_key_strategy)) {
                $this->_pk_primitives []= $name;
                $this->_pk_strategies []= $def->primary_key_strategy;
            }
        }
        if (empty($this->_pk_primitives) || empty($this->_pk_strategies))
            throw new jork\Exception("no primary key found for schema " . $this->class);

        return array($this->_pk_primitives, $this->_pk_strategies);
    }

    public function get_property_schema($name) {
        if (isset($this->primitives[$name]))
            return $this->primitives[$name];

        if (isset($this->components[$name]))
            return $this->components[$name];

        if (isset($this->embedded_components[$name]))
            return $this->embedded_components[$name];
        
        throw new jork\SchemaException("property '$name' of {$this->class} does not exist");
    }

    public function column_exists($col_name) {
        if (isset($this->primitives[$col_name])
                && is_null($this->primitives[$col_name]->column))
            return TRUE;
        
        foreach ($this->primitives as $prim_schema) {
            if ($prim_schema->column == $col_name)
                 return TRUE;
        }
        return FALSE;
    }

    public function table_name_for_property($prop_name) {
        return isset($this->primitives[$prop_name]->table)
                ? $this->primitives[$prop_name]->table
                : $this->table;
    }

    /**
     * @param string $col_name
     * @return PrimitivePropertySchema
     */
    public function primitive_by_col($col_name) {
        foreach ($this->primitives as $prim) {
            if ($prim->column === $col_name)
                return $prim;
        }
        if (isset($this->primitives[$col_name]))
            return $this->primitives[$col_name];
        throw new jork\SchemaException("no property is mapped to column '$col_name' in class " . $this->class);
    }

    /**
     * Returns the nam of the table which contains the column <code>$col_name</code>.
     * If <code>$col_name</code> is <code>NULL</code> then the column name of
     * the primary key property will be written to it and the return value
     * will be the name of the table which contains the primary key (which is
     * the same as <code>$this->table</code>).
     *
     * @param string $col_name
     * @return string
     */
    public function table_name_for_column(&$col_name) {
        if (NULL === $col_name) {
            $pk_prop_name = $this->primary_key();
            $pk_schema = $this->primitives[$pk_prop_name];
            $col_name = NULL === $pk_schema->column
                    ? $pk_schema->name
                    : $pk_schema->column;
            return $this->table;
        }
        foreach ($this->primitives as $prim_schema) {
            $tmp_col_name = NULL === $prim_schema->column
                    ? $prim_schema->name
                    : $prim_schema->column;
            if ($tmp_col_name == $col_name) {
                return NULL === $prim_schema->table
                        ? $this->table
                        : $prim_schema->table;
            }
        }
    }

     /**
      * @param $col_names array the names of the column names
      * @return array
      */
    public function table_names_for_columns(&$col_names) {
        $rval = array();
        if (count($col_names) == 0) {
            $pk_prop_names = $this->primary_keys();
            foreach ($pk_prop_names as $pk_prop_name) {
                $pk_schema = $this->primitives[$pk_prop_name];
                $col_names []= NULL === $pk_schema->column
                    ? $pk_schema->name
                    : $pk_schema->column;
                $rval []= NULL === $pk_schema->table
                    ? $this->table
                    : $pk_schema->table;
            }
            return $rval;
        }
        foreach ($this->primitives as $prim_schema) {
            $tmp_col_name = NULL === $prim_schema->column
                ? $prim_schema->name
                : $prim_schema->column;
            foreach($col_names as $idx => $col_name) {
                if ($col_name == $tmp_col_name) {
                    $rval[$idx] = NULL === $prim_schema->table
                        ? $this->table
                        : $prim_schema->table;
                }
            }
        }
        return $rval;
    }

    public function is_to_many_component($comp_name) {
        if (isset($this->embedded_components[$comp_name]))
            return FALSE;
        
        $comp_schema = $this->components[$comp_name];
        if ( ! is_object($comp_schema)) {
            //var_dump($comp_schema); die();
        }
        if ($comp_schema instanceof EmbeddableSchema)
            // embedded components are always to-one components by nature
            return FALSE;
        if ( ! isset($comp_schema->mapped_by))
            return $comp_schema->type == cy\JORK::ONE_TO_MANY
                || $comp_schema->type == cy\JORK::MANY_TO_MANY;

        $remote_comp_schema = SchemaPool::inst()->get_schema($comp_schema->class)
            ->components[$comp_schema->mapped_by];

        return $remote_comp_schema->type == cy\JORK::MANY_TO_MANY
            || $remote_comp_schema->type == cy\JORK::MANY_TO_ONE;
    }
    
}
