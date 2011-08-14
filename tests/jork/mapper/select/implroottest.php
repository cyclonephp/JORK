<?php


class JORK_Mapper_Select_ImplRootTest extends Kohana_Unittest_TestCase {

    public function testFrom() {
        $jork_query = new JORK_Query_Select;
        $jork_query->from('Model_User');
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->columns, array(
            array('t_users_0.id', 't_users_0_id'), array('t_users_0.name', 't_users_0_name')
            , array('t_users_0.password', 't_users_0_password')
            , array('t_users_0.created_at', 't_users_0_created_at')
            , array('user_contact_info_0.email', 'user_contact_info_0_email')
            , array('user_contact_info_0.phone_num', 'user_contact_info_0_phone_num')
        ));
        $this->assertEquals($db_query->tables, array(
            array('t_users', 't_users_0'),
        ));
    }

    public function testSelect() {
        $jork_query = new JORK_Query_Select;
        $jork_query->from('Model_Category');
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(array(
            array('t_categories_0.id', 't_categories_0_id')
            , array('t_categories_0.c_name', 't_categories_0_c_name')
            , array('t_categories_0.moderator_fk', 't_categories_0_moderator_fk')
        ), $db_query->columns);
        $this->assertEquals($db_query->tables, array(
            array('t_categories', 't_categories_0')
        ));
        $jork_query->select('id');
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->columns, array(
            array('t_categories_0.id', 't_categories_0_id')
        ));
    }

    public function testSelectPropChain() {
        $jork_query = new JORK_Query_Select;
        $jork_query->select('topic')->from('Model_Post');
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->columns, array(
            array('t_topics_0.id', 't_topics_0_id')
            , array('t_topics_0.name', 't_topics_0_name')
            , array('t_posts_0.id', 't_posts_0_id')
        ));
        $this->assertEquals($db_query->tables, array(
            array('t_posts', 't_posts_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('t_topics', 't_topics_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new DB_Expression_Binary('t_posts_0.topic_fk', '=', 't_topics_0.id')
                )
            )
        ));
    }

    public function testProjection() {
        $jork_query = JORK::select('author{id,name}')->from('Model_Post');
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->joins[0],
            array(
                'table' => array('t_users', 't_users_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new DB_Expression_Binary('t_posts_0.user_fk', '=', 't_users_0.id')
                )
            )
        );
    }

    public function testOrderByExpr() {
        $jork_query = JORK::from('Model_User')->order_by(DB::expr('avg({posts.id})'));
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(array(
                array(
                'column' => DB::expr('avg(t_posts_0.id)'),
                'direction' => 'ASC'
                )
            ),
        $db_query->order_by);
    }

    public function testOffsetLimitHasToMany() {
        $jork_query = JORK::from('Model_Topic')
            ->with('posts')
            ->offset(20)->limit(10);
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        //echo $db_query->compile();
        $this->assertEquals(array(
            'table' => array(DB::select_distinct('id')->from(array('t_topics', 't_topics_1'))
                ->offset(20)->limit(10), 'jork_offset_limit_subquery_0'),
            'type' => 'RIGHT',
            'conditions' => array(
                new DB_Expression_Binary('t_topics_0.id', '=', 'jork_offset_limit_subquery_0.id')
            )
        ), $db_query->joins[1]);
    }


}