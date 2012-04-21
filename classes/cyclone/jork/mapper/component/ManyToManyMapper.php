<?php
namespace cyclone\jork\mapper\component;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class ManyToManyMapper extends AbstractMapper {

    protected function localtbl_to_jointbl() {
        $local_join_cols = $this->_comp_schema->join_columns;
        $local_join_tables = $this->_parent_mapper->_entity_schema->table_names_for_columns($local_join_cols);

        $remote_join_cols = $this->_comp_schema->join_table->join_columns;
        $remote_join_table = $this->_comp_schema->join_table->name;
        $remote_join_table_alias = $this->_parent_mapper->table_alias($remote_join_table);
        $joins = array();
        foreach ($local_join_cols as $idx => $local_join_col) {
            $local_join_table = $local_join_tables[$idx];
            $this->_parent_mapper->add_table($local_join_table);
            $local_join_table_alias = $this->_parent_mapper->table_alias($local_join_table);
            $remote_join_col = $remote_join_cols[$idx];
            if ( ! isset($joins[$local_join_table])) {
                $joins[$local_join_table] = array(
                    'table' => array($remote_join_table, $remote_join_table_alias),
                    'type' => 'LEFT'
                );
                $this->_db_query->joins []= &$joins[$local_join_table];
            }
            $joins[$local_join_table]['conditions'] []= new db\BinaryExpression(
                $local_join_table_alias . '.' . $local_join_col
                , '='
                , $remote_join_table_alias . '.' . $remote_join_col
            );
        }
    }

    protected function jointbl_to_remotetbl() {
        $local_join_cols = $this->_comp_schema->join_table->inverse_join_columns;
        $local_table = $this->_comp_schema->join_table->name;
        $local_table_alias = $this->_parent_mapper->table_alias($local_table);

        $remote_join_cols = $this->_comp_schema->inverse_join_columns;
        $remote_join_tables = $this->_entity_schema->table_names_for_columns($remote_join_cols);

        $joins = array();
        foreach ($local_join_cols as $idx => $local_join_col) {
            $remote_join_col = $remote_join_cols[$idx];
            $remote_join_table = $remote_join_tables[$idx];
            //$this->add_table($remote_join_table);
            $remote_join_table_alias = $this->table_alias($remote_join_table);
            if ( ! isset($joins[$remote_join_table])) {
                $joins[$remote_join_table] = array(
                    'table' => array($remote_join_table, $remote_join_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$remote_join_table];
            }
            $joins[$remote_join_table]['conditions'] []= new db\BinaryExpression(
                $local_table_alias . '.' . $local_join_col
                , '='
                , $remote_join_table_alias . '.' . $remote_join_col
            );
        }
    }

    protected function comp2join() {
        $this->localtbl_to_jointbl();
        $this->jointbl_to_remotetbl();
    }

    protected function  comp2join_reverse() {
        $comp_schema = $this->_entity_schema->components[$this
                ->_parent_mapper->_entity_schema->components[$this->_comp_name]->mapped_by];

        $local_join_col = $this->_entity_schema->primary_key();
        $local_join_col_schema = $this->_entity_schema->primitives[$local_join_col];
        $local_join_col_name = isset($local_join_col_schema->column)
                ? $local_join_col_schema->column
                : $local_join_col;

        $local_table = isset($local_join_col_schema->table)
                ? $local_join_col_schema->table
                : $this->_entity_schema->table;

        //$this->add_table($local_table);
        $local_table_alias = $this->table_alias($local_table);

        $mid_local_column = $comp_schema->join_table->inverse_join_column;

        $join_table = $comp_schema->join_table->name;
        //$this->_parent_mapper->add_table($join_table);
        $join_table_alias = $this->_parent_mapper->table_alias($join_table);
        
        $mid_remote_column = $comp_schema->join_table->join_column;

        $remote_join_col = $this->_parent_mapper->_entity_schema->primary_key();
        $remote_join_col_schema = $this->_parent_mapper->_entity_schema->primitives[$remote_join_col];
        $remote_join_col_name = isset($remote_join_col_schema->column)
                ? $remote_join_col_schema->column
                : $remote_join_col;

        $remote_join_table = isset($remote_join_col_schema->table)
                ? $remote_join_col_schema->table
                : $this->_parent_mapper->_entity_schema->table;

        $this->_parent_mapper->add_table($remote_join_table);
        $remote_join_table_alias = $this->_parent_mapper->table_alias($remote_join_table);

        $this->_db_query->joins []= array(
            'table' => array($join_table, $join_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression($remote_join_table_alias.'.'.$remote_join_col_name
                        , '=', $join_table_alias.'.'.$mid_local_column)
            )
        );
        $this->_db_query->joins []= array(
            'table' => array($local_table, $local_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression($join_table_alias.'.'.$mid_remote_column
                        , '=', $local_table_alias.'.'.$local_join_col_name)
            )
        );
        
    }
    
}
