<?php

namespace cyclone\jork\schema;

use cyclone\db\schema;

/**
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.com>
 */
class SchemaBuilder {

    public static function factory($schemas = NULL, $default_types = NULL) {
        return new SchemaBuilder($schemas, $default_types);
    }

    /**
     * @var array<ModelSchema>
     */
    protected $_schemas;

    /**
     * @var array
     */
    protected $_default_types;

    public function __construct($schemas = NULL, $default_types = NULL) {
        if ( ! is_null($schemas)) {
            if ( ! is_array($schemas))
                throw new db\Exception("\$schemas must be an array");
            foreach ($schemas as $idx => $schema) {
                if ( ! $schema instanceof ModelSchema)
                    throw new db\Exception("the {$idx}th item of \$schemas is not a ModelSchema instance");
            }
        }
        $this->_schemas = $schemas;
        if ( ! is_null($default_types)) {
            foreach ($default_types as $php_type => $sql_type) {
                if ( ! in_array($php_type, SchemaValidator::$valid_types))
                    throw new db\Exception("invalid key in \$default_types: '$php_type'");
            }
        }
        $this->_default_types = $default_types;
    }

    /**
     * @return array<\cyclone\db\schema\Table>
     * @uses generate_db_schema()
     */
    public function build_schema() {
        return $this->generate_db_schema(SchemaPool::inst()->get_schemas());
    }

    protected function add_primitive_columns(ModelSchema $model_schema
            , &$table_pool) {
        foreach ($model_schema->primitives as $prim_schema) {
            $tbl_name = is_null($prim_schema->table) ? $model_schema->table : $prim_schema->table;

            $tbl = schema\Table::get_by_name($tbl_name);
            if (!isset($table_pool[$tbl->name])) {
                $table_pool[$tbl->name] = $tbl;
            }

            $col_name = is_null($prim_schema->column) ? $prim_schema->name : $prim_schema->column;
            $col = $tbl->create_column($col_name);
            $col->type = $this->_default_types[$prim_schema->type];
            $col->is_primary = !is_null($prim_schema->primary_key_strategy);
        }
    }

    protected function add_secondary_tables(ModelSchema $model_schema
            , &$table_pool) {
        if (is_array($model_schema->secondary_tables)) {
            foreach ($model_schema->secondary_tables as $sec_tbl) {
                $tbl = schema\Table::get_by_name($sec_tbl->name);
                if ( ! isset($table_pool[$tbl->name])) {
                    $table_pool[$tbl->name] = $tbl;
                }
                $inv_join_col = $tbl->create_column($sec_tbl->inverse_join_column);
                foreach ($model_schema->primitives as $prim_schema) {
                    $col_name = is_null($prim_schema->column)
                            ? $prim_schema->name
                            : $prim_schema->column;
                    if ($col_name == $sec_tbl->join_column) {
                        $inv_join_col->type = $this->_default_types[$prim_schema->type];
                        break;
                    }
                }
                if (NULL === $inv_join_col->type)
                    throw new SchemaBuilderException("failed to determine type of join column '{$sec_tbl->inverse_join_column}'");
            }
        }
    }

    protected function add_foreign_keys(ModelSchema $model_schema
            , $table_pool) {
        foreach ($model_schema->components as $comp_schema) {
            
        }
    }

    /**
     * @param array<ModelSchema> $schemas
     * @return array<\cyclone\db\schema\Table>
     * @usedby build_schema()
     */
    public function generate_db_schema() {
        $rval = array();
        foreach ($this->_schemas as $model_schema) {
            $this->add_secondary_tables($model_schema, $rval);
            $this->add_primitive_columns($model_schema, $rval);
            $this->add_foreign_keys($model_schema, $rval);
        }
        return $rval;
    }

}