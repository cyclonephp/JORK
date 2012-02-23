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
        $local_column = $this->_comp_schema->join_column;
        $local_table = $this->_model_schema->table_name_for_column($local_column);
        $fk = new schema\ForeignKey;
        $fk->local_table = schema\Table::get_by_name($local_table);
        $fk->local_columns = array($fk->local_table->get_column($local_column));

        $comp_class_schema = $this->_schema_pool[$this->_comp_schema->class];
        $foreign_column = $this->_comp_schema->inverse_join_column;
        $foreign_table = $comp_class_schema->table_name_for_column($foreign_column);
        $fk->foreign_table = schema\Table::get_by_name($foreign_table);
        $fk->foreign_columns = array($fk->foreign_table->get_column($foreign_column));
        $this->_table_pool[$local_table]->add_foreign_key($fk);
    }

}