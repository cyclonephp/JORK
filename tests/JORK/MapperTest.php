<?php

use cyclone as cy;
use cyclone\jork;
use cyclone\jork\query;
use cyclone\db;

/**
 * @author Bence Erős <crystal@cyclonephp.org>
 */
abstract class JORK_MapperTest extends Kohana_Unittest_TestCase {

    public function setUp() {
        parent::setUp();
        $this->load_schemas('basic');
    }

    protected function load_schemas($rel_filename) {
        $abs_filename = cy\FileSystem::get_default()->get_root_path('jork') . 'tests/testschemas/' . $rel_filename . '.php';
        if ( ! file_exists($abs_filename))
            throw new jork\SchemaException("failed to load test schemas '$rel_filename'");

        jork\schema\SchemaPool::inst()->set_schemas(require $abs_filename);
    }

    protected function assertCompiledTo(query\SelectQuery $jork_query
            , db\query\Select $expected_db_query
            , $message = '') {
        $mapper = jork\mapper\SelectMapper::for_query($jork_query);
        list($actual_db_query, ) = $mapper->map();

        if ( ! $expected_db_query->equals($actual_db_query)) {
            $failure = new PHPUnit_Framework_ComparisonFailure_Object($expected_db_query, $actual_db_query, FALSE, $message);
            $exception = new PHPUnit_Framework_ExpectationFailedException('failed to assert that the two queries are equal'
                , $failure
                , $message);
            $exception->setCustomMessage('failed to assert that the two queries are equal');
            throw $exception;
        }

    }

}
