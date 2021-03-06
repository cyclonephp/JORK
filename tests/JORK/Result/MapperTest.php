<?php

use cyclone as cy;
use cyclone\jork;

require_once __DIR__ . '/../DBTest.php';

class JORK_Result_MapperTest extends JORK_DbTest {

    public function setUp() {
        parent::setUp();
        $this->load_schemas('basic');
    }

    public function test_config() {
        cy\Config::inst()->get('jork.show_sql');
        cy\DB::executor('jork_test')->exec_custom('select 1');
    }

    public function test_impl_root() {
        $jork_query = new jork\query\SelectQuery;
        $jork_query->from('Model_Topic');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, $mappers) = $mapper->map();

        $resultset = array(
            array('t_topics_0_topicId' => '1', 't_topics_0_name' => 'hello'
                , 't_topics_0_createdAt' => '2011-01-05', 't_topics_0_creatorFk' => 1, 't_topics_0_modifiedAt' => '2011-01-05', 't_topics_0_modifierFk' => 1 )
            , array('t_topics_0_topicId' => '1', 't_topics_0_name' => 'hello'
                , 't_topics_0_createdAt' => '2011-01-05', 't_topics_0_creatorFk' => 1, 't_topics_0_modifiedAt' => '2011-01-05', 't_topics_0_modifierFk' => 1 )
        );
        foreach ($resultset as $idx => $row) {
            $topic = $mappers[NULL]->map_row($resultset[0]);
            $this->assertEquals(count($topic), 2);
            $this->assertEquals($topic[1], (boolean) ! $idx);
            $this->assertTrue($topic[0] instanceof  Model_Topic);
            $this->assertEquals($topic[0]->id, 1);
            $this->assertEquals($topic[0]->name, 'hello');
        }
    }

    public function test_first_from_db() {
        $result = cy\JORK::from('Model_User')->exec('jork_test');
        $this->assertEquals(4, count($result));
        $idx = 1;
        foreach ($result as $user) {
            $this->assertEquals($user->id, $idx);
            $this->assertEquals($user->name, "user$idx");
            ++$idx;
        }
    }

    public function test_many_comp_join() {
        $query = cy\JORK::from('Model_User')->with('posts.topic');
        $result = $query->exec('jork_test');
        $idx = 1;
        foreach ($result as $user) {
            $this->assertTrue($user instanceof  Model_User);
            $this->assertTrue($user->posts instanceof jork\model\collection\AbstractCollection);
            if ($idx == 1) {
                $this->assertTrue($user->posts[1] instanceof Model_Post);
                $this->assertEquals(1, $user->posts[1]->id);
                $this->assertEquals('t 01 p 01', $user->posts[1]->name);

                $this->assertTrue($user->posts[1]->topic instanceof Model_Topic);
                $this->assertEquals(1, $user->posts[1]->topic->id);

                $this->assertEquals(3, $user->posts[3]->id);
                $this->assertEquals('t 02 p 01', $user->posts[3]->name);
            }
            ++$idx;
        }
    }

    public function test_select_type_impl_root() {
        $query = cy\JORK::select('id uid', 'name', 'author.moderated_category ctg'
                , 'modinfo', cy\DB::expr('{id} - 5 cnt'))->from('Model_Post');
        $result = $query->exec('jork_test');
        $this->assertEquals(4, count($result));
        foreach ($result as $row) {
            if ($row['ctg'] != NULL) {
                $this->assertInstanceOf('Model_Category', $row['ctg']);
            }
            $this->assertInternalType('string', $row['name']);
            $this->assertInternalType('int', $row['uid']);
            $this->assertInstanceOf('Model_ModInfo', $row['modinfo']);
            $this->assertArrayHasKey('cnt', $row);
        }
    }

    public function test_select_itm_coll() {
        $query = cy\JORK::select('name', 'topics')->from('Model_Category');
        $result = $query->exec('jork_test');

        $this->assertEquals(3, count($result));
        $row1 = $result[0];
        $this->assertInternalType('string', $row1['name']);
        $this->assertInstanceOf('cyclone\jork\model\collection\AbstractCollection', $row1['topics']);
        $this->assertEquals(1, count($row1['topics']));
        $this->assertTrue(isset($row1['topics'][1]));
        $this->assertEquals(1, $row1['topics'][1]->id);

        $row2 = $result[1];
        $this->assertInstanceOf('cyclone\jork\model\collection\AbstractCollection', $row2['topics']);
        $this->assertEquals(2, $row2['topics']->count());
        $this->assertTrue(isset($row2['topics'][1]));
        $this->assertEquals(1, $row2['topics'][1]->id);

        $this->assertTrue(isset($row2['topics'][2]));
        $this->assertEquals(2, $row2['topics'][2]->id);

        $row3 = $result[2];
        $this->assertInstanceOf('cyclone\jork\model\collection\AbstractCollection', $row3['topics']);
        $this->assertEquals(1, count($row3['topics']));
        $this->assertTrue(isset($row3['topics'][1]));
        $this->assertEquals(1, $row3['topics'][1]->id);
    }

    public function test_select_type_expl_root() {
        $query = cy\JORK::select('p.id uid', 'p.name', 'p.author.moderated_category ctg'
                , 'p.modinfo', cy\DB::expr('{p.id} - 5 cnt'))->from('Model_Post p');

        $result = $query->exec('jork_test');
        $this->assertEquals(4, count($result));
        foreach ($result as $row) {
            if ($row['ctg'] != NULL) {
                $this->assertInstanceOf('Model_Category', $row['ctg']);
            }
            $this->assertInternalType('string', $row['p.name']);
            $this->assertInternalType('int', $row['uid']);
            $this->assertInstanceOf('Model_ModInfo', $row['p.modinfo']);
            $this->assertArrayHasKey('cnt', $row);
        }
    }

    /**
     * @dataProvider provider_for_query
     */
    public function test_for_query($jork_query, $exp_result_mapper_type) {
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, $mappers) = $mapper->map();

        $sql = cy\DB::compiler('jork_test')->compile_select($db_query);
        $db_result = cy\DB::executor('jork_test')->exec_select($sql);

        $result_mapper = jork\mapper\result\AbstractResult::for_query($jork_query, $db_result
                , $mapper->has_implicit_root, $mappers);
        $this->assertInstanceOf($exp_result_mapper_type, $result_mapper);
    }

    public function provider_for_query() {
        return array(
            array(cy\JORK::from('Model_User'), 'cyclone\jork\mapper\result\SimpleResult'),
            array(cy\JORK::from('Model_User u'), 'cyclone\jork\mapper\result\DefaultResult'),
            array(cy\JORK::select('name')->from('Model_User'), 'cyclone\jork\mapper\result\DefaultResult'),
        );
    }

    public function test_embedded() {
        $result = cy\JORK::from('Model_Topic')->with('modinfo')
                ->where('id', '=', cy\DB::esc(4))
                ->exec('jork_test');
        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Model_Topic', $result[0]);
        $this->assertInstanceOf('Model_ModInfo', $result[0]->modinfo);
        $this->assertEquals(4, $result[0]->modinfo->creator_fk);
    }


    /**
     * @dataProvider provider_outer_join_empty_row_skip
     */
    public function test_outer_join_empty_row_skip($topic_idx, $post_count) {
        //echo cy\JORK::from('Model_Topic')->with('posts')->compile('jork_test');
        $result = cy\JORK::from('Model_Topic')->with('posts')->exec('jork_test');
        $this->assertEquals(4, count($result));
        $idx = 1;
        foreach ($result as $topic) {
            $this->assertTrue($topic instanceof Model_Topic);
            $this->assertEquals($idx, $topic->id);
            ++$idx;
        }
        $this->assertEquals($topic_idx + 1, $result[$topic_idx]->id);
        $this->assertEquals($post_count, count($result[$topic_idx]->posts));
    }

    public function provider_outer_join_empty_row_skip() {
        return array(
            array(0, 2),
            array(1, 1),
            array(2, 0),
            array(3, 1)
        );
    }
}
