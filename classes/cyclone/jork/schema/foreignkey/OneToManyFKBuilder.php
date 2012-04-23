<?php
namespace cyclone\jork\schema\foreignkey;

use cyclone as cy;
use cyclone\db\schema;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class OneToManyFKBuilder extends ForeignKeyBuilder {

    public function create_foreign_key() {
        $comp_class_schema = $this->_schema_pool[$this->_comp_schema->class];
        $foreign_tables = array();
        $foreign_cols = array();
        foreach ($comp_class_schema->primitives as $prim_schema) {
            $col_name = NULL === $prim_schema->column ? $prim_schema->name : $prim_schema->column;
            if (in_array($col_name, $this->_comp_schema->join_columns)) {
                $table_name = NULL === $prim_schema->table
                        ? $comp_class_schema->table
                        : $prim_schema->table;
                $foreign_table = ($foreign_tables []= schema\Table::get_by_name($table_name));
                if (!isset($this->_table_pool[$foreign_table->name])) {
                    $this->_table_pool[$foreign_table->name] = $foreign_table;
                }
                $foreign_cols []= $foreign_table->get_column($col_name);
            }
        }
        if (empty($foreign_tables))
            throw new SchemaBuilderException("Failed to create foreign key constraint: "
                    . "no foreign table found for {$this->_model_schema->class}::\${$comp_schema->name}");

        $fks = array();
        $comp_schema = $this->_comp_schema;
        foreach ($foreign_tables as $idx => $foreign_table) {
            $foreign_col = $foreign_cols[$idx];
            if ( ! isset($fks[$foreign_table->name])) {
                $fks[$foreign_table->name] = new schema\ForeignKey();
            }
            $fk = $fks[$foreign_table->name];
            $fk->local_columns []= $foreign_col;

            $pk_prop_names = $this->_model_schema->primary_keys();
            $pk_schemas = array();
            foreach ($pk_prop_names as $pk_prop_name) {
                $pk_schemas []= $this->_model_schema->primitives[$pk_prop_name];
            }
            $local_col_names = array();
            if (empty($this->_comp_schema->inverse_join_columns)) {
                foreach ($pk_schemas as $pk_schema) {
                    $local_col_names []= NULL === $pk_schema->column
                        ? $pk_schema->name
                        : $pk_schema->column;
                }
            } else {
                $local_col_names = $comp_schema->inverse_join_columns;
            }

            foreach($this->_model_schema->primitives as $prim_schema) {
                $tmp_col_name = NULL === $prim_schema->column
                    ? $prim_schema->name
                    : $prim_schema->column;
                if (in_array($tmp_col_name, $local_col_names)) {
                    $local_table = schema\Table::get_by_name(NULL === $prim_schema->table
                        ? $this->_model_schema->table
                        : $prim_schema->table);
                    if ( ! isset($this->_table_pool[$local_table->name])) {
                        $this->_table_pool[$local_table->name] = $local_table;
                    }
                    $local_col = $local_table->get_column($tmp_col_name);
                    $fk->foreign_table = $local_table;
                    $fk->foreign_columns []= $local_col;
                }
            }
        }
        foreach ($fks as $tbl_name => $fk) {
            schema\Table::get_by_name($tbl_name)->add_foreign_key($fk);
        }
    }

}