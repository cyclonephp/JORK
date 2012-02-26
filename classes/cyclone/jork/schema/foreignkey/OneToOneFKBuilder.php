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
        $local_column = $this->_comp_schema->join_column;
        $local_table = $this->_model_schema->table_name_for_column($local_column);
        if ($local_column === $this->_comp_schema->join_column
                || ($local_column !== $this->_comp_schema->join_column
                    && $this->_model_schema->primary_key_strategy() === cy\JORK::ASSIGN)) {
            $fk = new schema\ForeignKey;
            $fk->local_table = schema\Table::get_by_name($local_table);
            if ( ! isset($this->_table_pool[$local_table])) {
                $this->_table_pool[$local_table] = $fk->local_table;
            }
            $fk->local_columns = array($fk->local_table->get_column($local_column));
            $this->_table_pool[$local_table]->add_foreign_key($fk);
        }

        $comp_class_schema = $this->_schema_pool[$this->_comp_schema->class];
        $inv_column = $this->_comp_schema->inverse_join_column;
        $inv_table = $comp_class_schema->table_name_for_column($inv_column);
        if ($inv_column !== $this->_comp_schema->inverse_join_column
                || $comp_class_schema->primary_key_strategy() == cy\JORK::ASSIGN) {
            $fk = new schema\ForeignKey;
            $fk->local_table = schema\Table::get_by_name($inv_table);
            if ( ! isset($this->_table_pool[$inv_table])) {
                $this->_table_pool[$inv_table] = $fk->local_table;
            }
            $fk->local_columns = array($fk->local_table->get_column($inv_column));

            $fk->foreign_table = schema\Table::get_by_name($local_table);
            if ( ! isset($this->_table_pool[$local_table])) {
                $this->_table_pool[$local_table] = $fk->foreign_table;
            }
            $fk->foreign_columns = array($fk->foreign_table->get_column($local_column));
            $this->_table_pool[$inv_table]->add_foreign_key($fk);
        }
    }

}