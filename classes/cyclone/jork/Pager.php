<?php

namespace cyclone\jork;

use cyclone as cy;
use cyclone\db;
köszi hogy megvártátok hogy befejezzük a délutáni dugást, de mostmár jöhetnétek
/**
 * @property int $page
 * @property int $page_size
 * @property int $total_count
 * @property int $page_count
 *
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class Pager {

    /**
     *
     * @var cyclone\db\query\Select
     */
    private $_db_query;

    /**
     *
     * @var cyclone\jork\ResultMapper
     */
    private $_jork_mapper;

    /**
     *
     * @var int
     */
    private $_page;

    /**
     *
     * @var int
     */
    private $_page_size;

    /**
     *
     * @var int
     */
    private $_total_count;

    /**
     *
     * @var int
     */
    private $_page_count;

    public function __construct(query\Select $query, $page, $page_size) {
        $this->_query = $query;
        $this->_page = $page;
        $this->_page_size = $page_size;
        $this->build_db_query($query);
        $this->init_count();
    }

    private function init_count() {
        $count_subquery = clone $this->_db_query;
        $count_subquery->columns = array(new db\CustomExpression('*'));
        $count_subquery->joins = NULL;
        $count_subquery->order_by = NULL;
        $count_subquery->offset = NULL;
        $count_subquery->limit = NULL;
        $count_result = cy\DB::select(array(cy\DB::expr('count(*)'), 'count'))
                ->from(array($count_subquery, 'count_subquery'))->exec()->as_array();
        $this->_total_count = $count_result[0]['count'];
        $this->page_count = ceil($this->_total_count / $this->_page_size);
    }

    private function build_db_query(query\Select $jork_query) {
        $jork_query->offset($this->_page_size * ($this->_page - 1));
        $jork_query->limit($this->_page_size);
        $mapper = mapper\Select::for_query($jork_query);
        list($this->_db_query, $mappers) = $mapper->map();

        try {
            $sql = cy\DB::compiler()->compile_select($this->_db_query);
        } catch (db\Exception $ex) {
            throw new Exception('Failed to compile JORK query', 1, $ex);
        }

        try {
            $db_result = cy\DB::executor()->exec_select($sql);
        } catch (db\Exception $ex) {
            throw new Exception('Failed execute SQL: ' . $sql, $ex->getCode(), $ex);
        }
        
        $this->_jork_mapper = mapper\Result::for_query($jork_query, $db_result, $mapper->has_implicit_root, $mappers);
    }

    /**
     * @return JORK_Result_Iterator
     */
    public function get_results() {
        return new result\Iterator($this->_jork_mapper->map());
    }

    public function __get($name) {
        $readonly_fields = array('page', 'page_size', 'total_count', 'page_count');
        if (in_array($name, $readonly_fields))
            return $this->{'_' . $name};
        return parent::__get($name);
    }

}