<?php

class JORK_Mapper_ExpressionTest extends Kohana_Unittest_TestCase {

    /**
     * @expectedException JORK_Exception
     */
    public function testExpression() {
        $jork_query = new JORK_Query_Select;
        $jork_query->select(DB::expr('{user.id} || {user.name} || {user.email} || {user.posts.id}'))
                ->from('Model_User user');
        $mapper = JORK_Mapper_Select::for_query($jork_query);
        list($db_query, ) = $mapper->map();
        $jork_query->select(DB::expr('{user.posts}'));
        $mapper->map();
    }

    /**
     * @expectedException JORK_Exception
     */
    public function testMapRow() {
        $expr = new JORK_Mapper_Expression('hello');
        $row = array('hello' => 'world');
        $result = $expr->map_row($row);
        $this->assertEquals('world', $result);

        $expr = new JORK_Mapper_Expression('hello alias');
        $row = array('alias' => 'world');
        $result = $expr->map_row($row);
        $this->assertEquals('world', $result);

        $expr = new JORK_Mapper_Expression('hello alias');
        $row = array('should' => 'fail');
        $expr->map_row($row);
    }

}