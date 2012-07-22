<?php

use cyclone as cy;
use cyclone\jork\model;


class Model_Post extends model\AbstractModel {


    public static function setup() {
        return cy\jork\schema\ModelSchema::factory()
            ->db_conn('jork_test')
            ->table('t_posts')
            ->primitive(cy\JORK::primitive('id', 'int')
                ->column('postId')
                ->primary_key()
        )->primitive(cy\JORK::primitive('name', 'string')->column('name')
        )->primitive(cy\JORK::primitive('topic_fk', 'int')->column('topicFk')
        )->primitive(cy\JORK::primitive('user_fk', 'int')->column('userFk')
        )
            ->component(cy\JORK::component('author', 'Model_User')
                ->mapped_by('posts')
        )->component(cy\JORK::component('topic', 'Model_Topic')
                ->type(cy\JORK::MANY_TO_ONE)
                ->join_column('topicFk')
        )->embedded_component('modinfo', 'Model_ModInfo');
    }

}
