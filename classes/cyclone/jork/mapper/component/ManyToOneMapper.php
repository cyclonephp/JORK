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

        $join_cols = $comp_schema->join_columns;
        $joins = array();
        foreach ($join_cols as $idx => $join_col) {
            $join_col_schema = $this->_parent_mapper->_entity_schema->get_property_schema($join_col);
            $join_table = isset($join_col_schema->table)
                ? $join_col_schema->table
                : $this->_parent_mapper->_entity_schema->table;

            $this->_parent_mapper->add_table($join_table);
            $join_table_alias = $this->_parent_mapper->table_alias($join_table);

            $remote_join_col = count($comp_schema->inverse_join_columns) > 0
                ? $comp_schema->inverse_join_columns[$idx]
                : $remote_schema->primary_key();

            $remote_join_col_schema = $remote_schema->get_property_schema($remote_join_col);

            $remote_join_table = isset($remote_join_col_schema->table)
                ? $remote_join_col_schema->table
                : $remote_schema->table;

            $remote_join_table_alias = $this->table_alias($remote_join_table);

            if ( ! isset($joins[$join_table])) {
                $joins[$join_table] = array(
                    'table' => array($remote_join_table, $remote_join_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$join_table];
            }
            $joins[$join_table]['conditions'] []= new db\BinaryExpression(
                $join_table_alias.'.'.$join_col
                , '='
                , $remote_join_table_alias.'.'.$remote_join_col);
        }
    }

    protected function  comp2join_reverse() {
        $local_schema = $this->_parent_mapper->_entity_schema->components[$this->_comp_name];
        
        $remote_class = $local_schema->class;
        
        $remote_schema = jork\model\AbstractModel::schema_by_class($remote_class);

        $remote_comp_def = $remote_schema->components[$local_schema->mapped_by];
        $remote_columns = $remote_comp_def->join_columns;
        $remote_tables = $remote_schema->table_names_for_columns($remote_columns);

        $joins = array();

        foreach ($remote_comp_def->join_columns as $idx => $remote_join_col) {
            $remote_join_col_def = $remote_schema->primitives[$remote_join_col];
            $remote_join_table = $remote_tables[$idx];
            $remote_table_alias = $this->table_alias($remote_join_table);
            $remote_column = $remote_columns[$idx];
            if ( ! isset($joins[$remote_join_table])) {
                $joins[$remote_join_table] = array(
                    'table' => array($remote_join_table, $remote_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$remote_join_table];
            }
            $joins[$remote_join_table]['conditions'] []= new db\BinaryExpression(
                    $this->_naming_srv->table_alias($this->_parent_mapper->_entity_alias
                    , $this->_parent_mapper->_entity_schema->table)
                    .'.'
                    .$this->_parent_mapper->_entity_schema->primary_key()
                , '='
                ,$remote_table_alias.'.'.$remote_column);
        }
return;

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
