<?php

namespace cyclone\jork\mapper\component;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class ManyToOneMapper extends AbstractMapper {

    protected function comp2join() {
        $comp_schema = $this->_parent_mapper->_entity_schema->components[$this->_comp_name];
        $remote_schema = jork\model\AbstractModel::schema_by_class($comp_schema->class);

        $join_col = $comp_schema->join_column;

        $join_col_schema = $this->_parent_mapper->_entity_schema->get_property_schema($join_col);

        $join_table = isset($join_col_schema->table)
                ? $join_col_schema->table
                : $this->_parent_mapper->_entity_schema->table;

        $this->_parent_mapper->add_table($join_table);

        //$join_table_alias = $this->_naming_srv->table_alias($this->_parent_mapper->_entity_alias, $join_table);
        $join_table_alias = $this->_parent_mapper->table_alias($join_table);

        $remote_join_col = isset($comp_schema->inverse_join_column)
                ? $comp_schema->inverse_join_column
                : $remote_schema->primary_key();

        $remote_join_col_schema = $remote_schema->get_property_schema($remote_join_col);

        $remote_join_table = isset($remote_join_col_schema->table)
                ? $remote_join_col_schema->table
                : $remote_schema->table;

        $remote_join_table_alias = $this->table_alias($remote_join_table);

        $this->_db_query->joins []= array(
            'table' => array($remote_join_table, $remote_join_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression($join_table_alias.'.'.$join_col, '='
                    , $remote_join_table_alias.'.'.$remote_join_col)
            )
        );

        
    }

    protected function  comp2join_reverse() {
        $local_schema = $this->_parent_mapper->_entity_schema->components[$this->_comp_name];
        
        $remote_class = $local_schema->class;
        
        $remote_schema = jork\model\AbstractModel::schema_by_class($remote_class);

        $remote_comp_def = $remote_schema->components[$local_schema->mapped_by];

        $remote_join_col_def = $remote_schema->primitives[$remote_comp_def->join_column];

        $remote_join_table = isset($remote_join_col_def->table)
                ? $remote_join_col_def->table
                : $remote_schema->table;

        $remote_table_alias = $this->table_alias($remote_join_table);

        $this->_db_query->joins []= array(
            'table' => array($remote_join_table, $remote_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression($this->_naming_srv->table_alias($this->_parent_mapper->_entity_alias
                        , $this->_parent_mapper->_entity_schema->table)
                        .'.'
                        .$this->_parent_mapper->_entity_schema->primary_key()
                , '='
                ,$remote_table_alias.'.'.$remote_comp_def->join_column)
            )
        );
        
    }

}
