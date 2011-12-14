<?php

use cyclone as cy;
use cyclone\jork\model;


class Model_Category extends model\AbstractModel {


    public function setup() {
        $this->_schema->db_conn = 'jork_test';
        $this->_schema->table = 't_categories';
        $this->_schema->primitive(cy\JORK::primitive('id', 'int')
                    ->primary_key()
                )->primitive(cy\JORK::primitive('name', 'string')->column('c_name')
                )->primitive(cy\JORK::primitive('moderator_fk', 'int')
                );

        /*$this->_schema->atomics = array(
            'id' => array(
                'type' => 'int',
                'primary' => true,
                'geneneration_strategy' => 'auto'
            ),
            'name' => array(
                'type' => 'string',
                'column' => 'c_name',
                'constraints' => array(
                    'max_length' => 64,
                    'min_length' => 3,
                    'not null' => true
                )
            ),
            'moderator_fk' => array(
                'type' => 'int'
            )
        );*/
        $this->_schema->component(cy\JORK::component('topics', 'Model_Topic')
                    ->type(cy\JORK::MANY_TO_MANY)->mapped_by('categories')
                )->component(cy\JORK::component('moderator', 'Model_User')
                    ->type(cy\JORK::ONE_TO_ONE)->join_column('moderator_fk')
                )->embedded_component('modinfo', 'Model_ModInfo');
        /*$this->_schema->components = array(
            'topics' => array(
                'class' => 'Model_Topic',
                'type' => cy\JORK::MANY_TO_MANY,
                'mapped_by' => 'categories'
            ),
            'moderator' => array(
                'class' => 'Model_User',
                'type' => cy\JORK::ONE_TO_ONE,
                'join_column' => 'moderator_fk'
            ),
            'modinfo' => 'Model_ModInfo'
        );*/
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
    
}
