<?php

namespace cyclone\jork\schema;
use cyclone\jork;

/**
 * @author Bence Erős <crystal@cyclonephp.org>
 * @package JORK
 */
class EmbeddableSchema {

    public $primitives = array();

    public $components = array();

    public $table;

    public $class;

    /**
     * @param PrimitivePropertySchema $schema
     * @return EmbeddableSchema
     */
    public function primitive(PrimitivePropertySchema $schema) {
        $this->primitives[$schema->name] = $schema;
        return $this;
    }

    /**
     * @param ComponentSchema $schema
     * @return EmbeddableSchema
     */
    public function component(ComponentSchema $schema) {
        $this->components[$schema->name] = $schema;
        return $this;
    }

    /**
     *
     * @var ModelSchema
     */
    protected $_parent_schema;

    public function  __construct(ModelSchema $parent_schema, $class) {
        $this->_parent_schema = $parent_schema;
        $this->class = $class;
    }

    public function primary_key() {
        return $this->_parent_schema->primary_key();
    }

    /**
     * @param string $name the name of the property which' schema will be queried
     * @return mixed @c PrimitivePropertySchema or @c ComponentSchema
     */
    public function get_property_schema($name) {
        if (isset($this->primitives[$name]))
            return $this->primitives[$name];

        if (isset($this->components[$name]))
            return $this->components[$name];

        throw new jork\SchemaException("property '$name' of {$this->class} does not exist");
    }

    /**
     * @param string $comp_name
     * @return boolean <code>TRUE</code> if the component is a to-many component
     * @throws cyclone\jork\SchemaException if this model doesn't have a component named <code>$comp_name</code>
     */
    public function is_to_many_component($comp_name) {
        if ( ! isset($this->components[$comp_name]))
             throw new jork\SchemaException("embeddable model schema '{$this->class}' does not have property '$comp_name'");
             
        $comp_schema = $this->components[$comp_name];
        
        if ( ! isset($comp_schema->mapped_by))
            return $comp_schema->type == JORK::ONE_TO_MANY
                || $comp_schema->type == JORK::MANY_TO_MANY;

        $remote_comp_schema = jork\AbstractModel::schema_by_class($comp_schema->class)
            ->components[$comp_schema->mapped_by];

        return $remote_comp_schema->type == JORK::MANY_TO_MANY
            || $remote_comp_schema->type == JORK::MANY_TO_ONE;
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

}
