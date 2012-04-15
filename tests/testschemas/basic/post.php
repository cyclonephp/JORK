<?php

use cyclone as cy;
use cyclone\jork\model;

$schema = new \cyclone\jork\schema\ModelSchema();
$schema->db_conn = 'jork_test';

$schema->db_conn = 'jork_test';
$schema->table = 't_posts';
$schema->primitive(cy\JORK::primitive('id', 'int')
        ->primary_key()
)->primitive(cy\JORK::primitive('name', 'string')
)->primitive(cy\JORK::primitive('topic_fk', 'int')
)->primitive(cy\JORK::primitive('user_fk', 'int'));

$schema->component(cy\JORK::component('author', 'Model_User')
        ->mapped_by('posts')
)->component(cy\JORK::component('topic', 'Model_Topic')
        ->type(cy\JORK::MANY_TO_ONE)
        ->join_column('topic_fk')
)->embedded_component('modinfo', 'Model_ModInfo');

return $schema;

/*
class Model_Post extends model\AbstractModel {


    public function setup() {
        /*$this->_schema->atomics = array(
            'id' => array(
                'type' => 'int',
                'primary' => true,
                'geneneration_strategy' => 'auto'
            ),
            'name' => array(
                'type' => 'string'
            ),
            'topic_fk' => array(
                'type' => 'int',
                'constraints' => array(
                    'not null' => true
                )
            ),
            'user_fk' => array(
                'type' => 'int',
                'constraints' => array(
                    'not null' => true
                )
            )
        /*$this->_schema->components = array(
            'author' => array(
                'class' => 'Model_User',
                'mapped_by' => 'posts'
            ),
            'topic' => array(
                'class' => 'Model_Topic',
                'type' => cy\JORK::MANY_TO_ONE,
                'join_column' => 'topic_fk'
            ),
            'modinfo' => 'Model_ModInfo'
        );*
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
}
*/