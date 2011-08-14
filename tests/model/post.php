<?php


class Model_Post extends JORK_Model_Abstract {


    public function setup() {
        $this->_schema->db_conn = 'jork_test';
        $this->_schema->table = 't_posts';
        $this->_schema->atomics = array(
            'id' => array(
                'type' => 'int',
                'primary' => true,
                'geneneration_strategy' => 'auto'
            ),
            'name' => array(
                'type' => 'string'
            ),
            'topic_fk' => array(
                'type' => 'int',
                'constraints' => array(
                    'not null' => true
                )
            ),
            'user_fk' => array(
                'type' => 'int',
                'constraints' => array(
                    'not null' => true
                )
            )
        );
        $this->_schema->components = array(
            'author' => array(
                'class' => 'Model_User',
                'mapped_by' => 'posts'
            ),
            'topic' => array(
                'class' => 'Model_Topic',
                'type' => JORK::MANY_TO_ONE,
                'join_column' => 'topic_fk'
            ),
            'modinfo' => 'Model_ModInfo'
        );
    }

    public static function inst() {
        return parent::_inst(__CLASS__);
    }
}
