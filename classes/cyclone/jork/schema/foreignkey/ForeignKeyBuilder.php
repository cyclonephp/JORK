<?php

namespace cyclone\jork\schema\foreignkey;

use cyclone as cy;
use cyclone\db\schema;
use cyclone\jork;

/**
 * @author Bence Eros <crystal@cyclonephp.org>
 * @package JORK
 */
abstract class ForeignKeyBuilder {

    /**
     * @var array<\cyclone\jork\schema\ModelSchema>
     */
    protected $_schema_pool;

    /**
     * @var \cyclone\jork\schema\ModelSchema
     */
    protected $_model_schema;

    /**
     * @var \cyclone\jork\schema\ComponentSchema
     */
    protected $_comp_schema;

    /**
     * @var array<\cyclone\db\schema\Table>
     */
    protected $_table_pool;

    /**
     * @param int $comp_type
     * @param array<\cyclone\jork\schema\ModelSchema> $schema_pool
     * @param \cyclone\jork\schema\ModelSchema $model_schema
     * @param \cyclone\jork\schema\ComponentSchema $comp_schema
     * @param array<\cyclone\db\schema\Table> $table_pool
     * @return ForeignKeyBuilder
     */
    public static function factory($comp_type
            , $schema_pool
            , jork\schema\ModelSchema $model_schema
            , $comp_schema
            , &$table_pool) {
        $fk_builders = array(
            cy\JORK::ONE_TO_ONE => 'cyclone\\jork\\schema\\foreignkey\\OneToOneFKBuilder',
            cy\JORK::ONE_TO_MANY => 'cyclone\\jork\\schema\\foreignkey\\OneToManyFKBuilder',
            cy\JORK::MANY_TO_ONE => 'cyclone\\jork\\schema\\foreignkey\\ManyToOneFKBuilder',
            cy\JORK::MANY_TO_MANY => 'cyclone\\jork\\schema\\foreignkey\\ManyToManyFKBuilder',
        );

        $class = $fk_builders[$comp_type];
        return new $class($schema_pool, $model_schema, $comp_schema, $table_pool);
    }


    /**
     * @param array<\cyclone\jork\schema\ModelSchema> $schema_pool
     * @param \cyclone\jork\schema\ModelSchema $model_schema
     * @param \cyclone\jork\schema\ComponentSchema $comp_schema
     * @param array<\cyclone\db\schema\Table> $table_pool
     */
    public function __construct($schema_pool, $model_schema, $comp_schema, &$table_pool) {
        $this->_schema_pool = $schema_pool;
        $this->_model_schema = $model_schema;
        $this->_comp_schema = $comp_schema;
        $this->_table_pool = &$table_pool;
    }

    public abstract function create_foreign_key();
}