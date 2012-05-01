<?php

namespace cyclone\jork\model\collection;

use cyclone as cy;
use cyclone\jork;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class OneToManyCollection extends AbstractCollection {

    public function  __construct($owner, $comp_name, $comp_schema) {
        parent::__construct($owner, $comp_name, $comp_schema);
        $this->_join_columns = $comp_schema->join_columns;
        $this->_inverse_join_columns = empty($comp_schema->inverse_join_columns)
                ? $owner->schema()->primary_keys()
                : $comp_schema->inverse_join_columns;
    }

    public function  append($value) {
        parent::append($value);
        var_dump($this->_inverse_join_columns);
        foreach ($this->_join_columns as $idx => $join_col) {
            $value->{$join_col}
                = $this->_owner->{$this->_inverse_join_columns[$idx]};
        }
    }

    public function  delete_by_pk($pk) {
        if ( ! is_array($pk)) {
            $pk = array($pk);
        }
        $this->_deleted[$pk] = $this->_storage[$pk]['value'];
        foreach ($this->_join_columns as $join_col) {
            $this->_deleted[$pk]->{$join_col} = NULL;
        }
        unset($this->_storage[$pk]);
        $this->_persistent = FALSE;
    }

    public function  notify_pk_creation($entity) {
        if ($entity == $this->_owner) {
            if (isset($this->_comp_schema->inverse_join_column)
                    && ($this->_owner->schema()->primary_key()
                        != $this->_comp_schema->inverse_join_column)) {
                //we are not joining on the primary key of the owner
                return;
            }
            $itm_join_col = $this->_join_column;
            $owner_pk = $entity->pk();
            foreach ($this->_storage as $item) {
                $item['persistent'] = FALSE;
                $item['value']->$itm_join_col = $owner_pk;
            }
            return;
        }

        // $entity is a collection item in $this->_storage
        // it's key must be updated
        $this->update_stor_pk($entity);
    }

    public function  notify_owner_deletion(db\ParamExpression $owner_pk) {
        if ( ! isset($this->_comp_schema->on_delete))
            return;
        $on_delete = $this->_comp_schema->on_delete;
        if (cy\JORK::SET_NULL == $on_delete) {
            $upd_stmt = new db\query\Update;
            $children_schema = jork\model\AbstractModel::schema_by_class($this->_comp_class);
            $join_primitive = $this->_comp_schema->join_column;

            $join_primitive_schema = $children_schema->primitives[$join_primitive];

            $join_column = isset($join_primitive_schema->column)
                    ? $join_primitive_schema->column
                    : $join_primitive;

            $upd_stmt->table = isset($join_primitive_schema->table)
                    ? $join_primitive_schema->table
                    : $children_schema->table;

            $upd_stmt->values = array($join_column => NULL);
            
            $upd_stmt->conditions = array(
                new db\BinaryExpression($join_column, '='
                        , $owner_pk)
            );
            $upd_stmt->exec($this->_owner->schema()->db_conn);
        } elseif (cy\JORK::CASCADE == $on_delete) {
            throw new jork\Exception('cascade delete is not yet implemented');
        } else
            throw new jork\Exception('invalid value for on_delete');
    }

    public function save() {
        if ($this->_persistent)
            // there is nothing to save
            return;
        foreach ($this->_deleted as $del_itm) {
            // join column has already been set to NULL
            // in delete_by_pk()
            $del_itm->save();
        }
        $this->_deleted = array();
        foreach ($this->_storage as $itm) {
            if (FALSE == $itm['persistent']) {
                $itm['value']->save();
                $itm['persistent'] = TRUE;
            }
        }
        $this->_persistent = TRUE;
    }
}
