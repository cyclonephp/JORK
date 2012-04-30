<?php

use cyclone\jork;

/**
 * @author Bence Erős <crystal@cyclonephp.org>
 */
class JORK_CompositePKInstancePoolTest extends PHPUnit_Framework_TestCase {

    public function test_for_class() {
        $pool = jork\InstancePool::for_class('Model_CompPK');
        $this->assertInstanceOf('cyclone\\jork\\CompositePKInstancePool', $pool);
    }

    /**
     * @expectedException \cyclone\jork\Exception
     */
    public function test_append_get() {
        $pool = jork\InstancePool::for_class('Model_CompPK');
        $this->assertNull($pool[array(1, 2, 3, 4, 5)]);

        $obj = new Model_CompPK;
        $obj->pk_1(1)->pk_2(2)->pk_3(3)->pk_4(4)->pk_5(5);
        $pool->append($obj);
        $this->assertSame($obj, $pool[array(1, 2, 3, 4, 5)]);

        $obj = new Model_CompPK;
        $obj->pk_1(2)->pk_2(3)->pk_3(4)->pk_4(5)->pk_5(6);
        $pool->append($obj);
        $this->assertSame($obj, $pool[array(2, 3, 4, 5, 6)]);

        $obj = new Model_CompPK;
        $obj->pk_1(2)->pk_2(3)->pk_3(4)->pk_4(5)->pk_5(6);
        $pool->append($obj);
        $this->assertSame($obj, $pool[array(2, 3, 4, 5, 6)]);

        $obj = new Model_CompPK;
        $obj->pk_1(1)->pk_2(2)->pk_3(4)->pk_4(5)->pk_5(6);
        $pool->append($obj);
        $this->assertSame($obj, $pool[array(1, 2, 4, 5, 6)]);
        $pool->append(new Model_User());
    }

    /**
     * @expectedException \cyclone\jork\Exception
     */
    public function test_unset() {
        $pool = jork\InstancePool::for_class('Model_CompPK');
        $obj = new Model_CompPK();
        $obj->pk_1(1)->pk_2(2)->pk_3(3)->pk_4(4)->pk_5(5);
        $pool->append($obj);

        $obj = new Model_CompPK();
        $obj->pk_1(1)->pk_2(2)->pk_3(3)->pk_4(5)->pk_5(6);
        $pool->append($obj);

        unset($pool[array(1, 2, 3, 4, 5)]);
        $this->assertNull($pool[array(1, 2, 3, 4, 5)]);

        unset($pool[array(1, 2, 3, 5, 6)]);
        $this->assertNull($pool[array(1, 2, 3, 5, 6)]);

        unset($pool[array(1, 2, 3, 4, 5)]);
    }

    public function test_count() {
        $pool = jork\InstancePool::for_class('Model_CompPK');
        $this->assertEquals(0, count($pool));
        $obj = new Model_CompPK();
        $obj->pk_1(1)->pk_2(2)->pk_3(3)->pk_4(4)->pk_5(5);
        $pool->append($obj);
        $this->assertEquals(1, count($pool));

        $obj = new Model_CompPK();
        $obj->pk_1(1)->pk_2(2)->pk_3(3)->pk_4(5)->pk_5(6);
        $pool->append($obj);
        $this->assertEquals(2, count($pool));

        $obj = new Model_CompPK();
        $obj->pk_1(2)->pk_2(2)->pk_3(3)->pk_4(4)->pk_5(5);
        $pool->append($obj);
        $this->assertEquals(3, count($pool));

        $obj = new Model_CompPK();
        $obj->pk_1(2)->pk_2(2)->pk_3(3)->pk_4(4)->pk_5(5);
        $pool->append($obj);
        $this->assertEquals(3, count($pool));
    }

}
