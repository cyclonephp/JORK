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
        $foreign_table = NULL;
        $foreign_col = NULL;
        foreach ($comp_class_schema->primitives as $prim_schema) {
            $col_name = NULL === $prim_schema->column ? $prim_schema->name : $prim_schema->column;
            if ($col_name === $comp_schema->join_column) {
                $table_name = NULL === $prim_schema->table ? $comp_class_schema->table : $prim_schema->table;
                $foreign_table = schema\Table::get_by_name($table_name);
                if (!isset($this->_table_pool[$foreign_table->name])) {
                    $this->_table_pool[$foreign_table->name] = $foreign_table;
                }
                $foreign_col = $foreign_table->get_column($col_name);
            }
        }
        if (NULL === $foreign_table)
            throw new SchemaBuilderException("Failed to create foreign key constraint: "
                    . "no foreign table found for {$model_schema->class}::\${$comp_schema->name}");
        $fk = new schema\ForeignKey;
        $fk->local_table = $foreign_table;
        // TODO composite foreign key handling
        $fk->local_columns = array($foreign_col);

        $pk_schema = $model_schema->primitives[$model_schema->primary_key()];
        if (NULL === $comp_schema->inverse_join_column) {
            $local_col_name = NULL === $pk_schema->column ? $pk_schema->name : $pk_schema->column;
        } else {
            $local_col_name = $comp_schema->inverse_join_column;
        }
        $local_table = NULL;
        foreach ($model_schema->primitives as $prim_schema) {
            $tmp_col_name = NULL === $prim_schema->column ? $prim_schema->name : $prim_schema->column;
            if ($tmp_col_name === $local_col_name) {
                $local_table = schema\Table::get_by_name(NULL === $prim_schema->table ? $model_schema->table : $prim_schema->table);
                if (!isset($this->_table_pool[$local_table->name])) {
                    $this->_table_pool[$local_table->name] = $local_table;
                }
                $local_col = $local_table->get_column($local_col_name);
                break;
            }
        }
        $fk->foreign_table = $local_table;
        $fk->foreign_columns = array($local_col);

        $this->_table_pool[$fk->local_table->name]->add_foreign_key($fk);
    }

}