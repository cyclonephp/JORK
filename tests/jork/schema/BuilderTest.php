<?php
use cyclone as cy;


use cyclone\jork;
use cyclone\jork\schema;
use cyclone\db;

class Schema_BuilderTest extends Kohana_Unittest_TestCase {

    private $_default_types = array(
        'int' => 'INT',
        'integer' => 'INT',
        'string' => 'TEXT',
        'float' => 'FLOAT',
        'bool' => 'SMALLINT',
        'boolean' => 'SMALLINT'
    );

    public function testBasic() {
        $schema = new schema\ModelSchema;
        $schema->table('tbl')
                ->primitive(cy\JORK::primitive('id', 'int')->primary_key())
                ->primitive(cy\JORK::primitive('name', 'string'))
                ->primitive(cy\JORK::primitive('degree', 'float'));
        $schema->class = 'TestModel';
        $rval = schema\SchemaBuilder::factory(array(
            'TestModel' => $schema
        ), $this->_default_types)->generate_db_schema();
        $this->assertEquals(1, count($rval));
        $this->assertArrayHasKey('tbl', $rval);
        $tbl = $rval['tbl'];
        $this->assertEquals(3, count($tbl->columns));
        $id_col = $tbl->columns[0];
        $this->assertEquals($tbl, $id_col->table);
        $this->assertEquals('id', $id_col->name);
        $this->assertEquals('INT', $id_col->type);
        $this->assertTrue($id_col->is_primary);

        $name_col = $tbl->columns[1];
        $this->assertEquals('name', $name_col->name);
        $this->assertEquals('TEXT', $name_col->type);
        $this->assertFalse($name_col->is_primary);

        $degree_col = $tbl->columns[2];
        $this->assertEquals('degree', $degree_col->name);
        $this->assertEquals('FLOAT', $degree_col->type);
        $this->assertFalse($degree_col->is_primary);
    }

    public function testSecondaryTable() {
        $schema = new schema\ModelSchema;
        $schema->class = 'TestModel1';
        $schema->table('tbl')
                ->secondary_table(cy\JORK::secondary_table('sec_tbl', 'tbl_id', 'tbl_fk'))
                ->primitive(cy\JORK::primitive('id', 'int')->column('tbl_id'))
                ->primitive(cy\JORK::primitive('detail', 'string')
                        ->table('sec_tbl'));
        $rval = schema\SchemaBuilder::factory(array(
            'TestModel1' => $schema
        ), $this->_default_types)->generate_db_schema();
        $this->assertEquals(2, count($rval));
        $this->assertArrayHasKey('tbl', $rval);
        $this->assertArrayHasKey('sec_tbl', $rval);
        $tbl = $rval['tbl'];

        $id_col = $tbl->columns[0];
        $this->assertEquals('id', $id_col->name);
        $this->assertEquals('INT', $id_col->type);

        $sec_tbl = $rval['sec_tbl'];
        $sec_id_col = $sec_tbl->columns[0];
        $this->assertEquals('tbl_fk', $sec_id_col->name);
        $this->assertEquals('INT', $sec_id_col->type);
    }

    public function testOneToManyForeignKeys() {
        $schema1 = new schema\ModelSchema;
        $schema1->class = 'TestModel1';
        $schema1->table('tbl_1')
                ->primitive(cy\JORK::primitive('id', 'int')->primary_key())
                ->component(cy\JORK::component('model2coll', 'TestModel2')
                ->type(cy\JORK::ONE_TO_MANY)->join_column('model1_fk'));

        $schema2 = new schema\ModelSchema;
        $schema2->class = 'TestModel2';
        $schema2->table('tbl_2')
                ->primitive(cy\JORK::primitive('id', 'int')->primary_key())
                ->primitive(cy\JORK::primitive('model1_fk', 'int'))
                ->component(cy\JORK::component('model1', 'TestModel1')
                        ->mapped_by('model2coll'));
        
        $rval = schema\SchemaBuilder::factory(array(
            'TestModel1' => $schema1,
            'TestModel2' => $schema2
        ), $this->_default_types)->generate_db_schema();
        $this->assertEquals(2, count($rval));
        $tbl_1 = $rval['tbl_1'];
        $tbl_2 = $rval['tbl_2'];
        $this->assertEquals(1, count($tbl_2->foreign_keys));
        $fk = $tbl_2->foreign_keys[0];
        $this->assertEquals($tbl_2, $fk->local_table);
        $this->assertEquals(array($tbl_2->get_column('model1_fk')), $fk->local_columns);
        $this->assertEquals($tbl_1, $fk->foreign_table);
        $this->assertEquals(array($tbl_1->get_column('id')), $fk->foreign_columns);
    }
    
}