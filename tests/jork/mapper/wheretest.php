<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;


class JORK_Mapper_WhereTest extends Kohana_Unittest_TestCase {

    public function testWhereImpl() {
        $jork_query = cy\JORK::from('Model_User')
                ->where('posts.modinfo.created_at', '>', DB::expr('2010-11-11'))
                ->where('exists', 'name')
                ->where('avg({id}) > x');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->where_conditions, array(
            new db\BinaryExpression('t_posts_0.created_at', '>'
                    , cy\DB::expr('2010-11-11')),
            new db\UnaryExpression('exists', 't_users_0.name'),
            new db\CustomExpression('avg(t_users_0.id) > x')
        ));
    }

    public function testWhere() {
        $jork_query = cy\JORK::from('Model_User user')
                ->where('user.posts.modinfo.created_at', '>', cy\DB::expr('2010-11-11'))
                ->where('exists', 'user.name')
                ->where('avg({user.id}) > x');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_users', 't_users_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('user_contact_info', 'user_contact_info_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_users_0.id', '=', 'user_contact_info_0.user_fk')
                )
            ),
            array(
                'table' => array('t_posts', 't_posts_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_users_0.id', '=', 't_posts_0.user_fk')
                )
            )
        ));
        $this->assertEquals($db_query->where_conditions, array(
            new db\BinaryExpression('t_posts_0.created_at', '>', cy\DB::expr('2010-11-11')),
            new db\UnaryExpression('exists', 't_users_0.name'),
            new db\CustomExpression('avg(t_users_0.id) > x')
        ));
    }

    public function testWhereObj() {
        $jork_query = cy\JORK::from('Model_Post post')
            ->where('post.author', '=', 'post.topic.modinfo.creator');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_posts', 't_posts_0')
        ));
        $this->assertEquals($db_query->where_conditions, array(
            new db\BinaryExpression('t_users_0.id', '=', 't_users_1.id')
        ));
        $this->assertEquals(array(
           array(
               'table' => array('t_users', 't_users_0'),
               'type' => 'LEFT',
               'conditions' => array(
                   new db\BinaryExpression('t_posts_0.user_fk', '=', 't_users_0.id')
               )
           ),
           array(
               'table' => array('t_topics', 't_topics_0'),
               'type' => 'LEFT',
               'conditions' => array(
                   new db\BinaryExpression('t_posts_0.topic_fk', '=', 't_topics_0.id')
               )
           ),
           array(
               'table' => array('t_users', 't_users_1'),
               'type' => 'LEFT',
               'conditions' => array(
                   new db\BinaryExpression('t_topics_0.creator_fk', '=', 't_users_1.id')
               )
           ),
        ), $db_query->joins);
        $this->assertEquals(array(
            new db\BinaryExpression('t_users_0.id', '=', 't_users_1.id')
        ), $db_query->where_conditions);
    }

    public function testWhereObjImpl() {
        $jork_query = cy\JORK::from('Model_Post')
            ->where('author', '=', 'topic.modinfo.creator');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_posts', 't_posts_0')
        ));
        $this->assertEquals(array(
            new db\BinaryExpression('t_users_0.id', '=', 't_users_1.id')
        ), $db_query->where_conditions);
        $this->assertEquals(array(
           array(
               'table' => array('t_users', 't_users_0'),
               'type' => 'LEFT',
               'conditions' => array(
                   new db\BinaryExpression('t_posts_0.user_fk', '=', 't_users_0.id')
               )
           ),
           array(
               'table' => array('t_topics', 't_topics_0'),
               'type' => 'LEFT',
               'conditions' => array(
                   new db\BinaryExpression('t_posts_0.topic_fk', '=', 't_topics_0.id')
               )
           ),
           array(
               'table' => array('t_users', 't_users_1'),
               'type' => 'LEFT',
               'conditions' => array(
                   new db\BinaryExpression('t_topics_0.creator_fk', '=', 't_users_1.id')
               )
           )
        ), $db_query->joins);
        $this->assertEquals(array(
            new db\BinaryExpression('t_users_0.id', '=', 't_users_1.id')
        ), $db_query->where_conditions);
    }


    /**
     * @expectedException cyclone\jork\Exception
     * @expectedExceptionMessage unable to check equality of class 'Model_User' with class 'Model_Topic'
     */
    public function testWhereObjFailOnClassDifference() {
        $jork_query = cy\JORK::from('Model_Post post')
            ->where('post.author', '=', 'post.topic');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        $mapper->map();
    }

    public function testWhereObjParam() {
        $topic = new Model_Topic;
        $topic->id = 14;

        $jork_query = cy\JORK::from('Model_Post')->where('topic', '=', $topic);
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(array(
            array(
                'table' => array('t_topics', 't_topics_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_posts_0.topic_fk', '=', 't_topics_0.id')
                )
            )
        ), $db_query->joins);
        $this->assertEquals(array(
            new db\BinaryExpression('t_topics_0.id', '=', 14)
        ), $db_query->where_conditions);
    }
}