<?php

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class JORK_Model_Collection_Reverse_ManyToOne extends JORK_Model_Collection {

    public function  __construct($owner, $comp_name, $comp_schema) {
        parent::__construct($owner, $comp_name, $comp_schema);
        $remote_comp_schema = JORK_Model_Abstract::schema_by_class($comp_schema['class'])
            ->components[$comp_schema['mapped_by']];

        $this->_inverse_join_column = $remote_comp_schema['join_column'];
        $this->_join_column = array_key_exists('inverse_join_column', $remote_comp_schema)
                ? $remote_comp_schema['inverse_join_column']
                : JORK_Model_Abstract::schema_by_class($comp_schema['class'])->primary_key();
    }

    public function append($value) {
        parent::append($value);
        $value->{$this->_inverse_join_column} = $this->_owner->{$this->_join_column};
    }

    public function delete_by_pk($pk) {
        $this->_deleted[$pk] = $this->_storage[$pk];
        $this->_deleted[$pk]['value']->{$this->_inverse_join_column} = NULL;
        unset($this->_storage[$pk]);
        $this->_persistent = FALSE;
    }

    public function notify_pk_creation($entity) {
        if ($entity == $this->_owner) {
            $owner_pk = $entity->pk();
            if (array_key_exists('inverse_join_column', $this->_comp_schema)
                    && ($this->_owner->schema()->primary_key()
                    != $this->_comp_schema['inverse_join_column'])) {
                //we are not joining on the primary key of the owner
                return;
            }
            $itm_join_col = $this->_inverse_join_column;
            foreach ($this->_storage as $item) {
                $item['persistent'] = FALSE;
                $item['value']->$itm_join_col = $owner_pk;
            }
            return;
        }
        $this->update_stor_pk($entity);
    }

    public function  notify_owner_deletion(DB_Expression_Param $owner_pk) {
        if ( ! array_key_exists('on_delete', $this->_comp_schema))
            return;
        $on_delete = $this->_comp_schema['on_delete'];
        if (JORK::SET_NULL === $on_delete) {
            $upd_stmt = new DB_Query_Update;
            $children_schema = JORK_Model_Abstract
                ::schema_by_class($this->_comp_schema['class']);
            $remote_comp_schema = $children_schema
                ->get_property_schema($this->_comp_schema['mapped_by']);

            $atomic_name = $remote_comp_schema['join_column'];

            $col_schema = $children_schema->get_property_schema($atomic_name);
            
            $upd_stmt->table = array_key_exists('table', $col_schema)
                    ? $col_schema['table']
                    : $children_schema->table;

            $col_name = array_key_exists('column', $col_schema)
                    ? $col_schema['column']
                    : $atomic_name;

            $upd_stmt->values = array(
                $col_name => NULL
            );

            $upd_stmt->conditions = array(
                new DB_Expression_Binary($col_name, '='
                        , $owner_pk)
            );
            
            $upd_stmt->exec($this->_owner->schema()->db_conn);
        } elseif (JORK::CASCADE == $on_delete) {
            throw new JORK_Exception('cascade delete is not yet implemented');
        } else
            throw new JORK_Exception('invalid value for on_delete');
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
