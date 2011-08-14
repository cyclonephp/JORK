<?php


class JORK_Mapping_Test extends Kohana_Unittest_TestCase {

    public function testMapEntity() {
//        $jork_query = JORK::from('Model_User user');
//        $mapper = new JORK_Mapper_Select($jork_query);
//        list($db_query, $metadata) = $mapper->map();
//        $this->assertEquals($db_query->tables
//                , array(array('t_users', 't_users_1')));
    }

//    public function testMapJoin() {
//        $jork_query = JORK::from('Model_User user')
//            ->join('posts.topic user_topics')
//            ->join('posts.topic.creator topic_creator')
//            ->join('posts.topic.categories cats');
//        $select_mapper = new JORK_Mapper_Select($jork_query);
//        list($db_query, $comp_mapper) = $select_mapper->map();
//        //print_r($db_query->joins);
//        $this->assertEquals($db_query->joins, array(
//            array(
//                'table' => array('t_posts', 't_posts_1')
//                , 'type' => 'INNER'
//                , 'conditions' => array(
//                    array('t_users_1.id', '=', 't_posts_1.user_fk')
//                )
//            ),
//            array(
//                'table' => array('t_topics', 't_topics_1')
//                , 'type' => 'INNER'
//                , 'conditions' => array(
//                    array('t_posts_1.topic_fk', '=', 't_topics_1.id')
//                )
//            ),
//            array(
//                'table' => array('t_users', 't_users_2')
//                , 'type' => 'INNER'
//                , 'conditions' => array(
//                    array('t_topics_1.created_by', '=', 't_users_2.id')
//                )
//            ),
//            array(
//                'table' => array('categories_topics', 'categories_topics_1')
//                , 'type' => 'INNER'
//                , 'conditions' => array(
//                    array('t_topics_1.id', '=', 'categories_topics_1.topic_fk')
//                )
//            ),
//            array(
//                'table' => array('t_categories', 't_categories_1')
//                , 'type' => 'INNER'
//                , 'conditions' => array(
//                    array('categories_topics_1.category_fk', '=', 't_categories_1.id')
//                )
//            )
//        ));
//    }

}