<?php

namespace cyclone\jork\schema;

/**
 * This class represents the mapping schema for a secondary table in a model schema.
 * Secondary tables are useful when you want to store some primitive properties
 * of a model in other tables, not in the "primary" table of the model.
 *
 * @see cyclone\\jork\\schema\\ModelSchema::$secondary_tables
 * @package JORK
 * @author Bence Eros <bence.eros@cyclonephp.org>
 */
class SecondaryTableSchema {

    /**
     * The table name.
     *
     * @var name
     */
    public $name;

    /**
     * The name of the join columns in the table of the model class which' schema
     * this secondary table belongs to.
     *
     * @var string
     */
    public $join_columns = array();

    /**
     * The name of the join columns in the secondary table.
     *
     * @var string
     */
    public $inverse_join_columns = array();

    function __construct($name, $join_column, $inverse_join_column) {
        $this->name = $name;
        if ($join_column !== NULL) {
            $this->join_columns []= $join_column;
        }
        if ($inverse_join_column !== NULL) {
            $this->inverse_join_columns []= $inverse_join_column;
        }
    }

            /**
     * Setter for the <code>$name</code> property.
     *
     * @param string $name
     * @return SecondaryTableSchema
     */
    public function name($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Setter for the <code>$join_column</code> property.
     *
     * @param string $column
     * @return SecondaryTableSchema
     */
    public function join_column($column) {
        $this->join_columns []= $column;
        return $this;
    }

    /**
     * Setter for the <code>$inverse_join_column</code> property.
     *
     * @param string $column
     * @return SecondaryTableSchema
     */
    public function inverse_join_column($column) {
        $this->inverse_join_columns []= $column;
        return $this;
    }
    
}