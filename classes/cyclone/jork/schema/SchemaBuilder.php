<?php

namespace cyclone\jork\schema;

use cyclone\db\schema;

/**
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.com>
 */
class SchemaBuilder {

    /**
     * @var SchemaBuilder
     */
    private static $_inst;

    /**
     * @return SchemaBuilder
     */
    public static function inst() {
        if (NULL === self::$_inst) {
            self::$_inst = new SchemaBuilder;
        }
        return self::$_inst;
    }

    public static function clear() {
        self::$_inst = NULL;
    }

    private function __construct() {
        // empty private constructor
    }

    /**
     * @return array<\cyclone\db\schema\Table>
     * @uses generate_db_schema()
     */
    public function build_schema() {
        return $this->generate_db_schema(SchemaPool::inst()->get_schemas());
    }

    /**
     * @param array<ModelSchema> $schemas
     * @return array<\cyclone\db\schema\Table>
     * @usedby build_schema()
     */
    public function generate_db_schema($schemas, $default_types) {
        $rval = array();
        foreach ($schemas as $model_schema) {
            $tbl = schema\Table::get_by_name($model_schema->table);
            foreach ($model_schema->primitives as $prim_schema) {
                $col_name = is_null($prim_schema->column)
                        ? $prim_schema->name
                        : $prim_schema->column;
                $col = $tbl->create_column($col_name);
                $col->type = $default_types[$prim_schema->type];
                $col->is_primary = ! is_null($prim_schema->primary_key_strategy);
            }
            $rval[$tbl->name] = $tbl;
        }
        return $rval;
    }

}