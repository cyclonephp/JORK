<?php
namespace cyclone\jork\mapper\component;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class OneToOneMapper extends AbstractMapper {

    protected function comp2join() {
        $comp_schema = $this->_comp_schema;

        $local_join_cols = $comp_schema->join_columns;
        $local_tables = $this->_parent_mapper->_entity_schema->table_names_for_columns($local_join_cols);

        $inv_join_cols = $comp_schema->inverse_join_columns;
        $inv_tables = $this->_entity_schema->table_names_for_columns($inv_join_cols);

        foreach ($local_join_cols as $idx => $local_join_col) {
            $local_table = $local_tables[$idx];
            $this->_parent_mapper->add_table($local_table);
            $local_table_alias = $this->_parent_mapper->table_alias($local_table);

            $inv_join_col = $inv_join_cols[$idx];
            $inv_table = $inv_tables[$idx];
            $inv_table_alias = $this->table_alias($inv_table);

            $this->_db_query->joins []= array(
                'table' => array($inv_table, $inv_table_alias),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression($local_table_alias . '.' . $local_join_col
                        , '=', $inv_table_alias . '.' . $inv_join_col)
                )
            );
        }
    }

    protected function  comp2join_reverse() {
        $remote_schema = jork\model\AbstractModel::schema_by_class($this->_comp_schema->class);
        $comp_schema = $remote_schema->components[$this->_comp_schema->mapped_by];

        $parent_join_col = isset($comp_schema->inverse_join_column)
                ? $comp_schema->inverse_join_column
                : $this->_entity_schema->primary_key();
        $parent_join_col_schema = $remote_schema->primitives[$parent_join_col];
        $parent_table = isset($parent_join_col_schema->table)
                ? $parent_join_col_schema->table
                : $this->_parent_mapper->_entity_schema->table;
        $this->_parent_mapper->add_table($parent_table);

        $local_join_col = $comp_schema->join_column;

        $local_join_col_schema = $this->_entity_schema->primitives[$local_join_col];
        $local_table = isset($local_join_col_schema->table)
                ? $local_join_col->table
                : $this->_entity_schema->table;
        $local_table_alias = $this->table_alias($local_table);

        $this->_db_query->joins []= array(
            'table' => array($local_table, $local_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression(
                    $this->_parent_mapper->table_alias($parent_table)
                    .'.'.$parent_join_col
                    , '='
                    , $local_table_alias.'.'.$local_join_col
                )
            )
        );

    }

}
