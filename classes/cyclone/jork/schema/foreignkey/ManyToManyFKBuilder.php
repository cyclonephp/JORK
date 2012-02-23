<?php
namespace cyclone\jork\schema\foreignkey;

use cyclone as cy;
use cyclone\db\schema;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ManyToManyFKBuilder extends ForeignKeyBuilder {

    public function create_foreign_key() {
        $local_column = $this->_comp_schema->join_column;
        $local_table = schema\Table::get_by_name($this
                ->_model_schema->table_name_for_column($local_column));

        $join_table = schema\Table::get_by_name($this->_comp_schema->join_table->name);
        if ( ! isset($this->_table_pool[$join_table->name])) {
            $this->_table_pool[$join_table->name] = $join_table;
        }
        // if the local join column is not the primary key then we add a foreign
        // key to the join table. Otherwise (if the local join column is the
        // local PK then this condition will be false since the above
        // table_name_for_column() call has already set $local_column to the
        // name of the primary key column
        if ($local_column == $this->_comp_schema->join_column) {
            $fk = new schema\ForeignKey;
            $fk->local_table = $local_table;
            $fk->local_columns = array($local_table->get_column($local_column));
            $fk->foreign_table = $join_table;
            $fk->foreign_columns = array($join_table->get_column($this->_comp_schema
                        ->join_table->join_column));
            $local_table->add_foreign_key($fk);
        }
        
        $fk = new schema\ForeignKey;
        $fk->local_table = $join_table;
        $fk->local_columns = array($join_table->get_by_name($this->_comp_schema
                ->join_table->join_column));

        $fk->foreign_table = $local_table;
        $fk->foreign_columns = array($local_table->get_column($local_column));
        $join_table->add_foreign_key($fk);

        $comp_class_schema = $this->_schema_pool[$this->_comp_schema->class];
        $inv_join_col = $this->_comp_schema->inverse_join_column;
        $inv_table = $comp_class_schema->table_name_for_column($inv_join_col);
        $inv_table = schema\Table::get_by_name($inv_table);

        $fk = new schema\ForeignKey;
        $fk->local_table = $join_table;
        $fk->local_columns = array($join_table->get_column($this->_comp_schema
                ->join_table->inverse_join_column));
        $fk->foreign_table = $inv_table;
        $fk->foreign_columns = array($inv_table->get_column($inv_join_col));
        $join_table->add_foreign_key($fk);

        // same as above, just on the other side
        if ($inv_join_col === $this->_comp_schema->inverse_join_column) {
            $fk = new schema\ForeignKey;
            $fk->local_table = $inv_table;
            $fk->local_columns = array($inv_table->get_column($inv_join_col));
            $fk->foreign_table = $join_table;
            $fk->foreign_columns = array($join_table->get_column($this->_comp_schema
                        ->join_table->inverse_join_column));
            $inv_table->add_foreign_key($fk);
        }
    }

}
