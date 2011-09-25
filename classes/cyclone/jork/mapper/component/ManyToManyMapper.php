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

    protected function comp2join() {
        $comp_schema = $this->_parent_mapper->_entity_schema->components[$this->_comp_name];
        $remote_schema = jork\model\AbstractModel::schema_by_class($comp_schema['class']);

        $local_join_col = $this->_parent_mapper->_entity_schema->primary_key();
        $local_join_col_schema = $this->_parent_mapper->_entity_schema->atomics[$local_join_col];
        $local_table = array_key_exists('table', $local_join_col_schema)
                ? $local_join_col_schema['table']
                : $this->_parent_mapper->_entity_schema->table;
        $this->_parent_mapper->add_table($local_table);
        $local_table_alias = $this->_parent_mapper->table_alias($local_table);
        $local_join_col_name = array_key_exists('column', $local_join_col_schema)
                ? $local_join_col_schema['column']
                : $local_join_col;
        
        $mid_local_join_col = $comp_schema['join_table']['join_column'];

        $join_table = $comp_schema['join_table']['name'];
        $join_table_alias = $this->_parent_mapper->table_alias($join_table);

        $mid_remote_join_col = $comp_schema['join_table']['inverse_join_column'];

        $remote_join_col = $remote_schema->primary_key();
        $remote_join_col_schema = $remote_schema->atomics[$remote_join_col];
        $remote_table = array_key_exists('table', $remote_join_col_schema)
                ? $remote_join_col_schema['table']
                : $remote_schema->table;
        $remote_table_alias = $this->table_alias($remote_table);
        $remote_join_col_name = array_key_exists('column', $remote_join_col_schema)
                ? $remote_join_col_schema['column']
                : $remote_join_col;

        $this->_db_query->joins []= array(
            'table' => array($join_table, $join_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression($local_table_alias.'.'.$local_join_col_name
                        , '=', $join_table_alias.'.'.$mid_local_join_col)
            )
        );
        $this->_db_query->joins []= array(
            'table' => array($remote_table, $remote_table_alias),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression($join_table_alias.'.'.$mid_remote_join_col
                        , '=', $remote_table_alias.'.'.$remote_join_col_name)
            )
        );

    }

    protected function  comp2join_reverse() {
        $comp_schema = $this->_entity_schema->components[$this
                ->_parent_mapper->_entity_schema->components[$this->_comp_name]['mapped_by']];

        $local_join_col = $this->_entity_schema->primary_key();
        $local_join_col_schema = $this->_entity_schema->atomics[$local_join_col];
        $local_join_col_name = array_key_exists('column', $local_join_col_schema)
                ? $local_join_col_schema['column']
                : $local_join_col;

        $local_table = array_key_exists('table', $local_join_col_schema)
                ? $local_join_col_schema['table']
                : $this->_entity_schema->table;

        //$this->add_table($local_table);
        $local_table_alias = $this->table_alias($local_table);

        $mid_local_column = $comp_schema['join_table']['inverse_join_column'];

        $join_table = $comp_schema['join_table']['name'];
        //$this->_parent_mapper->add_table($join_table);
        $join_table_alias = $this->_parent_mapper->table_alias($join_table);
        
        $mid_remote_column = $comp_schema['join_table']['join_column'];

        $remote_join_col = $this->_parent_mapper->_entity_schema->primary_key();
        $remote_join_col_schema = $this->_parent_mapper->_entity_schema->atomics[$remote_join_col];
        $remote_join_col_name = array_key_exists('column', $remote_join_col_schema)
                ? $remote_join_col_schema['column']
                : $remote_join_col;

        $remote_join_table = array_key_exists('table', $remote_join_col_schema)
                ? $remote_join_col_schema['table']
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
