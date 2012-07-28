<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;


class JORK_Query_CacheTest extends Kohana_Unittest_TestCase {

    public function  setUp() {
        parent::setUp();
        query\Cache::clear_pool();
    }

    public function testInst() {
        $inst = query\Cache::inst('Model_User');
        $this->assertInstanceOf('cyclone\jork\query\Cache', $inst);
    }

    public function testInsertSQL() {
        $cache = query\Cache::inst('Model_User');
        $inserts = $cache->insert_sql();
        $this->assertEquals(
            array(
                't_users' => cy\DB::insert('t_users')->returning('userId'),
                'user_contact_info' => cy\DB::insert('user_contact_info')
            ),
            $inserts
        );
    }

    public function testUpdateSQL() {
        $updates = query\Cache::inst('Model_User')->update_sql();
        $this->assertEquals(array(
            't_users' => cy\DB::update('t_users'),
            'user_contact_info' => cy\DB::update('user_contact_info')
        ), $updates);
    }
}