<?php

use cyclone as cy;
use cyclone\jork\model;
use cyclone\jork\schema;


class Model_ModInfo extends model\EmbeddableModel {

    public static function setup_embeddable(schema\EmbeddableSchema $schema) {
        $schema->primitive(cy\JORK::primitive('created_at', 'string')->column('createdAt')
            )->primitive(cy\JORK::primitive('creator_fk', 'int')->column('creatorFk')
            )->primitive(cy\JORK::primitive('modified_at', 'string')->column('modifiedAt')
            )->primitive(cy\JORK::primitive('modifier_fk', 'int')->column('modifierFk'))
            ->component(cy\JORK::component('creator', 'Model_User')
                ->type(cy\JORK::MANY_TO_ONE)->join_column('creatorFk')
            )->component(cy\JORK::component('modifier', 'Model_User')
            ->type(cy\JORK::MANY_TO_ONE)->join_column('modifierFk'));
    }

}