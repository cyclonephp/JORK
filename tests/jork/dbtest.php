<?php


abstract class JORK_DbTest extends Kohana_Unittest_TestCase {

    public function  setUp() {
        parent::setUp();
        $sql = file_get_contents(LIBPATH.'jork/tests/testdata.sql');
        try {
            DB::connector('jork_test')->connect();
            DB::executor('jork_test')->exec_custom($sql);
            DB::connector('jork_test')->commit();
        } catch (DB_Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->markTestSkipped('failed to establish database connection jork_test');
        }
    }

    public function  tearDown() {
        JORK_InstancePool::clear();
        parent::tearDown();
    }

}