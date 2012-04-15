<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;


class JORK_Mapper_SelectTest extends JORK_MapperTest {

    public function setUp() {
        parent::setUp();
        $this->load_schemas('basic');
    }

    public function testSelectManyToOne() {
        $jork_query = new query\SelectQuery;
        $jork_query->select('topic', 'topic.modinfo.creator')->from('Model_Topic topic');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_topics', 't_topics_0')
        ));
        //print_r($db_query->joins);
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
            )
        ));
    }

    public function testSelectManyToOne2() {
        $jork_query = new jork\query\SelectQuery;
        $jork_query->select('post', 'post.topic.modinfo.creator')
                ->from('Model_Post post');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_posts', 't_posts_0')
        ));
        //print_r($db_query->joins);
        $this->assertEquals($db_query->joins, array(
             array(
                'table' => array('t_topics', 't_topics_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_posts_0.topic_fk', '=', 't_topics_0.id')
                )
            ),
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
            )
           
        ));

    }

    public function testSelectManyToOneReverse() {
        $jork_query = new query\SelectQuery;
        $jork_query->select('t', 't.posts')->from('Model_Topic t');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_topics', 't_topics_0')
        ));
        $this->assertEquals($db_query->joins, array(array(
            'table' => array('t_posts', 't_posts_0'),
            'type' => 'LEFT',
            'conditions' => array(
                new db\BinaryExpression('t_topics_0.id', '=', 't_posts_0.topic_fk')
            )
        )));
        
    }

    public function testSelectOneToMany() {
        $jork_query = new query\SelectQuery;
        $jork_query->select('posts')->from('Model_User');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
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
        $jork_query = new query\SelectQuery;
        $jork_query->select('author')->from('Model_Post');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_posts', 't_posts_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('t_users', 't_users_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_posts_0.user_fk', '=', 't_users_0.id')
                )
            ),
            array(
                'table' => array('user_contact_info', 'user_contact_info_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_users_0.id', '=', 'user_contact_info_0.user_fk')
                )
            )
        ));

    }

    public function testOneToOne() {
        $jork_query = new query\SelectQuery;
        $jork_query->select('moderator')->from('Model_Category');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_categories', 't_categories_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('t_users', 't_users_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_categories_0.moderator_fk', '=', 't_users_0.id')
                )
            ),
            array(
                'table' => array('user_contact_info', 'user_contact_info_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_users_0.id', '=', 'user_contact_info_0.user_fk')
                )
            )
        ));
    }

    public function testOneToOneReverse() {
        $jork_query = new query\SelectQuery;
        $jork_query->select('moderated_category')->from('Model_User');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_users', 't_users_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('t_categories', 't_categories_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_users_0.id', '=', 't_categories_0.moderator_fk')
                )
            )
        ));
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
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_topics', 't_topics_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('categories_topics', 'categories_topics_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_topics_0.id', '=', 'categories_topics_0.topic_fk')
                )
            ),
            array(
                'table' => array('t_categories', 't_categories_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('categories_topics_0.category_fk', '=', 't_categories_0.id')
                )
            )
        ));
    }

    public function testManyToManyReverse() {
        $jork_query = cy\JORK::from('Model_Category')->with('topics');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $this->assertEquals($db_query->tables, array(
            array('t_categories', 't_categories_0')
        ));
        $this->assertEquals($db_query->joins, array(
            array(
                'table' => array('categories_topics', 'categories_topics_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('t_categories_0.id'
                            , '=', 'categories_topics_0.category_fk')
                )
            ),
            array(
                'table' => array('t_topics', 't_topics_0'),
                'type' => 'LEFT',
                'conditions' => array(
                    new db\BinaryExpression('categories_topics_0.topic_fk'
                            , '=', 't_topics_0.id')
                )
            )
        ));
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
