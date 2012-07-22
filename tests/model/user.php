<?php

use cyclone as cy;
use cyclone\jork\model;

class Model_User extends model\AbstractModel {


    public static function setup() {
        return cy\jork\schema\ModelSchema::factory()
            ->db_conn('jork_test')
            ->table('t_users')
            ->secondary_table(cy\JORK::secondary_table(
                'user_contact_info', 'userId', 'userFk'
                ))
            ->primitive(cy\JORK::primitive('id', 'int')->column('userId')
                    ->primary_key()
                )->primitive(cy\JORK::primitive('name', 'string')
                )->primitive(cy\JORK::primitive('password', 'string')
                )->primitive(cy\JORK::primitive('created_at', 'string')->column('createdAt')
                )->primitive(cy\JORK::primitive('email', 'string')->table('user_contact_info')
                )->primitive(cy\JORK::primitive('phone_num', 'string')
                    ->table('user_contact_info')->column('phoneNum')
                )->natural_ordering('name')
            ->component(cy\JORK::component('posts', 'Model_Post')
                    ->type(cy\JORK::ONE_TO_MANY)
                    ->join_column('userFk')
                    ->on_delete(cy\JORK::SET_NULL)
                )->component(cy\JORK::component('moderated_category', 'Model_Category')
                    ->mapped_by('moderator')
                    ->on_delete(cy\JORK::SET_NULL)
                );
    }

}
