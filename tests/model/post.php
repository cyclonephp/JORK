<?php

use cyclone as cy;
use cyclone\JORK;
use cyclone\jork\model;
use cyclone\jork\schema\ModelSchema;


class Model_Post extends model\AbstractModel {


    public static function setup() {
        // creating the ModelSchema instance using its static factory method
        return ModelSchema::factory()
        // setting the name of the database connection where the underlying database table exists
        // it is an optional property, by default the 'default' connection is used. See the manual
        // of the DB library for more details.
            ->db_conn('jork_test')
        // setting the table name
            ->table('t_posts')
        // creating a primitive property which' name is 'id' and its PHP type is int
            ->primitive(JORK::primitive('id', 'int')
        // setting up the table column name where the 'id' property should be mapped to
        // it is an optional property, by default the property name is used as column name
                ->column('postId')
        // marking the 'id' property as the auto-generated primary key of the entity
                ->primary_key()
        // creating a new primitive - the property name and the database column name are the same
        )->primitive(JORK::primitive('name', 'string')
        // yet another primitive, the property name and the column name are different
        )->primitive(JORK::primitive('topic_fk', 'int')->column('topicFk')
        // same
        )->primitive(JORK::primitive('user_fk', 'int')->column('userFk')
        // creating a component. A Model_Post instance will have a property named 'topic'
        // and its type will be Model_Topic - an other entity.
        )->component(JORK::component('topic', 'Model_Topic')
        // the connection's cardinality is many-to-one
        // meaning that many posts can have the same topic, but only one topic
        // can belong to a post
                ->type(JORK::MANY_TO_ONE)
        // the join column for representing this connection between the Model_Post and Model_Topic entities
        // (or between the t_posts and t_topics tables) will be the 'topicFk' column. It means that
        // a column named 'topicFk' exists in the t_posts table and it is a foreign key referencing
        // the primary key of the t_topics table (which is actually the topicId column).
                ->join_column('topicFk')
        // creating a component. A Model_Post instance will have a property named 'author'
        // and its type is Model_User - an other entity.
        )->component(JORK::component('author', 'Model_User')
            // the mapping schema of this property is defined on the other side
            // i.e. the Model_User class will have a property named 'posts' and its definition will
            // contain the mapping schema for this connection
                ->mapped_by('posts')
        // embedded components will be discussed later
        )->embedded_component('modinfo', 'Model_ModInfo');
    }

}
