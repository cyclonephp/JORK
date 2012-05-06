<?php
namespace cyclone\jork\schema\foreignkey;

use cyclone as cy;
use cyclone\db\schema;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ManyToOneFKBuilder extends ForeignKeyBuilder {

    public function create_foreign_key() {
        $comp_class_schema = $this->_schema_pool[$this->_comp_schema->class];
        $fks = array();
        $foreign_columns = $this->_comp_schema->inverse_join_columns;
        $foreign_tables = $comp_class_schema->table_names_for_columns($foreign_columns);
        foreach ($this->_comp_schema->join_columns as $idx => $local_column) {
            $local_table = $this->_model_schema->table_name_for_column($local_column);
            if ( ! isset($fks[$local_table])) {
                $fk = new schema\ForeignKey;
                $fk->local_table = schema\Table::get_by_name($local_table);
                $fks[$local_table] = $fk;
            }
            $fk = $fks[$local_table];
            $fk->local_columns []= $fk->local_table->get_column($local_column);

            $foreign_column = $foreign_columns[$idx];
            $foreign_table = $foreign_tables[$idx];
            $fk->foreign_table = schema\Table::get_by_name($foreign_table);
            $fk->foreign_columns []= $fk->foreign_table->get_column($foreign_column);
        }
        foreach ($fks as $tbl_name => $fk) {
            $this->_table_pool[$tbl_name]->add_foreign_key($fk);
        }
    }

}