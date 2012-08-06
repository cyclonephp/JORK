<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;
use cyclone\jork\schema;
use cyclone\jork\schema\SchemaPool;

require_once realpath(__DIR__) . '../../MapperTest.php';

class JORK_Mapping_SchemaTest extends JORK_MapperTest {

    /**
     * @expectedException cyclone\jork\SchemaException
     */
    public function test_get_prop_schema() {
        $schema = SchemaPool::inst()->get_schema('Model_User');
        /*$this->assertEquals($schema->get_property_schema('id'), array(
                'type' => 'int',
                'primary' => true,
                'geneneration_strategy' => 'auto'
            ));*/

        $this->assertEquals(cy\JORK::primitive('id', 'int')->column('userId')
                ->primary_key()
                , $schema->get_property_schema('id'));

        /*$this->assertEquals($schema->get_property_schema('posts'), array(
                'class' => 'Model_Post',
                'type' => cy\JORK::ONE_TO_MANY,
                'join_column' => 'user_fk',
                'on_delete' => cy\JORK::SET_NULL
            ));*/

        $this->assertEquals(cy\JORK::component('posts', 'Model_Post')
                ->type(cy\JORK::ONE_TO_MANY)->join_column('userFk')
                ->on_delete(cy\JORK::SET_NULL)
                , $schema->get_property_schema('posts'));
        
        $schema->get_property_schema('dummy');
    }

    /**
     * @dataProvider provider_is_to_many_component
     */
    public function test_is_to_many_component($class, $component, $is_to_many) {
        $this->load_schemas('basic');
        $this->assertEquals($is_to_many, SchemaPool::inst()->get_schema($class)
                ->is_to_many_component($component));
    }

    public function provider_is_to_many_component() {
        return array(
            array('Model_Category', 'moderator', FALSE),
            array('Model_User', 'moderated_category', FALSE),
            array('Model_Topic', 'posts', TRUE),
            array('Model_Post', 'topic', FALSE),
            array('Model_Post', 'author', FALSE),
            array('Model_User', 'posts', TRUE),
            array('Model_Category', 'topics', TRUE),
            array('Model_Topic', 'categories', TRUE)
        );
    }

    /**
     * @dataProvider provider_column_exists
     */
    public function test_column_exists($schema, $col_name, $expected) {
        $this->assertEquals($expected, $schema->column_exists($col_name));
    }

    public function provider_column_exists() {
        $rval = array();
        $rval []= array(
            schema\ModelSchema::factory()
                ->primitive(cy\JORK::primitive('prop1', 'int')),
            'prop1',
            TRUE
        );
        $rval []= array(
            schema\ModelSchema::factory()
                ->primitive(cy\JORK::primitive('prop1', 'int')->column('col1')),
            'col1',
            TRUE
        );
        $rval []= array(
            schema\ModelSchema::factory()
                ->primitive(cy\JORK::primitive('prop1', 'int')->column('col1')),
            'prop1',
            FALSE
        );
        return $rval;
    }

    public function provider_table_names_for_columns() {
        $rval = array();
        $rval []= array(
            schema\ModelSchema::factory()->table('tbl_1')->primitive(cy\JORK::primitive('id', 'int')
                ->primary_key())
            , array()
            , array('tbl_1')
        );
        $rval []= array(
            schema\ModelSchema::factory()->table('tbl_1')->primitive(cy\JORK::primitive('id', 'int')
                ->primary_key())
            , array('id')
            , array('tbl_1')
        );
        $rval []= array(
            schema\ModelSchema::factory()->table('tbl_1')
                ->secondary_table(cy\JORK::secondary_table('tbl_2', 'tbl1_fk', 'tbl2_fk'))
                ->primitive(cy\JORK::primitive('id', 'int')->primary_key())
                ->primitive(cy\JORK::primitive('col2', 'string')->table('tbl_2'))
            , array('id', 'col2')
            , array('tbl_1', 'tbl_2')
        );
        $rval []= array(
            schema\ModelSchema::factory()->table('tbl_1')
                ->secondary_table(cy\JORK::secondary_table('tbl_2', 'tbl1_fk', 'tbl2_fk'))
                ->primitive(cy\JORK::primitive('id', 'int')->primary_key())
                ->primitive(cy\JORK::primitive('col2', 'string')->table('tbl_2'))
            , array('id', 'id')
            , array('tbl_1', 'tbl_1')
        );
        return $rval;
    }

    /**
     * @dataProvider provider_table_names_for_columns
     */
    public function test_table_names_for_columns(schema\ModelSchema $model_schema
            , $col_names
            , $expected_table_names) {
        $this->assertEquals($expected_table_names, $model_schema->table_names_for_columns($col_names));
    }

    public function test_primary_key_info() {

    }
}