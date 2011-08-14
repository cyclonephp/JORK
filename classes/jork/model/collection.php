<?php

/**
 * Represents a collection of models which are components of an other model,
 * in other words it is used for storing to-many relationships between objects
 * at runtime.
 * 
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
abstract class JORK_Model_Collection extends ArrayObject implements IteratorAggregate {

    public static function for_component($owner, $comp_name) {
        $comp_schema = $owner->schema()->components[$comp_name];
        if (array_key_exists('mapped_by', $comp_schema)) {
            $remote_comp_schema = JORK_Model_Abstract::schema_by_class($comp_schema['class'])
                ->components[$comp_schema['mapped_by']];
            if (JORK::MANY_TO_ONE == $remote_comp_schema['type'])
                return new JORK_Model_Collection_Reverse_ManyToOne($owner
                        , $comp_name, $comp_schema);
            elseif (JORK::MANY_TO_MANY == $remote_comp_schema['type'])
                return new JORK_Model_Collection_Reverse_ManyToMany($owner
                        , $comp_name, $comp_schema);
        } else {
            if (JORK::ONE_TO_MANY == $comp_schema['type']) {
                return new JORK_Model_Collection_OneToMany($owner, $comp_name
                        , $comp_schema);
            } elseif (JORK::MANY_TO_MANY == $comp_schema['type']) {
                return new JORK_Model_Collection_ManyToMany($owner, $comp_name
                        , $comp_schema);
            }
        }
        throw new JORK_Exception("internal error: failed to initialize collection for component '$comp_name'");
    }

    /**
     * The owner of the components, the left side of the to-many relationship.
     *
     * @var JORK_Model_Abstract
     */
    protected $_owner;

    /**
     * The name of the component that's value is stored in this collection.
     *
     * @var string
     */
    protected $_comp_name;

    /**
     * The array representing the collection
     *
     * @var array
     */
    protected $_comp_schema;

    /**
     * Used by subclasses.
     *
     * @var string
     */
    protected $_join_column;

    /**
     * Used by subclasses.
     *
     * @var string
     */
    protected $_inverse_join_column;

    /**
     * Used by subclasses.
     *
     * @var string
     */
    protected $_comp_class;

    /**
     * Every item is a two-element array with the following keys:
     * - persistent: TRUE if the current connection-mapping foreign keys have
     * already been saved into the database
     * - value: a model object
     *
     * @var array
     */
    protected $_storage = array();

    /**
     * Stores the entities deleted from the collection, and performs the
     * required database operations when persisting.
     *
     * @var array
     */
    protected $_deleted = array();

    /**
     * Flag to avoid infinite recursions when as_string() is called on
     * bi-directional relations.
     *
     * @var boolean
     */
    private $_as_string_in_progress = FALSE;

    /**
     * FALSE if any items has been added ore removed since the last save() call.
     * Subclasses' save() method should not do anything if it's value is TRUE
     * and should set it to TRUE when the saving process is complete.
     *
     * @var boolean
     */
    protected $_persistent = TRUE;

    public function  __construct($owner, $comp_name, $comp_schema) {
        $this->_owner = $owner;
        $this->_comp_name = $comp_name;
        $this->_comp_schema = $comp_schema;
        $this->_comp_class = $comp_schema['class'];
        $this->_owner->add_pk_change_listener($this);
    }

    /**
     * Called when the parent component is inserted and it's primary key has
     * been generated.
     *
     * Implementations call the save() method.
     *
     * @param mixed $owner_pk the new primary key of the owner of the collection.
     * @see JORK_Model_Abstract::insert();
     */
    public abstract function notify_pk_creation($owner_pk);

    /**
     * Called if delete() is called on the owner of the collection.
     *
     * The $owner_pk parameter is not the same as $this->_owner->pk()
     * in cases when the deletion is called via Model::inst()->delete_by_pk($pk)
     * since in this cases the singleton doesn't hold any state, but this is the
     * owner of the collection. The $owner_pk parameter is already put into an
     * escaped parameter object by JORK_Model_Abstract::delete_by_pk()
     *
     * The method throws JORK_Exception if the 'on_delete' key exists in the
     * component definition but it's value is neither JORK::SET_NULL
     * nor JORK::CASCADE
     *
     * @see JORK_Model_Abstract::delete()
     * @param mixed $owner_pk the primary key of the owner.
     */
    public abstract function notify_owner_deletion(DB_Expression_Param $owner_pk);

    public abstract function save();

    public function  append($value) {
        if ( ! ($value instanceof $this->_comp_class))
            throw new JORK_Exception ("the items of this collection should be {$this->_comp_class} instances");
        $value->add_pk_change_listener($this);
        $pk = $value->pk();
        $new_itm = array(
            'persistent' => FALSE,
            'value' => $value
        );
        if (NULL === $pk) {
            $this->_storage []= $new_itm;
        } else {
            if (isset($this->_storage[$pk])) {
                $temp = $this->_storage[$pk];
                if ($temp['value']->pk() == $pk)
                    throw new JORK_Exception($this->_comp_class . '#' . $pk . ' has already been added to the collection');
                $this->_storage[$pk] = $new_itm;
                $this->_storage []= $temp;
            } else {
                $this->_storage[$pk] = $new_itm;
            }
        }
        $this->_persistent = FALSE;
    }

    public function append_persistent($value) {
        $this->_storage[$value->pk()] = array(
            'persistent' => TRUE,
            'value' => $value
        );
    }

    protected function update_stor_pk($entity) {
        $new_pk = $entity->pk();
        $old_pk = NULL;
        foreach ($this->_storage as $pk => $itm) {
            if ($itm['value']->pk() == $new_pk) {
                $old_pk = $pk;
                break;
            }
        }
        if ($old_pk === NULL) 
            throw new JORK_Exception("failed to update data structure: {$this->_comp_class} #$new_pk not found. Entities in collection: #" . implode(', #', $existing_pks));

        if (isset($this->_storage[$new_pk])) {
            $temp = $this->_storage[$new_pk];
            $this->_storage[$new_pk] = $this->_storage[$old_pk];
            $this->_storage []= $temp;
        } else {
            $this->_storage[$new_pk] = $this->_storage[$old_pk];
        }
        
        unset($this->_storage[$old_pk]);
    }

    public function  offsetGet($key) {
        if ( ! array_key_exists($key, $this->_storage))
            throw new JORK_Exception("undefined index $key in component collection '{$this->_comp_name}'");
        return $this->_storage[$key]['value'];
    }

    /**
     * Only for internal usage. Used when object graph is loöaded from the database.
     *
     * @param string $key
     * @param JORK_Model_Abstract $val
     * @see JORK_Model_Abstract::add_to_component_collections()
     * @package
     */
    public function  offsetSet($key, $val) {
        $this->_storage[$key] = array(
            'persistent' => TRUE,
            'value' => $val
        );
        $this->_persistent = FALSE;
    }

    public function  offsetExists($key) {
        return array_key_exists($key, $this->_storage);
    }

    public function  offsetUnset($key) {
        $this->delete_by_pk($key);
    }

    public abstract function delete_by_pk($key);

    public function delete($value) {
        $this->delete_by_pk($value->pk());
    }

    public function  count() {
        return count($this->_storage);
    }

    public function  getIterator() {
        return new JORK_Model_Iterator($this->_storage);
    }

    public function as_string($tab_cnt = 0) {
        if ($this->_as_string_in_progress)
            return '';

        $this->_as_string_in_progress = TRUE;
        $tabs = '';
        for($i = 0; $i < $tab_cnt; ++$i) {
            $tabs .= "\t";
        }
        $lines = array($tabs . "\033[33;1mCollection <" . $this->_comp_class . ">\033[0m");
        foreach ($this->_storage as $itm) {
            $lines []= $itm['value']->as_string($tab_cnt + 1);
        }
        $this->_as_string_in_progress = FALSE;
        return implode(PHP_EOL, $lines);
    }

    public function  __toString() {
        return $this->as_string();
    }
}
