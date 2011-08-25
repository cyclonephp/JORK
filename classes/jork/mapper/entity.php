<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Mapper_Entity implements JORK_Mapper_Row {

    const SELECT_ALL = 0;

    const SELECT_LAST = 1;

    const SELECT_NONE = 2;

    /**
     * @var array
     */
    protected $_table_aliases = array();

    /**
     * @var JORK_Mapping_Schema
     */
    public $_entity_schema;

    /**
     * @var string
     */
    protected $_entity_alias;

    /**
     *
     * @var DB_Query_Select
     */
    protected $_db_query;

    /**
     * @var JORK_Query_Select
     */
    protected $_jork_query;

    /**
     * The naming service to be used and passed on to the next mappers
     *
     * @var JORK_Naming_Service
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
     * The alias of the primary key in the database query result.
     *
     * @var string
     */
    protected $_result_primary_key_column;

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
        $pk = $db_row[$this->_result_primary_key_column];
        if (NULL === $pk)
            return array($this->_previous_result_entity = NULL, FALSE);
        if ($this->_previous_result_entity != NULL
                && $pk == $this->_previous_result_entity->pk()) { //same instance
            $is_new_entity = false;
            $entity = $this->_previous_result_entity;
            foreach ($this->_next_to_one_mappers as $prop_name => $one_mapper) {
                $one_mapper->map_row($db_row);
            }
        } else { //new entity found
            $is_new_entity = true;
            $instance_pool = JORK_InstancePool::inst($this->_entity_schema->class);
            $entity = $instance_pool->get_by_pk($pk);
            if (NULL === $entity) {
                $entity = new $this->_entity_schema->class;
                //atomics should only be loaded when we found a new entity
                //with a new primary key
                $atomics = array();
                foreach ($this->_result_atomics as $col_name => $prop_name) {
                    $atomics[$prop_name] = $db_row[$col_name];
                }
                $entity->populate_atomics($atomics);
                $instance_pool->add($entity);
            }

            $entity->init_component_collections($this->_next_to_many_mappers);
            
            $to_one_comps = array();
            foreach ($this->_next_to_one_mappers as $prop_name => $one_mapper) {
                list($comp, $is_new_comp) = $one_mapper->map_row($db_row);
                $to_one_comps[$prop_name] = $comp;
            }
            $entity->set_components($to_one_comps);
            $this->_previous_result_entity = $entity;
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
            if (array_key_exists($root_prop, $this->_entity_schema->atomics)) 
                return array($this, $root_prop);

            return array($this->_next_mappers[$root_prop], FALSE);
        }
        return $this->_next_mappers[$root_prop]->get_mapper_for_propchain($prop_chain);
    }
    

    public function  __construct(JORK_Naming_Service $naming_srv
            , JORK_Query_Select $jork_query
            , DB_Query_Select $db_query
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
        if ( ! array_key_exists($tbl_name, $this->_table_aliases)) {
            if ( ! array_key_exists($this->_entity_schema->table, $this->_table_aliases)) {
                $tbl_alias = $this->_table_aliases[$tbl_name] = $this->_naming_srv->table_alias($this->_entity_alias, $tbl_name);
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
        if ( !array_key_exists($tbl_name, $this->_table_aliases)) {
            $this->_table_aliases[$tbl_name] = $this->_naming_srv
                    ->table_alias($this->_entity_alias, $tbl_name);
        }
        return $this->_table_aliases[$tbl_name];
    }


    /**
     * Adds an atomic property join to the db query. Also joins the table of
     * the db column if it's not joined yet.
     *
     * @param string $property
     * @return the full column name (with table alias)
     */
    protected function add_atomic_property($prop_name, &$prop_schema) {
        if (in_array($prop_name, $this->_result_atomics))
                return;

        $tbl_name = array_key_exists('table', $prop_schema)
                ? $prop_schema['table']
                : $this->_entity_schema->table;
        
        if ( ! array_key_exists($tbl_name, $this->_table_aliases)) {
            $tbl_alias = $this->add_table($tbl_name);
        }
        $tbl_alias = $this->_table_aliases[$tbl_name];
        
        $col_name = array_key_exists('column', $prop_schema)
                ? $prop_schema['column']
                : $prop_name;

        $full_column = $tbl_alias.'.'.$col_name;
        $full_alias = $tbl_alias.'_'.$col_name;
        
        $this->_db_query->columns []= array($full_column, $full_alias);
        $this->_result_atomics[$full_alias] = $prop_name;

        if ($prop_name == $this->_entity_schema->primary_key()) {
            $this->_result_primary_key_column = $full_alias;
        }
    }

    protected function join_secondary_table($tbl_name) {
        if ( ! is_array($this->_entity_schema->secondary_tables)
                || ! array_key_exists($tbl_name, $this->_entity_schema->secondary_tables)) 
            throw new JORK_Schema_Exception ('class '.$this->_entity_schema->class
                    .' has no secondary table "'.$tbl_name.'"');
        if ( ! array_key_exists($tbl_name, $this->_table_aliases)) {
            $this->add_table($this->_entity_schema->table);
        }
        $table_schema = $this->_entity_schema->secondary_tables[$tbl_name];


        $inverse_join_col = array_key_exists('inverse_join_column', $table_schema)
                ? $table_schema['inverse_join_column']
                : $this->_entity_schema->primary_key();

        $tbl_alias = $this->table_alias($tbl_name);
        
        $this->_db_query->joins []= array(
            'table' => array($tbl_name, $tbl_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new DB_Expression_Binary(
                    $this->table_alias($this->_entity_schema->table).'.'.$inverse_join_col
                    , '='
                    , $tbl_alias.'.'.$table_schema['join_column']
                )
            )
        );
    }

    /**
     *
     * @param string $prop_name
     * @param array $prop_schema
     * @return JORK_Mapper_Entity
     */
    protected function get_component_mapper($prop_name, $prop_schema = NULL) {
        if (array_key_exists($prop_name, $this->_next_mappers))
            return $this->_next_mappers[$prop_name];

        if (NULL == $prop_schema) {
            $prop_schema = $this->_entity_schema->components[$prop_name];
        }

        $select_item = $this->_entity_alias == '' ? $prop_name
                : $this->_entity_alias.'.'.$prop_name;

        $next_mapper = $this->_next_mappers[$prop_name] =
                JORK_Mapper_Component::factory($this, $prop_name, $select_item);

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
    public function merge_prop_chain(array $prop_chain, $select_policy = JORK_Mapper_Entity::SELECT_NONE) {
        $root_prop = array_shift($prop_chain);
        $schema = $this->_entity_schema->get_property_schema($root_prop);
        if ($schema instanceof JORK_Model_Embeddable) { // embedded component
            
        }
        if ( ! empty($prop_chain)) {
            if ( is_array($schema) && ! array_key_exists('class', $schema))
                throw new JORK_Syntax_Exception('only the last item of a property
                    chain can be an atomic property');
            $next_mapper = $this->get_component_mapper($root_prop, $schema);
            if ($select_policy == self::SELECT_ALL) {
                $next_mapper->select_all_atomics();
            }
            $next_mapper->merge_prop_chain($prop_chain, $select_policy);
        } else {
            if (array_key_exists('class', $schema)) { // component
                $next_mapper = $this->get_component_mapper($root_prop, $schema);
                if ($select_policy != self::SELECT_NONE) {
                    $next_mapper->select_all_atomics();
                }
            } elseif (is_array($schema)) { // atomic property
                $this->add_atomic_property($root_prop, $schema);
            } else { // embedded component
                $next_mapper = $this->get_component_mapper($root_prop, $schema);
                $next_mapper->select_all_atomics();
            }
        }
        //The primary key column should _always_ be selected
        if (NULL === $this->_result_primary_key_column
                // dirty hack, must be cleaned up
                && ! ($this instanceof JORK_Mapper_Component_Embedded)) {
            $pk = $this->_entity_schema->primary_key();
            $this->add_atomic_property($pk, $this->_entity_schema->atomics[$pk]);
        }
    }

    /**
     * Puts all atomic properties into the db query select list.
     * Called if the select list is empty.
     */
    public function select_all_atomics() {
        foreach ($this->_entity_schema->atomics as $prop_name => $prop_schema) {
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
            if ( ! array_key_exists($root_prop, $this->_entity_schema->atomics)) {
                if (array_key_exists($root_prop, $this->_entity_schema->components)) {
                    // if the last property of the property chain is not an atomic
                    // property, then we return the mapper ($this), the entity
                    // schema of the mapper, and the final property
                    return array($this, $this->_entity_schema, $root_prop);
                }
                throw new JORK_Exception('property "'.$root_prop.'" of class "'
                        .$this->_entity_schema->class.'" does not exist');
            }
            $col_schema = $this->_entity_schema->atomics[$root_prop];
            $table = array_key_exists('table', $col_schema)
                    ? $col_schema['table']
                    : $this->_entity_schema->table;
            $this->add_table($table);
            return $this->_table_aliases[$table].'.'.$root_prop;
        } else { //going on with the next component mapper
            if ( ! array_key_exists($root_prop, $this->_entity_schema->components))
                throw new JORK_Exception('class '.$this->_entity_schema->class
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
