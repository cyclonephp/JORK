<?php

use cyclone as cy;
use cyclone\jork\model;
use cyclone\jork\schema\ModelSchema;
use cyclone\JORK;

class Model_User extends model\AbstractModel {


    public static function setup() {
        return ModelSchema::factory()
            ->db_conn('jork_test')
        // passing the name of the primary (base) table
            ->table('t_users')
        // setting up a secondary table
        // its name will be user_contact_info
        // in the t_users table the userId column will be used as the join column
        // in the user_contact_info table the userFk column will be used as the join column
            ->secondary_table(JORK::secondary_table(
                'user_contact_info', 'userId', 'userFk'
                ))
            ->primitive(JORK::primitive('id', 'int')->column('userId')
                    ->primary_key()
                )->primitive(JORK::primitive('name', 'string')
                )->primitive(JORK::primitive('password', 'string')
                )->primitive(JORK::primitive('created_at', 'string')->column('createdAt')
        // setting up the email primitive property
        // and specifying the user_contact_info for its owner table instead of the
        // t_users (primary) table which is used by default.
                )->primitive(JORK::primitive('email', 'string')->table('user_contact_info')
        // the phone_num property's column, the phoneNum column will also be in the
        // t_user_contact_info secondary table
                )->primitive(JORK::primitive('phone_num', 'string')
                    ->table('user_contact_info')->column('phoneNum')
                )->natural_ordering('name')
            ->component(JORK::component('posts', 'Model_Post')
                    ->type(JORK::ONE_TO_MANY)
                    ->join_column('userFk')
                    ->on_delete(JORK::SET_NULL)
                )->component(JORK::component('moderated_category', 'Model_Category')
                    ->mapped_by('moderator')
                    ->on_delete(JORK::SET_NULL)
                );
    }

}
