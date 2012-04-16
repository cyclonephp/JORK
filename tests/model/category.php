<?php

use cyclone as cy;
use cyclone\jork\model;


class Model_Category extends model\AbstractModel {


    public static function setup() {
        return \cyclone\jork\schema\ModelSchema::factory()
            ->db_conn('jork_test')
            ->table('t_categories')
            ->primitive(cy\JORK::primitive('id', 'int')
                    ->primary_key()
                )->primitive(cy\JORK::primitive('name', 'string')->column('c_name')
                )->primitive(cy\JORK::primitive('moderator_fk', 'int')
                )
            ->component(cy\JORK::component('topics', 'Model_Topic')
                    ->type(cy\JORK::MANY_TO_MANY)->mapped_by('categories')
                )->component(cy\JORK::component('moderator', 'Model_User')
                    ->type(cy\JORK::ONE_TO_ONE)->join_column('moderator_fk')
                )->embedded_component('modinfo', 'Model_ModInfo');
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
    
}
