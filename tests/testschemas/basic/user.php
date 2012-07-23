<?php

use cyclone as cy;
use cyclone\jork\model;

$schema = new \cyclone\jork\schema\ModelSchema();
$schema->db_conn = 'jork_test';
$schema->table = 't_users';
$schema->secondary_table(cy\JORK::secondary_table(
    'user_contact_info', 'userId', 'userFk'
));
$schema->primitive(cy\JORK::primitive('id', 'int')->column('userId')
        ->primary_key()
)->primitive(cy\JORK::primitive('name', 'string')
)->primitive(cy\JORK::primitive('password', 'string')
)->primitive(cy\JORK::primitive('created_at', 'string')->column('createdAt')
)->primitive(cy\JORK::primitive('email', 'string')->table('user_contact_info')
)->primitive(cy\JORK::primitive('phone_num', 'string')->table('user_contact_info')
)->natural_ordering('name');

$schema->component(cy\JORK::component('posts', 'Model_Post')
        ->type(cy\JORK::ONE_TO_MANY)
        ->join_column('userFk')
        ->on_delete(cy\JORK::SET_NULL)
)->component(cy\JORK::component('moderated_category', 'Model_Category')
        ->mapped_by('moderator')
        ->on_delete(cy\JORK::SET_NULL)
);

return $schema;
/*
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
                )->primitive(cy\JORK::primitive('created_at', 'string')
                )->primitive(cy\JORK::primitive('email', 'string')->table('user_contact_info')
                )->primitive(cy\JORK::primitive('phone_num', 'string')->table('user_contact_info')
                )->natural_ordering('name');
        $this->_schema->component(cy\JORK::component('posts', 'Model_Post')
                    ->type(cy\JORK::ONE_TO_MANY)
                    ->join_column('user_fk')
                    ->on_delete(cy\JORK::SET_NULL)
                )->component(cy\JORK::component('moderated_category', 'Model_Category')
                    ->mapped_by('moderator')
                    ->on_delete(cy\JORK::SET_NULL)
                );
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
    
}
*/