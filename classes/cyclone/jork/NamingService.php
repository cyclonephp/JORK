<?php

namespace cyclone\jork;

/**
 * @author Bence Eros <crystal@cyclonephp.com>
 * @package JORK
 */
class NamingService {

    public function  __construct() {
        
    }

    private $_table_aliases = array();

    private $_table_usage = array();

    /**
     * Stores name => JORK_Mapping_Schema pairs.
     * @var array
     */
    private $_entity_aliases = array();

    private $_offset_limit_subquery_count = 0;

    /**
     * @var JORK_Mapping_Schema
     */
    private $_implicit_root_schema;

    /**
     * Registers a new alias name. After an alias name is registered it can be
     * used as an explicit root entity class of a property chain.
     *
     * @param string $entity_class entity class name OR property chain
     * @param string $alias
     */
    public function set_alias($entity_class, $alias) {
        $this->_entity_aliases[$alias] = $this->get_schema($entity_class);
    }

    public function set_implicit_root($class) {
        $this->_entity_aliases[NULL] =
        $this->_entity_aliases[$class] =
        $this->_implicit_root_schema = model\AbstractModel::schema_by_class($class);
    }

    /**
     * @param string $name a property chain or an alias name
     * @throws cyclone\jork\Exception if $name is not a valid name
     * @return JORK_Mapping_Schema or string
     */
    public function get_schema($name) {
        if ( ! array_key_exists($name, $this->_entity_aliases)) {
            $this->search_schema($name);
        }
        return $this->_entity_aliases[$name];
    }

    /**
     * Called if $name does not exist in $this->_aliases
     *
     * @param string $name a property chain or an alias name
     * @throws cyclone\jork\Exception if $name is not a valid name
     * @see JORK_Naming_Service::get_schema()
     */
    private function search_schema($name) {
        $segments = explode('.', $name);
        if (1 == count($segments)) {
            if (NULL == $this->_implicit_root_schema) {
                $this->_entity_aliases[$name] = model\AbstractModel::schema_by_class($name);
                return;
            } else {
                if (isset($this->_implicit_root_schema->primitives[$name])) {
                    $this->_entity_aliases[$name] = $this->_implicit_root_schema->primitives[$name];
                    return;
                }
                if (isset($this->_implicit_root_schema->components[$name])) {
                    $cmp_schema = $this->_implicit_root_schema->components[$name];
                    $this->_entity_aliases[$name] = model\AbstractModel::schema_by_class($cmp_schema->class);
                    return;
                }
                if (isset($this->_implicit_root_schema->embedded_components[$name])) {
                    $cmp_schema = $this->_implicit_root_schema->embedded_components[$name];
                    $this->_entity_aliases[$name] = model\AbstractModel::schema_by_class($cmp_schema->class);
                    return;
                }
            }
        } else {
            $walked_segments = array();
            if (NULL == $this->_implicit_root_schema) {
                if ( ! isset($this->_entity_aliases[$segments[0]]))
                    throw new Exception('invalid identifier: '.$name);
                $root_schema = $this->_entity_aliases[$segments[0]]; //explicit root entity class
                $walked_segments []= array_shift($segments);
            } else {
                $root_schema = $this->_implicit_root_schema;
            }
            $current_schema = $root_schema;
            foreach ($segments as $seg) {
                if (NULL == $current_schema) // only the last segment can be an atomic property
                    throw new jork\Exception('invalid identifier: '.$name); // otherwise the search fails
                $found = FALSE;
                $walked_segments []= $seg;
                if (isset($current_schema->components[$seg])) {
                    $current_schema = model\AbstractModel
                        ::schema_by_class($current_schema->components[$seg]->class);
                    $this->_entity_aliases[implode('.', $walked_segments)] = $current_schema;
                    $found = TRUE;
                } elseif (isset($current_schema->embedded_components[$seg])) {
                    $current_schema = $current_schema->embedded_components[$seg];
                    $this->_entity_aliases[implode('.', $walked_segments)] = $current_schema;
                    $found = TRUE;
                } elseif (isset($current_schema->primitives[$seg])) {
                    $this->_entity_aliases[implode('.', $walked_segments)] = $current_schema->primitives[$seg];
                    //the schema in the next iteration will be NULL if column
                    // (primitive property) found
                    $current_schema = NULL;
                } else
                    throw new Exception('invalid identifier: '.$name);
            }
        }
    }

    public function table_alias($prop_chain, $table_name, $needs_unique = FALSE) {
        if ( ! isset($this->_table_aliases[$prop_chain])) {
            $this->_table_aliases[$prop_chain] = array();
        }

        // doesn't work if called with needs_unique = TRUE then with
        // needs_unique = TRUE with the same table name        
        if ( $needs_unique || ! isset($this->_table_aliases[$prop_chain][$table_name])) {
            if ( ! isset($this->_table_usage[$table_name])) {
                $this->_table_usage[$table_name] = 0;
            }
            $this->_table_aliases[$prop_chain][$table_name] = $table_name . '_'
                    . ($this->_table_usage[$table_name]++);
        }
        
        return $this->_table_aliases[$prop_chain][$table_name];
    }

    public function offset_limit_subquery_alias() {
        return 'jork_offset_limit_subquery_'.$this->_offset_limit_subquery_count++;
    }

    
}
