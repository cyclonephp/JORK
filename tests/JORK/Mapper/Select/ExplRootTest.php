<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;

require_once __DIR__ . '/../../MapperTest.php';
class JORK_Mapper_ExplRootTest extends JORK_MapperTest {

    public function setUp() {
        parent::setUp();
        $this->load_schemas('basic');
    }

    public function testFrom() {
        $jork_query = new query\SelectQuery;
        $jork_query->from('Model_User user', 'Model_Topic topic');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_users', 't_users_0'),
            array('t_topics', 't_topics_0')
        ));
    }

    public function testWith() {
        $jork_query = cy\JORK::from('Model_Topic t')->with('t.posts');
        $db_query = cy\DB::select(
            array('t_posts_0.id', 't_posts_0_id'),
            array('t_posts_0.name', 't_posts_0_name'),
            array('t_posts_0.topic_fk', 't_posts_0_topic_fk'),
            array('t_posts_0.user_fk', 't_posts_0_user_fk'),
            array('t_topics_0.id', 't_topics_0_id'),
            array('t_topics_0.name', 't_topics_0_name')
        )->from(array('t_topics', 't_topics_0'))
            ->left_join(array('t_posts', 't_posts_0'))
            ->on('t_topics_0.id', '=', 't_posts_0.topic_fk');
        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testProjection() {
        $jork_query = cy\JORK::select('topic.modinfo.creator{id,name,posts}')->from('Model_Topic topic');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_topics', 't_topics_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('t_users', 't_users_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_topics_0.creator_fk', '=', 't_users_0.id')
                )
            ),
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
    }

    public function testOrderBy() {
        $jork_query = cy\JORK::from('Model_Post post')->order_by('post.modinfo.created_at');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->order_by, array(
            array(
                'column' => 't_posts_0.created_at',
                'direction' => 'ASC'
            )
        ));
    }

    public function testGroupBy() {
        $jork_query = cy\JORK::select(cy\DB::expr('count({post.id})'), 'post.author')
                ->from('Model_Post post')
                ->group_by('post.author.name');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->group_by, array('t_users_0.name'));
    }

    public function testOrderByExpr() {
        $jork_query = cy\JORK::from('Model_User u')->order_by(cy\DB::expr('avg({u.posts.id})'));
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(array(
                array(
                'column' => cy\DB::expr('avg(t_posts_0.id)'),
                'direction' => 'ASC'
                )
            ),
        $db_query->order_by);
    }

    public function testOffsetLimitHasToMany() {
        $jork_query = cy\JORK::from('Model_Topic t', 'Model_Category c')
            ->with('t.posts')
            ->offset(20)->limit(10);
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(array(
            'table' => array(cy\DB::select_distinct(array('t_topics_1.id', 't_topics_1_id')
                    , array('t_categories_1.id', 't_categories_1_id'))
                ->from(array('t_topics', 't_topics_1'))
                ->from(array('t_categories', 't_categories_1'))
                ->offset(20)->limit(10), 'jork_offset_limit_subquery_0'),
            'type' => 'RIGHT',
            'conditions' => array(
                new db\BinaryExpression('t_topics_0.id', '=', 'jork_offset_limit_subquery_0.t_topics_1_id'),
                new db\BinaryExpression('t_categories_0.id', '=', 'jork_offset_limit_subquery_0.t_categories_1_id')
            )
        ), $db_query->joins[1]);
    }

    
}
