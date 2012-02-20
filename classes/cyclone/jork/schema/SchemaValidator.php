<?php

namespace cyclone\jork\schema;

use cyclone as cy;
use cyclone\jork;

/**
 * @package jork
 * @author Bence Eros <crystal@cyclonephp.org>
 */
class SchemaValidator {

    private static $_inst;

    /**
     *
     * @return cyclone\jork\schema\SchemaValidator
     */
    public static function inst() {
        if (NULL === self::$_inst) {
            self::$_inst = new SchemaValidator;
        }
        return self::$_inst;
    }

    private function __construct() {
        // empty private constructor
    }

    /**
     *
     * @var array<\cyclone\jork\schema\ModelSchema>
     */
    private $_schemas;

    private $_validators = array(
        array(__CLASS__, 'test_primary_keys')
    );

    public function validate() {
        try {
            $this->_schemas = SchemaPool::inst()->get_schemas();
        } catch (jork\SchemaException $ex) {
            echo "fatal schema error during validation:" . PHP_EOL;
            echo $ex->getMessage() . PHP_EOL;
            $rval = $ex->getCode();
            if ( ! $rval) {
                $rval = 5;
            }
            return $rval;
        }
        $this->exec_validators()->render();
        return 0;
    }

    private function exec_validators() {
        $result = new ValidationResult;
        foreach ($this->_validators as $validator) {
            try {
                $result->merge(call_user_func($validator, $this->_schemas));
            } catch (\Exception $ex) {
                throw new jork\Exception("fatal error during schema validation: " . $ex->getMessage()
                        , $ex->getCode(), $ex);
            }
        }
        return $result;
    }

    private static function test_primary_keys($schemas) {
        $rval = new ValidationResult;
    }

}