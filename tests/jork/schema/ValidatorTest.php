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
        $rval = schema\SchemaValidator::test_comp_classes(array(
            'TestModel1' => $schema
        ));
        $this->assertEquals(array(
            "class NonExistentClass is not mapped but referenced using TestModel1::\$dummy"
        ), $rval->error);
    }

    public function testJoinColumnTest() {
        $schema1 = new schema\ModelSchema;
        $schema1->class = 'TestModel1';
        $schema1->primitive(cy\JORK::primitive('model2_fk', 'int'));
        $schema1->component(cy\JORK::component('model2', 'TestModel2')
                ->type(cy\JORK::ONE_TO_ONE)->join_column('m1_prop_nonexistent')
                ->inverse_join_column('m2_prop_nonexistent'));

        $schema1->component(cy\JORK::component('model_nontyped', 'TestModel2'));
        $schema1->component(cy\JORK::component('model2_1_N_no_join_col', 'TestModel2')
                ->type(cy\JORK::ONE_TO_MANY));

        $schema1->component(cy\JORK::component('model2_1_N_bad_join_col', 'TestModel2')
                ->type(cy\JORK::ONE_TO_MANY)->join_column('model1_fk_nonexistent'));

        $schema1->component(cy\JORK::component('model2_1_N_bad_inv_join_col', 'TestModel2')
                ->type(cy\JORK::ONE_TO_MANY)
                ->join_column('model1_fk')
                ->inverse_join_column('model2_fk_nonexistent'));

        $schema1->component(cy\JORK::component('model2_N_1_no_join_col', 'TestModel2')
                ->type(cy\JORK::MANY_TO_ONE));

        $schema1->component(cy\JORK::component('model2_N_1_bad_join_col', 'TestModel2')
                ->type(cy\JORK::MANY_TO_ONE)->join_column('model2_fk_nonexistent'));

        $schema1->component(cy\JORK::component('model2_N_1_bad_inv_join_col', 'TestModel2')
                ->type(cy\JORK::MANY_TO_ONE)->join_column('model2_fk')
                ->inverse_join_column('model1_fk_nonexistent'));

        $schema1->component(cy\JORK::component('model2_N_N', 'TestModel2')
                ->type(cy\JORK::MANY_TO_MANY)
                ->join_column('model2_fk_nonexistent')
                ->inverse_join_column('model1_fk_nonexistent'));

        $schema2 = new schema\ModelSchema;
        $schema2->class = 'TestModel2';
        $schema2->primitive(cy\JORK::primitive('model1_fk', 'int'));
        $rval = schema\SchemaValidator::test_component_foreign_keys(array(
            'TestModel1' => $schema1
            , 'TestModel2' => $schema2
        ));
        $this->assertEquals(array(
            'local join column TestModel1::$m1_prop_nonexistent doesn\'t exist'
            , 'inverse join column TestModel2::$m2_prop_nonexistent doesn\'t exist'
            , 'unknown component cardinality "" at TestModel1::$model_nontyped'
            , 'one-to-many component TestModel1::$model2_1_N_no_join_col doesn\'t have join column'
            , 'column TestModel2::$model1_fk_nonexistent doesn\'t exist but referenced by TestModel1::$model2_1_N_bad_join_col'
            , 'column TestModel1::$model2_fk_nonexistent doesn\'t exist but referenced by TestModel1::$model2_1_N_bad_inv_join_col'
            , 'many-to-one component TestModel1::$model2_N_1_no_join_col doesn\'t have join column'
            , 'column TestModel1::$model2_fk_nonexistent doesn\'t exist but referenced by TestModel1::$model2_N_1_bad_join_col'
            , 'column TestModel2::$model1_fk_nonexistent doesn\'t exist but referenced by TestModel1::$model2_N_1_bad_inv_join_col'
            , 'no join table defined for many-to-many component TestModel1::$model2_N_N'
            , 'column TestModel1::$model2_fk_nonexistent doesn\'t exist but referenced by TestModel1::$model2_N_N'
            , 'column TestModel2::$model1_fk_nonexistent doesn\'t exist but referenced by TestModel1::$model2_N_N'
        ), $rval->error);
    }

    public function testMappedByTest() {
        $schema1 = new schema\ModelSchema;
        $schema1->class = 'TestModel1';
        $schema1->component(cy\JORK::component('m2_prop', 'TestModel2')
                ->mapped_by('m1_prop_missing'));

        $schema2 = new schema\ModelSchema;
        $schema2->class = 'TestModel2';

        $rval = schema\SchemaValidator::test_mapped_by(array(
            'TestModel1' => $schema1
            , 'TestModel2' => $schema2
        ));
        $this->assertEquals(array(
            'property TestModel2::$m1_prop_missing doesn\'t exist but referenced by TestModel1::$m2_prop'
        ), $rval->error);
    }

    public function testPrimitivesTest() {
        $schema = new schema\ModelSchema;
        $schema->class = 'TestModel1';
        $schema->primitive(cy\JORK::primitive('prim1', 'x'));
        $rval = schema\SchemaValidator::test_primitives(array(
            'TestModel1' => $schema
        ));
        $this->assertEquals(array(
            'TestModel1::$prim1 has invalid type \'x\''
        ), $rval->error);
    }
    
}