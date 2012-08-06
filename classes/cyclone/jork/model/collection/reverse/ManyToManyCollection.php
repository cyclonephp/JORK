<?php

namespace cyclone\jork\model\collection\reverse;

use cyclone as cy;
use cyclone\jork;
use cyclone\jork\schema\SchemaPool;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ManyToManyCollection extends jork\model\collection\AbstractCollection {

    public function delete_by_pk($pk) {
        if ( ! is_array($pk)) {
            $pk = array($pk);
        }
        $this->_deleted[$pk] = $this->_storage[$pk];
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
        
        $comp_schema = SchemaPool::inst()->get_schema($this->_comp_schema->class)
            ->get_property_schema($this->_comp_schema->mapped_by);
        $pk = $this->_owner->pk();
        $db_conn = $this->_owner->schema()->db_conn;
        if ( count($this->_deleted) > 0) {
            $del_stmt = new db\query\Delete;
            $del_stmt->table = $comp_schema->join_table->name;
            if (count($pk) == 1) {
                $del_keys = array();
                foreach ($this->_deleted as $dummy) {
                    $key = $this->_deleted->key();
                    $del_keys []= $key[0];
                }
                $del_stmt->conditions = array(
                    new db\BinaryExpression($comp_schema->join_table->inverse_join_columns[0]
                        , '=', cy\DB::esc($pk[0])),
                    new db\BinaryExpression($comp_schema->join_table->join_columns[0]
                        , 'IN', new db\SetExpression($del_keys))
                );
            } else
                throw new \cyclone\jork\Exception("many-to-many collection doesn't support deletion of composite key-mapped relations");
            $del_stmt->exec($db_conn);
        }
        if (count($this->_storage) > 0) {
            $ins_stmt = new db\query\Insert;
            $ins_stmt->table = $comp_schema->join_table->name;
            $ins_stmt->values = array();
            $local_join_col = $comp_schema->join_table->inverse_join_columns[0];
            $inverse_join_col = $comp_schema->join_table->join_columns[0];
            foreach ($this->_storage as $itm) {
                $itm_pk = $this->_storage->key();
                if (FALSE == $itm['persistent']) {
                    $itm['value']->save();
                    $ins_stmt->values []= array(
                        $local_join_col => $pk[0],
                        $inverse_join_col => $itm_pk[0]
                    );
                }
            }
            $ins_stmt->exec($db_conn);
        }
        $this->_persistent = TRUE;
    }
    
}
