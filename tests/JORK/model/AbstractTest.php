<?php

use cyclone as cy;
use cyclone\db;

require_once __DIR__ . '../../DBTest.php';

class JORK_Model_AbstractTest extends JORK_DbTest {

    public function test_many_to_one_fk() {
        $post = new Model_Post;
        $topic = new Model_Topic;
        $topic->id = 10;
        $post->topic = $topic;
        $this->assertEquals(10, $post->topic_fk);
    }

    public function test_many_to_one_reverse_fk() {
        $post = new Model_Post;
        $user = new Model_User;
        $user->id = 6;
        $post->author = $user;
        $this->assertEquals($post->user_fk, 6);
    }

    public function test_one_to_one_fk() {
        $category = new Model_Category;
        $user = new Model_User;
        $user->id = 5;
        $category->moderator = $user;
        $this->assertEquals(5, $category->moderator_fk);
    }

    public function test_one_to_one_reverse_fk() {
        $category = new Model_Category;
        $user = new Model_User;
        $user->id = 3;
        $user->moderated_category = $category;
        $this->assertEquals($category->moderator_fk, 3);
    }

    public function test_one_to_many_fk() {
        $user = new Model_User;
        $user->id = 34;
        $post = new Model_Post;
        //$this->markTestSkipped('not yet implemented');
        $user->posts->append($post);
        $this->assertEquals($post->user_fk, 34);
    }

    public function test_one_to_many_reverse_fk() {
        $topic = new Model_Topic;
        $topic->id = 2;
        $post = new Model_Post;
        $topic->posts->append($post);
        $this->assertEquals(2, $post->topic_fk);
    }

    public function test_pk() {
        $user = new Model_User();
        $user->id = 5;
        $this->assertEquals(array(5), $user->pk());
    }

    public function test_simple_save() {
        $user = new Model_User;
        $user->name = 'foo bar';
        $user->save();
        $this->assertEquals(5, $user->id);
        $result = cy\JORK::from('Model_User')->where('id', '=', cy\DB::esc(5))
                ->exec('jork_test');
        foreach ($result as $user) {
            $this->assertEquals(5, $user->id);
            $this->assertEquals('foo bar', $user->name);
        }
    }

    public function test_fk_one_to_many_update_on_save() {
        $user = new Model_User;
        $post = new Model_Post;
        $user->posts->append($post);
        $user->name = 'foo bar';
        $user->save();
        $this->assertEquals(5, $user->id);
        $this->assertEquals(5, $post->user_fk);
    }

    public function test_fk_many_to_one_reverse_update_on_save() {
        $topic = new Model_Topic;
        $topic->name = 'foo bar';
        $post = new Model_Post;
        $topic->posts->append($post);
        
        $topic->save();
        $this->assertEquals(5, $topic->id);
        $this->assertEquals(5, $post->topic_fk);
    }

    public function test_update() {
        $user = new Model_User;
        $user->id = 4;
        $user->name = 'foo';

        $post = new Model_Post;

        $user->posts->append($post);

        $user->save();

        $result = cy\DB::select()->from('t_users')->where('userId', '=', cy\DB::esc(4))->exec('jork_test');
        foreach ($result as $row) {
            $this->assertEquals('foo', $row['name']);
        }

        $result = cy\DB::select()->from('t_posts')->where('postId', '=', cy\DB::esc(5))->exec('jork_test');
        $this->assertEquals(1, count($result));
    }

    public function test_delete() {
       $result = cy\JORK::from('Model_Post')->where('id', '=', cy\DB::esc(4))
               ->exec('jork_test');
       $post = $result[0];
       $post->delete();
       $this->assertEquals(0, count(cy\DB::select()->from('t_posts')
               ->where('postId', '=', cy\DB::esc(4))->exec('jork_test')));

       $user = new Model_User;
       $user->delete();
    }

    /**
     * Tests component behavior on entity deletion
     */
    public function test_delete_components() {
        $result = cy\JORK::from('Model_Topic')
                ->with('posts')
                ->where('id', '=', cy\DB::esc(1))
                ->exec('jork_test');
        $topic = $result[0];
        $topic->delete();
        $this->assertEquals(2, count(DB::select()->from('t_posts')
                ->where('topicFk', 'is', NULL)->exec('jork_test')));

    }

    public function test_set_null_fk_for_reverse_one_to_one() {
        $result = cy\JORK::from('Model_User')->where('id', '=', cy\DB::esc(1))
                ->exec('jork_test');
        $user = $result[0];

        $user->delete();

        $this->assertEquals(2, count(
            cy\DB::select()->from('t_categories')->where('moderatorFk', 'is', NULL)
                ->exec('jork_test')->as_array()
        ));
    }

    public function test_atomic_type_casts() {
        $user = new Model_User;
        $user->id = 1;
        $this->assertInternalType('int', $user->id);
        $user->id = '145';
        $this->assertInternalType('int', $user->id);
        $user->name = 256;
        $this->assertEquals(256, $user->name);
        $this->assertInternalType('string', $user->name);

        $result = cy\JORK::from('Model_User')->where('id', '=', cy\DB::esc(1))
                ->exec('jork_test');
        $user = $result[0];
        $this->assertInternalType('int', $user->id);
        $this->assertInternalType('string', $user->name);
    }

    /**
     * @expectedException cyclone\jork\Exception
     */
    public function test_atomic_type_check() {
        $user = new Model_User;
        $user->moderated_category = new Model_Post;
    }

    public function test_no_cascade() {
        $topic = new Model_Topic;
        $topic->name = 'topic05';
        for ($i = 5; $i < 10; ++$i) {
            $post = new Model_Post;
            $post->name = 'post '.$i;
            $topic->posts->append($post);
        }
        $topic->save(FALSE);
        $this->assertEquals(5, $topic->id);
        $topics = cy\DB::select()->from('t_topics')->exec('jork_test');
        $this->assertEquals(5, count($topics));

        $posts = cy\DB::select()->from('t_posts')->exec('jork_test');
        $this->assertEquals(4, count($posts));
    }

    public function test_cascade_all() {
        $topic = new Model_Topic;
        $topic->name = 'topic05';
        for ($i = 5; $i < 10; ++$i) {
            $post = new Model_Post;
            $post->name = 'post '.$i;
            $topic->posts->append($post);
        }
        $topic->save(TRUE);
        $this->assertEquals(5, $topic->id);
        $topics = cy\DB::select()->from('t_topics')->exec('jork_test');
        $this->assertEquals(5, count($topics));

        $posts = cy\DB::select()->from('t_posts')->exec('jork_test');
        $this->assertEquals(9, count($posts));
    }

    public function test_cascade_some() {
        $topic = new Model_Topic;
        $topic->name = 'topic05';
        for ($i = 5; $i < 10; ++$i) {
            $post = new Model_Post;
            $post->name = 'post '.$i;
            $topic->posts->append($post);
        }

        $category = new Model_Category;
        $category->name = 'wont be saved';

        $topic->save(array('posts'));
        $this->assertEquals(5, $topic->id);
        $topics = cy\DB::select()->from('t_topics')->exec('jork_test');
        $this->assertEquals(5, count($topics));

        $posts = cy\DB::select()->from('t_posts')->exec('jork_test');
        $this->assertEquals(9, count($posts));

        $categories = cy\DB::select()->from('t_categories')->exec('jork_test');
        $this->assertEquals(3, count($categories));
    }

    public function test_lazy_loading_primitive() {
        $result = cy\JORK::select('u{id}')->from('Model_User u')
                ->where('u.id', '=', cy\DB::esc(1))->exec('jork_test')->as_array();
        $user = $result[0]['u'];
        $this->assertEquals(1, $user->id);
        $this->assertNull($user->name);
        $this->assertEquals('user1', $user->name());
    }

    public function test_lazy_loading_component() {
        $result = cy\JORK::from('Model_Post')
            ->where('id', '=', cy\DB::esc(1))->exec('jork_test');

        $post = $result[0];

        $this->assertEquals(1, $post->id);
        $this->assertNull($post->topic);
        $this->assertInstanceOf('Model_Topic', $post->topic());
        $this->assertEquals(1, $post->topic->id);

        $topic = Model_Topic::get(2);
        $this->assertEquals(0, count($topic->posts));
        $this->assertEquals(1, count($topic->posts()));
        $this->assertEquals(3, $topic->posts[3]->id);
    }

    public function test_populate() {
        $user = new Model_User;
        $user->populate(array(
            'id' => 1,
            'name' => 'user #1',
            'email' => 'user1@example.com',
            'posts' => array(
                array(
                    'id' => 2,
                    'name' => 'post #2',
                    'topic' => array(
                        'id' => 3,
                        'name' => 'topic #3'
                    ),
                    'modinfo' => array(
                        'creator_fk' => 2
                    )
                )
            )
        ));
        $this->assertEquals(1, $user->id);
        $this->assertEquals('user #1', $user->name);
        $this->assertInstanceOf('Model_Post', $user->posts[2]);
        $post = $user->posts[2];
        $this->assertEquals(2, $post->id);
        $this->assertEquals('post #2', $post->name);
        $this->assertInstanceOf('Model_ModInfo', $post->modinfo);
        $this->assertEquals(2, $post->modinfo->creator_fk);

        $this->assertInstanceOf('Model_Topic', $post->topic);
        $this->assertEquals(3, $post->topic->id);
        $this->assertEquals('topic #3', $post->topic->name);
    }

}