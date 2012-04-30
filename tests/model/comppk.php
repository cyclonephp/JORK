<?php

use cyclone as cy;
use cyclone\jork\model;
use cyclone\jork\schema;

class Model_CompPK extends model\AbstractModel {

    public static function setup() {
        return schema\ModelSchema::factory()
            ->db_conn('jork_test')
            ->table('t_comppk')
            ->primitive(cy\JORK::primitive('pk_1', 'int')->primary_key(cy\JORK::ASSIGN))
            ->primitive(cy\JORK::primitive('pk_2', 'int')->primary_key(cy\JORK::ASSIGN))
            ->primitive(cy\JORK::primitive('pk_3', 'int')->primary_key(cy\JORK::ASSIGN))
            ->primitive(cy\JORK::primitive('pk_4', 'int')->primary_key(cy\JORK::ASSIGN))
            ->primitive(cy\JORK::primitive('pk_5', 'int')->primary_key(cy\JORK::ASSIGN))
            ->primitive(cy\JORK::primitive('prop6', 'string'))
            ->primitive(cy\JORK::primitive('prop7', 'string'));
    }
}