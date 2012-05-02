<?php

namespace cyclone\jork\model\collection;

use cyclone as cy;
use cyclone\jork;
use cyclone\db;

/**
 * Represents a collection of models which are components of an other model,
 * in other words it is used for storing to-many relationships between objects
 * at runtime.
 * 
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
abstract class AbstractCollection implements \ArrayAccess, \Iterator, \Countable {

    public static function for_component($owner, $comp_name) {
        $comp_schema = $owner->schema()->components[$comp_name];
        if (isset($comp_schema->mapped_by)) {
            $remote_comp_schema = jork\model\AbstractModel::schema_by_class($comp_schema->class)
                ->components[$comp_schema->mapped_by];
            if (cy\JORK::MANY_TO_ONE == $remote_comp_schema->type)
                return new reverse\ManyToOneCollection($owner
                        , $comp_name, $comp_schema);
            elseif (cy\JORK::MANY_TO_MANY == $remote_comp_schema->type)
                return new reverse\ManyToManyCollection($owner
                        , $comp_name, $comp_schema);
        } else {
            if (cy\JORK::ONE_TO_MANY == $comp_schema->type) {
                return new OneToManyCollection($owner, $comp_name
                        , $comp_schema);
            } elseif (cy\JORK::MANY_TO_MANY == $comp_schema->type) {
                return new ManyToManyCollection($owner, $comp_name
                        , $comp_schema);
            }
        }
        throw new jork\Exception("internal error: failed to initialize collection for component '$comp_name'");
    }

    /**
     * The owner of the components, the left side of the to-many relationship.
     *
     * @var cyclone\jork\model\AbstractModel
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
     * @var array
     */
    protected $_join_columns;

    /**
     * Used by subclasses.
     *
     * @var array
     */
    protected $_inverse_join_columns;

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
     * @var \cyclone\jork\InstancePool
     */
    protected $_storage;

    /**
     * Stores the entities deleted from the collection, and performs the
     * required database operations when persisting.
     *
     * @var \cyclone\jork\InstancePool
     */
    protected $_deleted;

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

    /**
     * Initialized by @c sort()
     *
     * @var ComparatorProvider
     */
    private $_cmp_provider;

    protected $_invalid_key_items = array();

    public function  __construct($owner, $comp_name, $comp_schema) {
        $this->_owner = $owner;
        $this->_comp_name = $comp_name;
        $this->_comp_schema = $comp_schema;
        $this->_comp_class = $comp_schema->class;
        $this->_owner->add_pk_change_listener($this);
        $this->_storage = jork\InstancePool::for_class($comp_schema->class);
        $this->_deleted = jork\InstancePool::for_class($comp_schema->class);
    }

    /**
     * Called when the parent component is inserted and it's primary key has
     * been generated.
     *
     * Implementations call the save() method.
     *
     * @param mixed $owner_pk the new primary key of the owner of the collection.
     * @see cyclone\\jork\\model\\AbstractModel::insert();
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
     * The method throws cyclone\jork\Exception if the 'on_delete' key exists in the
     * component definition but it's value is neither JORK::SET_NULL
     * nor JORK::CASCADE
     *
     * @see  cyclone\\jork\\model\\AbstractModel::delete()
     * @param mixed $owner_pk the primary key of the owner.
     */
    public abstract function notify_owner_deletion(db\ParamExpression $owner_pk);

    public abstract function save();

    public function  append($value) {
        if ( ! ($value instanceof $this->_comp_class))
            throw new jork\Exception ("the items of this collection should be {$this->_comp_class} instances");
        $value->add_pk_change_listener($this);
        $pk = $value->pk();
        if (count($pk) > 1)
            throw new jork\Exception("composite primary key entity handling is not supported");

        $pk = $pk[0];
        $new_itm = new \ArrayObject(array(
            'persistent' => FALSE,
            'value' => $value
        ));
        if (NULL === $pk) {
            $this->_storage []= $new_itm;
        } else {
            if (isset($this->_storage[array($pk)])) {
                $temp = $this->_storage[array($pk)];
                $temp_pk = $temp['value']->pk();
                if ($temp_pk[0] == $pk)
                    throw new jork\Exception($this->_comp_class . '#' . $pk . ' has already been added to the collection');
                $this->_storage[array($pk)] = $new_itm;
                $this->_storage []= $temp;
            } else {
                $this->_storage[array($pk)] = $new_itm;
            }
        }
        $this->_persistent = FALSE;
    }

    public function append_persistent($value) {
        $this->_storage[$value->pk()] = new \ArrayObject(array(
            'persistent' => TRUE,
            'value' => $value
        ));
    }

    protected function update_invalid_storage_keys() {
        foreach ($this->_invalid_key_items as $entity) {
            $new_pk = $entity->pk();
            $old_pk = NULL;
            foreach ($this->_storage as $itm) {
                $pk = $this->_storage->key();
                if ($itm['value']->pk() == $new_pk) {
                    $old_pk = $pk;
                    break;
                }
            }
            if ($old_pk === NULL)
                throw new jork\Exception("failed to update data structure: {$this->_comp_class} #$new_pk not found.");

            if (isset($this->_storage[$new_pk])) {
                $temp = $this->_storage[$new_pk];
                $this->_storage[$new_pk] = $this->_storage[$old_pk];
                $this->_storage []= $temp;
            } else {
                $this->_storage[$new_pk] = $this->_storage[$old_pk];
            }

            unset($this->_storage[$old_pk]);
        }
        $this->_invalid_key_items = array();
    }

    public function  offsetGet($key) {
        if ( ! is_array($key)) {
            $key = array($key);
        }
        if ( ! isset($this->_storage[$key]))
            throw new jork\Exception("undefined index $key in component collection '{$this->_comp_name}'");
        return $this->_storage[$key]['value'];
    }

    /**
     * Only for internal usage. Used when object graph is loaded from the database.
     *
     * @param string $key
     * @param JORK_Model_Abstract $val
     * @see JORK_Model_Abstract::add_to_component_collections()
     * @package
     */
    public function  offsetSet($key, $val) {
        $this->_storage[$key] = new \ArrayObject(array(
            'persistent' => TRUE,
            'value' => $val
        ));
        $this->_persistent = FALSE;
    }

    public function  offsetExists($key) {
        return isset($this->_storage[array($key)]);
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

    public function rewind() {
        $this->_storage->rewind();
    }

    public function valid() {
        return $this->_storage->valid();
    }

    public function next() {
        $this->_storage->next();
    }

    public function current() {
        $current = $this->_storage->current();
        return $current['value'];
    }

    public function key() {
        $key = $this->_storage->key();
        if (count($key) == 1) {
            return $key[0];
        }
        return $key;
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

    public function as_array() {
        $rval = array();
        foreach ($this as $item) {
            $rval []= NULL === $item ? NULL : $item->as_array();
        }
        return $rval;
    }

    public function jsonSerializable() {
        return $this->as_array();
    }

    private function get_comparator_fn($order, $comparator) {
        
    }

    public function sort($order = cy\JORK::SORT_REGULAR, $comparator = NULL) {
        if (count($this->_join_columns) > 1)
            throw new jork\Exception("composite key sorting is not yet supported");

        if (NULL === $this->_cmp_provider) {
            $model_schema = jork\model\AbstractModel::schema_by_class($this->_comp_class);
            $this->_cmp_provider = ComparatorProvider::for_schema($model_schema);
        }
        $cmp_fn = $this->_cmp_provider->get_comparator($order, $comparator);

        $storage = array();
        foreach ($this->_storage as $value) {
            $key = $this->_storage->key();
            $storage[$key[0]] =$value;
        }

        uasort($storage, $cmp_fn);
        $this->_storage = jork\InstancePool::for_class($this->_comp_class);
        foreach ($storage as $k => $v) {
            $this->_storage[array($k)] = $v;
        }
        return $this;
    }

}
