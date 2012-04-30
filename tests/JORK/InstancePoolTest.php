<?php

use cyclone as cy;
use cyclone\jork;

/**
 * @author Bence Erős <crystal@cyclonephp.org>
 */
class JORK_InstancePoolTest extends PHPUnit_Framework_TestCase {

    public function tearDown() {
        parent::tearDown();
        jork\InstancePool::clear();
    }

    public function test_inst() {
        $inst1 = jork\InstancePool::inst('Model_User');
        $inst2 = jork\InstancePool::inst('Model_Post');
        $inst3 = jork\InstancePool::inst('Model_User');
        $this->assertSame($inst1, $inst3);
        $this->assertFalse($inst1 === $inst2);
    }

    /**
     * @expectedException \cyclone\jork\Exception
     */
    public function test_append_get() {
        $pool = jork\InstancePool::for_class('Model_User');
        $this->assertNull($pool[array(1)]);
        $user = new Model_User;
        $user->id = 1;
        $pool->append($user);
        $this->assertSame($user, $pool[array(1)]);
        $user2 = new Model_User;
        $user2->id = 2;
        $pool->append($user2);
        $this->assertSame($user2, $pool[array(2)]);
        $pool->append(new Model_Post);
    }

    /**
     * @expectedException cyclone\jork\Exception
     */
    public function test_delete_by_pk() {
        $pool = jork\InstancePool::for_class('Model_User');
        $user = new Model_User;
        $user->id = 2;
        $pool->append($user);
        unset($pool[array(2)]);
        $this->assertNull($pool[array(2)]);
        unset($pool[array(2)]);
    }

    public function test_count() {
        $user1 = new Model_User;
        $user1->id = 1;
        $pool = jork\InstancePool::for_class('Model_User');
        $this->assertEquals(0, count($pool));
        $pool->append($user1);
        $this->assertEquals(1, count($pool));

        $user2 = new Model_User;
        $user2->id = 2;
        $pool->append($user2);
        $this->assertEquals(2, count($pool));
    }

    public function test_iteration() {
        $users = array();
        $pool = jork\InstancePool::for_class('Model_User');
        for ($i = 1; $i < 5; ++$i) {
            $user = new Model_User;
            $user->id = $i;
            $users []= $user;
            $pool->append($user);
        }

        $idx = 0;
        foreach ($pool as $id => $user) {
            $this->assertEquals($users[$idx]->id, $id);
            $this->assertEquals($users[$idx], $user);
            ++$idx;
        }
        $this->assertEquals(4, $idx);
    }

}
