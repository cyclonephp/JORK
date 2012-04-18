<?php
namespace cyclone\jork\mapper\component;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class OneToManyMapper extends AbstractMapper {

    protected function comp2join() {
        $parent_ent_schema = $this->_parent_mapper->_entity_schema;
        $comp_schema = $parent_ent_schema->components[$this->_comp_name];

        $local_join_cols = $comp_schema->join_columns;
        $local_join_tables = $this->_entity_schema->table_names_for_columns($local_join_cols);

        $remote_join_cols = $comp_schema->inverse_join_columns;
        $remote_tables = $this->_entity_schema->table_names_for_columns($remote_join_cols);

        $joins = array();

        foreach ($local_join_cols as $idx => $local_join_col) {
            $local_join_table = $local_join_tables[$idx];
            $local_join_table_alias = $this->table_alias($local_join_table);

            $remote_join_col = $remote_join_cols[$idx];
            $remote_join_table = $remote_tables[$idx];
            $this->_parent_mapper->add_table($remote_join_table);
            $remote_join_table_alias = $this->_parent_mapper->table_alias($remote_join_table);

            if ( ! isset($joins[$local_join_table])) {
                $joins[$local_join_table] = array(
                    'table' => array($local_join_table, $local_join_table_alias)
                    , 'type' => 'LEFT'
                    , 'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$local_join_table];
            }
            $joins[$local_join_table]['conditions'] []= new db\BinaryExpression($remote_join_table_alias
                    .'.'.$remote_join_col
                , '=', $local_join_table_alias.'.'
                    .$local_join_col);
        }
    }

    protected function comp2join_reverse() {
        $parent_ent_schema = $this->_parent_mapper->_entity_schema;
        $local_comp_schema = $parent_ent_schema->components[$this->_comp_name];
        $comp_schema = jork\model\AbstractModel::schema_by_class($local_comp_schema->class)->components[$local_comp_schema->mapped_by];

        $local_join_cols = $comp_schema->inverse_join_columns;
        $local_join_tables = $this->_entity_schema->table_names_for_columns($local_join_cols);

        $remote_join_cols = $comp_schema->join_columns;
        $remote_join_tables = $parent_ent_schema->table_names_for_columns($remote_join_cols);

        $joins = array();

        foreach ($local_join_cols as $idx => $local_join_col) {
            $local_join_table = $local_join_tables[$idx];
            $local_join_table_alias = $this->table_alias($local_join_table);

            $remote_join_col = $remote_join_cols[$idx];
            $remote_join_table = $remote_join_tables[$idx];
            $this->_parent_mapper->add_table($remote_join_table);
            $remote_join_table_alias = $this->_parent_mapper->table_alias($remote_join_table);

            if ( ! isset($joins[$local_join_table])) {
                $joins[$local_join_table] = array(
                    'table' => array($local_join_table, $local_join_table_alias),
                    'type' => 'LEFT',
                    'conditions' => array()
                );
                $this->_db_query->joins []= &$joins[$local_join_table];
            }
            $joins[$local_join_table]['conditions'] []= new db\BinaryExpression($remote_join_table_alias
                    .'.'.$remote_join_col
                , '=', $local_join_table_alias.'.'
                    .$local_join_col);
        }
    }

}
