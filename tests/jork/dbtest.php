<?php

use cyclone as cy;
use cyclone\db;


abstract class JORK_DbTest extends Kohana_Unittest_TestCase {

    public function  setUp() {
        parent::setUp();
        $sql = file_get_contents(\cyclone\LIBPATH.'jork/tests/testdata.sql');
        try {
            cy\DB::connector('jork_test')->connect();
            cy\DB::executor('jork_test')->exec_custom($sql);
            cy\DB::connector('jork_test')->commit();
        } catch (db\Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->markTestSkipped('failed to establish database connection jork_test');
        }
    }

    public function  tearDown() {
        cy\jork\InstancePool::clear();
        parent::tearDown();
    }

}