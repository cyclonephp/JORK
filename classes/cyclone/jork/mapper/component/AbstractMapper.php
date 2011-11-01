<?php

namespace cyclone\jork\mapper\component;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * This class is responsible for mapping any property chains represented by
 * JORK joins to DB joins and creating the result mappers.
 * 
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
abstract class AbstractMapper extends jork\mapper\EntityMapper {

    /**
     * @var jork\mapper\EntityMapper
     */
    protected $_parent_mapper;

    protected $_comp_name;

    protected $_comp_schema;

    protected $_is_reverse;

    public function  __construct(jork\mapper\EntityMapper $parent_mapper, $comp_name, $select_item) {
        parent::__construct($parent_mapper->_naming_srv
                , $parent_mapper->_jork_query
                , $parent_mapper->_db_query
                , $select_item);
        $this->_parent_mapper = $parent_mapper;
        $this->_comp_name = $comp_name;
        $this->_comp_schema = $parent_mapper->_entity_schema->components[$comp_name];
        $this->_is_reverse = isset($this->_comp_schema->mapped_by);
    }

   

    /**
     * Factory method for creating the required component mapper for the component.
     *
     * @param JORK_Mapper_Entity $parent_mapper
     * @param string $comp_name
     * @param string $select_item
     * @return JORK_Mapper_Component
     * @see JORK_Mapper_Entity::get_component_mapper()
     */
    public static function factory(jork\mapper\EntityMapper $parent_mapper
            , $comp_name, $select_item) {
        if (isset($parent_mapper->_entity_schema->embedded_components[$comp_name]))
            return new EmbeddedMapper ($parent_mapper, $comp_name, $select_item);
        
        $comp_def = $parent_mapper->_entity_schema->components[$comp_name];

//        if ($comp_def instanceof jork\schema\EmbeddableSchema) {
//            return new EmbeddedMapper($parent_mapper, $comp_name, $select_item);
//        }

        $impls = array(
            cy\JORK::ONE_TO_ONE => 'cyclone\\jork\\mapper\\component\\OneToOneMapper',
            cy\JORK::ONE_TO_MANY => 'cyclone\\jork\\mapper\\component\\OneToManyMapper',
            cy\JORK::MANY_TO_ONE => 'cyclone\\jork\\mapper\\component\ManyToOneMapper',
            cy\JORK::MANY_TO_MANY => 'cyclone\\jork\\mapper\\component\ManyToManyMapper'
        );

        if (isset($comp_def->mapped_by)) {
            $remote_schema = jork\model\AbstractModel::schema_by_class($comp_def->class);

            $remote_comp_def = $remote_schema->get_property_schema($comp_def->mapped_by);

            $class = $impls[$remote_comp_def->type];

            return new $class($parent_mapper, $comp_name, $select_item);

        } else {
            if ( ! isset($comp_def->type, $impls))
                throw new jork\Exception("unknown component type: {$comp_def->type}");
            $class = $impls[$comp_def->type];

            return new $class($parent_mapper, $comp_name, $select_item);
        }

    }
    protected abstract function comp2join();

    protected abstract function comp2join_reverse();

    protected function is_primary_join_table($tbl_name) {
        static $primary_join_tables;
        //TODO it's just a mock return value (which works in most cases...)
        return $tbl_name == $this->_entity_schema->table;
        if (NULL == $primary_join_tables) {
            $primary_join_tables = array();
            if (isset($this->_comp_schema->join_column)) {
                $join_col_schema = $this->_parent_mapper->_entity_schema->columns[$this->_comp_schema->join_column];
                $primary_join_tables []= isset($join_col_schema->table)
                        ? $join_col_schema->table
                        : $this->_entity_schema->table;
            } else { //TODO composite foreign key

            }
        }
        return in_array($tbl_name, $primary_join_tables);
    }

    protected function  add_table($tbl_name) {
        if ($this->is_primary_join_table($tbl_name)) {
            if ( ! array_key_exists($tbl_name, $this->_table_aliases)) {
                if ($this->_is_reverse) {
                    $this->comp2join_reverse();
                } else {
                    $this->comp2join();
                }
            }
        } else {
            parent::add_table($tbl_name);
        }
    }

    public function create_collection() {
        $last_parent_entity = $this->_parent_mapper->get_last_entity();
        if (NULL === $last_parent_entity)
             return NULL;
        return jork\model\collection\AbstractCollection::for_component($last_parent_entity, $this->_comp_name);
    }

}
