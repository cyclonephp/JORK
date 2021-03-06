<?php

namespace cyclone\jork\schema;

/**
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.org>
 */
class JoinTableSchema {

    public $name;

    public $join_columns = array();

    public $inverse_join_columns = array();

    /**
     *
     * @param string $name
     * @param string $join_column
     * @param string $inverse_join_column
     */
    public function __construct($name, $join_column, $inverse_join_column) {
        $this->name = $name;
        $this->join_columns []= $join_column;
        $this->inverse_join_columns []= $inverse_join_column;
    }

    public function name($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * The name of the join column in the join table - to the (local) model table.
     *
     * @param string $column
     * @return JoinTableSchema
     */
    public function join_column($column) {
        $this->join_columns []= $column;
        return $this;
    }

    /**
     * The name of the join column in the join table - to the foreign table.
     *
     * @param string $column
     * @return JoinTableSchema
     */
    public function inverse_join_column($column) {
        $this->inverse_join_columns []= $column;
        return $this;
    }

}