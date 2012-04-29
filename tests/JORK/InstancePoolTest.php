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
        $inst1 = jork\InstancePool::inst('class1');
        $inst2 = jork\InstancePool::inst('class2');
        $inst3 = jork\InstancePool::inst('class1');
        $this->assertSame($inst1, $inst3);
        $this->assertFalse($inst1 === $inst2);
    }

    public function test_add_get() {
        $pool = new jork\InstancePool('Model_User');
        $this->assertNull($pool->get_by_pk(array(1)));
        $user = new Model_User;
        $user->id = 1;
        $pool->add($user);
        $this->assertSame($user, $pool->get_by_pk(array(1)));
        $user2 = new Model_User;
        $user2->id = 2;
        $pool->add($user2);
        $this->assertSame($user2, $pool->get_by_pk(array(2)));
    }

    /**
     * @expectedException cyclone\jork\Exception
     */
    public function test_delete_by_pk() {
        $pool = new jork\InstancePool('Model_User');
        $user = new Model_User;
        $user->id = 2;
        $pool->add($user);
        $pool->delete_by_pk(array(2));
        $this->assertNull($pool->get_by_pk(array(2)));
        $pool->delete_by_pk(array(2));
    }

}
