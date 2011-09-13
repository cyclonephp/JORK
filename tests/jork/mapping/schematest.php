<?php

use cyclone as cy;
use cyclone\db;
use cyclone\jork;
use cyclone\jork\query;

class JORK_Mapping_SchemaTest extends Kohana_Unittest_TestCase {

    /**
     * @expectedException cyclone\jork\SchemaException
     */
    public function testGetPropSchema() {
        $schema = jork\model\AbstractModel::schema_by_class('Model_User');
        $this->assertEquals($schema->get_property_schema('id'), array(
                'type' => 'int',
                'primary' => true,
                'geneneration_strategy' => 'auto'
            ));

        $this->assertEquals($schema->get_property_schema('posts'), array(
                'class' => 'Model_Post',
                'type' => cy\JORK::ONE_TO_MANY,
                'join_column' => 'user_fk',
                'on_delete' => cy\JORK::SET_NULL
            ));
        $schema->get_property_schema('dummy');
    }

    /**
     * @dataProvider providerIsToManyComponent
     */
    public function testIsToManyComponent($class, $component, $is_to_many) {
        $this->assertEquals($is_to_many, jork\model\AbstractModel::schema_by_class($class)
                ->is_to_many_component($component));
    }

    public function providerIsToManyComponent() {
        return array(
            array('Model_Category', 'moderator', FALSE),
            array('Model_User', 'moderated_category', FALSE),
            array('Model_Topic', 'posts', TRUE),
            array('Model_Post', 'topic', FALSE),
            array('Model_Post', 'author', FALSE),
            array('Model_User', 'posts', TRUE),
            array('Model_Category', 'topics', TRUE),
            array('Model_Topic', 'categories', TRUE)
        );
    }

    
}