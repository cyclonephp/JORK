<?php


class JORK_Model_CollectionTest extends Kohana_Unittest_TestCase {


    /**
     * @expectedException JORk_Exception
     */
    public function testForComponent() {
        $user = new Model_User;
        $coll = JORK_Model_Collection::for_component($user, 'posts');
        $this->assertInstanceOf('JORK_Model_Collection_OneToMany', $coll);

        $topic = new Model_Topic;
        $coll = JORK_Model_Collection::for_component($topic, 'categories');
        $this->assertInstanceOf('JORK_Model_Collection_ManyToMany', $coll);

        $coll = JORK_Model_Collection::for_component($topic, 'posts');
        $this->assertInstanceOf('JORK_Model_Collection_Reverse_ManyToOne', $coll);

        $cat = new Model_Category;
        $coll = JORK_Model_Collection::for_component($cat, 'topics');
        $this->assertInstanceOf('JORK_Model_Collection_Reverse_ManyToMany', $coll);

        $coll = JORK_Model_Collection::for_component($cat, 'moderator');
    }

    public function testIteration() {
        $result = JORK::from('Model_Topic')->with('posts')
                ->where('id', '=', DB::esc(1))->exec('jork_test');
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