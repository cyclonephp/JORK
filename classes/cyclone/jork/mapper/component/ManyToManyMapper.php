<?php
namespace cyclone\jork\mapper\component;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
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

    protected function foreigntbl_to_jointbl() {
        $comp_schema = $this->_entity_schema->components[$this->_parent_mapper
            ->_entity_schema
            ->components[$this->_comp_name]->mapped_by];
        $local_cols = $comp_schema->inverse_join_columns;
        $local_tables = $this->_parent_mapper->_entity_schema->table_names_for_columns($local_cols);

        $remote_cols = $comp_schema->join_table->inverse_join_columns;
        $remote_table = $comp_schema->join_table->name;
        $remote_table_alias = $this->_parent_mapper->table_alias($remote_table);
        $joins = array();
        foreach ($local_cols as $idx => $local_col) {
            $local_table = $local_tables[$idx];
            $this->_parent_mapper->add_table($local_table);
            $local_table_alias = $this->_parent_mapper->table_alias($local_table);
            $remote_col = $remote_cols[$idx];
            if ( ! isset($joins[$local_table])) {
                $joins[$local_table] = array(
                    'table' => array($remote_table, $remote_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$local_table];
            }
            $joins[$local_table]['conditions'] []= new db\BinaryExpression(
                $local_table_alias . '.' . $local_col
                , '='
                , $remote_table_alias . '.' . $remote_col
            );
        }
    }

    protected function jointbl_to_localtbl() {
        $comp_schema = $this->_entity_schema->components[$this->_parent_mapper
            ->_entity_schema
            ->components[$this->_comp_name]->mapped_by];
        $local_cols = $comp_schema->join_table->join_columns;
        $local_table = $comp_schema->join_table->name;
        $this->_parent_mapper->add_table($local_table);
        $local_table_alias = $this->_parent_mapper->table_alias($local_table);
        $remote_cols = $comp_schema->join_columns;
        $remote_tables = $this->_entity_schema->table_names_for_columns($remote_cols);

        $joins = array();
        foreach($local_cols as $idx => $local_col) {
            $remote_table = $remote_tables[$idx];
            //$this->add_table($remote_table);
            $remote_table_alias = $this->table_alias($remote_table);
            $remote_col = $remote_cols[$idx];

            if ( ! isset($joins[$remote_table])) {
                $joins[$remote_table] = array(
                    'table' => array($remote_table, $remote_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$remote_table];
            }
            $joins[$remote_table]['conditions'] []= new db\BinaryExpression(
                $local_table_alias . '.' . $local_col
                , '='
                , $remote_table_alias . '.' . $remote_col
            );
        }

    }

    protected function  comp2join_reverse() {
        $this->foreigntbl_to_jointbl();
        $this->jointbl_to_localtbl();
    }
    
}
