<?php
namespace cyclone\jork\mapper\component;

use cyclone\jork;
use cyclone\jork\schema\SchemaPool;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class OneToOneMapper extends AbstractMapper {

    protected function comp2join() {
        $comp_schema = $this->_comp_schema;

        $local_join_cols = $comp_schema->join_columns;
        $local_tables = $this->_parent_mapper->_entity_schema->table_names_for_columns($local_join_cols);

        $inv_join_cols = $comp_schema->inverse_join_columns;
        $inv_tables = $this->_entity_schema->table_names_for_columns($inv_join_cols);

        $joins = array();

        foreach ($local_join_cols as $idx => $local_join_col) {
            $local_table = $local_tables[$idx];
            $this->_parent_mapper->add_table($local_table);
            $local_table_alias = $this->_parent_mapper->table_alias($local_table);

            $inv_join_col = $inv_join_cols[$idx];
            $inv_table = $inv_tables[$idx];
            $inv_table_alias = $this->table_alias($inv_table);

            if ( ! isset($joins[$inv_table])) {
                $joins[$inv_table] = array(
                    'table' => array($inv_table, $inv_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$inv_table];
            }
            $joins[$inv_table]['conditions'] []= new db\BinaryExpression(
                    $local_table_alias . '.' . $local_join_col
                    , '='
                    , $inv_table_alias . '.' . $inv_join_col
            );
        }
    }

    protected function  comp2join_reverse() {
        $remote_schema = SchemaPool::inst()->get_schema($this->_comp_schema->class);
        $comp_schema = $remote_schema->components[$this->_comp_schema->mapped_by];

        $parent_join_cols = $comp_schema->inverse_join_columns;
        $parent_tables = $this->_parent_mapper->_entity_schema->table_names_for_columns($parent_join_cols);

        $local_join_cols = $comp_schema->join_columns;
        $local_tables = $this->_entity_schema->table_names_for_columns($local_join_cols);

        $joins = array();

        foreach($parent_join_cols as $idx => $parent_join_col) {
            $parent_table = $parent_tables[$idx];

            $this->_parent_mapper->add_table($parent_table);

            $local_join_col = $local_join_cols[$idx];

            $local_table = $local_tables[$idx];
            $local_table_alias = $this->table_alias($local_table);

            if ( ! isset($joins[$local_table])) {
                $joins[$local_table] = array(
                    'table' => array($local_table, $local_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$local_table];
            }
            $joins[$local_table]['conditions'] []= new db\BinaryExpression(
                $this->_parent_mapper->table_alias($parent_table)
                    .'.'.$parent_join_col
                , '='
                , $local_table_alias.'.'.$local_join_col
            );
        }
    }

}
