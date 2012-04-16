<?php
use cyclone as cy;
use cyclone\jork\model;

class Model_Topic extends model\AbstractModel {

    public static function setup() {
        return cy\jork\schema\ModelSchema::factory()
            ->db_conn('jork_test')
            ->table('t_topics')
            ->primitive(cy\JORK::primitive('id', 'int')
                    ->primary_key()
                )->primitive(cy\JORK::primitive('name', 'string'))
            ->component(cy\JORK::component('categories', 'Model_Category')->type(cy\JORK::MANY_TO_MANY)
                ->join_table(cy\JORK::join_table('categories_topics', 'topic_fk', 'category_fk'))
                )->component(cy\JORK::component('posts', 'Model_Post')->mapped_by('topic')->on_delete(cy\JORK::SET_NULL))
                ->natural_ordering('name')
            ->embedded_component('modinfo', 'Model_ModInfo');
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
}
