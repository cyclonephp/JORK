<?php

use cyclone as cy;
use cyclone\jork\query;


class JORK_Test extends Kohana_Unittest_TestCase {

    public function testInst() {
        $this->assertTrue(cy\JORK::inst() instanceof cy\JORK);
    }

    public function testHelpers() {
        $this->assertTrue(cy\JORK::select() instanceof query\SelectQuery);
        $this->assertTrue(cy\JORK::from() instanceof query\SelectQuery);
    }
}