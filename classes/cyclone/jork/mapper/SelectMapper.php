<?php

namespace cyclone\jork\mapper;

use cyclone\jork;
use cyclone\db;
use cyclone as cy;

/**
 * Maps a jork select to a db select.
 * 
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
abstract class SelectMapper {

    /**
     * The JORK query to be mapped. This query instance will be passed to the
     * created JORK_Mapper_Entity instances.
     *
     * @var cyclone\jork\query\Select
     */
    protected $_jork_query;

    /**
     * The DB query to be populated by the mapper methods. This query instance
     * will be passed to the created @c jork\mapper\Entity instances.
     *
     * @var cyclone\db\query\Select
     */
    protected $_db_query;

    /**
     * @var array<jork\apper\Result>
     */
    protected $_mappers;

    /**
     * @var jork\NamingService
     */
    protected $_naming_srv;

    /**
     * @var boolean
     */
    public $has_implicit_root;

    /**
     * @var jork\schema\ModelSchema
     */
    protected $_implicit_root;

    protected function  __construct(jork\query\SelectQuery $jork_query) {
        $this->_jork_query = $jork_query;
        $this->_db_query = new db\query\Select;
        $this->_naming_srv = new jork\NamingService;
    }

    public static function for_query(jork\query\SelectQuery $jork_query) {
        if (count($jork_query->from_list) == 1
                &&  ! array_key_exists('alias', $jork_query->from_list[0])) {
            return new select\ImplRoot($jork_query);
        } else {
            return new select\ExplRoot($jork_query);
        }
    }

    public function map() {

        $this->map_from();

        $this->map_join();

        $this->map_with();

        $this->map_select();

        $this->map_where();

        $this->map_group_by();

        $this->map_order_by();

        $this->map_offset_limit();

        return array($this->_db_query, $this->_mappers);
    }

    protected function create_entity_mapper($select_item) {
        return new EntityMapper($this->_naming_srv
                , $this->_jork_query
                , $this->_db_query
                , $select_item);
    }

    protected abstract function map_from();

    protected function map_join() {
        
    }

    protected abstract function map_with();

    /**
     * Resolves a custom database expression passed as string.
     *
     * Picks property chains it founds in enclosing brackets, resolves the
     * property chains to table names. If the last item is a primitive property
     * then it puts the coresponding table column to the resolved expression,
     * otherwise throws an exception
     *
     * @param <type> $expr
     * @return string
     */
    protected abstract function map_db_expression($expr);

    /**
     * Maps the SELECT clause of the jork query to the db query.
     *
     * @see JORK_Mapper_Select::$_jork_query
     * @see JORK_Mapper_Select::$_db_query
     * @return void
     */
    protected abstract function map_select();

    /**
     * Merges the property projections of a select item using the already created
     * mappers.
     *
     * @param JORK_Query_PropChain $prop_chain
     * @param <type> $projections
     * @usedby JORK_Mapper_Select::map_select()
     */
    protected abstract function add_projections(jork\query\PropChain $prop_chain, $projections);


    /**
     * Resolves any kind of database expressions, takes operands as property
     * chains, replaces them with the corresponding table aliases and column names
     * and merges the property chains.
     *
     * @param cyclone\db\Expression $expr
     * @return cyclone\DB\Expression
     */
    protected abstract function resolve_db_expr(db\Expression $expr);
    

    /**
     * Maps a binary operator expression to a DB expression. Both operands
     * should be the same class. The class/property names are replaced
     * with the corresponding primary key columns.
     * 
     * @param DB_Expression_Binary $expr
     * @throws cyclone\jork\Exception if the two operands are not the same class
     */
    protected function obj2condition(db\BinaryExpression $expr) {
        $left_is_array = is_array($expr->left_operand);
        $right_is_array = is_array($expr->right_operand);

        $left_is_model = $expr->left_operand instanceof jork\model\AbstractModel;
        $right_is_model = $expr->right_operand instanceof jork\model\AbstractModel;

        if ( ! ($left_is_array || $left_is_model))
            throw new jork\Exception('left operator is neither a valid property chain nor a model object but '.  gettype($expr->left_operand));

        if ( ! ($right_is_array || $right_is_model))
            throw new jork\Exception('right operator is neither a valid property chain nor a model object');

        if ( ! ($expr->operator == '=' || strtolower($expr->operator) == 'in'))
            throw new jork\Exception('only = or IN is possible between objects, operator \''
                    . $expr->operator . '\' is forbidden');

        //holy shit... it's coming -.-
        if ($left_is_array) {
            list($left_mapper, $left_ent_schema, $left_last_prop)
                    = $expr->left_operand;
//                if ($left_ent_schema->components[$left_last_prop]['class']
//                    != $right_ent_schema->components[$right_last_prop]['class'])
//                    throw new cyclone\jork\Exception("unable to check equality of class '"
//                            . $left_ent_schema->components[$left_last_prop]['class'] . "' with class '"
//                            . $right_ent_schema->components[$right_last_prop]['class'] . "'");
            $left_class = $left_ent_schema->components[$left_last_prop]->class;

            $prim_keys = jork\model\AbstractModel::schema_by_class($left_ent_schema->components[$left_last_prop]->class)->primary_keys();
            if (count($prim_keys) == 1) {
                $prim_key = $prim_keys[0];
                $left_prop_chain = array($left_last_prop
                    , $prim_key);
                $left_mapper->merge_prop_chain($left_prop_chain, EntityMapper::SELECT_NONE);
                $expr->left_operand = $left_mapper->resolve_prop_chain($left_prop_chain);
            } else
                throw new jork\Exception("objects with composite primary keys are not yet supported in WHERE clause ("
                    . $left_ent_schema->components[$left_last_prop]->class . " has "
                    . count($prim_keys) . " primary key columns)");

        } elseif ($left_is_model) {
            $left_class = $expr->left_operand->schema()->class;
            $pk = $expr->left_operand->pk();
            if (count($pk) == 1) {
                $expr->left_operand = $pk[0];
            } else
                throw new jork\Exception("objects with composite primary keys are not yet supported in WHERE clause");
        }

        if ($right_is_array) {
            list($right_mapper, $right_ent_schema, $right_last_prop)
                    = $expr->right_operand;
            $right_class = $right_ent_schema->components[$right_last_prop]->class;
            $prim_keys = jork\model\AbstractModel::schema_by_class($right_ent_schema->components[$right_last_prop]->class)->primary_keys();
            if (count($prim_keys) == 1) {
                $prim_key = $prim_keys[0];
                $right_prop_chain = array($right_last_prop
                    , $prim_key);
                $right_mapper->merge_prop_chain($right_prop_chain, EntityMapper::SELECT_NONE);
                $expr->right_operand = $right_mapper->resolve_prop_chain($right_prop_chain);
            } else
                throw new jork\Exception("objects with composite primary keys are not yet supported in WHERE clause");
        } elseif ($right_is_model) {
            $right_class = $expr->right_operand->schema()->class;
            $pk = $expr->right_operand->pk();
            if (count($pk) == 1){
                $expr->right_operand = $pk[0];
            } else
                throw new jork\Exception("objects with composite primary keys are not yet supported in WHERE clause");

        }
        if ($left_class != $right_class)
            throw new jork\Exception("unable to check equality of class '$left_class' with class '$right_class'");
    }

    /**
     * Maps the where clause of the jork query
     *
     * @see JORK_Mapper_Select::$_jork_query
     * @see JORK_Mapper_Select::$_db_query
     * @see JORK_Mapper_Select::resolve_db_expr()
     */
    protected function map_where() {
        foreach ($this->_jork_query->where_conditions as $cond) {
            $this->_db_query->where_conditions []= $this->resolve_db_expr($cond);
        }
    }

    protected abstract function map_group_by();

    protected abstract function map_order_by();


    /**
     * Removes the join conditions from an offset-limit subquery which tables
     * are not needed by any <code>WHERE</code> conditions.
     *
     * These joined tables are not used to filter the number of the rows so
     * they are unnecessarily make the query slower.
     *
     * @param DB_Query_Select $subquery the subquery which join clauses will be filtered
     * @usedby JORK_Mapper_Select::build_offset_limit_subquery()
     */
    protected function filter_unneeded_subquery_joins(db\query\Select $subquery) {
        if (NULL == $subquery->where_conditions) {
            // if there are no WHERE conditions, then no joined tables are needed
            // in the WHERE clause.
            $subquery->joins = NULL;
            return;
        }

        foreach ($subquery->joins as $k => &$join) {
            // join tables are two item arrays where 0. item is the table name
            // and 1. item is the alias
            // the alias name may appear in the where conditions
            $join_tbl_alias = $join['table'][1];
            $needed = FALSE;
            foreach ($subquery->where_conditions as $where) {
                if ($where->contains_table_name($join_tbl_alias)) {
                    $needed = TRUE;
                    break;
                }
            }
            if ( ! $needed) {
                unset($subquery->joins[$k]);
            }
        }
    }
    

    /**
     * Returns <code>TRUE</code> if any of the mappers has at least one to-many mappers,
     * recursively.
     *
     * @return boolean
     * @see EntityMapper::has_to_many_child()
     */
    protected abstract function has_to_many_child();

    /**
     * Creates a SimpleDB join condition that joins a subquery that properly
     * controls the offset-limit clauses.
     *
     * This method is invoked by JORK_Mapper_Select::map_offset_limit() if the
     * JORK query generated at least one to-many component mapper. Otherwise
     * no offset-limit subquery is needed, the offset and limit clauses are
     * simply copied from the JORK query to the DB query.
     *
     * @return array
     * @usedby JORK_Mapper_Select::map_offset_limit()
     * @uses JORK_Mapper_Select::filter_unneeded_subquery_joins()
     */
    protected abstract function build_offset_limit_subquery(db\query\Select $subquery);

    /**
     * Maps the offset and limit clauses of the JORK query to the DB query.
     *
     * If there is no offset and limit clause in the JORK query then returns
     * immediately. If there is at least one to-many component mapper in
     * $this->_mappers then creates an offset-limit subquery. Before calling
     * $this->build_offset_limit_subquery() it clones $this->_db_query and sets
     * its following properties: order_by, distinct, offset and limit. Then it passes
     * the cloned query to build_offset_limit_subquery(). If no offset-limit
     * subquery is needed then simply copies the offset and limit clauses from
     * the JORK query to the DB query.
     *
     * @uses JORK_Mapper_Select::has_to_many_child();
     * @uses JORK_Mapper_Select::build_offset_limit_subquery();
     */
    protected function  map_offset_limit() {
        if (NULL == $this->_jork_query->offset
                && NULL == $this->_jork_query->limit)
            // no offset & limit in the jork query, nothing to do here
            return;

        if ($this->has_to_many_child()) {
            $subquery = clone $this->_db_query;

            $subquery->order_by = NULL;
            $subquery->distinct = TRUE;
            $subquery->offset = $this->_jork_query->offset;
            $subquery->limit = $this->_jork_query->limit;
            
            $this->_db_query->joins []= $this->build_offset_limit_subquery($subquery);
        } else { // nothing magic is needed here, the row count in the SQL
            // result will be the same as the record count in the object query results
            $this->_db_query->offset = $this->_jork_query->offset;
            $this->_db_query->limit = $this->_jork_query->limit;
        }
    }

}
