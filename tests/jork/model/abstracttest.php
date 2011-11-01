<?php

use cyclone as cy;
use cyclone\db;


class JORK_Model_AbstractTest extends JORK_DbTest {

    public function testInst() {
        Model_User::inst();
    }

    public function testManyToOneFK() {
        $post = new Model_Post;
        $topic = new Model_Topic;
        $topic->id = 10;
        $post->topic = $topic;
        $this->assertEquals(10, $post->topic_fk);
    }

    public function testManyToOneReverseFK() {
        $post = new Model_Post;
        $user = new Model_User;
        $user->id = 6;
        $post->author = $user;
        $this->assertEquals($post->user_fk, 6);
    }

    public function testOneToOneFK() {
        $category = new Model_Category;
        $user = new Model_User;
        $user->id = 5;
        $category->moderator = $user;
        $this->assertEquals(5, $category->moderator_fk);
    }

    public function testOneToOneReverseFK() {
        $category = new Model_Category;
        $user = new Model_User;
        $user->id = 3;
        $user->moderated_category = $category;
        $this->assertEquals($category->moderator_fk, 3);
    }

    public function testOneToManyFK() {
        $user = new Model_User;
        $user->id = 34;
        $post = new Model_Post;
        //$this->markTestSkipped('not yet implemented');
        $user->posts->append($post);
        $this->assertEquals($post->user_fk, 34);
    }

    public function testOneToManyReverseFK() {
        $topic = new Model_Topic;
        $topic->id = 2;
        $post = new Model_Post;
        $topic->posts->append($post);
        $this->assertEquals(2, $post->topic_fk);
    }

    public function testPk() {
        $user = new Model_User();
        $user->id = 5;
        $this->assertEquals(5, $user->pk());
    }

    public function testSimpleSave() {
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

    public function testFKOneToManyUpdateOnSave() {
        $user = new Model_User;
        $post = new Model_Post;
        $user->posts->append($post);
        $user->name = 'foo bar';
        $user->save();
        $this->assertEquals(5, $user->id);
        $this->assertEquals(5, $post->user_fk);
    }

    public function testFKManyToOneReverseUpdateOnSave() {
        $topic = new Model_Topic;
        $topic->name = 'foo bar';
        $post = new Model_Post;
        $topic->posts->append($post);
        
        $topic->save();
        $this->assertEquals(5, $topic->id);
        $this->assertEquals(5, $post->topic_fk);
    }

    public function testUpdate() {
        $user = new Model_User;
        $user->id = 4;
        $user->name = 'foo';

        $post = new Model_Post;

        $user->posts->append($post);

        $user->save();

        $result = cy\DB::select()->from('t_users')->where('id', '=', cy\DB::esc(4))->exec('jork_test');
        foreach ($result as $row) {
            $this->assertEquals('foo', $row['name']);
        }

        $result = cy\DB::select()->from('t_posts')->where('id', '=', cy\DB::esc(5))->exec('jork_test');
        $this->assertEquals(1, count($result));
    }

    public function testDelete() {
       $result = cy\JORK::from('Model_Post')->where('id', '=', cy\DB::esc(4))
               ->exec('jork_test');
       $post = $result[0];
       $post->delete();
       $this->assertEquals(0, count(cy\DB::select()->from('t_posts')
               ->where('id', '=', cy\DB::esc(4))->exec('jork_test')));

       $user = new Model_User;
       $user->delete();
    }

    /**
     * Tests component behavior on entity deletion
     */
    public function testDeleteComponents() {
        $result = cy\JORK::from('Model_Topic')
                ->with('posts')
                ->where('id', '=', cy\DB::esc(1))
                ->exec('jork_test');
        $topic = $result[0];
        $topic->delete();
        $this->assertEquals(2, count(DB::select()->from('t_posts')
                ->where('topic_fk', 'is', NULL)->exec('jork_test')));

    }

    public function testSetNullFkForReverseOneToOne() {
        $result = cy\JORK::from('Model_User')->where('id', '=', cy\DB::esc(1))
                ->exec('jork_test');
        $user = $result[0];

        $user->delete();

        $this->assertEquals(2, count(
            cy\DB::select()->from('t_categories')->where('moderator_fk', 'is', NULL)
                ->exec('jork_test')->as_array()
        ));
    }

    public function testAtomicTypeCasts() {
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
    public function testAtomicTypeCheck() {
        $user = new Model_User;
        $user->moderated_category = new Model_Post;
    }

    public function testNoCascade() {
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

    public function testCascadeAll() {
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

    public function testCascadeSome() {
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

}