<?php

use cyclone as cy;
use cyclone\db;


class JORK_Model_Collection_ManyToManyTest extends JORK_DbTest {

    public function testAppend() {
        $topic = new Model_Topic;
        $category = new Model_Category;
        $category->id = 3;
        $topic->categories->append($category);
        $this->assertEquals(1, count($topic->categories));
        $this->assertEquals($topic->categories[3], $category);
    }

    public function testDelete() {
        $topic = new Model_Topic;
        $category = new Model_Category;
        $category->id = 3;
        $topic->categories->append($category);
        $this->assertEquals(1, count($topic->categories));

        $topic->categories->delete($category);
        $this->assertEquals(0, count($topic->categories));
    }

    public function testSave() {
        $result = cy\JORK::from('Model_Topic')->with('categories')
                ->where('id', '=', cy\DB::esc(2))->exec('jork_test');
        $topic = $result[0];
        $this->assertInstanceOf('Model_Topic', $topic);
        
        $result = cy\JORK::from('Model_Category')->where('id', '=', cy\DB::esc(3))
                ->exec('jork_test');
        $category = $result[0];
        $this->assertInstanceOf('Model_Category', $category);

        $topic->categories->append($category);
        unset($topic->categories[2]);
        $topic->save();
        $result = cy\DB::select()->from('categories_topics')
                ->where('topicFk', '=', cy\DB::esc(2))->exec('jork_test')->as_array();
        $this->assertEquals(1, count($result));
        $this->assertEquals(3, $result[0]['categoryFk']);
        $this->assertEquals(4, count(cy\DB::select()->from('categories_topics')
                ->exec('jork_test')->as_array()));
    }
}