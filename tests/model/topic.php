<?php
use cyclone as cy;
use cyclone\jork\model;

class Model_Topic extends model\AbstractModel {

    public function setup() {
        $this->_schema->db_conn = 'jork_test';
        $this->_schema->table = 't_topics';
        $this->_schema->atomics = array(
            'id' => array(
                'type' => 'int',
                'primary' => true,
                'geneneration_strategy' => 'auto'
            ),
            'name' => array(
                'type' => 'string',
                'constraints' => array(
                    'max_length' => 64,
                    'not null' => true
                )
            )
        );
        $this->_schema->components = array(
            'categories' => array(
                'class' => 'Model_Category',
                'type' => cy\JORK::MANY_TO_MANY,
                'join_table' => array(
                    'name' => 'categories_topics',
                    'join_column' => 'topic_fk',
                    'inverse_join_column' => 'category_fk'
                )
            ),
            'posts' => array(
		'class' => 'Model_Post',
		'mapped_by' => 'topic',
                'on_delete' => cy\JORK::SET_NULL
            ),
            'modinfo' => 'Model_ModInfo'
        );
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
}
