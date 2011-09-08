<?php


class JORK_Result_MapperTest extends JORK_DbTest {

    public function testConfig() {
        \cyclone\Config::inst()->get('jork.show_sql');
        DB::executor('jork_test')->exec_custom('select 1');
    }

    public function testImplRoot() {
        $jork_query = new JORK_Query_Select;
        $jork_query->from('Model_Topic');
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, $mappers) = $mapper->map();

        $resultset = array(
            array('t_topics_0_id' => '1', 't_topics_0_name' => 'hello'
                , 't_topics_0_created_at' => '2011-01-05', 't_topics_0_creator_fk' => 1, 't_topics_0_modified_at' => '2011-01-05', 't_topics_0_modifier_fk' => 1 )
            , array('t_topics_0_id' => '1', 't_topics_0_name' => 'hello'
                , 't_topics_0_created_at' => '2011-01-05', 't_topics_0_creator_fk' => 1, 't_topics_0_modified_at' => '2011-01-05', 't_topics_0_modifier_fk' => 1 )
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

    public function testFirstFromDB() {
        $result = JORK::from('Model_User')->exec('jork_test');
        $this->assertEquals(4, count($result));
        $idx = 1;
        foreach ($result as $user) {
            $this->assertEquals($user->id, $idx);
            $this->assertEquals($user->name, "user$idx");
            ++$idx;
        }
    }

    public function testManyCompJoin() {
        $query = JORK::from('Model_User')->with('posts.topic');
        $result = $query->exec('jork_test');
        $idx = 1;
        foreach ($result as $user) {
            $this->assertTrue($user instanceof  Model_User);
            $this->assertTrue($user->posts instanceof JORK_Model_Collection);
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

    public function testSelectTypeImplRoot() {
        $query = JORK::select('id uid', 'name', 'author.moderated_category ctg'
                , 'modinfo', DB::expr('{id} - 5 cnt'))->from('Model_Post');
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

    public function testSelectItmColl() {
        $query = JORK::select('name', 'topics')->from('Model_Category');
        $result = $query->exec('jork_test');
        //echo $query->compile('jork_test');
        
        $this->assertEquals(3, count($result));
        $row1 = $result[0];
        $this->assertInternalType('string', $row1['name']);
        $this->assertInstanceOf('JORK_Model_Collection', $row1['topics']);
        $this->assertEquals(1, count($row1['topics']));
        $this->assertTrue(isset($row1['topics'][1]));
        $this->assertEquals(1, $row1['topics'][1]->id);

        $row2 = $result[1];
        $this->assertInstanceOf('JORK_Model_Collection', $row2['topics']);
        $this->assertEquals(2, count($row2['topics']));
        $this->assertTrue(isset($row2['topics'][1]));
        $this->assertEquals(1, $row2['topics'][1]->id);

        $this->assertTrue(isset($row2['topics'][2]));
        $this->assertEquals(2, $row2['topics'][2]->id);

        $row3 = $result[2];
        $this->assertInstanceOf('JORK_Model_Collection', $row3['topics']);
        $this->assertEquals(1, count($row3['topics']));
        $this->assertTrue(isset($row3['topics'][1]));
        $this->assertEquals(1, $row3['topics'][1]->id);
    }

    public function testSelectTypeExplRoot() {
        $query = JORK::select('p.id uid', 'p.name', 'p.author.moderated_category ctg'
                , 'p.modinfo', DB::expr('{p.id} - 5 cnt'))->from('Model_Post p');

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
     * @dataProvider providerForQuery
     */
    public function testForQuery($jork_query, $exp_result_mapper_type) {
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, $mappers) = $mapper->map();

        $sql = DB::compiler('jork_test')->compile_select($db_query);
        $db_result = DB::executor('jork_test')->exec_select($sql);

        $result_mapper = JORK_Mapper_Result::for_query($jork_query, $db_result
                , $mapper->has_implicit_root, $mappers);
        $this->assertInstanceOf($exp_result_mapper_type, $result_mapper);
    }

    public function providerForQuery() {
        return array(
            array(JORK::from('Model_User'), 'JORK_Mapper_Result_Simple'),
            array(JORK::from('Model_User u'), 'JORK_Mapper_Result_Default'),
            array(JORK::select('name')->from('Model_User'), 'JORK_Mapper_Result_Default'),
        );
    }

    public function testEmbedded() {
        $result = JORK::from('Model_Topic')->with('modinfo')
                ->where('id', '=', DB::esc(4))
                ->exec('jork_test');
        $this->assertEquals(1, count($result));
        $this->assertInstanceOf('Model_Topic', $result[0]);
        $this->assertInstanceOf('Model_ModInfo', $result[0]->modinfo);
        $this->assertEquals(4, $result[0]->modinfo->creator_fk);
    }


    /**
     * @dataProvider providerOuterJoinEmptyRowSkip
     */
    public function testOuterJoinEmptyRowSkip($topic_idx, $post_count) {
        $result = JORK::from('Model_Topic')->with('posts')->exec('jork_test');
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

    public function providerOuterJoinEmptyRowSkip() {
        return array(
            array(0, 2),
            array(1, 1),
            array(2, 0),
            array(3, 1)
        );
    }
}