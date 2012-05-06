<?php

namespace cyclone\jork\mapper\select;

use cyclone as cy;
use cyclone\jork;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ImplRoot extends jork\mapper\SelectMapper {

    protected final function  __construct(jork\query\SelectQuery $jork_query) {
        parent::__construct($jork_query);
        $this->has_implicit_root = TRUE;
        $impl_root_class = $jork_query->from_list[0]['class'];
        $this->_implicit_root = jork\model\AbstractModel::schema_by_class($impl_root_class);
        $this->_naming_srv->set_implicit_root($impl_root_class);
    }

    protected function map_from() {
        $this->_mappers[NULL] = $this->create_entity_mapper(NULL);
    }

    protected function map_with() {
        foreach ($this->_jork_query->with_list as $with_item) {
            if (array_key_exists('alias', $with_item)) {
                $this->_naming_srv->set_alias($with_item['prop_chain'], $with_item['alias']);
            }
            $this->_mappers[NULL]->merge_prop_chain($with_item['prop_chain']->as_array()
                    , jork\mapper\EntityMapper::SELECT_ALL);
        }
    }

    
    protected function map_db_expression($expr) {
        $pattern = '/\{([^\}]*)\}/';
        preg_match_all($pattern, $expr, $matches);
        $resolved_expr_all = $expr;
        foreach ($matches[0] as $idx => $match) {
            $prop_chain = jork\query\PropChain::from_string($matches[1][$idx]);
            $prop_chain_arr = $prop_chain->as_array();
            $resolved_expr = $this->_mappers[NULL]->resolve_prop_chain($prop_chain_arr);
            $resolved_expr_all = str_replace($match, $resolved_expr, $resolved_expr_all);
        }
        return $resolved_expr_all;
    }

    protected function map_select() {
        if (empty($this->_jork_query->select_list)) {
            $this->_mappers[NULL]->select_all_atomics();
            return;
        }
        foreach ($this->_jork_query->select_list as &$select_item) {
            if (array_key_exists('expr', $select_item)) { //database expression
                $resolved = $this->map_db_expression($select_item['expr']);

                $expr_mapper = new jork\mapper\ExpressionMapper($resolved);
                $select_item['alias'] = $expr_mapper->col_name;
                $this->_mappers[$expr_mapper->col_name] = $expr_mapper;
                $this->_db_query->columns []= new db\CustomExpression($resolved);
                continue;
            }
            $prop_chain = $select_item['prop_chain']->as_array();
            $this->_mappers[NULL]->merge_prop_chain($prop_chain, jork\mapper\EntityMapper::SELECT_LAST);
            if (array_key_exists('projection', $select_item)) {
                $this->add_projections($select_item['prop_chain'], $select_item['projection']);
            }
        }
    }

    protected function add_projections(jork\query\PropChain $prop_chain, $projections) {
        list($mapper,, ) = $this->_mappers[NULL]->resolve_prop_chain($prop_chain->as_array());
        foreach ($projections as $proj) {
            $mapper->merge_prop_chain(explode('.', $proj), jork\mapper\EntityMapper::SELECT_ALL);
        }
    }

    protected function resolve_db_expr(db\Expression $expr) {
        if ($expr instanceof db\BinaryExpression) {
            if ($expr->left_operand instanceof db\Expression) {
                $expr->left_operand = $this->resolve_db_expr($expr->left_operand);
            } elseif (is_string($expr->left_operand)) {
                $expr->left_operand = $this->_mappers[NULL]
                                ->resolve_prop_chain(explode('.', $expr->left_operand));
            }

            if ($expr->right_operand instanceof db\Expression) {
                $expr->right_operand = $this->resolve_db_expr($expr->right_operand);
            } elseif (is_string($expr->right_operand)) {
                $expr->right_operand = $this->_mappers[NULL]
                                ->resolve_prop_chain(explode('.', $expr->right_operand));
            }
            if (is_array($expr->left_operand) || is_array($expr->right_operand)) {
                $this->obj2condition($expr);
            }
        } elseif ($expr instanceof db\UnaryExpression) {
            $expr->operand = $this->_mappers[NULL]->resolve_prop_chain(explode('.', $expr->operand));
        } elseif ($expr instanceof db\CustomExpression) {
            $expr->str = $this->map_db_expression($expr->str);
        }
        return $expr;
    }

    private function add_natural_ordering() {
        foreach ($this->_implicit_root->natural_ordering as $ordering) {
            $property = $this->_implicit_root->primitives[$ordering->property];
            if ( ! is_null($property->column)) {
                $column = $property->column;
            } else {
                $column = $ordering->property;
            }
            $table_name = NULL === $property->table 
                    ? $this->_implicit_root->table
                    : $property->table;
            $table_name = $this->_naming_srv->table_alias(NULL, $table_name);
            $this->_db_query->order_by($table_name . '.' . $column, $ordering->direction);
        }
    }

    protected function map_order_by() {
        if ($this->_jork_query->order_by === NULL) {
            $this->add_natural_ordering();
            return;
        }

        foreach ($this->_jork_query->order_by as $ord) {
            if ($ord['column'] instanceof db\CustomExpression) {
                $col = $ord['column'];
                $col->str = $this->map_db_expression($col->str);
            } else {
                $col = $this->_mappers[NULL]->resolve_prop_chain(explode('.', $ord['column']));
                if (is_array($col))
                    throw new jork\Exception($ord['column'] . ' is not an atomic property');
            }
            $this->_db_query->order_by [] = array(
                'column' => $col,
                'direction' => $ord['direction']
            );
        }
    }

    protected function map_group_by() {
        if (NULL === $this->_jork_query->group_by)
            return;
        foreach ($this->_jork_query->group_by as $group_by_itm) {
            $col = $this->_mappers[NULL]->resolve_prop_chain($group_by_itm);
            if (is_array($col))
                throw new jork\Exception($group_by_itm.' is not an atomic property');
            $this->_db_query->group_by []= $col;
        }
    }

    /**
     * {@inheritdoc }
     */
    protected function  has_to_many_child() {
        return $this->_mappers[NULL]->has_to_many_child();
    }

    /**
     * {@inheritdoc }
     */
    protected function  build_offset_limit_subquery(db\query\Select $subquery) {
        $ent_schema = $this->_mappers[NULL]->_entity_schema;

        $existing_alias = $this->_naming_srv->table_alias(NULL, $ent_schema->table);

        foreach ($ent_schema->primary_keys() as $primary_key) {
            $subquery->columns = array($primary_key);
            $subquery->tables = array(
                array($ent_schema->table
                , $this->_naming_srv->table_alias(NULL, $ent_schema->table, TRUE))
            );
        }

        $this->filter_unneeded_subquery_joins($subquery);
        $subquery_alias = $this->_naming_srv->offset_limit_subquery_alias();
        return array(
                'table' => array($subquery
                    , $subquery_alias),
                'type' => 'RIGHT',
                'conditions' => array(
                    new db\BinaryExpression($existing_alias.'.'.$primary_key
                            , '=', $subquery_alias . '.' . $primary_key)
                )
            );
    }
    
}
