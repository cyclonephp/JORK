<?php

namespace cyclone\jork;

use cyclone as cy;
use cyclone\db;

/**
 * <p>The instances of this class can be used as a <primary key -> entity> hashmap.
 * Every entity class has (at most) one dedicated instance managed internally
 * by the InstancePool, this can be accessed using the @c inst() method. It is
 * used mainly by @c\cyclone\jork\EntityMapper to try to ensure that one entity
 * has at most one in-memory representation. During SQL query result processing
 * (mapping it to object graph) if the <code>EntityMapper</code> finds a new entity
 * in a given row of an SQL query, it doesn't instantiate the entity class but obtains
 * reference to it using the @c offsetGet() method, which will return the already existing
 * instance with the given primary key or <code>NULL</code> if such instance is not present
 * yet (in the latter case the @c \cyclone\jork\mapper\EntityMapper will create the entity
 * and add it to the pool using @c add() )</p>
 *
 * The secondary aim of instancePool is that it is used as the internal data storage
 * of entity collections ( @ \cyclone\jork\model\collection\AbstractCollection subclasses).
 *
 * @author Bence Erős <crystal@cyclonephp.org>
 * @package JORK
 * @usedby \cyclone\jork\mapper\EntityMapper
 */
class InstancePool implements \ArrayAccess, \Iterator, \Countable {

    private static $_instances = array();

    /**
     * Returns the dedicated @c InstancePool instance for the class.
     * <em>Note: further <code>InstancePool</code> instances can also be
     * created for a class using the public constructor.</em>
     *
     * @param $class string
     * @return mixed
     * @usedby \cyclone\jork\mapper\EntityMapper
     */
    public static function inst($class) {
        if ( ! isset(self::$_instances[$class])) {
            self::$_instances[$class] = InstancePool::for_class($class);
        }
        return self::$_instances[$class];
    }

    public static function for_class($entity_class) {
        $schema = schema\SchemaPool::inst()->get_schema($entity_class);
        if (count($schema->primary_keys()) == 1) {
            return new InstancePool($entity_class);
        }
        return new CompositePKInstancePool($entity_class);
    }

    /**
     * Removes all InstancePool instances obtainable using the @c inst() method.
     * Only used for unit testing.
     *
     */
    public static function clear() {
        self::$_instances = array();
    }

    /**
     * the class which' instances should be stored
     *
     * @var string
     */
    protected $_class;

    protected $_pool;

    private $_iterator = NULL;

    protected function  __construct($class) {
        $this->_class = $class;
        $this->_pool = new \ArrayObject();
    }

    public function append(model\AbstractModel $instance) {
        if ( ! ($instance instanceof $this->_class))
            throw new Exception("unable to add an instance of class '"
                . get_class($instance)
                . " to InstancePool of class '{$this->_class}'");

        $pk = $instance->pk();
        $this->_pool[$pk[0]] = $instance;
    }

    public function count() {
        return count($this->_pool);
    }

    public function valid() {
        return $this->_iterator->valid();
    }

    public function rewind() {
        $this->_iterator = $this->_pool->getIterator();
        $this->_iterator->rewind();
    }

    public function next() {
        $this->_iterator->next();
    }

    public function key() {
        return array($this->_iterator->key());
    }

    public function current() {
        return $this->_iterator->current();
    }

    public function offsetGet($primary_key) {
        return isset($this->_pool[$primary_key[0]] )
            ? $this->_pool[$primary_key[0]]
            : NULL;
    }

    /**
     * Calls @c append() or throws an exception if <code>$key</code> is not the same as
     * the primary key of <code>$value</code>.
     *
     * @param $key array
     * @param $value model\AbstractModel
     * @throws \cyclone\jork\Exception
     * @uses model\AbstractModel::pk()
     */
    public function offsetSet($key, $value) {
        if ($key != $value->pk())
            throw new Exception('$key must be equal to the primary key of $value');
        $this->append($value);
    }

    /**
     * Removes the entity specified by its primary key <code>$pk</code>
     * from the instance pool. If the entity is not found then it
     * throws an exception.
     *
     * @param $primary_key array
     * @throws Exception if the entity is not present in the instance pool.
     */
    public function offsetUnset($primary_key) {
        if (isset($this->_pool[$primary_key[0]])) {
            unset($this->_pool[$primary_key[0]]);
        } else
            throw new Exception("key '{$primary_key[0]}' not found");
    }

    public function offsetExists($primary_key) {
        return isset($this->_pool[$primary_key[0]]);
    }

}
