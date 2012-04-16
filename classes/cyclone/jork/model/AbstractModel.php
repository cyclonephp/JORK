<?php

namespace cyclone\jork\model;

use cyclone\jork;
use cyclone\jork\query;
use cyclone\db;
use cyclone as cy;

/**
 * The base class for all JORK model classes.
 * 
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
abstract class AbstractModel implements \ArrayAccess, \IteratorAggregate{

    /**
     * Mapping schema should be populated in the implementation of this method.
     *
     * It will only be called when the singleton instance is created. In the
     * method the schema object if accessible via <code>$this->_schema</code>.
     *
     * @usedby JORK_Model_Abstract::_inst()
     */
    public static function setup() {

    }

    /**
     * Stores the singleton instances per-class.
     *
     * @var array<JORK_Model_Abstract>
     * @usedby JORK_Model_Abstract::_inst()
     */
    private static $_instances = array();

    protected static $_cfg;

    /**
     * It should be called only by the subclasses. All subclasses should contain
     * a static method with this code:
     * <code>pubic static function inst() {
     *      return parent::_inst(__CLASS__);
     * }</code>
     *
     * @param string $classname
     * @return JORK_Model_Abstract
     */
    protected static function _inst($classname) {
        if ( ! isset(self::$_instances[$classname])) {
            $inst = new $classname;
//            $inst->_schema = new jork\schema\ModelSchema;
//            $inst->_schema->class = $classname;
//            $inst->setup();
//            foreach ($inst->_schema->embedded_components as $k => &$v) {
//                $emb_inst = call_user_func(array($v, 'inst'));
//                $emb_schema = new jork\schema\EmbeddableSchema($inst->_schema, $v);
//                $emb_inst->_schema = $emb_schema;
//                $emb_inst->setup();
//                $emb_schema->table = $inst->_schema->table;
//                $v = $emb_schema;
//            }
            $inst->_schema = jork\schema\SchemaPool::inst()->get_schema($classname);
            self::$_instances[$classname] = $inst;
        }
        return self::$_instances[$classname];
    }

    /**
     * Loads the JORK configuration for later usage.
     *
     * If you override the constructor in the model classes don't forget about
     * calling @c parent::__construct()
     */
    public function  __construct() {
        if (NULL === self::$_cfg) {
            self::$_cfg = cy\Config::inst()->get('jork');
        }
        $schema_pool = jork\schema\SchemaPool::inst();
        if ( ! $schema_pool->schema_exists(get_class($this))) {
            /*$this->_schema = new jork\schema\ModelSchema();
            $this->setup();*/
            $schema_pool->add_schema(get_class($this), static::setup());
        }
    }

    /**
     * @param string $class
     * @return jork\schema\ModelSchema
     */
    public static function schema_by_class($class) {
        return jork\schema\SchemaPool::inst()->get_schema($class);
    }

    /**
     * Gets the mapping schema of the current entity.
     * 
     * @return \cyclone\jork\schema\ModelSchema
     */
    public function schema() {
        return jork\schema\SchemaPool::inst()->get_schema(get_class($this));
    }

    /**
     * Only to be used by the singleton instance. Other instances should use
     * <code>$this->schema()</code> to get their own mapping schema.
     *
     * @var \cyclone\jork\schema\ModelSchema
     */
    protected $_schema;

    /**
     * Used to store the primitive properties of the entity. All items are 2-item
     * arrays with the following keys:
     * * 'value': the typecasted value of the property
     * * 'persistent': (boolean) determines if the property has been saved since
     * it has been loaded from the database of not. Also FALSE if it hasn't been
     * loaded from the database but it was set by the user.
     *
     * @var array
     */
    protected $_primitives = array();

    /**
     * Used to store the loaded components of the entity. The items are instances
     * of @c jork\model\AbstractModel (for to-one components) or @c jork\model\collection\AbstractCollection
     * (for to-many components).
     *
     * @var array
     */
    protected $_components = array();

    /**
     * Determines if the properties of the entity are all persistent or not.
     * If they are, then there is nothing to do when saving the entity.
     *
     * @var boolean
     */
    protected $_persistent = FALSE;

    /**
     * A set of listeners to be notified when the primary key of the model changes.
     *
     * @var array<jork\model\collection\AbstractCollection>
     */
    protected $_pk_change_listeners = array();

    /**
     * Indicates if the saving process of the entity has already been started
     * or not. It's used to prevent infinite recursion in the case of saving
     * bidirectional relationships.
     *
     * @var boolean
     */
    protected $_save_in_progress = FALSE;

    private $_as_string_in_progress = FALSE;

    /**
     * @return mixed the primary key of the entity
     */
    public function pk() {
        $pk = $this->schema()->primary_key();
        return array_key_exists($pk, $this->_primitives)
                ? $this->_primitives[$pk]['value']
                : NULL;
    }

    /**
     * @param mixed $pk
     * @return JORK_Model_Abstract
     */
    public function get($pk) {
        $schema = $this->schema();
        $result = cy\JORK::from($schema->class)
                ->where($schema->primary_key(), '=', cy\DB::esc($pk))
                ->exec($schema->db_conn);
        switch (count($result)) {
            case 0:
                return NULL;
            case 1:
                return $result[0];
            default:
                throw new jork\Exception('Found multiple entities with primary key ' . $pk);
        }
    }

    public function add_pk_change_listener($listener) {
        $this->_pk_change_listeners []= $listener;
    }

    /**
     * Only for internal usage.
     *
     * Used by <code>JORK_Mapper_Entity::map_row()</code> to initialize the component
     * collections, to be ready when the method calls
     * <code>JORK_Model_Abstract::add_to_component_collections()</code>.
     *
     * @param array $prop_names
     * @usedby JORK_Mapper_Entity::map_row()
     * @see JORK_Model_Abstract::add_to_component_collections()
     */
    public function init_component_collections(&$prop_names) {
        foreach (array_diff_key($prop_names, $this->_components) as $prop => $dummy) {
            if ( ! array_key_exists($prop, $this->_components)) {
                $this->_components[$prop] = array('value' =>
                    collection\AbstractCollection::for_component($this, $prop));
            }
        }
    }

    /**
     * Only for internal usage.
     *
     * Used by <code>JORK_Mapper_Entity::map_row()</code> to quickly load the atomic properties
     * instead of executing <code>JORK_Model_Abstract::__set()</code> each time.
     *
     * @param array $atomics
     * @usedby JORK_Mapper_Entity::map_row()
     */
    public function populate_atomics($atomics) {
        $schema = $this->schema();
        if (self::$_cfg['force_type']) {
            foreach ($atomics as $k => $v) {
                $this->_primitives[$k] = array(
                    'value' => $this->force_type($v, $schema->primitives[$k]->type),
                    'persistent' => TRUE
                );
            }
        } else {
            foreach ($atomics as $k => $v) {
                $this->_primitives[$k] = array(
                    'value' => $v,
                    'persistent' => TRUE
                );
            }
        }
    }

    /**
     *
     * @param array $properties
     * @param boolean $strict if FALSE then the properties in <code>$properties</code>
     * 	which don't exist in the model properties will be skipped without any warnings.
     *  Otherwise an exception will be thrown on non-existent properties.
     * @throws cyclone\jork\Exception
     */
    public function populate($properties, $strict = TRUE) {
        $schema = $this->schema();
        foreach ($properties as $name => $value) {
            if (isset($schema->primitives[$name])) {
                $this->__set($name, $value);
            } elseif (isset($schema->components[$name]) && ! empty($value)) {
                $comp_elem_class = $schema->components[$name]->class;
                if ($schema->is_to_many_component($name)) {
                    $coll = $this->__get($name);
                    foreach ($properties[$name] as $elem_arr) {
                        $elem = new $comp_elem_class;
                        $elem->populate($elem_arr, $strict);
                        $coll->append($elem);
                    }
                } else {
                    $elem = new $comp_elem_class;
                    $elem->populate($value, $strict);
                    $this->__set($name, $elem);
                }
            } elseif (isset($schema->embedded_components[$name])) {
                $comp_elem_class = $schema->embedded_components[$name]->class;
                $elem = new $comp_elem_class;
                $elem->populate($value, $strict);
                $this->__set($name, $elem);
            } elseif ($strict)
                throw new jork\Exception("unknown property '$name' of entity '{$schema->class}'");
        }
    }

    /**
     * Only for internal usage.
     *
     * Used by <code>JORK_Mapper_Entity::map_row()</code> to quickly load the to-one components
     * instead of executing <code>JORK_Model_Abstract::__set()</code> each time.
     *
     * @param array<JORK_Model_Abstract> $components
     * @usedby JORK_Mapper_Entity::map_row()
     */
    public function set_components($components) {
        foreach ($components as $k => $v) {
            $this->_components[$k] = array(
                'value' => $v,
                'persistent' => TRUE
            );
        }
    }

    /**
     * Only for internal usage.
     *
     * Used by <code>JORK_Mapper_Entity::map_row()</code> to quickly load the to-many components
     * instead of executing <code>JORK_Model_Abstract::__set()</code> each time.
     *
     * @param array<JORK_Model_Abstract> $components
     * @usedby JORK_Mapper_Entity::map_row()
     */
    public function add_to_component_collections($components) {
        foreach ($components as $prop_name => $new_comp) {
            $this->_components[$prop_name]['value'][$new_comp->pk()] = $new_comp;
        }
    }

    /**
     *
     * @param string $key
     * @param JORK_Model_Abstract $val
     * @param array $comp_schema
     */
    protected function update_component_fks_reverse($key, $val, $comp_schema) {
        $remote_schema = $val->schema()->components[$comp_schema->mapped_by];
        if (NULL === $val) {
            switch ($remote_schema->type) {
                case cy\JORK::ONE_TO_MANY:
                    $this->_primitives[$remote_schema->join_column]['value'] = NULL;
                    $this->_primitives[$remote_schema->join_column]['persistent'] = FALSE;
                    break;
                case cy\JORK::ONE_TO_ONE:
                    $val->_atomics[$remote_schema->join_column]['value'] = NULL;
                    $val->_atomics[$remote_schema->join_column]['persistent'] = FALSE;
                    break;
            }
        } else {
            switch ($remote_schema->type) {
                case cy\JORK::ONE_TO_MANY:
                    $this->_primitives[$remote_schema->join_column]['value'] = isset($remote_schema->inverse_join_column)
                        ? $val->_primitives[$remote_schema->inverse_join_column]['value']
                        : $val->pk();
                    $this->_primitives[$remote_schema->join_column]['persistent'] = FALSE;
                    break;
                case cy\JORK::ONE_TO_ONE:
                    $val->_primitives[$remote_schema->join_column]['value'] = isset($remote_schema->inverse_join_column)
                        ? $this->_primitives[$remote_schema->inverse_join_column]['value']
                        : $this->pk();
                    $val->_primitives[$remote_schema->join_column]['persistent'] = FALSE;
                    break;
            }
        }
    }

    /**
     * Updates the foreign keys when the value of a component changes.
     *
     * @param string $key the name of the component
     * @param AbstractModel $val
     * @see AbstractModel::__set()
     */
    protected function update_component_fks($key, AbstractModel $val = NULL) {
        $schema = $this->schema();
        $comp_schema = $schema->components[$key];
        if (isset($comp_schema->mapped_by)) {
            $this->update_component_fks_reverse($key, $val, $comp_schema);
            return;
        }
        if (NULL === $val) {
            switch ($comp_schema->type) {
                case cy\JORK::MANY_TO_ONE:
                    $this->_primitives[$comp_schema->join_column]['value'] = NULL;
                    $this->_primitives[$comp_schema->join_column]['persistent'] = FALSE;
                    break;
                case cy\JORK::ONE_TO_ONE:
                    $this->_primitives[$comp_schema->join_column]['value'] = NULL;
                    $this->_primitives[$comp_schema->join_column]['persistent'] = FALSE;
                    break;
            }
        } else {
            switch ($comp_schema->type) {
                case cy\JORK::MANY_TO_ONE:
                    $this->_primitives[$comp_schema->join_column]['value'] = isset($comp_schema->inverse_join_column)
                        ? $val->_primitives[$comp_schema->inverse_join_column]['value']
                        : $val->pk();
                    $this->_primitives[$comp_schema->join_column]['persistent'] = FALSE;
                    break;
                case cy\JORK::ONE_TO_ONE:
                    $this->_primitives[$comp_schema->join_column]['value'] = isset($comp_schema->inverse_join_column)
                        ? $val->_primitives[$comp_schema->inverse_join_column]['value']
                        : $val->pk();
                    $this->_primitives[$comp_schema->join_column]['persistent'] = FALSE;
                    break;
            }
        }
    }

    /**
     * Magic getter implementation for the entity.
     * 
     * First checks the atomics in
     * the schema, if it finds one then returns the value from this entity, or
     * NULL if not found. Then it checks the components of the schema, and if it
     * founds one with <code>$key</code> then checks if the component exists in
     * the entity or not. If it exists, then it returns it, otherwise it returns
     * NULL or an empty JORK_Model_Collection instance (the latter case happens
     * if the component is a to-many component).
     *
     * If it doesn't find the property in the schema then throws a cyclone\jork\Exception.
     *
     * @param string $key
     * @return mixed
     * @throws cyclone\jork\Exception
     */
    public function  __get($key) {
        $schema = $this->schema();
        if (isset($schema->primitives[$key])) {
            return isset($this->_primitives[$key])
                    ? $this->_primitives[$key]['value']
                    : NULL;
        }
        if (isset($schema->components[$key])) {
            if (isset($this->_components[$key]))
                // return if the component value is already initialized
                return $this->_components[$key]['value'];
            if ($schema->is_to_many_component($key)) {
                // it's a to-many relation and initialize an
                // empty component collection
                $this->_components[$key] = array(
                    'value' => collection\AbstractCollection::for_component($this, $key)
                );
            } else {
                $this->_components[$key] = array(
                    'persistent' => TRUE, // default NULL must not be persisted
                    'value' => NULL
                );
            }
            return $this->_components[$key]['value'];
                   
        }
        if (isset($schema->embedded_components[$key])) {
            if ( ! isset($this->_components[$key])) {
                $this->_components[$key] = array(
                    'persistent' => TRUE,
                    'value' => NULL
                );
            }
            return $this->_components[$key]['value'];
        }
        throw new jork\Exception("class '{$schema->class}' has no property '$key'");
    }

    /**
     * Used to force typecasting of atomic properties. Used when the entity
     * is loaded from the database and when the value of the atomic property
     * is changed.
     *
     * @param mixed $val
     * @param string $type
     * @return mixed
     * @see JORK_Model_Abstract::__set()
     * @see JORK_Model_Abstract::populate_atomics()
     */
    private function force_type($val, $type) {
        if (NULL === $val) {
            return NULL;
        } else {
            // doing type casts
            switch ($type) {
                case 'string':
                    return (string) $val;
                case 'int':
                case 'integer':
                    return (int) $val;
                case 'float':
                    return (float) $val;
                case 'bool':
                case 'boolean':
                    return (bool) $val;
                default:
                    $schema = $this->schema();
                    throw new jork\Exception("invalid type for atomic propery '$val' in class '{$schema->class}': '{$type}.'
                    It must be one of the followings: string, int, float, bool, datetime");
            }
        }
    }

    public function __set($key, $val) {
        $schema = $this->schema();
        if (isset($schema->primitives[$key])) {
            if ( ! isset($this->_primitives[$key])) {
                $this->_primitives[$key] = array();
            }

            if ( ! $this instanceof EmbeddableModel) { // TODO it should be cleaned up
                list($pk_primitive, $pk_strategy) = $schema->primary_key_info();
                if ($pk_strategy === cy\JORK::ASSIGN
                        && $key === $pk_primitive
                        && isset($this->_primitives[$pk_primitive]['persistent'])
                        && $this->_primitives[$pk_primitive]['persistent'] === TRUE) {
                    AssignedPrimaryKeyUtils::inst()->register_old_pk($schema->class
                            , $this->_primitives[$pk_primitive]['value']
                            , $val);
                }
            }
            $this->_primitives[$key]['value'] = self::$_cfg['force_type']
                    ? $this->force_type($val, $schema->primitives[$key]->type)
                    : $val;
            $this->_primitives[$key]['persistent'] = FALSE;
        } elseif (isset($schema->components[$key])) {
            if ( ! $val instanceof  $schema->components[$key]->class)
                throw new jork\Exception("value of {$schema->class}::$key must be an instance of {$schema->components[$key]->class}");
            if ( ! isset($this->_components[$key])) {
                $this->_components[$key] = array(
                    'value' => $val,
                    'persistent' => FALSE
                );
                $this->update_component_fks($key, $val);
            } else {
                $this->_components[$key]['value'] = $val;
                $this->_components[$key]['persistent'] = FALSE;
            }
        } elseif (isset($schema->embedded_components[$key])) {
            if ( ! $val instanceof  $schema->embedded_components[$key]->class)
                throw new jork\Exception("value of {$schema->class}::$key must be an instance of {$schema->components[$key]->class}");
            if ( ! isset($this->_components[$key])) {
                $this->_components[$key] = array(
                    'value' => $val,
                    'persistent' => FALSE
                );
            } else {
                $this->_components[$key]['value'] = $val;
                $this->_components[$key]['persistent'] = FALSE;
            }
        } else
            throw new jork\Exception("class '{$schema->class}' has no property '$key'");

        $this->_persistent = FALSE;
    }

    public function  __call($name, $args) {
        switch(count($args)) {
            case 0:
                $schema = $this->schema();
                if (isset($schema->primitives[$name])) {
                    if ( ! isset($this->_primitives[$name])) {
                        $this->_primitives[$name] = array(
                            'value' => $this->fetch_primitive($name),
                            'persistent' => TRUE
                        );
                    }
                    return $this->_primitives[$name]['value'];
                } elseif (isset($schema->components[$name])) {
                    if ( ! isset($this->_components[$name])
                            || $this->_components[$name]['value'] === NULL
                            || count($this->_components[$name]['value']) == 0) {
                        $this->fetch_component($name);
                    }
                    return $this->_components[$name]['value'];
                }
                break;
            case 1:
                $this->__set($name, $args[0]);
                break;
            default:
                throw new jork\Exception("unknown method '$name'");
        }
    }

    private function fetch_primitive($prop_name) {
        $pk_val = $this->pk();
        if (NULL === $pk_val)
            return NULL;
        
        $model_schema = $this->schema();
        $prop_schema = $model_schema->primitives[$prop_name];
        $db_column = isset($prop_schema->column) ? $prop_schema->column : $prop_name;

        $select_query = query\Cache::inst($model_schema->class)->fetch_primitive_sql($prop_name);
        $select_query->where_conditions[0]->right_operand = new db\ParamExpression($pk_val);

        $result = $select_query->exec($model_schema->db_conn)->as_array();
        if (count($result) == 0)
            return NULL;

        return $result[0][$prop_name];
    }

    private function fetch_component($prop_name) {
        $schema = $this->schema();

        cy\JORK::from($schema->class)->with($prop_name)
                ->where($schema->primary_key(), '=', cy\DB::esc($this->pk()))
                ->exec($schema->db_conn);
        if ( ! isset($this->_components[$prop_name]) && $schema->is_to_many_component($prop_name)) {
            $this->_components[$prop_name] = array(
                'persistent' => TRUE,
                'value' => collection\AbstractCollection::for_component($this, $prop_name)
            );
        }
    }

    /**
     * The <code>insert()</code> method should be called explicitly called typically
     * in one case: if this entity is in one-to-one relation with an other entity
     * (the owner) and it's joined to the owner by it's primary key, therefore the
     * primary key is set manually instead of being auto-generated. In this case
     * you have to call <code>insert()</code> instead of <code>save()</code> since
     * <code>save()</code> will call <code>update()</code> in this case (since
     * the primary key exists in the entity).
     *
     * The method doesn't do anything if the entity is persistent.
     *
     * @usedby AbstractCollection::save()
     * 
     */
    public function insert($cascade = TRUE) {
        if ($this->_save_in_progress)
            // avoiding infinite recursion when cascaded
            // saving bi-directional relationships
            return;
        
        if ( ! $this->_persistent) {

            $schema = $this->schema();
            $insert_sqls = query\Cache::inst(get_class($this))->insert_sql();
            $ins_tables = array();
            $values = array();
            $prim_table = NULL;
            list($pk_primitive, $pk_strategy) = $schema->primary_key_info();
            if ($pk_strategy == cy\JORK::ASSIGN && ! isset($this->_primitives[$pk_primitive]))
                throw new jork\Exception("can not save '"
                        . $schema->class
                        . "' instance: primary key is not assigned");
            
            foreach ($schema->primitives as $col_name => $col_def) {
                if (isset($this->_primitives[$col_name])) {
                    if ($this->_primitives[$col_name]['persistent'] == FALSE) {
                        $ins_table = isset($col_def->table)
                                ? $col_def->table
                                : $schema->table;
                        if ( ! in_array($ins_table, $ins_tables)) {
                            $ins_tables [] = $ins_table;
                        }
                        if ( ! isset($values[$ins_table])) {
                            $values[$ins_table] = array();
                        }
                        $col = isset($col_def->column)
                                ? $col_def->column
                                : $col_name;
                        $values[$ins_table][$col] = $this->_primitives[$col_name]['value'];

                        // In fact the value is not yet persistent, but we assume
                        // that no problem will happen until the insertions
                        $this->_primitives[$col_name]['persistent'] = TRUE;
                    }
                } elseif ($col_def->primary_key_strategy == cy\JORK::AUTO) {
                    // The primary key does not exist in the record
                    // therefore we save the table name for the table
                    // containing the primary key
                    $prim_table = $schema->table_name_for_property($col_name);
                }
            }
            
            if (empty($values))
                throw new jork\Exception("error while saving '{$schema->class}' instance: no values to be inserted");

            if (NULL === $prim_table) {
                foreach ($values as $tbl_name => $ins_values) {
                    $insert_sqls[$tbl_name]->values = array($ins_values);
                    $insert_sqls[$tbl_name]->exec($schema->db_conn, FALSE);
                }
            } else {
                foreach ($values as $tbl_name => $ins_values) {
                    $insert_sqls[$tbl_name]->values = array($ins_values);
                    $tmp_id = $insert_sqls[$tbl_name]->exec($schema->db_conn);
                    if ($prim_table == $tbl_name) {
                        $this->_primitives[$pk_primitive] = array(
                            'value' => self::$_cfg['force_type']
                                ? $this->force_type($tmp_id, $schema->primitives[$pk_primitive]->type)
                                : $tmp_id,
                            'persistent' => TRUE
                        );
                        foreach ($this->_pk_change_listeners as $listener) {
                            $listener->notify_pk_creation($this);
                        }
                    }
                }
            }
            // The insert process finished, the entity is now persistent
            $this->_persistent = TRUE;
        }
        
        $this->cascade_save($cascade);
    }

    /**
     * Typically this method should never be called from outside, just made public
     * for edge-cases.
     *
     * @usedby JORK_Model_Abstract::save()
     */
    public function update($cascade = TRUE) {
         if ($this->_save_in_progress)
            // avoiding infinite recursion when cascaded
            // saving bi-directional relationships
            return;

        if ( ! $this->_persistent) {
            $this->_save_in_progress = TRUE;

            $schema = $this->schema();

            list($pk_primitive, $pk_strategy) = $schema->primary_key_info();
            if ($pk_strategy == cy\JORK::ASSIGN && ! isset($this->_primitives[$pk_primitive]))
                throw new jork\Exception("can not save '"
                        . $schema->class
                        . "' instance: primary key is not assigned");

            $update_sqls = query\Cache::inst(get_class($this))->update_sql();

            $upd_tables = array();
            $values = array();
            foreach ($schema->primitives as $col_name => $col_def) {
                if (isset($this->_primitives[$col_name])
                        && (FALSE == $this->_primitives[$col_name]['persistent'])) {
                    $tbl_name = isset($col_def->table)
                            ? $col_def->table
                            : $schema->table;
                    if ( ! in_array($tbl_name, $upd_tables)) {
                        $upd_tables []= $tbl_name;
                    }
                    if ( ! isset($values[$tbl_name])) {
                        $values[$tbl_name] = array();
                    }
                    $col = isset($col_def->column)
                                ? $col_def->column
                                : $col_name;
                    $values[$tbl_name][$col] = $this->_primitives[$col_name]['value'];
                    $this->_primitives[$col_name]['persistent'] = TRUE;
                }
            }
            //TODO should be improved for proper secondary table handling
            // (otherwise i'm so fuckin' fed up with secondary tables :P)
            foreach ($values as $tbl_name => $upd_vals) {
                $update_sqls[$tbl_name]->values = $upd_vals;

                $pk = $this->pk();
                if ($pk_strategy == cy\JORK::ASSIGN) {
                    // if the primary key strategy is ASSIGN then maybe
                    // the assigned primary key has been modified
                    try {
                        // we try to fetch here the optionally existent
                        // old primary key - in this case it should be in the condition
                        // of the WHERE clause
                        $pk = AssignedPrimaryKeyUtils::inst()->get_old_pk($schema->class, $pk);
                    } catch (jork\Exception $ex) {
                        
                    }
                }

                $update_sqls[$tbl_name]->where($schema->primary_key(), '='
                        , DB::esc($pk));
                $update_sqls[$tbl_name]->exec($schema->db_conn);
            }

            $this->_persistent = TRUE;
            $this->_save_in_progress = FALSE;
        }
        $this->cascade_save($cascade);
    }

    /**
     * Saves the entity. Performs an SQL INSERT statement if the primary key of
     * the entity is not set, otherwise an SQL UPDATE is executed.
     *
     * The <code>update()</code> and <code>insert()</code> methods are also public,
     * but these should be rarely used.
     *
     * If $cascade is TRUE, then all components will be saved.
     * If $cascade is FALSE, then no components will be saved.
     * If $cascade is an array, then the components enumerated in the array
     *      will be saved.
     *
     * Example:
     * <code>
     * // saving the topic and it's posts, but no other components of the topic
     *  $topic->save(array('posts'));
     * </code>
     *
     * @param mixed $cascade boolean or array
     * @see JORK_Model_Abstract::insert()
     * @see JORK_Model_Abstract::update()
     */
    public function save($cascade = TRUE) {
        $schema = $this->schema();
        $pk_strategy = $schema->primary_key_strategy();
        if ($pk_strategy == cy\JORK::AUTO) {
            if ($this->pk() === NULL) {
                $this->insert($cascade);
            } else {
                $this->update($cascade);
            }
        } else {
            // generation strategy is assigned
            $pk_name = $schema->primary_key();
            
            // fail here if the primary key doesn't have a manually assigned value
            if ( ! isset($this->_primitives[$pk_name]))
                throw new jork\Exception("can not save '"
                        . $schema->class
                        . "' instance: primary key is not assigned");
            $pk_primitive = $this->_primitives[$pk_name];
            if ($pk_primitive['persistent'] == TRUE) {
                // if the primary key is persistent then it will be an update
                $this->update($cascade);
            } else {
                if (AssignedPrimaryKeyUtils::inst()->old_pk_exists($schema->class
                        , $pk_primitive['value'])) {
                    // the assigned primary key is not persistent
                    // but the primary key has been reassigned
                    // so the entity needs to be updated
                    // and its primary key column will be updated too
                    $this->update($cascade);
                } else {
                    // the entity has got a new primary key, and no
                    // previous primary keys found - it will be an insert
                    $this->insert($cascade);
                }
            }
        }
        
    }

    /**
     *
     * @param mixed $cascade
     * @throws cyclone\jork\Exception if $cascade if netither a boolean nor an array
     * @usedby JORK_Model_Abstract::insert()
     * @usedby JORK_Model_Abstract::update()
     */
    private function cascade_save($cascade) {
        if (FALSE == $cascade)
            return;
        if (TRUE === $cascade) {
            $comps = $this->_components;
        } elseif (is_array($cascade)) {
            // TODO improve
            $comp_keys = array_intersect(array_keys($this->_components), $cascade);
            $comps = array();
            foreach ($comp_keys as $key) {
                $comps []= $this->_components[$key];
            }
        } else
            throw new jork\Exception('$cascade parameter must be boolean or array');

        // turn the lock on
        $this->_save_in_progress = TRUE;
        foreach ($comps as $k => $comp) {
            if ( ! is_null($comp['value'])) {
                $comp['value']->save();
            }
        }
        $this->_save_in_progress = FALSE;
    }

    public function delete() {
        $this->delete_by_pk($this->pk());
    }

    public function delete_by_pk($pk) {
        if ($pk === NULL)
            return;

        $schema = $this->schema();
        $delete_sqls = query\Cache::inst(get_class($this))->delete_sql();
        $pk = new db\ParamExpression($pk);
        foreach ($delete_sqls as $del_stmt) {
            $del_stmt->conditions[0]->right_operand = $pk;
            $del_stmt->exec($schema->db_conn);
        }

        foreach ($schema->components as $comp_name => $comp_def) {
            if (isset($comp_def->on_delete)) {
                $on_delete = $comp_def->on_delete;
                if (cy\JORK::CASCADE === $on_delete) {
                    
                    //$component['value']->delete();
                } elseif (cy\JORK::SET_NULL == $on_delete) {
                    if ($schema->is_to_many_component($comp_name)) {
                        // to-many component
                        if ( ! isset($this->_components[$comp_name])) {
                            $this->_components[$comp_name] = array(
                                'value' => collection\AbstractCollection::for_component($this, $comp_name)
                            );
                        }
                        $this->_components[$comp_name]['value']->notify_owner_deletion($pk);
                    } elseif (isset($comp_def->mapped_by)) {
                        // we handle reverse one-to-one components here
                        $remote_class_schema = self::schema_by_class($comp_def->class);
                        if (cy\JORK::ONE_TO_ONE == $remote_class_schema
                                ->components[$comp_def->mapped_by]->type) {
                            $this->set_null_fk_for_reverse_one_to_one($remote_class_schema
                                    , $comp_def, $pk);
                        }
                    }
                }
            } 
        }
    }

    private function set_null_fk_for_reverse_one_to_one(jork\schema\ModelSchema $remote_class_schema
            , $comp_def, db\ParamExpression $pk) {
        $remote_comp_schema = $remote_class_schema
                                ->components[$comp_def->mapped_by];
        $schema = $this->schema();

        $upd_stmt = new db\query\Update;

        $remote_primitive_schema = $remote_class_schema->primitives[$remote_comp_schema->join_column];

        $remote_join_col = isset($remote_primitive_schema->column) ? $remote_primitive_schema->column : $remote_comp_schema->join_column;

        $upd_stmt->values = array(
            $remote_join_col => NULL
        );

        $local_join_atomic = isset($remote_comp_schema->inverse_join_column)
                ? $remote_comp_schema->join_column
                : $schema->primary_key();

        $local_join_col = isset($schema->primitives[$local_join_atomic]->column)
                ? $schema->atomics[$local_join_atomic]->column
                : $local_join_atomic;

        $upd_stmt->table = isset($remote_primitive_schema->table)
                ? $remote_primitive_schema->table
                : $remote_class_schema->table;

        if ($local_join_atomic == $schema->primary_key()) {
            // we are simply happy, the primary key is the
            // join column and we have it
            $local_join_cond = $pk;
        } else {
            // the local join column is not the primary key
            if (isset($this->_primitives[$local_join_atomic])) {
                // but if it's loaded then we are still happy
                $local_join_cond = new DB_Expression_Param($this->_primitives[$local_join_atomic]);
            } else {
                // otherwise we have to create a subselect to
                // get the value of the local join column based on the primary key
                // and we hope that the local join column is unique
                $local_join_cond = new db\query\SelectQuery;
                $local_join_cond->columns = array($local_join_col);
                $local_join_cond->tables = array(
                    isset($schema->atomics[$local_join_atomic]->table)
                            ? $schema->atomics[$local_join_atomic]->table
                            : $schema->table
                );
                $local_join_cond->where_conditions = array(
                    new db\BinaryExpression($schema->primary_key()
                            , '=', $pk)
                );
            }
        }

        $upd_stmt->conditions = array(
            new db\BinaryExpression($remote_join_col, '=', $local_join_cond)
        );

        $upd_stmt->exec($schema->db_conn);
    }

    public function as_string($tab_cnt = 0) {
        if ($this->_as_string_in_progress)
            return '';

        $this->_as_string_in_progress = TRUE;
        $tabs = '';
        for($i = 0; $i < $tab_cnt; ++$i) {
            $tabs .= "\t";
        }

        $prim_key = $this->schema()->primary_key();

        $lines = array($tabs  . "\033[36;1m" . get_class($this) . "\033[0m");
        foreach ($this->_primitives as $name => $itm) {
            if ($name == $prim_key) {
                $color = "\033[37;1m";
            } else {
                $color = '';
            }
            $val = $itm['value'] === NULL ? 'NULL' : $itm['value'];
            $lines []= $tabs . $color . $name . ': ' . $val . "\033[0m";
        }
        foreach ($this->_components as $name => $comp) {
            $lines []= $comp['value']->as_string($tab_cnt + 1);
        }
        $this->_as_string_in_progress = FALSE;
        return implode(PHP_EOL, $lines);
    }

    public function  __toString() {
        return $this->as_string();
    }

    public function as_array() {
        $rval = array();
        $schema = $this->schema();
        foreach ($schema->primitives as $k => $dummy) {
            $rval[$k] = isset($this->_primitives[$k]) ? $this->_primitives[$k]['value'] : NULL;
        }
        foreach ($schema->components as $k => $dummy) {
            $rval[$k] = isset($this->_components[$k]) && isset($this->_components[$k]['value'])
                    ? $this->_components[$k]['value']->as_array() : NULL;
        }
        foreach ($schema->embedded_components as $k => $dummy) {
            $rval[$k] = isset($this->_components[$k]) ? $this->_components[$k]['value'] : NULL;
        }
        return $rval;
    }

    public function __unset($key) {
        $schema = $this->schema();
        if (isset($schema->primitives[$key])) {
            $this->_primitives[$key] = array(
                'value' => NULL,
                'persistent' => FALSE
            );
            $this->_persistent = FALSE;
        } elseif (isset($schema->components[$key])) {
            if ($schema->is_to_many_component($key)) {
                throw new jork\Exception("Removing to-many relations is not yet supported");
            } else {
                $this->_components[$key] = array(
                    'value' => NULL,
                    'persistent' => FALSE
                );
                $this->update_component_fks($key);
            }
        } else {
            throw new jork\Exception("Property {$schema->class}::$key does not exist");
        }
    }

    public function  __isset($key) {
        $schema = $this->schema();
        return isset($schema->primitives[$key])
                || isset($schema->components[$key]);
    }

    public function offsetGet($key) {
        return $this->__get($key);
    }

    public function offsetSet($key, $value) {
        $this->__set($key, $value);
    }

    public function offsetExists($key) {
        return $this->__isset($key);
    }

    public function offsetUnset($key) {
        $this->__unset($key);
    }

    public function  getIterator() {
        return new Iterator($this->_primitives + $this->_components);
    }

}
