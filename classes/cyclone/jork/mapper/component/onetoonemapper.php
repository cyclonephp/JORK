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

        $local_join_col = $comp_schema['join_column'];
        $local_table = $this->_parent_mapper->_entity_schema->table_name_for_column($local_join_col);
        $this->_parent_mapper->add_table($local_table);
        $local_table_alias = $this->_parent_mapper->table_alias($local_table);

        $remote_join_col = array_key_exists('inverse_join_column', $comp_schema)
                ? $comp_schema['inverse_join_column']
                : $this->_entity_schema->primary_key();

        $remote_table = $this->_entity_schema->table_name_for_column($remote_join_col);
        $remote_table_alias = $this->table_alias($remote_table);

        $this->_db_query->joins []= array(
            'table' => array($remote_table, $remote_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression(
                    $local_table_alias.'.'.$local_join_col
                    , '='
                    , $remote_table_alias.'.'.$remote_join_col
                )
            )
        );

    }

    protected function  comp2join_reverse() {
        $remote_schema = jork\model\AbstractModel::schema_by_class($this->_comp_schema['class']);
        $comp_schema = $remote_schema->components[$this->_comp_schema['mapped_by']];

        $parent_join_col = array_key_exists('inverse_join_column', $comp_schema)
                ? $comp_schema['inverse_join_column']
                : $this->_entity_schema->primary_key();
        $parent_join_col_schema = $remote_schema->atomics[$parent_join_col];
        $parent_table = array_key_exists('table', $parent_join_col_schema)
                ? $parent_join_col_schema['table']
                : $this->_parent_mapper->_entity_schema->table;
        $this->_parent_mapper->add_table($parent_table);

        $local_join_col = $comp_schema['join_column'];

        $local_join_col_schema = $this->_entity_schema->atomics[$local_join_col];
        $local_table = array_key_exists('table', $local_join_col_schema)
                ? $local_join_col['table']
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
