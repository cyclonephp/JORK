<?php

use cyclone\DB;
use cyclone\FileSystem;
use cyclone\db\Exception;
use cyclone\jork\InstancePool;

require_once realpath(__DIR__) . '/MapperTest.php';

abstract class JORK_DbTest extends JORK_MapperTest {

    public function  setUp() {
        parent::setUp();
        $sql = file_get_contents(FileSystem::get_default()->get_root_path('jork') . 'tests/testdata.sql');
        try {
            DB::connector('jork_test')->connect();
            DB::connector('jork_test')->start_transaction();
            DB::executor('jork_test')->exec_custom($sql);
            DB::connector('jork_test')->commit();
        } catch (Exception $ex) {
            echo $ex->getMessage() . PHP_EOL;
            $this->markTestSkipped('failed to establish database connection jork_test');
        }
    }

    public function  tearDown() {
        InstancePool::clear();
        parent::tearDown();
    }

}