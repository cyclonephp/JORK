<?php

use cyclone as cy;
use cyclone\jork;
use cyclone\jork\model;

class JORK_Model_Collection_SortingTest extends Kohana_Unittest_TestCase {

    public function testSortByPK() {
        $user = new Model_User;
        $posts_coll = model\collection\AbstractCollection::for_component($user, 'posts');

        $post_ids = array(2, 3, 1);
        foreach ($post_ids as $id) {
            $post = new Model_Post;
            $post->id = $id;
            $posts_coll->append($post);
        }

        $sorted_coll = $posts_coll->sort();
        $this->assertEquals($posts_coll, $sorted_coll);

        $expected_post_ids = array(1, 2, 3);
        $idx = 0;
        foreach ($sorted_coll as $id => $post) {
            $this->assertEquals($expected_post_ids[$idx], $post->id);
            $this->assertEquals($expected_post_ids[$idx], $id);
            ++$idx;
        }

        $sorted_coll = $posts_coll->sort(cy\JORK::SORT_REVERSE);
        $this->assertEquals($posts_coll, $sorted_coll);

        $expected_post_ids = array(3, 2, 1);
        $idx = 0;
        foreach ($sorted_coll as $id => $post) {
            $this->assertEquals($expected_post_ids[$idx], $post->id);
            $this->assertEquals($expected_post_ids[$idx], $id);
            ++$idx;
        }
    }

    public function testNaturalOrdering() {
        $category = new Model_Category;
        $topics_coll = model\collection\AbstractCollection::for_component($category, 'topics');

        $topic_names = array('B', 'C', 'A');
        foreach ($topic_names as $name) {
            $topic = new Model_Topic;
            $topic->name = $name;
            $topics_coll->append($topic);
        }

        $sorted_coll = $topics_coll->sort();
        $this->assertEquals($sorted_coll, $topics_coll);

        $expected_topic_names = array('A', 'B', 'C');
        $idx = 0;
        foreach ($sorted_coll as $topic) {
            $this->assertEquals($expected_topic_names[$idx], $topic->name);
            ++$idx;
        }

        $sorted_coll = $topics_coll->sort(cy\JORK::SORT_REVERSE);
        $this->assertEquals($sorted_coll, $topics_coll);

        $expected_topic_names = array('C', 'B', 'A');
        $idx = 0;
        foreach ($sorted_coll as $topic) {
            $this->assertEquals($expected_topic_names[$idx], $topic->name);
            ++$idx;
        }
    }

    public function testUserComparator() {
        $category = new Model_Category;
        $topics_coll = model\collection\AbstractCollection::for_component($category, 'topics');

        $topic_names = array('B', 'C', 'A');
        foreach ($topic_names as $name) {
            $topic = new Model_Topic;
            $topic->name = $name;
            $topics_coll->append($topic);
        }

        $cmp = function($topic_a, $topic_b) {
            if ($topic_a->name < $topic_b->name)
                return -1;

            if ($topic_a->name > $topic_b->name)
                return 1;

            return 0;
        };

        $sorted_coll = $topics_coll->sort(cy\JORK::SORT_REGULAR, $cmp);
        $this->assertEquals($sorted_coll, $topics_coll);

        $expected_topic_names = array('A', 'B', 'C');
        $idx = 0;
        foreach ($sorted_coll as $topic) {
            $this->assertEquals($expected_topic_names[$idx], $topic->name);
            ++$idx;
        }

        $sorted_coll = $topics_coll->sort(cy\JORK::SORT_REVERSE, $cmp);
        $this->assertEquals($sorted_coll, $topics_coll);

        $expected_topic_names = array('C', 'B', 'A');
        $idx = 0;
        foreach ($sorted_coll as $topic) {
            $this->assertEquals($expected_topic_names[$idx], $topic->name);
            ++$idx;
        }
    }
    
}