<?php

use cyclone as cy;
use cyclone\jork\model;

class Model_User extends model\AbstractModel {


    protected function setup() {
        $this->_schema->db_conn = 'jork_test';
        $this->_schema->table = 't_users';
        $this->_schema->secondary_table(cy\JORK::secondary_table(
                'user_contact_info', 'id', 'user_fk'
                ));
        $this->_schema->primitive(cy\JORK::primitive('id', 'int')
                    ->primary_key()
                )->primitive(cy\JORK::primitive('name', 'string')
                )->primitive(cy\JORK::primitive('password', 'string')
                )->primitive(cy\JORK::primitive('created_at', 'datetime')
                )->primitive(cy\JORK::primitive('email', 'string')->table('user_contact_info')
                )->primitive(cy\JORK::primitive('phone_num', 'string')->table('user_contact_info')
                )->natural_ordering('name');
        /*$this->_schema->atomics = array(
            'id' => array(
                'type' => 'int',
                'primary' => true,
                'geneneration_strategy' => 'auto'
            ),
            'name' => array(
                'type' => 'string',
                'constraints' => array(
                    'not null' => true,
                    'max_length' => 64
                )
            ),
            'password' => array(
                'type' => 'string',
                'constraints' => array(
                    'not null' => true,
                    'length' => 32
                )
            ),
            'created_at' => array(
                'type' => 'datetime',
                'constraints' => array(
                    'not null' => true
                )
            ),
            'email' => array(
                'type' => 'string',
                'table' => 'user_contact_info',
                'constraints' => array(
                    'max_length' => 128
                )
            ),
            'phone_num' => array(
                'type' => 'string',
                'table' => 'user_contact_info',
                'constraints' => array(
                    'regex' => '/^\d{2}-\d{2}-\d{3}-\d{4}$/'
                )
            )
        );*/
        $this->_schema->component(cy\JORK::component('posts', 'Model_Post')
                    ->type(cy\JORK::ONE_TO_MANY)
                    ->join_column('user_fk')
                    ->on_delete(cy\JORK::SET_NULL)
                )->component(cy\JORK::component('moderated_category', 'Model_Category')
                    ->mapped_by('moderator')
                    ->on_delete(cy\JORK::SET_NULL)
                );
        /*$this->_schema->components = array(
            'posts' => array(
                'class' => 'Model_Post',
                'type' => cy\JORK::ONE_TO_MANY,
                'join_column' => 'user_fk',
                'on_delete' => cy\JORK::SET_NULL
            ),
            'moderated_category' => array(
                'class' => 'Model_Category',
                'mapped_by' => 'moderator',
                'on_delete' => cy\JORK::SET_NULL
            )
        );*/
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
    
}
