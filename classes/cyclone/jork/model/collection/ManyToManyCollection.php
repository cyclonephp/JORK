<?php

namespace cyclone\jork\model\collection;

use cyclone as cy;
use cyclone\jork;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ManyToManyCollection extends AbstractCollection {

    public function delete_by_pk($pk) {
        if ( ! is_array($pk)) {
            $pk = array($pk);
        }
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
        if (count($this->_deleted) > 0) {
            $del_stmt = new db\query\Delete;
            $del_stmt->table = $this->_comp_schema->join_table->name;
            $del_stmt->conditions = array();

            if (count($pk) == 1) {
                $del_keys = array();
                foreach ($this->_deleted as $dummy) {
                    $key = $this->_deleted->key();
                    $del_keys []= $key[0];
                }
                $del_stmt->conditions = array(
                    new db\BinaryExpression($this->_comp_schema->join_table->join_columns[0]
                        , '=', DB::esc($pk[0])),
                    new db\BinaryExpression($this->_comp_schema->join_table->inverse_join_columns[0]
                        , 'IN', new db\SetExpression($del_keys))
                );
            } else
                throw new \cyclone\jork\Exception("many-to-many collection doesn't support deletion of composite key-mapped relations");

            $del_stmt->exec($db_conn);
        }
        if (count($this->_storage) > 0) {
            $ins_stmt = new db\query\Insert;
            if (count($pk) == 1) {
                $ins_stmt->table = $this->_comp_schema->join_table->name;
                $ins_stmt->values = array();
                $local_join_col = $this->_comp_schema->join_table->join_columns[0];
                $inverse_join_col = $this->_comp_schema->join_table->inverse_join_columns[0];
                foreach ($this->_storage as $itm) {
                    $item_pk = $itm['value']->pk();
                    if (FALSE == $itm['persistent']) {
                        $itm['value']->save();
                        $ins_stmt->values []= array(
                            $local_join_col => $pk[0],
                            $inverse_join_col => $item_pk[0]
                        );
                    }
                }
            } else
                throw new \cyclone\jork\Exception("many-to-many collection doesn't support deletion of composite key-mapped relations");

            $ins_stmt->exec($db_conn);
        }
        $this->_persistent = TRUE;
    }
   
    
}
