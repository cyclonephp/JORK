<?php

namespace cyclone\jork\schema;

use cyclone as cy;
use cyclone\jork;

/**
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.org>
 */
class SchemaValidator {

    private static $_inst;

    /**
     *
     * @return cyclone\jork\schema\SchemaValidator
     */
    public static function inst() {
        if (NULL === self::$_inst) {
            self::$_inst = new SchemaValidator;
        }
        return self::$_inst;
    }

    private function __construct() {
        // empty private constructor
    }

    /**
     *
     * @var array<\cyclone\jork\schema\ModelSchema>
     */
    private $_schemas;

    private $_validators = array(
        array(__CLASS__, 'test_primary_keys')
        , array(__CLASS__, 'test_table_name')
        , array(__CLASS__, 'test_comp_classes')
        , array(__CLASS__, 'test_mapped_by')
        , array(__CLASS__, 'test_component_foreign_keys')
    );

    public function validate() {
        try {
            $this->_schemas = SchemaPool::inst()->get_schemas();
        } catch (jork\SchemaException $ex) {
            echo "fatal schema error during validation:" . PHP_EOL;
            echo $ex->getMessage() . PHP_EOL;
            $rval = $ex->getCode();
            if ( ! $rval) {
                $rval = 5;
            }
            return $rval;
        }
        $this->exec_validators()->render();
        return 0;
    }

    private function exec_validators() {
        $result = new ValidationResult;
        foreach ($this->_validators as $validator) {
            try {
                $result->merge(call_user_func($validator, $this->_schemas));
            } catch (\Exception $ex) {
                throw new jork\Exception("fatal error during schema validation: " . $ex->getMessage()
                        , $ex->getCode(), $ex);
            }
        }
        return $result;
    }

    public static function test_primary_keys($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            try {
                $schema->primary_key();
            } catch (jork\Exception $ex) {
                $rval->add_error("class {$schema->class} doesn't have primary key property");
            }
        }
        return $rval;
    }

    public static function test_table_name($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            if (empty($schema->table)) {
                $rval->add_error("class {$schema->class} does not have table");
            }
        }
        return $rval;
    }

    public static function test_component_foreign_keys($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            foreach ($schema->components as $comp_schema) {
                if (empty($comp_schema->mapped_by)) {
                    $type_range = array(cy\JORK::ONE_TO_ONE
                        , cy\JORK::ONE_TO_MANY
                        , cy\JORK::MANY_TO_ONE
                        , cy\JORK::MANY_TO_MANY);
                    if ( ! in_array($comp_schema->type, $type_range)
                            || is_null($comp_schema->type)) {
                        $rval->add_error('unknown component cardinality "'
                                . $comp_schema->type
                                . '" at ' . $schema->class . '::$' . $comp_schema->name);
                        continue;
                    }
                    switch($comp_schema->type) {
                        case cy\JORK::ONE_TO_ONE:
                            $join_column = empty($comp_schema->join_column)
                                ? $schemas[$comp_schema->class]->primary_key()
                                : $comp_schema->join_column;
                            if ( ! isset($schema->primitives[$join_column])) {
                                $rval->add_error('local join column ' . $schema->class
                                        . '::$' . $comp_schema->join_column
                                        . ' doesn\'t exist');
                            }
                            $inverse_schema = $schemas[$comp_schema->class];
                            $inverse_join_col = empty($comp_schema->inverse_join_column)
                                    ? $inverse_schema->primary_key()
                                    : $comp_schema->inverse_join_column;
                            if ( ! isset($inverse_schema->primitives[$inverse_join_col])) {
                                $rval->add_error('inverse join column ' . $comp_schema->class
                                        . '::$' . $comp_schema->inverse_join_column
                                        . ' doesn\'t exist');
                            }
                            break;
                        case cy\JORK::ONE_TO_MANY:
                            if ( ! isset($comp_schema->join_column)) {
                                $rval->add_error('one-to-many component '
                                        . $schema->class . '::$' . $comp_schema->name
                                        . ' doesn\'t have join column');
                                break;
                            }
                            $comp_class_schema = $schemas[$comp_schema->class];
                            if ( ! $comp_class_schema->column_exists($comp_schema->join_column)) {
                                $rval->add_error('property ' . $comp_schema->class
                                        . '::$' . $comp_schema->join_column
                                        . ' doesn\'t exist but referenced by '
                                        . $schema->class . '::$' . $comp_schema->name);
                            }
                            break;
                        case cy\JORK::MANY_TO_ONE:
                            break;
                        case cy\JORK::MANY_TO_MANY:
                            break;
                    }
                }
            }
        }
        return $rval;
    }

    public static function test_comp_classes($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            foreach ($schema->components as $comp_schema) {
                if ( ! isset($schemas[$comp_schema->class])) {
                    $rval->add_error('class ' . $comp_schema->class
                            . ' is not mapped but referenced using '
                            . $schema->class . '::$' . $comp_schema->name);
                }
            }
        }
        return $rval;
    }

    public static function test_mapped_by($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            foreach ($schema->components as $comp_schema) {
                if ( ! empty($comp_schema->mapped_by)) {
                    $inverse_schema = $schemas[$comp_schema->class];
                    if ( ! isset($inverse_schema->components[$comp_schema->mapped_by])) {
                        $rval->add_error('property ' . $inverse_schema->class
                                . '::$' . $comp_schema->mapped_by
                                . ' doesn\'t exist but referenced by '
                                . $schema->class . '::$' . $comp_schema->name);
                    }
                }
            }
        }
        return $rval;
    }

}