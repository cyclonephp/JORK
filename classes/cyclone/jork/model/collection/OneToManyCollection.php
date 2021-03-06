<?php

namespace cyclone\jork\model\collection;

use cyclone as cy;
use cyclone\jork;
use cyclone\jork\schema;
use cyclone\jork\schema\SchemaPool;
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
        $remote_schema = schema\SchemaPool::inst()->get_schema($this->_comp_class);
        $local_schema = $this->_owner->schema();
        foreach ($this->_join_columns as $idx => $join_col) {
            $remote_prop = $remote_schema->primitive_by_col($join_col);
            $local_prop = $local_schema->primitive_by_col($this->_inverse_join_columns[$idx]);
            $value->{$remote_prop->name}
                = $this->_owner->{$local_prop->name};
        }
    }

    public function  delete_by_pk($pk) {
        if ( ! is_array($pk)) {
            $pk = array($pk);
        }
        $this->_deleted[$pk]
            = $this->_storage[$pk]['value'];
        $remote_schema = schema\SchemaPool::inst()->get_schema($this->_comp_class);
        foreach ($this->_join_columns as $join_col) {
            $local_prop_name = $remote_schema->primitive_by_col($join_col)->name;
            $this->_deleted[$pk]->{$local_prop_name} = NULL;
        }
        unset($this->_storage[$pk]);
        $this->_persistent = FALSE;
    }

    public function  notify_pk_creation($entity) {
        if ($entity == $this->_owner) {
            if ( ! empty($this->_comp_schema->inverse_join_columns)
                    && ($this->_owner->schema()->primary_key()
                        != $this->_comp_schema->inverse_join_columns)) {
                //we are not joining on the primary key of the owner
                return;
            }
            $itm_join_col = $this->_join_columns[0];
            $itm_join_prop = schema\SchemaPool::inst()
                ->get_schema($this->_comp_class)
                ->primitive_by_col($itm_join_col)->name;
            $owner_pk = $entity->pk();
            foreach ($this->_storage as $item) {
                $item['persistent'] = FALSE;
                $item['value']->$itm_join_prop = $owner_pk[0];
            }
            return;
        }

        // $entity is a collection item in $this->_storage
        // it's key must be updated
        $this->_invalid_key_items []= $entity;
    }

    public function  notify_owner_deletion(db\ParamExpression $owner_pk) {
        if ( ! isset($this->_comp_schema->on_delete))
            return;
        $on_delete = $this->_comp_schema->on_delete;
        if (cy\JORK::SET_NULL == $on_delete) {
            $upd_stmt = new db\query\Update;
            $children_schema = SchemaPool::inst()->get_schema($this->_comp_class);
            $join_primitive = $this->_comp_schema->join_columns[0];

            $join_primitive_schema = $children_schema->primitive_by_col($join_primitive);

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
        $this->_deleted = jork\InstancePool::for_class($this->_comp_schema->class);
        foreach ($this->_storage as $itm) {
            if (FALSE == $itm['persistent']) {
                $itm['value']->save();
                $itm['persistent'] = TRUE;
            }
        }
        $this->_persistent = TRUE;
        $this->update_invalid_storage_keys();
    }
}
