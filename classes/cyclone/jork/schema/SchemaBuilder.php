<?php

namespace cyclone\jork\schema;

use cyclone\db\schema;
use cyclone as cy;

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

    protected $_table_pool = array();

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

    protected function add_primitive_columns(ModelSchema $model_schema) {
        foreach ($model_schema->primitives as $prim_schema) {
            $tbl_name = is_null($prim_schema->table) ? $model_schema->table : $prim_schema->table;

            $tbl = schema\Table::get_by_name($tbl_name);
            if ( ! isset($this->_table_pool[$tbl->name])) {
                $this->_table_pool[$tbl->name] = $tbl;
            }

            $col_name = is_null($prim_schema->column) ? $prim_schema->name : $prim_schema->column;
            $col = $tbl->get_column($col_name);
            $col->type = $this->_default_types[$prim_schema->type];
            $col->is_primary = !is_null($prim_schema->primary_key_strategy);
        }
    }

    protected function add_secondary_tables(ModelSchema $model_schema) {
        if (is_array($model_schema->secondary_tables)) {
            foreach ($model_schema->secondary_tables as $sec_tbl) {
                $tbl = schema\Table::get_by_name($sec_tbl->name);
                if ( ! isset($this->_table_pool[$tbl->name])) {
                    $this->_table_pool[$tbl->name] = $tbl;
                }
                $inv_join_col = $tbl->get_column($sec_tbl->inverse_join_column);
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

    protected function add_foreign_keys(ModelSchema $model_schema) {
        foreach ($model_schema->components as $comp_schema) {
            if (isset($comp_schema->mapped_by))
                continue;

            switch($comp_schema->type) {
                case cy\JORK::ONE_TO_ONE:
                    break;
                case cy\JORK::ONE_TO_MANY:
                    $comp_class_schema = $this->_schemas[$comp_schema->class];
                    $foreign_table = NULL;
                    $foreign_col = NULL;
                    foreach ($comp_class_schema->primitives as $prim_schema) {
                        $col_name = NULL === $prim_schema->column
                                ? $prim_schema->name
                                : $prim_schema->column;
                        if ($col_name === $comp_schema->join_column) {
                            $table_name = NULL === $prim_schema->table
                                    ? $comp_class_schema->table
                                    : $prim_schema->table;
                            $foreign_table = schema\Table::get_by_name($table_name);
                            if ( ! isset($this->_table_pool[$foreign_table->name])) {
                                $this->_table_pool[$foreign_table->name] = $foreign_table;
                            }
                            $foreign_col = $foreign_table->get_column($col_name);
                        }
                    }
                    if (NULL === $foreign_table)
                        throw new SchemaBuilderException("Failed to create foreign key constraint: "
                                . "no foreign table found for {$model_schema->class}::\${$comp_schema->name}");
                    $fk = new schema\ForeignKey;
                    $fk->local_table = $foreign_table;
                    // TODO composite foreign key handling
                    $fk->local_columns = array($foreign_col);

                    $pk_schema = $model_schema->primitives[$model_schema->primary_key()];
                    if (NULL === $comp_schema->inverse_join_column) {
                        $local_col_name = NULL === $pk_schema->column
                                ? $pk_schema->name
                                : $pk_schema->column;
                    } else {
                        $local_col_name = $comp_schema->inverse_join_column;
                    }
                    $local_table = NULL;
                    foreach ($model_schema->primitives as $prim_schema) {
                        $tmp_col_name = NULL === $prim_schema->column
                                ? $prim_schema->name
                                : $prim_schema->column;
                        if ($tmp_col_name === $local_col_name) {
                            $local_table = schema\Table::get_by_name(NULL === $prim_schema->table
                                    ? $model_schema->table
                                    : $prim_schema->table);
                            if ( ! isset($this->_table_pool[$local_table->name])) {
                                $this->_table_pool[$local_table->name] = $local_table;
                            }
                            $local_col = $local_table->get_column($local_col_name);
                            break;
                        }
                    }
                    $fk->foreign_table = $local_table;
                    $fk->foreign_columns = array($local_col);
                    
                    $this->_table_pool[$fk->local_table->name]->add_foreign_key($fk);
                    break;
                case cy\JORK::MANY_TO_ONE:
                    break;
                case cy\JORK::MANY_TO_MANY:
                    break;
            }
        }
    }

    /**
     * @param array<ModelSchema> $schemas
     * @return array<\cyclone\db\schema\Table>
     * @usedby build_schema()
     */
    public function generate_db_schema() {
        foreach ($this->_schemas as $model_schema) {
            $this->add_secondary_tables($model_schema);
            $this->add_primitive_columns($model_schema);
            $this->add_foreign_keys($model_schema);
        }
        return $this->_table_pool;
    }

}