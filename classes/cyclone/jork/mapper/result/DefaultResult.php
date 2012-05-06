<?php

namespace cyclone\jork\mapper\result;

use cyclone\jork;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class DefaultResult extends AbstractResult {

    /**
     *
     * @var jork\query\Select
     */
    private $_jork_query;

    /**
     * @var db\query\Result
     */
    private $_db_result;

    /**
     * @var boolean
     */
    private $_has_implicit_root;

    /**
     * The mappers of the root entities of the query. Passed in the constructor.
     *
     * @var array<jork\mapper\RowMapper>
     */
    private $_root_mappers;

    /**
     * Mappers that should return an entity collection in each row.
     *
     * @var array<jork\mapper\RowMapper>
     */
    private $_coll_mappers = array();

    /**
     * Mappers that should return an entity in each row.
     *
     * @var array<JORK_Mapper_Row>
     */
    private $_entity_mappers = array();

    /**
     * Mappers that should return an atomic property (scalar) in each row.
     *
     * @var array<cyclone\jork\mapper\RowMapper>
     */
    private $_atomic_mappers = array();

    /**
     * @var array<string>
     */
    private $_atomic_props = array();

    public function  __construct(jork\query\SelectQuery $jork_query
            , db\query\result\AbstractResult $db_result, $has_implicit_root
            , $mappers) {
        $this->_jork_query = $jork_query;
        $this->_db_result = $db_result;
        $this->_has_implicit_root = $has_implicit_root;
        $this->_root_mappers = $mappers;
        $this->extract_mappers($mappers);
    }

    private function extract_mappers($mappers) {
        foreach ($this->_jork_query->select_list as $select_itm) {
            if (array_key_exists('expr', $select_itm)) { // database expression
                $alias = $select_itm['alias'];
                $this->_entity_mappers[$alias] = $mappers[$alias];
                continue;
            }

            $alias = isset($select_itm['alias'])
                    ? $select_itm['alias']
                    : $select_itm['prop_chain']->as_string();
            
            $prop_chain = $select_itm['prop_chain']->as_array();
            if ($this->_has_implicit_root) {
                $root_mapper = $mappers[NULL];
            } else {
                $root_prop = array_shift($prop_chain);
                $root_mapper = $mappers[$root_prop];
            }
            if (empty($prop_chain)) {
                $itm_mapper = $root_mapper;
                $this->_entity_mappers[$alias] = $itm_mapper;
            } else {
                list($itm_mapper, $atomic_prop) = $root_mapper
                        ->get_mapper_for_propchain($prop_chain);
                if (FALSE === $atomic_prop) {
                    if ($root_mapper->is_to_many_comp($prop_chain)) {
                        $this->_coll_mappers[$alias] = $itm_mapper;
                    } else {
                        $this->_entity_mappers[$alias] = $itm_mapper;
                    }
                } else {
                    $this->_atomic_mappers[$alias] = $itm_mapper;
                    $this->_atomic_props[$alias] = $atomic_prop;
                }
            }
        }
    }

    public function map() {
        $obj_result = array();
        $prev_row = NULL;
        foreach ($this->_db_result as $row) {
            $is_new_row = FALSE;

            foreach ($this->_root_mappers as $mapper) {
                list($entity, $is_new) = $mapper->map_row($row);
                $is_new_row = $is_new_row || $is_new;
            }

            if ($is_new_row) {
                if (isset($obj_result_row)) {
                    $obj_result []= $obj_result_row;
                }
                $obj_result_row = array();
                foreach ($this->_coll_mappers as $alias => $mapper) {
                    $obj_result_row[$alias] = $mapper->create_collection();
                }
                foreach ($this->_entity_mappers as $alias => $mapper) {
                    $obj_result_row[$alias] = $mapper->get_last_entity();
                }
                foreach ($this->_atomic_mappers as $alias => $mapper) {
                    $last_entity = $mapper->get_last_entity();
                    $obj_result_row[$alias] = $last_entity === NULL ? NULL :
                            $last_entity->{$this->_atomic_props[$alias]};
                }
            }

            foreach ($this->_coll_mappers as $alias => $mapper) {
                $last_entity = $mapper->get_last_entity();
                if ($obj_result_row[$alias] != NULL && $last_entity != NULL) {
                    $obj_result_row[$alias]->append_persistent($last_entity);
                }
            }
        }
        
        if (isset($obj_result_row)) {
            $obj_result [] = $obj_result_row;
        }
        return $obj_result;
    }
    
}