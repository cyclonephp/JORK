<?php

use cyclone as cy;


class JORK_Model_CollectionTest extends Kohana_Unittest_TestCase {


    /**
     * @expectedException cyclone\jork\Exception
     */
    public function test_for_component() {
        $user = new Model_User;
        $coll = cy\jork\model\collection\AbstractCollection::for_component($user, 'posts');
        $this->assertInstanceOf('cyclone\\jork\\model\\collection\\OneToManyCollection', $coll);

        $topic = new Model_Topic;
        $coll = cy\jork\model\collection\AbstractCollection::for_component($topic, 'categories');
        $this->assertInstanceOf('cyclone\\jork\\model\\collection\\ManyToManyCollection', $coll);

        $coll = cy\jork\model\collection\AbstractCollection::for_component($topic, 'posts');
        $this->assertInstanceOf('cyclone\\jork\\model\\collection\\reverse\\ManyToOneCollection', $coll);

        $cat = new Model_Category;
        $coll = cy\jork\model\collection\AbstractCollection::for_component($cat, 'topics');
        $this->assertInstanceOf('cyclone\\jork\\model\\collection\\reverse\\ManyToManyCollection', $coll);

        $coll = cy\jork\model\collection\AbstractCollection::for_component($cat, 'moderator');
    }

    public function test_iteration() {
        $result = cy\JORK::from('Model_Topic')->with('posts')
                ->where('id', '=', cy\DB::esc(1))->exec('jork_test');
        $topic = $result[0];

        $counter = 0;
        foreach ($topic->posts as $post) {
            ++$counter;
            $this->assertInstanceOf('Model_Post', $post);
            $this->assertEquals($counter, $post->id);
            $this->assertEquals("t 01 p 0$counter", $post->name);
        }
        $this->assertEquals(2, $counter);
    }

}