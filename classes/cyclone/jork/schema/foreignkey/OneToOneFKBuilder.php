<?php
namespace cyclone\jork\schema\foreignkey;

use cyclone as cy;
use cyclone\db\schema;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class OneToOneFKBuilder extends ForeignKeyBuilder {

    public function create_foreign_key() {
        $join_columns = $this->_comp_schema->join_columns;
        $local_tables = $this->_model_schema->table_names_for_columns($join_columns);
        $local_fks = array();
        foreach ($join_columns as $idx => $local_column) {
            //$local_tables[$idx] = $local_table = $this->_model_schema->table_name_for_column($local_column);
            if (($join_columns == $this->_comp_schema->join_columns)
                || ($this->_model_schema->primary_key_strategy() === cy\JORK::ASSIGN)) {
                $local_table = $local_tables[$idx];
                if ( ! isset($local_fks[$local_table])) {
                    $local_fks[$local_table] = new schema\ForeignKey();
                    $local_fks[$local_table]->local_table = schema\Table::get_by_name($local_table);
                }
                $fk = $local_fks[$local_table];
                if ( ! isset($this->_table_pool[$local_table])) {
                    $this->_table_pool[$local_table] = $fk->local_table;
                }
                $fk->local_columns []= $fk->local_table->get_column($local_column);
            }
        }

        foreach ($local_fks as $local_table => $fk) {
            $this->_table_pool[$local_table]->add_foreign_key($fk);
        }

        $comp_class_schema = $this->_schema_pool[$this->_comp_schema->class];

        $inv_join_cols = $this->_comp_schema->inverse_join_columns;
        $inv_tables = $comp_class_schema->table_names_for_columns($inv_join_cols);
        foreach ($inv_join_cols as $idx => $inv_column) {
            $inv_table = $inv_tables[$idx];
         /*   if ($inv_column !== $this->_comp_schema->inverse_join_columns[$idx]
                    || $comp_class_schema->primary_key_strategy() == cy\JORK::ASSIGN) {*/
                $fk = new schema\ForeignKey;
                $fk->local_table = schema\Table::get_by_name($inv_table);
                if ( ! isset($this->_table_pool[$inv_table])) {
                    $this->_table_pool[$inv_table] = $fk->local_table;
                }
                $fk->local_columns = array($fk->local_table->get_column($inv_column));

                $fk->foreign_table = schema\Table::get_by_name($local_tables[$idx]);
                if ( ! isset($this->_table_pool[$local_tables[$idx]])) {
                    $this->_table_pool[$local_tables[$idx]] = $fk->foreign_table;
                }
                $fk->foreign_columns = array($fk->foreign_table->get_column($local_column));
                $this->_table_pool[$inv_table]->add_foreign_key($fk);
            }
        //}
    }

}