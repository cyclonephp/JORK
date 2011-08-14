<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Mapper_Component_Embedded extends JORK_Mapper_Entity {

    /**
     *
     * @var JORK_Mapper_Entity
     */
    protected $_parent_mapper;

    protected $_comp_name;

    /**
     *
     * @var JORK_Mapping_Schema_Embeddable
     */
    protected $_comp_schema;

    /**
     * Used at result mapping.
     *
     * @var array the previous database result row
     */
    protected $_prev_result_row;

    public function map_row(&$db_row) {
        if ($db_row == $this->_prev_result_row)
            return array($this->_previous_result_entity, false);

        $class = $this->_entity_schema->class;
        $entity = new $class;

        $atomics = array();
        foreach ($this->_result_atomics as $col_name => $prop_name) {
            $atomics[$prop_name] = $db_row[$col_name];
        }
        
        $entity->populate_atomics($atomics);

        $this->_previous_result_entity = $entity;
        
        return array($entity, true);
    }


    public function __construct($parent_mapper, $comp_name, $select_item) {
        $this->_parent_mapper = $parent_mapper;
        $this->_comp_name = $comp_name;
        $this->_entity_alias = $select_item;
        $this->_entity_schema = $this->_parent_mapper
                ->_entity_schema->components[$comp_name];
        $this->_naming_srv = $this->_parent_mapper->_naming_srv;
        $this->_db_query = $this->_parent_mapper->_db_query;
        $this->_jork_query = $parent_mapper->_jork_query;
        $this->_table_aliases = &$this->_parent_mapper->_table_aliases;
    }

    protected function add_atomic_property($prop_name, &$prop_schema) {
        if (in_array($prop_name, $this->_result_atomics))
                return;

        $tbl_name = $this->_parent_mapper->_entity_schema->table;

        if ( ! array_key_exists($tbl_name, $this->_parent_mapper->_table_aliases)) {
            $tbl_alias = $this->_parent_mapper->add_table($tbl_name);
        }
        $tbl_alias = $this->_parent_mapper->_table_aliases[$tbl_name];

        $col_name = array_key_exists('column', $prop_schema)
                ? $prop_schema['column']
                : $prop_name;

        $full_column = $tbl_alias.'.'.$col_name;
        $full_alias = $tbl_alias.'_'.$col_name;
        $this->_db_query->columns []= array($full_column, $full_alias);
        $this->_result_atomics[$full_alias] = $prop_name;

    }

    protected function  add_table($tbl_name) {
        $this->_parent_mapper->add_table($tbl_name);
    }

    protected function  table_alias($tbl_name) {
        return $this->_parent_mapper->table_alias($tbl_name);
    }
    
    
}
