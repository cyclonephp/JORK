<?php

namespace cyclone\jork\model\collection;

use cyclone as cy;
use cyclone\jork;
use cyclone\jork\schema;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
class ComparatorProvider {

    private static $_instance_pool = array();

    /**
     * @param ModelSchema $schema the schema of the model instances which
     *  the provider will create comparators for
     * @return ComparatorProvider
     */
    public static function for_schema(schema\ModelSchema $schema) {
        if ( ! isset(self::$_instance_pool[$schema->class])) {
            self::$_instance_pool[$schema->class]
                    = new ComparatorProvider($schema);
        }
        return self::$_instance_pool[$schema->class];
    }

    /**
     *
     * @var cyclone\jork\schema\ModelSchema
     */
    private $_model_schema;

    private function __construct(schema\ModelSchema $model_schema) {
        $this->_model_schema = $model_schema;
    }

    /**
     * At most 2 items: for ascending and descending ordering (<code>'regular'</code>
     * and <code>'reverse'</code> keys).
     *
     * @var array<function>
     */
    private $_comparators = array();

    public function get_comparator($order, $user_comparator) {
        if (NULL === $user_comparator) {
            if ( ! isset($this->_comparators[$order])) {
                if ($order == cy\JORK::SORT_REGULAR) {
                    $on_lower_asc = -1;
                    $on_lower_desc = 1;
                    $on_higher_asc = 1;
                    $on_higher_desc = -1;
                } elseif ($order === cy\JORK::SORT_REVERSE) {
                    $on_lower_asc = 1;
                    $on_lower_desc = -1;
                    $on_higher_asc = -1;
                    $on_higher_desc = 1;
                } else
                    throw new jork\Exception("unknown ordering: '$order'");

                // (prop_name =>, 'on_lower' =>, 'on_higher' => )
                $ordering_values = array();

                if ( ! empty($this->_model_schema->natural_ordering)) {
                    foreach ($this->_model_schema->natural_ordering as $ordering) {
                        $is_asc = $ordering->direction == 'asc';
                        $ordering_values [] = array(
                            'prop_name' => $ordering->property,
                            'on_lower' => $is_asc ? $on_lower_asc : $on_lower_desc,
                            'on_higher' => $is_asc ? $on_higher_asc : $on_higher_desc
                        );
                    }
                } else {
                    $ordering_values [] = array(
                        'on_lower' => $on_lower_asc
                        , 'on_higher' => $on_higher_asc
                        , 'prop_name' => $this->_model_schema->primary_key()
                    );
                }

                if (count($ordering_values) == 1) {
                    $ord = $ordering_values[0];
                    $ordering_property = $ord['prop_name'];
                    $on_lower = $ord['on_lower'];
                    $on_higher = $ord['on_higher'];
                    $this->_comparators[$order] = function($a, $b)
                            use ($ordering_property, $on_lower, $on_higher) {
                                $a_val = $a['value']->$ordering_property;
                                $b_val = $b['value']->$ordering_property;

                                if ($a_val < $b_val)
                                    return $on_lower;

                                if ($a_val > $b_val)
                                    return $on_higher;

                                return 0;
                            };
                } else {
                    $this->_comparators[$order] = function($a, $b) use ($ordering_values) {
                        foreach ($ordering_values as $ord_val) {
                            $a_val = $a['value']->{$ord_val['prop_name']};
                            $b_val = $b['value']->{$ord_val['prop_name']};
                            
                            if ($a_val < $b_val)
                                return $ord_val['on_lower'];

                            if ($a_val > $b_val)
                                return $ord_val['on_higher'];
                        }
                        return 0;
                    };
                }
            }
            return $this->_comparators[$order];
        } else {
            $modifier = $order == cy\JORK::SORT_REGULAR ? 1 : -1;
            return function($a, $b) use ($user_comparator, $modifier) {
                return $modifier * $user_comparator($a['value'], $b['value']);
            };
        }
    }
    
}