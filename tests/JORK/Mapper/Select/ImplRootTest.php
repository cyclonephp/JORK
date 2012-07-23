<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;

require_once __DIR__ . '/../../MapperTest.php';

class JORK_Mapper_Select_ImplRootTest extends JORK_MapperTest {

    public function setUp() {
        parent::setUp();
        $this->load_schemas('basic');
    }


    public function testFrom() {
        $jork_query = cy\JORK::from('Model_User');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->columns, array(
            array('t_users_0.userId', 't_users_0_userId'), array('t_users_0.name', 't_users_0_name')
            , array('t_users_0.password', 't_users_0_password')
            , array('t_users_0.createdAt', 't_users_0_createdAt')
            , array('user_contact_info_0.email', 'user_contact_info_0_email')
            , array('user_contact_info_0.phoneNum', 'user_contact_info_0_phoneNum')
        ));
        $this->assertEquals(array(
            array('column' => 't_users_0.name', 'direction' => 'asc')
        ), $db_query->order_by);
        $this->assertEquals($db_query->tables, array(
            array('t_users', 't_users_0'),
        ));
    }

    public function testWith() {
        $jork_query = cy\JORK::from('Model_Topic')->with('posts');
        $db_query = cy\DB::select(
            array('t_posts_0.postId', 't_posts_0_postId'),
            array('t_posts_0.name', 't_posts_0_name'),
            array('t_posts_0.topicFk', 't_posts_0_topicFk'),
            array('t_posts_0.userFk', 't_posts_0_userFk'),
            array('t_topics_0.topicId', 't_topics_0_topicId'),
            array('t_topics_0.name', 't_topics_0_name')
        )->from(array('t_topics', 't_topics_0'))
            ->left_join(array('t_posts', 't_posts_0'))
            ->on('t_topics_0.topicId', '=', 't_posts_0.topicFk')
        ->order_by('t_topics_0.name', 'asc');
        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testSelect() {
        $jork_query = new query\SelectQuery;
        $jork_query->from('Model_Category');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(array(
            array('t_categories_0.categoryId', 't_categories_0_categoryId')
            , array('t_categories_0.name', 't_categories_0_name')
            , array('t_categories_0.moderatorFk', 't_categories_0_moderatorFk')
        ), $db_query->columns);
        $this->assertEquals($db_query->tables, array(
            array('t_categories', 't_categories_0')
        ));
        $jork_query->select('id');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->columns, array(
            array('t_categories_0.categoryId', 't_categories_0_categoryId')
        ));
    }

    public function testSelectPropChain() {
        $jork_query = new query\SelectQuery;
        $jork_query->select('topic')->from('Model_Post');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->columns, array(
            array('t_topics_0.topicId', 't_topics_0_topicId')
            , array('t_topics_0.name', 't_topics_0_name')
            , array('t_posts_0.postId', 't_posts_0_postId')
        ));
        $this->assertEquals($db_query->tables, array(
            array('t_posts', 't_posts_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('t_topics', 't_topics_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_posts_0.topicFk', '=', 't_topics_0.topicId')
                )
            )
        ));
    }

    public function testProjection() {
        $jork_query = cy\JORK::select('author{id,name}')->from('Model_Post');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->joins[0],
            array(
                'table' => array('t_users', 't_users_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_posts_0.userFk', '=', 't_users_0.userId')
                )
            )
        );
    }

    public function testOrderByExpr() {
        $jork_query = cy\JORK::from('Model_User')->order_by(cy\DB::expr('avg({posts.id})'));
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(array(
                array(
                'column' => cy\DB::expr('avg(t_posts_0.postId)'),
                'direction' => 'ASC'
                )
            ),
        $db_query->order_by);
    }

    public function testOffsetLimitHasToMany() {
        $jork_query = cy\JORK::from('Model_Topic')
            ->with('posts')
            ->offset(20)->limit(10);
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        //echo $db_query->compile('jork_test');
        $expected = array(
            'table' => array(cy\DB::select_distinct('topicId')->from(array('t_topics', 't_topics_1'))
                ->offset(20)->limit(10), 'jork_offset_limit_subquery_0'),
            'type' => 'RIGHT',
            'conditions' => array(
                new db\BinaryExpression('t_topics_0.topicId', '=', 'jork_offset_limit_subquery_0.topicId')
            ));
        $this->assertEquals($expected, $db_query->joins[1]);
    }


}
