<?php

namespace cyclone\jork\schema;

use cyclone as cy;

/**
 * This class represents the mapping schema to be used for mapping a primitive
 * (scalar) proprety of the instances of a given JORK model class.
 *
 * @package JORK
 * @author Bence Eros <crystal@cyclonephp.com>
 */
class PrimitivePropertySchema {

    /**
     * The name of the property.
     *
     * @var string
     */
    public $name;

    /**
     * The type of the model property.
     *
     * @var string
     */
    public $type;

    /**
     * This value indicates if the property is a primary key or not. If it is
     * not the primary key of the model, then its value must be <code>NULL</code>.
     * Otherwise <code>cyclone\JORK::AUTO</code> or <code>cyclone\JORK::ASSIGN</code>
     * indicating the primary key generation strategy.
     *
     * @var string
     */
    public $primary_key_strategy = NULL;

    /**
     * TODO
     *
     * @var 
     */
    public $generation_strategy;

    /**
     *
     * @var array
     */
    public $constraints;

    /**
     * The name of the database column that staores the property. If it's <code>NULL</code>
     * then the name of the property will be used as the column name.
     *
     * @var string
     */
    public $column;

    /**
     * The name if the table that stores the property (ie. contains the
     * <code>$column</code> database column). If it's <code>NULL</code> then the
     * table of the model schema will be used.
     *
     * @var string
     * @see cyclone\\jork\\schema\\ModelSchema::$table
     */
    public $table;

    public function __construct($name, $type) {
        $this->name = $name;
        $this->type = $type;
    }

        /**
     * Setter for the <code>$name</code> property.
     *
     * @param string $name
     * @return PrimitivePropertySchema <code>$this</code>
     */
    public function name($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Setter for the <code>$type</code> property.
     *
     * @param string $type
     * @return PrimitivePropertySchema <code>$this</code>
     */
    public function type($type) {
        $this->type = $type;
        return $this;
    }


    /**
     * Setter for the <code>$primary_key_strategy</code> property.
     *
     * @param string $primary_key_strategy
     * @return PrimitivePropertySchema
     */
    public function primary_key($primary_key_strategy = cy\JORK::AUTO) {
        $this->primary_key_strategy = $primary_key_strategy;
        return $this;
    }

    /**
     * Setter for the <code>$column</code> property.
     *
     * @param string $column
     * @return PrimitivePropertySchema <code>$this</code>
     */
    public function column($column) {
        $this->column = $column;
        return $this;
    }


    /**
     *
     * @param string $generation_strategy
     * @return PrimitivePropertySchema <code>$this</code>
     */
    public function generation_strategy($generation_strategy) {
        $this->generation_strategy = $generation_strategy;
        return $this;
    }

    /**
     * Setter for the <code>$table</code> property.
     *
     * @param string $table
     * @return PrimitivePropertySchema
     */
    public function table($table) {
        $this->table = $table;
        return $this;
    }

}