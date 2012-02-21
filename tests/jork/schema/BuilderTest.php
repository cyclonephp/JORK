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
        $rval = schema\SchemaBuilder::inst()->generate_db_schema(array(
            'TestModel' => $schema
        ), $this->_default_types);
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
    
}