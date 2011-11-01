<?php

namespace cyclone\jork\model\collection;

use cyclone as cy;
use cyclone\jork;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class ManyToManyCollection extends AbstractCollection {

    public function delete_by_pk($pk) {
        $this->_deleted[$pk] = $this->_storage[$pk]['value'];
        unset($this->_storage[$pk]);
        $this->_persistent = FALSE;
    }

    public function notify_pk_creation($owner_pk) {
        //$this->save();
    }

    public function notify_owner_deletion(db\ParamExpression $owner_pk) {

    }

    public function save() {
        if ($this->_persistent)
            // there nothing to save
            return;

        $pk = $this->_owner->pk();
        $db_conn = $this->_owner->schema()->db_conn;
        if ( ! empty($this->_deleted)) {
            $del_stmt = new db\query\Delete;
            $del_stmt->table = $this->_comp_schema->join_table->name;
            $del_stmt->conditions = array(
                new db\BinaryExpression($this->_comp_schema->join_table->join_column
                        , '=', DB::esc($pk)),
                new db\BinaryExpression($this->_comp_schema->join_table->inverse_join_column
                        , 'IN', new db\SetExpression(array_keys($this->_deleted)))
            );
            $del_stmt->exec($db_conn);
        }
        if ( ! empty($this->_storage)) {
            $ins_stmt = new db\query\Insert;
            $ins_stmt->table = $this->_comp_schema->join_table->name;
            $ins_stmt->values = array();
            $local_join_col = $this->_comp_schema->join_table->join_column;
            $inverse_join_col = $this->_comp_schema->join_table->inverse_join_column;
            foreach ($this->_storage as $itm) {
                if (FALSE == $itm['persistent']) {
                    $itm['value']->save();
                    $ins_stmt->values []= array(
                        $local_join_col => $pk,
                        $inverse_join_col => $itm['value']->pk()
                    );
                }
            }
            $ins_stmt->exec($db_conn);
        }
        $this->_persistent = TRUE;
    }
   
    
}
