<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;

class JORK_Mapper_ExpressionTest extends Kohana_Unittest_TestCase {

    /**
     * @expectedException cyclone\jork\Exception
     */
    public function testExpression() {
        $jork_query = new query\SelectQuery;
        $jork_query->select(cy\DB::expr('{user.id} || {user.name} || {user.email} || {user.posts.id}'))
                ->from('Model_User user');
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $jork_query->select(cy\DB::expr('{user.posts}'));
        $mapper->map();
    }

    /**
     * @expectedException  cyclone\jork\Exception
     */
    public function testMapRow() {
        $expr = new jork\mapper\ExpressionMapper('hello');
        $row = array('hello' => 'world');
        $result = $expr->map_row($row);
        $this->assertEquals('world', $result);

        $expr = new jork\mapper\ExpressionMapper('hello alias');
        $row = array('alias' => 'world');
        $result = $expr->map_row($row);
        $this->assertEquals('world', $result);

        $expr = new jork\mapper\ExpressionMapper('hello alias');
        $row = array('should' => 'fail');
        $expr->map_row($row);
    }

}
