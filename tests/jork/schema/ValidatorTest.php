<?php
use cyclone as cy;
use cyclone\jork\schema;
use cyclone\jork;

class Schema_ValidatorTest extends Kohana_Unittest_TestCase {

    public function testTableNameTest() {
        $schema = new schema\ModelSchema;
        $schema->class = 'TestModel';
        $result = schema\SchemaValidator::test_table_name(array($schema));
        $this->assertEquals(array(
            "class {$schema->class} does not have table"
        ), $result->error);
    }

    public function testPrimaryKeyTest() {
        $schema = new schema\ModelSchema;
        $schema->class = 'TestModel';
        $result = schema\SchemaValidator::test_primary_keys(array($schema));
        $this->assertEquals(array(
            "class {$schema->class} doesn't have primary key property"
        ), $result->error);
    }

    public function testCompClassTest() {
        $schema = new schema\ModelSchema;
        $schema->class = 'TestModel1';
        $schema->component(cy\JORK::component('dummy', 'NonExistentClass'));
        $rval = schema\ScemaValidator::test_comp_classes(array(
            'TestModel1' => $schema
        ));
        $this->assertEquals(array(
            "class NonExistentClass is not mapped but referenced using TestModel1::\$dummy"
        ), $rval->error);
    }

    public function testJoinColumnTest() {
        $schema1 = new schema\ModelSchema;
        $schema1->class = 'TestModel1';
        $schema1->component(cy\JORK::component('model2', 'TestModel2')
                ->type(cy\JORK::ONE_TO_ONE)->join_column('m1_id')
                ->inverse_join_column('m2_id'));
        $schema2 = new schema\ModelSchema;
        $schema2->class = 'TestModel2';
        $rval = schema\SchemaValidator::test_component_foreign_keys(array(
            'TestModel1' => $schema1
            , 'TestModel2' => $schema2
        ));
        $this->assertEquals(array(
            'local join column TestModel1::$m1_id doesn\'t exist',
            'inverse join column TestModel2::$m2_id doesn\'t exist'
        ), $rval->error);
    }
    
}