<?php

use cyclone as cy;
use cyclone\jork\model;


class Model_ModInfo extends model\EmbeddableModel {

    public function setup() {
        $this->_schema->atomics = array(
            'created_at' => array(
                'type' => 'datetime',
                'constraints' => array(
                    'not null' => true
                )
            ),
            'creator_fk' => array(
                'type' => 'int',
                'constraints' => array(
                    'not null' => true
                )
            ),
            'modified_at' => array(
                'type' => 'datetime'
            ),
            'modifier_fk' => array(
                'type' => 'int',
                'constraints' => array(
                    'not null' => true
                )
            )
        );
        
        $this->_schema->components = array(
            'creator' => array(
                'class' => 'Model_User',
                'type' => cy\JORK::MANY_TO_ONE,
                'join_column' => 'creator_fk'
            ),
            'modifier' => array(
                'class' => 'Model_User',
                'type' => cy\JORK::MANY_TO_ONE,
                'join_column' => 'modifier_fk'
            )
        );
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }

}