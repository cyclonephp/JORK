<?php

use cyclone as cy;
use cyclone\db;

class JORK_Model_Collection_Reverse_ManyToManyTest extends JORK_DbTest {

    public function testAppend() {
        $cat = new Model_Category;
        $topic = new Model_Topic;
        $topic->id = 4;
        $cat->topics->append($topic);

        $this->assertEquals(1, count($cat->topics));
        $this->assertEquals($topic, $cat->topics[4]);
    }

    public function testDelete() {
        $cat = new Model_Category;
        $topic = new Model_Topic;
        $topic->id = 4;
        $cat->topics->append($topic);

        unset($cat->topics[4]);
        $this->assertEquals(0, count($cat->topics));

    }

    public function testSave() {
        $result = cy\JORK::from('Model_Category')->with('topics')
                ->where('id', '=', DB::esc(2))
                ->exec('jork_test');
        $category = $result[0];

        $result = cy\JORK::from('Model_Topic')->where('id', '=', DB::esc(3))
                ->exec('jork_test');
        $topic = $result[0];
        $this->assertEquals(2, count($category->topics));

        unset($category->topics[2]);
        $category->topics->append($topic);
        
        $category->topics->save();
        $result = cy\DB::select()->from('categories_topics')
                ->where('category_fk', '=', cy\DB::esc(2))
                ->exec('jork_test')->as_array();
        $this->assertEquals(2, count($result));
        $this->assertEquals(1, $result[0]['topic_fk']);
        $this->assertEquals(3, $result[1]['topic_fk']);
    }
    
}