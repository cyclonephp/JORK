<?php


class JORK_Model_Collection_Reverse_ManyToOneTest extends Kohana_Unittest_TestCase {

    public function testAppend() {
        $topic = new Model_Topic;
        $topic->id = 42;
        $post = new Model_Post;
        $post->id = 23;

        $topic->posts->append($post);
        $this->assertEquals(1, count($topic->posts));
        $this->assertEquals($post, $topic->posts[23]);
        $this->assertEquals($post->topic_fk, 42);
    }

    public function testDelete() {
        $topic = new Model_Topic;
        $topic->id = 42;
        $post = new Model_Post;
        $post->id = 23;

        $topic->posts->append($post);
        $this->assertEquals(1, count($topic->posts));
        unset($topic->posts[23]);
        $this->assertEquals($post->topic_fk, NULL);
        $this->assertEquals(0, count($topic->posts));
    }

    /**
     * Tests component behavior on entity deletion
     */
    public function testNotifyOwnerDeletion() {
        $result = JORK::from('Model_Topic')
                ->with('posts')
                ->where('id', '=', DB::esc(1))
                ->exec('jork_test');
        $topic = $result[0];

        $topic->delete();

        $this->assertEquals(2, count(DB::select()->from('t_posts')
                ->where('topic_fk', 'is', NULL)->exec('jork_test')));
    }
}