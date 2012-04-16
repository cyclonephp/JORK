<?php

use cyclone as cy;
use cyclone\jork\model;
use cyclone\jork\schema;


class Model_ModInfo extends model\EmbeddableModel {

    public static function setup_embeddable(schema\EmbeddableSchema $schema) {
        $schema->primitive(cy\JORK::primitive('created_at', 'string')
            )->primitive(cy\JORK::primitive('creator_fk', 'int')
            )->primitive(cy\JORK::primitive('modified_at', 'string')
            )->primitive(cy\JORK::primitive('modifier_fk', 'int'))
            ->component(cy\JORK::component('creator', 'Model_User')
                ->type(cy\JORK::MANY_TO_ONE)->join_column('creator_fk')
            )->component(cy\JORK::component('modifier', 'Model_User')
            ->type(cy\JORK::MANY_TO_ONE)->join_column('modifier_fk'));
    }

}