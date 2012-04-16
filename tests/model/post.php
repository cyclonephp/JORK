<?php

use cyclone as cy;
use cyclone\jork\model;


class Model_Post extends model\AbstractModel {


    public static function setup() {
        return cy\jork\schema\ModelSchema::factory()
                ->db_conn('jork_test')
                ->table('t_posts')
            ->primitive(cy\JORK::primitive('id', 'int')
                    ->primary_key()
                )->primitive(cy\JORK::primitive('name', 'string')
                )->primitive(cy\JORK::primitive('topic_fk', 'int')
                )->primitive(cy\JORK::primitive('user_fk', 'int')
                )
            ->component(cy\JORK::component('author', 'Model_User')
                    ->mapped_by('posts')
                )->component(cy\JORK::component('topic', 'Model_Topic')
                    ->type(cy\JORK::MANY_TO_ONE)
                    ->join_column('topic_fk')
                )->embedded_component('modinfo', 'Model_ModInfo');
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
}
