<?php
namespace cyclone\jork\schema\foreignkey;

use cyclone as cy;
use cyclone\db\schema;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ManyToManyFKBuilder extends ForeignKeyBuilder {

    protected function create_jointbl() {
        $join_table =schema\Table::get_by_name($this->_comp_schema->join_table->name);
        $this->_table_pool[$join_table->name] = $join_table;
        $local_join_cols = $this->_comp_schema->join_columns;
        $local_tables = $this->_model_schema->table_names_for_columns($local_join_cols);
        foreach ($this->_comp_schema->join_table->join_columns as $idx => $join_col_name) {
            $prop_schema = $this->_model_schema->primitive_by_col($local_join_cols[$idx]);
            $join_table->get_column($join_col_name)->type = $this->_default_types[$prop_schema->type];
        }

        $inverse_join_cols = $this->_comp_schema->inverse_join_columns;
        $inv_tables = $this->_schema_pool[$this->_comp_schema->class]->table_names_for_columns($inverse_join_cols);
        foreach ($this->_comp_schema->join_table->inverse_join_columns as $idx => $join_col_name) {
            $prop_schema = $this->_schema_pool[$this->_comp_schema->class]->primitive_by_col($inverse_join_cols[$idx]);
            $join_table->get_column($join_col_name)->type = $this->_default_types[$prop_schema->type];
        }
    }

    protected function localtbl_to_jointbl() {
        $fks = array();
        $jointbl_fks = array();
        $foreign_table = schema\Table::get_by_name($this->_comp_schema->join_table->name);
        $this->_table_pool[$foreign_table->name] = $foreign_table;
        $local_columns = $this->_comp_schema->join_columns;
        $local_tables = $this->_model_schema->table_names_for_columns($local_columns);
        foreach ($local_columns as $idx => $join_column) {
            $local_table = schema\Table::get_by_name($local_tables[$idx]);

            if ( ! isset($fks[$local_table->name])) {
                $fk = $fks[$local_table->name] = new schema\ForeignKey;
                $fk->local_table = $local_table;
                $fk->foreign_table = $foreign_table;
            }
            $fk = $fks[$local_table->name];
            $fk->local_columns []= $local_table->get_column($join_column);
            $fk->foreign_columns []= $foreign_table->get_column($this->_comp_schema->join_table->join_columns[$idx]);
            if ( ! isset($jointbl_fks[$local_table->name])) {
                $fk = $jointbl_fks[$local_table->name] = new schema\ForeignKey;
                $fk->local_table = $foreign_table;
                $fk->foreign_table = $local_table;
            }
            $fk = $jointbl_fks[$local_table->name];
            $fk->local_columns []= $foreign_table->get_column($this->_comp_schema->join_table->join_columns[$idx]);
            $fk->foreign_columns []= $local_table->get_column($join_column);

            if ( ! isset($this->_table_pool[$local_table->name])) {
                $this->_table_pool[$local_table->name] = $local_table;
            }
            if ( ! isset($this->_table_pool[$foreign_table->name])) {
                $this->_table_pool[$foreign_table->name] = $foreign_table;
            }
        }

        foreach ($fks as $tbl_name => $fk) {
            //$this->_table_pool[$tbl_name]->add_foreign_key($fk);
        }
        foreach ($jointbl_fks as $fk) {
            $this->_table_pool[$foreign_table->name]->add_foreign_key($fk);
        }
    }

    protected function jointbl_to_foreigntbl() {
        $fks = array();
        $jointbl_fks = array();

        $local_cols = $this->_comp_schema->join_table->inverse_join_columns;
        $local_table = schema\Table::get_by_name($this->_comp_schema->join_table->name);
        $this->_table_pool[$local_table->name] = $local_table;

        $remote_cols = $this->_comp_schema->inverse_join_columns;
        $remote_tables = $this->_schema_pool[$this->_comp_schema->class]->table_names_for_columns($remote_cols);

        foreach ($local_cols as $idx => $local_col) {
            $remote_col = $remote_cols[$idx];
            $remote_table = schema\Table::get_by_name($remote_tables[$idx]);
            if ( ! isset($jointbl_fks[$remote_table->name])) {
                $fk = $jointbl_fks[$remote_table->name] = new schema\ForeignKey;
                $fk->local_table = $local_table;
                $fk->foreign_table = $remote_table;
            }
            $fk = $jointbl_fks[$remote_table->name];
            $fk->local_columns []= $local_table->get_column($local_col);
            $fk->foreign_columns []= $remote_table->get_column($remote_col);
        }
        foreach ($jointbl_fks as $fk) {
            $this->_table_pool[$local_table->name]->add_foreign_key($fk);
        }
    }

    public function create_foreign_key() {
        $this->create_jointbl();
        $this->localtbl_to_jointbl();
        $this->jointbl_to_foreigntbl();
        return;
        $fks = array();
        $join_tbl_fks = array();
        foreach ($this->_comp_schema->join_columns as $local_column) {
            $local_table = schema\Table::get_by_name($this
                ->_model_schema->table_name_for_column($local_column));

            $join_table = schema\Table::get_by_name($this->_comp_schema->join_table->name);
            if (!isset($this->_table_pool[$join_table->name])) {
                $this->_table_pool[$join_table->name] = $join_table;
            }
            // if the local join column is not the primary key then we add a foreign
            // key to the join table. Otherwise (if the local join column is the
            // local PK then this condition will be false since the above
            // table_name_for_column() call has already set $local_column to the
            // name of the primary key column
            if (in_array($local_column, $this->_comp_schema->join_columns)) {
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
            $join_local_col = $join_table->get_column($this->_comp_schema
                ->join_table->join_column);
            $local_prop = $this->_model_schema->primitive_by_col($this->_comp_schema->join_column);
            $join_local_col->type = $this->_default_types[$local_prop->type];
            $fk->local_columns = array($join_local_col);

            $fk->foreign_table = $local_table;
            $fk->foreign_columns = array($local_table->get_column($local_column));
            $join_table->add_foreign_key($fk);

            $comp_class_schema = $this->_schema_pool[$this->_comp_schema->class];
            $inv_join_col = $this->_comp_schema->inverse_join_column;
            $inv_table = $comp_class_schema->table_name_for_column($inv_join_col);
            $inv_table = schema\Table::get_by_name($inv_table);
            if ( ! isset($this->_table_pool[$inv_table->name])) {
                $this->_table_pool[$inv_table->name] = $inv_table;
            }

            $fk = new schema\ForeignKey;
            $fk->local_table = $join_table;
            $join_foreign_col = $join_table->get_column($this->_comp_schema
                ->join_table->inverse_join_column);
            $foreign_join_prop = $comp_class_schema->primitive_by_col($inv_join_col);
            $join_foreign_col->type = $this->_default_types[$foreign_join_prop->type];
            $fk->local_columns = array($join_foreign_col);
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

}
