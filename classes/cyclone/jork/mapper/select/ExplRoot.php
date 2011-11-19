<?php

namespace cyclone\jork\mapper\select;

use cyclone as cy;
use cyclone\jork;
use cyclone\db;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class ExplRoot extends jork\mapper\SelectMapper {

    protected function map_from() {
        if (empty($this->_jork_query->select_list)) {
            $this->_jork_query->select_list = array();
            foreach ($this->_jork_query->from_list as $from_item) {
                //fail early
                if ( ! array_key_exists('alias', $from_item))
                    throw new jork\SyntaxException('if the query hasn\'t got an
                            implicit root entity, then all explicit root entities must
                            have an alias name');

                $this->_naming_srv->set_alias($from_item['class'], $from_item['alias']);
                $this->_mappers[$from_item['alias']] =
                    $this->create_entity_mapper($from_item['alias']);

                $this->_jork_query->select_list []= array(
                    'prop_chain' => jork\query\PropChain::from_string($from_item['alias'])
                );
            }
            return;
        }

        foreach ($this->_jork_query->from_list as $from_item) {
            //fail early
            if ( ! array_key_exists('alias', $from_item))
                throw new jork\SyntaxException('if the query hasn\'t got an
                            implicit root entity, then all explicit root entities must
                            have an alias name');

            $this->_naming_srv->set_alias($from_item['class'], $from_item['alias']);
            $this->_mappers[$from_item['alias']] =
                    $this->create_entity_mapper($from_item['alias']);
        }
    }

    protected function map_with() {
        foreach ($this->_jork_query->with_list as $with_item) {
            if (array_key_exists('alias', $with_item)) {
                $this->_naming_srv->set_alias($with_item['prop_chain'], $with_item['alias']);
            }
            $prop_chain = $with_item['prop_chain']->as_array();
            $root_entity = array_shift($prop_chain);
            if (!array_key_exists($root_entity, $this->_mappers))
                throw new jork\SyntaxException('invalid root entity in WITH clause: ' . $root_entity);

            $this->_mappers[$root_entity]->merge_prop_chain($prop_chain, jork\mapper\EntityMapper::SELECT_ALL);
        }
    }

    /**
     * Resolves a custom database expression passed as string.
     *
     * Picks property chains it founds in enclosing brackets, resolves the
     * property chains to table names. If the last item is an atomic property
     * then it puts the coresponding table column to the resolved expression,
     * otherwise throws an exception
     *
     * @param <type> $expr
     * @return string
     */
    protected function map_db_expression($expr) {
        $pattern = '/\{([^\}]*)\}/';
        preg_match_all($pattern, $expr, $matches);
        $resolved_expr_all = $expr;
        foreach ($matches[0] as $idx => $match) {
            $prop_chain = jork\query\PropChain::from_string($matches[1][$idx]);
            $prop_chain_arr = $prop_chain->as_array();
            $root_prop = array_shift($prop_chain_arr);
            $resolved_expr = $this->_mappers[$root_prop]
                    ->resolve_prop_chain($prop_chain_arr);
            if (is_array($resolved_expr))
                throw new jork\Exception('invalid property chain in database expression \''.$expr.'\'');
            $resolved_expr_all = str_replace($match, $resolved_expr, $resolved_expr_all);
        }
        return $resolved_expr_all;
    }


    /**
     * Maps the SELECT clause of the jork query to the db query.
     *
     * @see JORK_Mapper_Select::$_jork_query
     * @see JORK_Mapper_Select::$_db_query
     * @return void
     */
    protected function map_select() {
        if (empty($this->_jork_query->select_list)) {
            foreach ($this->_mappers as $mapper) {
                $mapper->select_all_atomics();
            }
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
            $root_entity = array_shift($prop_chain);
            if ( ! array_key_exists($root_entity, $this->_mappers))
                throw new JORK_Syntax_Exception('invalid property chain in select clause:'
                        . $select_item['prop_chain']->as_string());
            if (empty($prop_chain)) {
                if ( ! isset($select_item['projection'])) {
                    $this->_mappers[$root_entity]->select_all_atomics();
                }
            } else {
                $this->_mappers[$root_entity]->merge_prop_chain($prop_chain, jork\mapper\EntityMapper::SELECT_LAST);
            }
            if (array_key_exists('projection', $select_item)) {
                $this->add_projections($select_item['prop_chain'], $select_item['projection']);
            }
        }
    }

    protected function add_projections(jork\query\PropChain $prop_chain, $projections) {
        $prop_chain_arr = $prop_chain->as_array();
        $root_prop = array_shift($prop_chain_arr);
        if (empty($prop_chain_arr)) {
            $mapper = $this->_mappers[$root_prop];
            $last_prop = NULL;
        } else {
            list($mapper,, $last_prop) = $this->_mappers[$root_prop]->resolve_prop_chain($prop_chain_arr);
        }
        foreach ($projections as $raw_projection) {
            $proj = explode('.', $raw_projection);
            if ($last_prop !== NULL) {
                array_unshift($proj, $last_prop);
            }
            $mapper->merge_prop_chain($proj, jork\mapper\EntityMapper::SELECT_ALL);
        }
    }

    /**
     * Resolves any kind of database expressions, takes operands as property
     * chains, replaces them with the corresponding table aliases and column names
     * and merges the property chains.
     *
     * @param DB_Expression $expr
     * @return DB_Expression
     */
    protected function resolve_db_expr(db\Expression $expr) {
        if ($expr instanceof db\BinaryExpression) {
            if ($expr->left_operand instanceof DB\Expression) {
                $expr->left_operand = $this->resolve_db_expr($expr->left_operand);
            } elseif (is_string($expr->left_operand)) {
                $left_prop_chain = explode('.', $expr->left_operand);
                $left_root_prop = array_shift($left_prop_chain);
                $expr->left_operand = $this->_mappers[$left_root_prop]->resolve_prop_chain($left_prop_chain);
            }

            
            if ($expr->right_operand instanceof db\Expression) {
                $expr->right_operand = $this->resolve_db_expr($expr->right_operand);
            } elseif (is_string($expr->right_operand)) {
                $right_prop_chain = explode('.', $expr->right_operand);
                $right_root_prop = array_shift($right_prop_chain);
                $expr->right_operand = $this->_mappers[$right_root_prop]->resolve_prop_chain($right_prop_chain);
            }
            if (is_array($expr->left_operand) || is_array($expr->right_operand)) {
                $this->obj2condition($expr);
            }
        } elseif ($expr instanceof db\UnaryExpression) {
            $prop_chain = explode('.', $expr->operand);
            $root_prop = array_shift($prop_chain);
            $expr->operand = $this->_mappers[$root_prop]->resolve_prop_chain($prop_chain);
        } elseif ($expr instanceof db\CustomExpression) {
            $expr->str = $this->map_db_expression($expr->str);
        }
        return $expr;
    }


    protected function map_order_by() {
        if ($this->_jork_query->order_by === NULL)
            return;

        foreach ($this->_jork_query->order_by as $ord) {
            if ($ord['column'] instanceof db\CustomExpression) {
                $col = $ord['column'];
                $col->str = $this->map_db_expression($col->str);
            } else {
                $col_arr = explode('.', $ord['column']);
                $root_prop = array_shift($col_arr);
                $col = $this->_mappers[$root_prop]->resolve_prop_chain($col_arr);
                if (is_array($col))
                    throw new jork\Exception($ord['column'] . ' is not an atomic property');
            }
            $this->_db_query->order_by [] = array(
                'column' => $col,
                'direction' => $ord['direction']
            );
        }
    }

    protected function  map_group_by() {
        if (NULL === $this->_jork_query->group_by)
            return;
        foreach ($this->_jork_query->group_by as $group_by_itm) {
            $prop_chain = explode('.', $group_by_itm);
            $root_prop = array_shift($prop_chain);
            $col = $this->_mappers[$root_prop]
                    ->resolve_prop_chain($prop_chain);
            if (is_array($col))
                throw new jork\Exception ($group_by_itm.' is not an atomic property');
            $this->_db_query->group_by []= $col;
        }
    }

    /**
     * {@inheritdoc }
     */
    protected function  has_to_many_child() {
        foreach ($this->_mappers as $mapper) {
            if ($mapper instanceof jork\mapper\EntityMapper
                    && $mapper->has_to_many_child())
               return TRUE;
        }
        return FALSE;
    }

    /**
     * {@inheritdoc }
     */
    protected function  build_offset_limit_subquery(db\query\Select $subquery) {
        $subquery_alias = $this->_naming_srv->offset_limit_subquery_alias();

        $subquery->columns = array();
        $subquery->tables = array();
        $join_conditions = array();
        
        foreach ($this->_jork_query->from_list as $from_itm) {
            $ent_schema = jork\model\AbstractModel::schema_by_class($from_itm['class']);

            $existing_table_alias = $this->_naming_srv->table_alias($from_itm['alias'], $ent_schema->table);
            
            $table_alias = $this->_naming_srv->table_alias($from_itm['class']
                    , $ent_schema->table, TRUE);
            $subquery->tables []= array($ent_schema->table, $table_alias);

            $primary_key = $ent_schema->primary_key();
            $column_alias = $table_alias . '_' . $primary_key;

            $subquery->columns []= array($table_alias . '.' . $primary_key
                , $column_alias);

            $join_conditions []= new db\BinaryExpression($existing_table_alias . '.' . $primary_key
                    , '=', $subquery_alias . '.' . $column_alias);
        }

        $this->filter_unneeded_subquery_joins($subquery);

        return array(
            'table' => array($subquery, $subquery_alias),
            'type' => 'RIGHT',
            'conditions' => $join_conditions
        );
    }
    
}
