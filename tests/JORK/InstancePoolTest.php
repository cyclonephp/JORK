<?php

use cyclone as cy;
use cyclone\jork;

/**
 * @author Bence Erős <crystal@cyclonephp.org>
 */
class JORK_InstancePoolTest extends Kohana_Unittest_TestCase {

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

}
