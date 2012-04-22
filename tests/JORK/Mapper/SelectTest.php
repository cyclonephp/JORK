<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;

require_once realpath(__DIR__) .'../../MapperTest.php';

class JORK_Mapper_SelectTest extends JORK_MapperTest {

    public function setUp() {
        parent::setUp();
        $this->load_schemas('basic');
    }

    public function testSelectManyToOne() {
        $jork_query = cy\JORK::select('topic', 'topic.modinfo.creator')->from('Model_Topic topic');
        $db_query = cy\DB::select(array('t_topics_0.id', 't_topics_0_id')
            , array('t_topics_0.name', 't_topics_0_name')
            , array('t_users_0.id', 't_users_0_id')
            , array('t_users_0.name', 't_users_0_name')
            , array('t_users_0.password', 't_users_0_password')
            , array('t_users_0.created_at', 't_users_0_created_at')
            , array('user_contact_info_0.email', 'user_contact_info_0_email')
            , array('user_contact_info_0.phone_num', 'user_contact_info_0_phone_num'))->from(array('t_topics', 't_topics_0'))
            ->left_join(array('t_users', 't_users_0'))
                ->on('t_topics_0.creator_fk', '=', 't_users_0.id')
            ->left_join(array('user_contact_info', 'user_contact_info_0'))
                ->on('t_users_0.id', '=', 'user_contact_info_0.user_fk');
        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testSelectManyToOne2() {
        $jork_query = cy\JORK::select('post', 'post.topic.modinfo.creator')
                ->from('Model_Post post');

        $db_query = cy\DB::select(array('t_posts_0.id', 't_posts_0_id')
            , array('t_posts_0.name', 't_posts_0_name')
            , array('t_posts_0.topic_fk', 't_posts_0_topic_fk')
            , array('t_posts_0.user_fk', 't_posts_0_user_fk')
            , array('t_users_0.id', 't_users_0_id')
            , array('t_users_0.name', 't_users_0_name')
            , array('t_users_0.password', 't_users_0_password')
            , array('t_users_0.created_at', 't_users_0_created_at')
            , array('user_contact_info_0.email', 'user_contact_info_0_email')
            , array('user_contact_info_0.phone_num', 'user_contact_info_0_phone_num')
            , array('t_topics_0.id', 't_topics_0_id')
        )->from(array('t_posts', 't_posts_0'))
            ->left_join(array('t_topics', 't_topics_0'))
                ->on('t_posts_0.topic_fk', '=', 't_topics_0.id')
            ->left_join(array('t_users', 't_users_0'))
                ->on('t_topics_0.creator_fk', '=', 't_users_0.id')
            ->left_join(array('user_contact_info', 'user_contact_info_0'))
                ->on('t_users_0.id', '=', 'user_contact_info_0.user_fk');

        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testSelectManyToOneReverse() {
        $jork_query = cy\JORK::select('t', 't.posts')->from('Model_Topic t');
        $db_query = cy\DB::select(array('t_topics_0.id', 't_topics_0_id')
                , array('t_topics_0.name', 't_topics_0_name')
                , array('t_posts_0.id', 't_posts_0_id')
                , array('t_posts_0.name', 't_posts_0_name')
                , array('t_posts_0.topic_fk', 't_posts_0_topic_fk')
                , array('t_posts_0.user_fk', 't_posts_0_user_fk'))
            ->from(array('t_topics', 't_topics_0'))
            ->left_join(array('t_posts', 't_posts_0'))
                ->on('t_topics_0.id', '=', 't_posts_0.topic_fk');
        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testSelectOneToMany() {
        $jork_query = cy\JORK::select('posts')
            ->from('Model_User');
        $db_query =cy\DB::select(array('t_posts_0.id', 't_posts_0_id')
            , array('t_posts_0.name', 't_posts_0_name')
            , array('t_posts_0.topic_fk', 't_posts_0_topic_fk')
            , array('t_posts_0.user_fk', 't_posts_0_user_fk')
            , array('t_users_0.id', 't_users_0_id')
        )->from(array('t_users', 't_users_0'))
            ->left_join(array('t_posts', 't_posts_0'))
                ->on('t_users_0.id', '=', 't_posts_0.user_fk')
        ->order_by('t_users_0.name', 'asc');

        $this->assertCompiledTo($jork_query, $db_query);
        $this->assertEquals($db_query->tables, array(
            array('t_users', 't_users_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('t_posts', 't_posts_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_users_0.id', '=', 't_posts_0.user_fk')
                )
            )
        ));
    }

    public function testSelectOneToManyReverse() {
        $jork_query = cy\JORK::select('author')->from('Model_Post');
        $db_query = cy\DB::select(array('t_users_0.id', 't_users_0_id')
            , array('t_users_0.name', 't_users_0_name')
            , array('t_users_0.password', 't_users_0_password')
            , array('t_users_0.created_at', 't_users_0_created_at')
            , array('user_contact_info_0.email', 'user_contact_info_0_email')
            , array('user_contact_info_0.phone_num', 'user_contact_info_0_phone_num')
            , array('t_posts_0.id', 't_posts_0_id'))->from(array('t_posts', 't_posts_0'))
            ->left_join(array('t_users', 't_users_0'))
                ->on('t_posts_0.user_fk', '=', 't_users_0.id')
            ->left_join(array('user_contact_info', 'user_contact_info_0'))
                ->on('t_users_0.id', '=', 'user_contact_info_0.user_fk');

        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testOneToOne() {
        $jork_query = cy\JORK::select('moderator')->from('Model_Category');
        $db_query = cy\DB::select(array('t_users_0.id', 't_users_0_id')
            , array('t_users_0.name', 't_users_0_name')
            , array('t_users_0.password', 't_users_0_password')
            , array('t_users_0.created_at', 't_users_0_created_at')
            , array('user_contact_info_0.email', 'user_contact_info_0_email')
            , array('user_contact_info_0.phone_num', 'user_contact_info_0_phone_num')
            , array('t_categories_0.id', 't_categories_0_id'))->from(array('t_categories', 't_categories_0'))
            ->left_join(array('t_users', 't_users_0'))
                ->on('t_categories_0.moderator_fk', '=', 't_users_0.id')
            ->left_join(array('user_contact_info', 'user_contact_info_0'))
                ->on('t_users_0.id', '=', 'user_contact_info_0.user_fk');

        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testOneToOneReverse() {
        $jork_query = cy\JORK::select('moderated_category')->from('Model_User');
        $db_query = cy\DB::select(array('t_categories_0.id', 't_categories_0_id')
            , array('t_categories_0.c_name', 't_categories_0_c_name')
            , array('t_categories_0.moderator_fk', 't_categories_0_moderator_fk')
            , array('t_users_0.id', 't_users_0_id')
        )->from(array('t_users', 't_users_0'))
            ->left_join(array('t_categories', 't_categories_0'))
                ->on('t_users_0.id', '=', 't_categories_0.moderator_fk')
        ->order_by('t_users_0.name', 'asc');
        $this->assertCompiledTo($jork_query, $db_query);
    }

    /**
     * issue #138 (redmine)
     */
    public function testAtomicPropertySelection() {
        $query = cy\JORK::select('t.modinfo.creator.id', 't.modinfo.creator.name')->from('Model_Topic t');
        $mapper = jork\mapper\SelectMapper::for_query($query);
        list($db_query, ) = $mapper->map();
    }

    public function testManyToMany() {
        $jork_query = cy\JORK::from('Model_Topic')->with('categories');
        $db_query = cy\DB::select(array('t_categories_0.id', 't_categories_0_id')
            , array('t_categories_0.c_name', 't_categories_0_c_name')
            , array('t_categories_0.moderator_fk', 't_categories_0_moderator_fk')
            , array('t_topics_0.id', 't_topics_0_id')
            , array('t_topics_0.name', 't_topics_0_name'))->from(array('t_topics', 't_topics_0'))
            ->left_join(array('categories_topics', 'categories_topics_0'))
                ->on('t_topics_0.id', '=', 'categories_topics_0.topic_fk')
            ->left_join(array('t_categories', 't_categories_0'))
                ->on('categories_topics_0.category_fk', '=', 't_categories_0.id')
        ->order_by('t_topics_0.name', 'asc');

        $this->assertCompiledTo($jork_query, $db_query);
    }

    public function testManyToManyReverse() {
        $jork_query = cy\JORK::from('Model_Category')->with('topics');
        $db_query = cy\DB::select(array('t_topics_0.id', 't_topics_0_id')
            , array('t_topics_0.name', 't_topics_0_name')
            , array('t_categories_0.id', 't_categories_0_id')
            , array('t_categories_0.c_name', 't_categories_0_c_name')
            , array('t_categories_0.moderator_fk', 't_categories_0_moderator_fk')
        )->from(array('t_categories', 't_categories_0'))
            ->left_join(array('categories_topics', 'categories_topics_0'))
                ->on('t_categories_0.id', '=', 'categories_topics_0.category_fk')
            ->left_join(array('t_topics', 't_topics_0'))
                ->on('categories_topics_0.topic_fk', '=', 't_topics_0.id');
        $this->assertCompiledTo($jork_query, $db_query);
    }



    public function testForQuery() {
        $jork_query = cy\JORK::from('Model_Post');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        $this->assertTrue($mapper instanceof jork\mapper\select\ImplRoot);
        
        $jork_query = JORK::from('Model_Post post');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        $this->assertTrue($mapper instanceof jork\mapper\select\ExplRoot);

    }

    public function testOffsetLimitNoToMany() {
        $jork_query = cy\JORK::from('Model_Post')
            ->with('author.moderated_category')
            ->where('id', '>', DB::expr(5))
            ->offset(20)->limit(10);
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals(20, $db_query->offset);
        $this->assertEquals(10, $db_query->limit);
    }

}
