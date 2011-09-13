<?php

use cyclone as cy;
use cyclone\db;


class JORK_Model_Collection_OneToManyTest extends JORK_DbTest {

    public function testAppend() {
        $user = new Model_User;
        $user->id = 15;
        $post = new Model_Post;
        $user->posts->append($post);
        $this->assertEquals(15, $post->user_fk);
        $this->assertEquals(1, count($user->posts));
        //$this->assertEquals($user, $post->author);
    }

    /**
     * @expectedException cyclone\jork\Exception
     * @expectedExceptionMessage the items of this collection should be Model_Post instances
     */
    public function testTypeSafety() {
        $topic = new Model_Topic;
        $user = new Model_User;
        $user->posts->append($topic);
    }


    public function testDelete() {
        $user = new Model_User;
        $user->id = 15;
        $post = new Model_Post;
        $post->id = 12;
        $user->posts->append($post);

        unset($user->posts[12]);
        $this->assertEquals(0, count($user->posts));
    }

    public function testSave() {
        $user = new Model_User;
        $user->name = 'foo bar';
        $post = new Model_Post;
        $user->posts->append($post);
        $user->save();
        $this->assertEquals(5, $user->id);
        $this->assertEquals(5, $post->id);
        $this->assertEquals(5, $post->user_fk);

        $result = cy\DB::select()->from('t_posts')->where('id', '=', cy\DB::esc(5))->exec('jork_test');
        $this->assertEquals(1, count($result));

        $user->posts->delete($post);
        $user->posts->save();

        $result = cy\DB::select()->from('t_posts')->where('id', '=', cy\DB::esc(5))->exec('jork_test');
        foreach ($result as $row) {
            $this->assertEquals(NULL, $row['user_fk']);
        }
    }

    public function testNotifyOwnerDeletion() {
        Model_User::inst()->delete_by_pk(1);

        $this->assertEquals(2, count(cy\DB::select()->from('t_posts')
                ->where('user_fk', 'IS', NULL)
                ->exec('jork_test')->as_array()));
    }
}
