<?php

namespace cyclone\jork\model\collection\reverse;

use cyclone as cy;
use cyclone\jork;
use cyclone\jork\schema;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ManyToOneCollection extends jork\model\collection\AbstractCollection {

    public function  __construct($owner, $comp_name, $comp_schema) {
        parent::__construct($owner, $comp_name, $comp_schema);
        $remote_comp_schema = jork\model\AbstractModel::schema_by_class($comp_schema->class)
            ->components[$comp_schema->mapped_by];

        $this->_inverse_join_columns = $remote_comp_schema->join_columns;
        $this->_join_columns = empty($remote_comp_schema->inverse_join_columns)
                ? jork\model\AbstractModel::schema_by_class($comp_schema->class)->primary_keys()
                : $remote_comp_schema->inverse_join_columns;
    }

    public function append($value) {
        parent::append($value);
        $inv_join_cols = $this->_inverse_join_columns;
        $remote_schema = schema\SchemaPool::inst()->get_schema($this->_comp_class);
        $local_schema = $this->_owner->schema();
        foreach ($this->_join_columns as $idx => $join_col) {
            $local_prop = $local_schema->primitive_by_col($join_col)->name;
            $remote_prop = $remote_schema->primitive_by_col($this->_inverse_join_columns[$idx])->name;
            $value->{$remote_prop} = $this->_owner->{$local_prop};
        }
    }

    public function delete_by_pk($pk) {
        if ( ! is_array($pk)) {
            $pk = array($pk);
        }
        $this->_deleted[$pk] = $this->_storage[$pk];
        $remote_schema = schema\SchemaPool::inst()->get_schema($this->_comp_class);
        foreach ($this->_inverse_join_columns as $inv_join_col) {
            $inv_join_prop = $remote_schema->primitive_by_col($inv_join_col)->name;
            $this->_deleted[$pk]['value']->$inv_join_prop = NULL;
        }
        unset($this->_storage[$pk]);
        $this->_persistent = FALSE;
    }

    public function notify_pk_creation($entity) {
        if ($entity == $this->_owner) {
            $owner_pk = $entity->pk();
            if (isset($this->_comp_schema->inverse_join_columns[0])
                    && ($this->_owner->schema()->primary_key()
                    != $this->_comp_schema->inverse_join_columns)) {
                //we are not joining on the primary key of the owner
                return;
            }
            $itm_join_col = $this->_inverse_join_columns[0];
            $remote_schema = schema\SchemaPool::inst()->get_schema($this->_comp_class);
            $itm_join_prop = $remote_schema->primitive_by_col($itm_join_col)->name;
            foreach ($this->_storage as $item) {
                $item['persistent'] = FALSE;
                $item['value']->$itm_join_prop = $owner_pk[0];
            }
            return;
        }
        $this->_invalid_key_items []= $entity;
    }

    public function  notify_owner_deletion(db\ParamExpression $owner_pk) {
        if ( ! isset($this->_comp_schema->on_delete))
            return;
        $on_delete = $this->_comp_schema->on_delete;
        if (cy\JORK::SET_NULL === $on_delete) {
            $upd_stmt = new db\query\Update;
            $children_schema = jork\model\AbstractModel
                ::schema_by_class($this->_comp_schema->class);
            $remote_comp_schema = $children_schema
                ->get_property_schema($this->_comp_schema->mapped_by);

            $primitive_name = $remote_comp_schema->join_columns[0];
            $primitive_prop_name = $children_schema->primitive_by_col($primitive_name)->name;
            $col_schema = $children_schema->get_property_schema($primitive_prop_name);
            
            $upd_stmt->table = isset($col_schema->table)
                    ? $col_schema->table
                    : $children_schema->table;

            $col_name = isset($col_schema->column)
                    ? $col_schema->column
                    : $primitive_name;

            $upd_stmt->values = array(
                $col_name => NULL
            );

            $upd_stmt->conditions = array(
                new db\BinaryExpression($col_name, '='
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
            // there nothing to save
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
