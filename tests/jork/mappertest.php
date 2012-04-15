<?php

use cyclone as cy;
use cyclone\jork;

/**
 * @author Bence Erős <crystal@cyclonephp.org>
 */
abstract class JORK_MapperTest extends Kohana_Unittest_TestCase {

    public function setUp() {
        parent::setUp();
        $this->load_schemas('basic');
    }

    protected function load_schemas($rel_filename) {
        $abs_filename = cy\FileSystem::get_root_path('jork') . 'tests/testschemas/' . $rel_filename . '.php';
        if ( ! file_exists($abs_filename))
            throw new jork\SchemaException("failed to load test schemas '$rel_filename'");

        jork\schema\SchemaPool::inst()->set_schemas(require $abs_filename);
    }

}
