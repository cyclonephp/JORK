<?php

use cyclone as cy;
use cyclone\jork\model;
use cyclone\jork\schema\ModelSchema;
use cyclone\JORK;


class Model_Category extends model\AbstractModel {


    public static function setup() {
        return ModelSchema::factory()
            ->db_conn('jork_test')
            ->table('t_categories')
            ->primitive(JORK::primitive('id', 'int')->column('categoryId')
                    ->primary_key()
                )->primitive(JORK::primitive('name', 'string')
                )->primitive(JORK::primitive('moderator_fk', 'int')->column('moderatorFk')
                )
            ->component(JORK::component('topics', 'Model_Topic')
                    ->type(JORK::MANY_TO_MANY)->mapped_by('categories')
        // a one-to-one connection example here. By default the primary keys
        // are used on both sides as join columns. You can override it using
        // join_column() on the local side and with inverse_join_column() on the other side.
                )->component(JORK::component('moderator', 'Model_User')
                    ->type(JORK::ONE_TO_ONE)->join_column('moderatorFk')
                )->embedded_component('modinfo', 'Model_ModInfo');
    }

}
