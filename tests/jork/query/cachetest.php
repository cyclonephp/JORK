<?php


class JORK_Query_CacheTest extends Kohana_Unittest_TestCase {

    public function  setUp() {
        parent::setUp();
        JORK_Query_Cache::clear_pool();
    }

    public function testInst() {
        $inst = JORK_Query_Cache::inst('Model_User');
        $this->assertInstanceOf('JORK_Query_Cache', $inst);
    }

    public function testInsertSQL() {
        $cache = JORK_Query_Cache::inst('Model_User');
        $inserts = $cache->insert_sql();
        $this->assertEquals(
            array(
                't_users' => DB::insert('t_users'),
                'user_contact_info' => DB::insert('user_contact_info')
            ),
            $inserts
        );
    }

    public function testUpdateSQL() {
        $updates = JORK_Query_Cache::inst('Model_User')->update_sql();
        $this->assertEquals(array(
            't_users' => DB::update('t_users'),
            'user_contact_info' => DB::update('user_contact_info')
        ), $updates);
    }
}