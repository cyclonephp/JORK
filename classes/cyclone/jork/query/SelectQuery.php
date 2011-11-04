<?php

namespace cyclone\jork\query;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class SelectQuery {

    /**
     * holds the select list of the query. 
     *
     * @var array
     */
    public $select_list;

    /**
     * @var array
     */
    public $from_list;

    /**
     * @var array
     */
    public $with_list;

    /**
     * @var array
     */
    public $join_list;

    /**
     * @var array
     */
    public $where_conditions = array();

    /**
     * @var array
     */
    public $order_by;

    /**
     * @var array
     */
    public $group_by;

    /**
     * @var int
     */
    public $offset;

    /**
     * @var int
     */
    public $limit;

    public function  __construct() {
        $this->join_list = new \ArrayObject;
        $this->with_list = new \ArrayObject;
    }

    /**
     * builder method for the select clause of the query
     *
     * @var string select item
     * @var ...
     * @return JORK_Query_Select
     */
    public function select() {
        $args = func_get_args();
        return $this->select_array($args);
    }

    /**
     * Builder method for the select clause of the query
     *
     * @var array<string> args
     * @return JORK_Query_Select
     *
     * @see JORK::select()
     */
    public function select_array($args) {
        static $pattern = '/^(?<prop_chain>[a-zA-Z_\.]+)(\{(?<projection>[a-zA-Z_,]+)\})?( +(?<alias>[a-zA-Z_]+))?$/';
        foreach ($args as $arg) {
            if ($arg instanceof db\Expression) {
                $this->select_list []= array(
                    'expr' => $arg->str
                );
                continue;
            }
            preg_match($pattern, $arg, $matches);
            if (empty($matches))
                throw new jork\SyntaxException('invalid select list item: '.$arg);
            $select_item = array(
                'prop_chain' => PropChain::from_string($matches['prop_chain']),
            );
            if (isset($matches['projection']) && $matches['projection'] != '') {
                $select_item['projection'] = explode(',', $matches['projection']);
            }
            if (isset($matches['alias'])) {
                $select_item['alias'] = $matches['alias'];
            }
            $this->select_list []= $select_item;
        }
        return $this;
    }

    /**
     * Builder method for the from clause if the query.
     *
     * @param string from list item
     * @param ...
     * @return JORK_Query_Select
     */
    public function from() {
        $args = func_get_args();
        return $this->from_array($args);
    }

    /**
     *
     * @param array<string> $args
     * @return JORK_Query_Select
     */
    public function from_array($args) {
        foreach ($args as $arg) {
            preg_match('/^(?<class>[a-zA-Z_0-9\\\\]+)( +(?<alias>[a-zA-Z_0-9]+))?$/', $arg, $matches);
            if (empty($matches))
                throw new jork\SyntaxException ('invalid from list item: '.$arg);
            $item = array(
                'class' => $matches['class']
            );
            if (isset($matches['alias'])) {
                $item['alias'] = $matches['alias'];
            }
            $this->from_list []= $item;
        }
        return $this;
    }

    /**
     *
     * @return cyclone\jork\query\SelectQuery
     */
    public function with() {
        foreach (func_get_args() as $arg) {
            if ($arg instanceof SelectQuery) {
                $this->with_list []= $arg;
                continue;
            }
            preg_match('/^(?<prop_chain>[a-zA-Z_0-9.]+)( +(?<alias>[a-zA-Z_0-9]+))?$/', $arg, $matches);
            if (empty($matches))
                throw new jork\SyntaxException ('invalid with list item: '.$arg);
            $item = array(
                'prop_chain' => PropChain::from_string($matches['prop_chain'])
            );
            if (isset($matches['alias'])) {
                $item['alias'] = $matches['alias'];
            }
            $this->with_list []= $item;
        }
        return $this;
    }

    /**
     * @param string $entity_class_def
     * @param string $type
     * @return JORK_Query_Select
     */
    public function join($entity_class_def, $type = 'INNER') {
        preg_match('/^(?<class>[a-zA-Z_0-9.]+)( +(?<alias>[a-zA-Z_0-9]+))?$/', $entity_class_def, $matches);
        if (empty($matches))
            throw new jork\SyntaxException('invalid from list item: '.$entity_class_def);
        
        $item = array(
            'type' => $type,
            'class' => $matches['class']
        );
        if (array_key_exists('alias', $matches)) {
            $item['alias'] = $matches['alias'];
        }
        $this->join_list []= $item;
        $this->_last_join = &$this->join_list[count($this->join_list) - 1];
        return $this;
    }

    /**
     * @param string $entity_class_def
     * @return JORK_Query_Select
     */
    public function left_join($entity_class_def) {
        $this->join($entity_class_def, 'LEFT');
        return $this;
    }

    /**
     * @return JORK_Query_Select
     */
    public function on() {
        $this->_last_join['condition'] = func_get_args();
        return $this;
    }

    /**
     * @return JORK_Query_Select 
     */
    public function where() {
        $args = func_get_args();
        $this->where_conditions []= cy\DB::create_expr($args);
        return $this;
    }

    /**
     * @return JORK_Query_Select 
     */
    public function group_by() {
        $this->group_by = func_get_args();
        return $this;
    }

    /**
     * @param string $column
     * @param string $direction
     * @return JORK_Query_Select
     */
    public function order_by($column, $direction = 'ASC') {
        $this->order_by []= array(
            'column' => $column,
            'direction' => $direction
        );
        return $this;
    }

    /**
     * @param int $offset
     * @return JORK_Query_Select
     */
    public function offset($offset) {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * @param int $limit
     * @return JORK_Query_Select
     */
    public function limit($limit) {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * @param string $database
     * @return JORK_Result_Iterator 
     */
    public function exec($database = 'default') {
        $mapper = jork\mapper\SelectMapper::for_query($this);
        list($db_query, $mappers) = $mapper->map();
        
        if (cy\Config::inst()->get('jork.show_sql')) {
            echo $db_query->compile($database).PHP_EOL;
        }
        $sql = cy\DB::compiler($database)->compile_select($db_query);
        $db_result = cy\DB::executor($database)->exec_select($sql);
        
        $result_mapper = jork\mapper\result\AbstractResult::for_query($this, $db_result
                , $mapper->has_implicit_root, $mappers);
        //var_dump($result_mapper->map());
        return new jork\result\Iterator($result_mapper->map());
    }

    public function compile($database = 'default') {
        $mapper = jork\mapper\Select::for_query($this);
        list($db_query, $mappers) = $mapper->map();

        if (cy\Config::inst()->get('jork.show_sql')) {
            echo $db_query->compile($database).PHP_EOL;
        }
        return cy\DB::compiler($database)->compile_select($db_query);
    }

    
}
