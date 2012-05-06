<?php

namespace cyclone\jork\schema;

/**
 * This class represents the mapping schema to be used for mapping a composite
 * (object) proprety of the instances of a given JORK model class.
 *
 * @package JORK
 * @author Bence Eros <bence.eros@cyclonephp.org>
 */
class ComponentSchema {

    /**
     * The name of the model property.
     *
     * @var string
     */
    public $name;

    /**
     * The name of the class of the property.
     *
     * @var string
     */
    public $class;

    /**
     * The cardinality of the connection between the classes. Can be one of the
     * followings:
     * <ul>
     *  <li>cyclone\\JORK::ONE_TO_ONE</li>
     *  <li>cyclone\\JORK::ONE_TO_MANY</li>
     *  <li>cyclone\\JORK::MANY_TO_ONE</li>
     *  <li>cyclone\\JORK::MANY_TO_MANY</li>
     * </ul>
     *
     *
     * @var string
     */
    public $type;

    /**
     * In the case of one-to-one and many-to-one connections, it is the name of
     * the local join column. In the case of one-to-many connections, it is the
     * name of the foreign join column.
     *
     * @var string
     */
    public $join_columns = array();

    /**
     * In the case of one-to-one and many-to-one connections, it is the name of
     * the foreign join column. In the case of one-to-many connections, it is the
     * name of the local join column.
     *
     * @var string
     */
    public $inverse_join_columns = array();

    /**
     * The mapping schema of the join table used to map a many-to-many relation.
     * If the component is not in a many-to-many relation with the model, then
     * this property should be omitted.
     *
     * @var JoinTableSchema
     */
    public $join_table;

    /**
     * Determines what to do with this component if the owner model is deleted.
     * Possible values:
     * <ul>
     *  <li><code>cyclone\JORK::SET_NULL</code>: the value of the join column will be set to <code>NULL</code></li>
     *  <li><code>cyclone\JORK::CASCADE</code>: the component object will be deleted too with the model instance.
     * </ul>
     *
     * @var string
     */
    public $on_delete;

    /**
     * If the mapping shema for the connection is already defined in the other class
     * (assuming that the connection is bi-directional), then it's recommended
     * to set the <code>$mapped_by</code> property of the model to the name of the
     * corresponding component of the connected model class.
     *
     * If the <code>$mapped_by</code> property of a component schema is set, then
     * the <code>$class</code> property must also be set, and everything else must
     * be omitted, since in this case all mapping information will be read from the
     * referenced component schema.
     *
     * @var string
     */
    public $mapped_by;

    public function __construct($name, $class) {
        $this->name = $name;
        $this->class = $class;
    }

        /**
     * Setter for the <code>$name</code> property.
     *
     * @param <type> $name
     * @return ComponentSchema
     */
    public function name($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Setter for the <code>$class</code> property.
     *
     * @param string $class
     * @return ComponentSchema
     */
    public function clazz($class) {
        $this->class = $class;
        return $this;
    }

    /**
     * Setter for the <code>type</code> property.
     *
     * @param string $type
     * @return ComponentSchema
     */
    public function type($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Setter for the <code>$join_column</code> property.
     *
     * @param string $column
     * @return ComponentSchema
     */
    public function join_column($column) {
        $this->join_columns []= $column;
        return $this;
    }

    /**
     * Setter for the <code>$inverse_join_column</code> property.
     *
     * @param string $column
     * @return ComponentSchema
     */
    public function inverse_join_column($column) {
        $this->inverse_join_columns []= $column;
        return $this;
    }

    /**
     * Setter for the <code>$join_table</code> property.
     *
     * @param JoinTableSchema $join_table
     * @return ComponentSchema <code>$this</code>
     */
    public function join_table(JoinTableSchema $join_table) {
        $this->join_table = $join_table;
        return $this;
    }

    /**
     * Setter for the <code>$on_delete</code> property.
     *
     * @param string $on_delete
     * @return ComponentSchema
     */
    public function on_delete($on_delete) {
        $this->on_delete = $on_delete;
        return $this;
    }

    /**
     * Setter for the <code>$mapped_by</code> property.
     *
     * @param string $mapped_by
     * @return ComponentSchema
     */
    public function mapped_by($mapped_by) {
        $this->mapped_by = $mapped_by;
        return $this;
    }
}