<?php

namespace cyclone\jork\mapper;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class EntityMapper implements RowMapper {

    const SELECT_ALL = 0;

    const SELECT_LAST = 1;

    const SELECT_NONE = 2;

    /**
     * @var array
     */
    protected $_table_aliases = array();

    /**
     * @var jork\schema\ModelSchema
     */
    public $_entity_schema;

    /**
     * @var string
     */
    protected $_entity_alias;

    /**
     *
     * @var db\query\Select
     */
    protected $_db_query;

    /**
     * @var jork\query\Select
     */
    protected $_jork_query;

    /**
     * The naming service to be used and passed on to the next mappers
     *
     * @var jork\NamingService
     */
    protected $_naming_srv;

    /**
     * Stores key-value pairs where the key is a column name in the expected
     * database query result and the value is the name of the atomic property
     * that should be populated with the column value.
     *
     * @var array
     */
    protected $_result_atomics = array();

    /**
     * The aliases of the primary keys in the database query result.
     *
     * @var array
     */
    protected $_result_primary_key_columns = array();

    /**
     * The next mappers to be executed on the same row. All items should also in
     * $_next_to_one_mappers or $_next_to_many_mappers.
     *
     * @var array<JORK_Mapper_Component>
     */
    protected $_next_mappers = array();

    /**
     * The mappers that should fetch only one component. Subset of
     * $_next_mappers.
     *
     * @var array<JORK_Mapper_Component>
     */
    protected $_next_to_one_mappers = array();

    /**
     * The mappers that should fetch a collection of components. Subset of
     * $_next_mappers.
     *
     * @var array<JORK_Mapper_Component>
     */
    protected $_next_to_many_mappers = array();

    /**
     * The instance that was queried from the previous database result row.
     * It's used to determine if a new instance should be created or not
     *
     * @var JORK_Model_Abstract
     * @see map_row()
     */
    protected $_previous_result_entity;

    public function map_row(&$db_row) {
        if (empty($this->_result_primary_key_columns))
            return array(NULL, FALSE);

        $pk = array();
        $pk_is_null = TRUE;
        foreach ($this->_result_primary_key_columns as $pk_col) {
            $pk_col_val = $db_row[$pk_col];
            if ( ! is_null($pk_col_val)) {
                $pk_is_null = FALSE;
            }
            $pk []= $pk_col_val;
        }

//        if ($pk_is_null)
//            return array($this->_previous_result_entity = NULL, FALSE);
//
//
//        $pk = $db_row[$this->_result_primary_key_column];
//        if (NULL === $pk)
//            return array($this->_previous_result_entity = NULL, FALSE);
        if ($this->_previous_result_entity != NULL
                && $pk == $this->_previous_result_entity->pk()) { //same instance
            $is_new_entity = false;
            $entity = $this->_previous_result_entity;
            foreach ($this->_next_to_one_mappers as $prop_name => $one_mapper) {
                $one_mapper->map_row($db_row);
            }
        } else { //new entity found
            $is_new_entity = true;
            $instance_pool = jork\InstancePool::inst($this->_entity_schema->class);
            $entity = $instance_pool[$pk];
            if (NULL === $entity) {
                $entity = new $this->_entity_schema->class;
                //atomics should only be loaded when we found a new entity
                //with a new primary key
                $atomics = array();
                foreach ($this->_result_atomics as $col_name => $prop_name) {
                    $atomics[$prop_name] = $db_row[$col_name];
                }
                $entity->populate_atomics($atomics);
                $instance_pool[$entity->pk()] = ($entity);
            }

            $entity->init_component_collections($this->_next_to_many_mappers);

            $this->_previous_result_entity = $entity;
            $to_one_comps = array();
            foreach ($this->_next_to_one_mappers as $prop_name => $one_mapper) {
                list($comp, $is_new_comp) = $one_mapper->map_row($db_row);
                $to_one_comps[$prop_name] = $comp;
            }
            $entity->set_components($to_one_comps);
        }
        
        $to_many_comps = array();
        foreach ($this->_next_to_many_mappers as $prop_name => $mapper) {
            list($comp, $is_new_component) = $mapper->map_row($db_row);
            if ($is_new_component) {
                $to_many_comps[$prop_name] = $comp;
            }
        }
        $entity->add_to_component_collections($to_many_comps);
        return array($entity, $is_new_entity);
    }

    public function  get_last_entity() {
        return $this->_previous_result_entity;
    }

    /**
     * Returns the mapper for the last property in the property chain.
     *
     * @param array $prop_chain
     * @return array: 0th item is the mapper, 1st item is the last propertyof the
     *  prop. chain if it is an entity, otherwise (if it's an atomic prop.) it will be FALSE.
     * @usedby JORK_Mapper_Result_Default::extract_mappers()
     */
    public function get_mapper_for_propchain($prop_chain) {
        $root_prop = array_shift($prop_chain);
        if (empty ($prop_chain)) {
            if (isset($this->_entity_schema->primitives[$root_prop]))
                return array($this, $root_prop);

            return array($this->_next_mappers[$root_prop], FALSE);
        }
        return $this->_next_mappers[$root_prop]->get_mapper_for_propchain($prop_chain);
    }
    

    public function  __construct(jork\NamingService $naming_srv
            , jork\query\SelectQuery $jork_query
            , db\query\Select $db_query
            , $select_item = NULL) {
        $this->_naming_srv = $naming_srv;
        $this->_jork_query = $jork_query;
        $this->_db_query = $db_query;

        $this->_entity_alias = $select_item;

        $this->_entity_schema = $this->_naming_srv->get_schema($this->_entity_alias);

    }

    /**
     * @param string $tbl_name
     * @return string the generated alias
     */
    protected function add_table($tbl_name) {
        if ( ! isset($this->_table_aliases[$tbl_name])) {
            if ( ! isset($this->_table_aliases[ $this->_entity_schema->table ])) {
                $tbl_alias = $this->_naming_srv->table_alias($this->_entity_alias, $tbl_name);
                $this->_table_aliases[$tbl_name] = $tbl_alias;
                $this->_db_query->tables []= array($tbl_name, $tbl_alias);
            }
            if ($tbl_name != $this->_entity_schema->table) {
                $this->join_secondary_table($tbl_name);
            }
        }
        return $this->_table_aliases[$tbl_name];
    }

    /**
     *
     * @param string $tbl_name
     * @return string
     * @see JORK_Naming_Service::table_alias($tbl_name)
     */
    protected function table_alias($tbl_name) {
        if ( ! isset($this->_table_aliases[$tbl_name])) {
            $this->_table_aliases[$tbl_name] = $this->_naming_srv
                    ->table_alias($this->_entity_alias, $tbl_name);
        }
        return $this->_table_aliases[$tbl_name];
    }


    /**
     * Adds an atomic property join to the db query. Also joins the table of
     * the db column if it's not joined yet.
     *
     * @param string $prop_name
     * @param cyclone\jork\schema\PrimitivePropertySchema $prop_schema
     * @return the full column name (with table alias)
     */
    protected function add_atomic_property($prop_name, &$prop_schema) {
        if (in_array($prop_name, $this->_result_atomics))
                return;

        $tbl_name = isset($prop_schema->table)
                ? $prop_schema->table
                : $this->_entity_schema->table;
        
        if ( ! isset($this->_table_aliases[$tbl_name])) {
            $tbl_alias = $this->add_table($tbl_name);
        }
        $tbl_alias = $this->_table_aliases[$tbl_name];
        
        $col_name = isset($prop_schema->column)
                ? $prop_schema->column
                : $prop_name;

        $full_column = $tbl_alias.'.'.$col_name;
        $full_alias = $tbl_alias.'_'.$col_name;
        
        $this->_db_query->columns []= array($full_column, $full_alias);
        $this->_result_atomics[$full_alias] = $prop_name;

        if (in_array($prop_name, $this->_entity_schema->primary_keys())) {
            $this->_result_primary_key_columns []= $full_alias;
        }
    }

    protected function join_secondary_table($tbl_name) {
        if (empty($this->_entity_schema->secondary_tables)
                || ! isset($this->_entity_schema->secondary_tables[$tbl_name]))
            throw new jork\SchemaException ('class '.$this->_entity_schema->class
                    .' has no secondary table "'.$tbl_name.'"');
        if ( ! isset($this->_table_aliases[$tbl_name])) {
            $this->add_table($this->_entity_schema->table);
        }
        $table_schema = $this->_entity_schema->secondary_tables[$tbl_name];


        $inverse_join_cols = isset($table_schema->inverse_join_columns)
                ? $table_schema->inverse_join_columns
                : $this->_entity_schema->primary_keys();

        $local_table_alias = $this->table_alias($this->_entity_schema->table);

        $tbl_alias = $this->table_alias($tbl_name);
        $join = array(
            'table' => array($tbl_name, $tbl_alias),
            'type' => 'LEFT',
            'conditions' => array()
        );

        foreach ($inverse_join_cols as $idx => $inverse_join_col) {
            $join['conditions'] []= new db\BinaryExpression(
                $local_table_alias.'.'.$table_schema->join_columns[$idx]
                , '='
                , $tbl_alias.'.'.$inverse_join_col
            );
        }
        $this->_db_query->joins []= $join;
    }

    /**
     *
     * @param string $prop_name
     * @param array $prop_schema
     * @return jork\mapper\EntityMapper
     */
    protected function get_component_mapper($prop_name, $prop_schema = NULL) {
        if (isset($this->_next_mappers[$prop_name]))
            return $this->_next_mappers[$prop_name];

        if (NULL == $prop_schema) {
            if (isset($this->_entity_schema->components[$prop_name])) {
            $prop_schema = $this->_entity_schema->components[$prop_name];
            } else {
                $prop_schema = $this->_entity_schema->embedded_components[$prop_name];
            }
        }

        $select_item = $this->_entity_alias == '' ? $prop_name
                : $this->_entity_alias.'.'.$prop_name;

        $next_mapper = component\AbstractMapper::factory($this, $prop_name, $select_item);
        $this->_next_mappers[$prop_name] = $next_mapper;

        $next_mapper_class = get_class($next_mapper);

        if ($this->_entity_schema->is_to_many_component($prop_name)) {
            $this->_next_to_many_mappers[$prop_name] = $next_mapper;
        } else {
            $this->_next_to_one_mappers[$prop_name] = $next_mapper;
        }

        return $next_mapper;
    }

    /**
     * Here we don't take care about the property projections.
     * These must be merged one-by-one at JORK_Mapper_Select->map_select()
     *
     * @param array $prop_chain the array representation of the property chain
     * @throws JORK_Schema_Exception
     */
    public function merge_prop_chain(array $prop_chain, $select_policy = self::SELECT_NONE) {
        $root_prop = array_shift($prop_chain);
        $schema = $this->_entity_schema->get_property_schema($root_prop);
        if ($schema instanceof jork\schema\EmbeddableSchema) { // embedded component
            
        }
        if ( ! empty($prop_chain)) {
            if ( ! isset($schema->class))
                throw new jork\SyntaxException('only the last item of a property
                    chain can be an atomic property');
            $next_mapper = $this->get_component_mapper($root_prop, $schema);
            if ($select_policy == self::SELECT_ALL) {
                $next_mapper->select_all_atomics();
            }
            $next_mapper->merge_prop_chain($prop_chain, $select_policy);
        } else {
            if (isset($schema->class)) { // component
                $next_mapper = $this->get_component_mapper($root_prop, $schema);
                if ($select_policy != self::SELECT_NONE) {
                    $next_mapper->select_all_atomics();
                }
            } elseif (isset($schema->type)) { // atomic property
                $this->add_atomic_property($root_prop, $schema);
            } else { // embedded component
                $next_mapper = $this->get_component_mapper($root_prop, $schema);
                $next_mapper->select_all_atomics();
            }
        }
        //The primary key column should _always_ be selected
        if (empty($this->_result_primary_key_columns)
                // dirty hack, must be cleaned up
                && ! ($this instanceof component\EmbeddedMapper)) {
            foreach($this->_entity_schema->primary_keys() as $pk) {
                $this->add_atomic_property($pk, $this->_entity_schema->primitives[$pk]);
            }
        }
    }

    /**
     * Puts all atomic properties into the db query select list.
     * Called if the select list is empty.
     */
    public function select_all_atomics() {
        if ( ! is_object($this->_entity_schema)) {
            //var_dump($this->_entity_schema); die();
        }
        foreach ($this->_entity_schema->primitives as $prop_name => $prop_schema) {
            $this->add_atomic_property($prop_name, $prop_schema);
        }
    }

    /**
     * If the last item of the property chain is an atomic property then the method
     * returns the qualified name of the corresponding database column.
     *
     * Otherwise it returns the mapper object of the last property, the mapped entity schema
     * and the last item of the property chain.
     *
     * @param array $prop_chain
     * @return mixed
     */
    public function resolve_prop_chain($prop_chain) {
        $root_prop = array_shift($prop_chain);
        if (empty($prop_chain)) { //we are there
            if ( ! isset($this->_entity_schema->primitives[$root_prop])) {
                if (isset($this->_entity_schema->components[$root_prop])) {
                    // if the last property of the property chain is not an atomic
                    // property, then we return the mapper ($this), the entity
                    // schema of the mapper, and the final property
                    return array($this, $this->_entity_schema, $root_prop);
                }
                throw new jork\Exception('property "'.$root_prop.'" of class "'
                        .$this->_entity_schema->class.'" does not exist');
            }
            $col_schema = $this->_entity_schema->primitives[$root_prop];
            $table = isset($col_schema->table)
                    ? $col_schema->table
                    : $this->_entity_schema->table;
            $this->add_table($table);
            return $this->_table_aliases[$table].'.'.$root_prop;
        } else { //going on with the next component mapper
            if ( ! isset($this->_entity_schema->components[$root_prop])
                    && ! isset($this->_entity_schema->embedded_components[$root_prop]))
                throw new jork\Exception('class '.$this->_entity_schema->class
                        .' has no component '.$root_prop);
            return $this->get_component_mapper($root_prop)->resolve_prop_chain($prop_chain);
        }
    }

    /**
     * @return boolean
     */
    public function has_to_many_child() {
        if ( ! empty($this->_next_to_many_mappers))
            return TRUE;

        foreach ($this->_next_to_one_mappers as $mapper) {
            if ($mapper->has_to_many_child())
                return TRUE;
        }

        return FALSE;
    }

    /**
     *
     * @param array $prop_chain
     * @return boolean
     * @usedby JORK_Mapper_Result_Default::map()
     */
    public function is_to_many_comp($prop_chain) {
        $root_prop = array_shift($prop_chain);
        if (isset($this->_next_to_many_mappers[$root_prop]))
            return TRUE;

        if (isset($this->_next_to_one_mappers[$root_prop])) {
            return $this->_next_to_one_mappers[$root_prop]->is_to_many_comp($prop_chain);
        }

        return FALSE;
    }
    
}
