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

    public static $valid_types = array(
        'int', 'integer'
        , 'bool', 'boolean'
        , 'float'
        , 'string'
    );

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
        , array(__CLASS__, 'test_primitives')
        , array(__CLASS__, 'test_secondary_tables')
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

    public static function test_primitives($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            foreach ($schema->primitives as $primitive) {
                if ( ! in_array($primitive->type, self::$valid_types)) {
                    $rval->add_error($schema->class . '::$' . $primitive->name
                            . ' has invalid type \'' . $primitive->type . "'");
                }
            }
        }
        return $rval;
    }

    private static function test_one_to_one_foreign_keys($schemas
            , ModelSchema $schema
            , ComponentSchema $comp_schema
            , ValidationResult $result) {
        $join_column = empty($comp_schema->join_column) ? $schemas[$comp_schema->class]->primary_key() : $comp_schema->join_column;
        if ( ! isset($schema->primitives[$join_column])) {
            $result->add_error('local join column ' . $schema->class
                    . '::$' . $comp_schema->join_column
                    . ' doesn\'t exist');
        }
        $inverse_schema = $schemas[$comp_schema->class];
        $inverse_join_col = empty($comp_schema->inverse_join_column) ? $inverse_schema->primary_key() : $comp_schema->inverse_join_column;
        if ( ! isset($inverse_schema->primitives[$inverse_join_col])) {
            $result->add_error('inverse join column ' . $comp_schema->class
                    . '::$' . $comp_schema->inverse_join_column
                    . ' doesn\'t exist');
        }
    }

    private static function test_one_to_many_foreign_keys($schemas
            , ModelSchema $schema
            , ComponentSchema $comp_schema
            , ValidationResult $result) {
        if ( ! isset($comp_schema->join_column)) {
            $result->add_error('one-to-many component '
                    . $schema->class . '::$' . $comp_schema->name
                    . ' doesn\'t have join column');
            return;
        }
        $comp_class_schema = $schemas[$comp_schema->class];
        if ( ! $comp_class_schema->column_exists($comp_schema->join_column)) {
            $result->add_error('column ' . $comp_schema->class
                    . '::$' . $comp_schema->join_column
                    . ' doesn\'t exist but referenced by '
                    . $schema->class . '::$' . $comp_schema->name);
        }
        if ( ! empty($comp_schema->inverse_join_column)) {
            if ( ! $schema->column_exists($comp_schema->inverse_join_column)) {
                $result->add_error('column ' . $schema->class . '::$'
                        . $comp_schema->inverse_join_column
                        . ' doesn\'t exist but referenced by '
                        . $schema->class . '::$' . $comp_schema->name);
            }
        }
    }

    private static function test_many_to_one_foreign_keys($schemas
            , ModelSchema $schema
            , ComponentSchema $comp_schema
            , ValidationResult $result) {
        if ( ! isset($comp_schema->join_column)) {
            $result->add_error('many-to-one component '
                    . $schema->class . '::$' . $comp_schema->name
                    . ' doesn\'t have join column');
            return;
        }
        if ( ! $schema->column_exists($comp_schema->join_column)) {
            $result->add_error('column ' . $schema->class
                    . '::$' . $comp_schema->join_column
                    . ' doesn\'t exist but referenced by '
                    . $schema->class . '::$' . $comp_schema->name);
        }
        $comp_class_schema = $schemas[$comp_schema->class];
        if ( ! empty($comp_schema->inverse_join_column)) {
            if (!$comp_class_schema->column_exists($comp_schema->inverse_join_column)) {
                $result->add_error('column ' . $comp_class_schema->class . '::$'
                        . $comp_schema->inverse_join_column
                        . ' doesn\'t exist but referenced by '
                        . $schema->class . '::$' . $comp_schema->name);
            }
        }
    }

    private static function test_many_to_many_foreign_keys($schemas
            , ModelSchema $schema
            , ComponentSchema $comp_schema
            , ValidationResult $result) {
        if ( ! is_object($comp_schema->join_table)
                || ! ($comp_schema->join_table instanceof JoinTableSchema)) {
            $result->add_error('no join table defined for many-to-many component '
                    . $schema->class . '::$' . $comp_schema->name);
        }
        if ( ! $schema->column_exists($comp_schema->join_column)) {
            $result->add_error('column ' . $schema->class . '::$'
                    . $comp_schema->join_column . ' doesn\'t exist but referenced by '
                    . $schema->class . '::$' . $comp_schema->name);
        }
        $comp_class_schema = $schemas[$comp_schema->class];
        if ( ! $comp_class_schema->column_exists($comp_schema->inverse_join_column)) {
            $result->add_error('column ' . $comp_schema->class . '::$'
                    . $comp_schema->inverse_join_column . ' doesn\'t exist but referenced by '
                    . $schema->class . '::$' . $comp_schema->name);
        }
    }

    public static function test_component_foreign_keys($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            foreach ($schema->components as $name => $comp_schema) {
                if ( ! $comp_schema instanceof ComponentSchema) {
                    $rval->add_error('mapping schema of ' . $schema->class . '::$'
                            . $name . ' is not a ComponentSchema instance');
                    continue;
                }
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
                            self::test_one_to_one_foreign_keys($schemas, $schema, $comp_schema, $rval);
                            break;
                        case cy\JORK::ONE_TO_MANY:
                            self::test_one_to_many_foreign_keys($schemas, $schema, $comp_schema, $rval);
                            break;
                        case cy\JORK::MANY_TO_ONE:
                            self::test_many_to_one_foreign_keys($schemas, $schema, $comp_schema, $rval);
                            break;
                        case cy\JORK::MANY_TO_MANY:
                            self::test_many_to_many_foreign_keys($schemas, $schema, $comp_schema, $rval);
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

    public static function test_secondary_tables($schemas) {
        $rval = new ValidationResult;
        foreach ($schemas as $schema) {
            foreach ($schema->secondary_tables as $sec_tbl) {
                if ( ! $schema->column_exists($sec_tbl->join_column)) {
                    $rval->add_error('column ' . $schema->class . '::$'
                            . $sec_tbl->join_column . ' doesn\'t exist but referenced by secondary table \''
                            . $sec_tbl->name . '\'');
                }
            }
        }
        return $rval;
    }

}