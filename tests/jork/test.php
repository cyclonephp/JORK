<?php


class JORK_Test extends Kohana_Unittest_TestCase {

    public function testInst() {
        $this->assertTrue(JORK::inst() instanceof JORK);
    }

    public function testHelpers() {
        $this->assertTrue(JORK::select() instanceof JORK_Query_Select);
        $this->assertTrue(JORK::from() instanceof JORK_Query_Select);
    }
}