<?php
use cyclone as cy;
use cyclone\jork\model;
use cyclone\jork\schema\ModelSchema;
use cyclone\JORK;

class Model_Topic extends model\AbstractModel {

    public static function setup() {
        // creating the schema instance and setting up the connection and table name
        return ModelSchema::factory()
            ->db_conn('jork_test')
            ->table('t_topics')
            // setting up the primary key property
            ->primitive(JORK::primitive('id', 'int')->column('topicId')
                    ->primary_key()
            // setting up an ordinary primitive property
            )->primitive(JORK::primitive('name', 'string')
            // creating a many-to-many component
            // it means that a Model_Topic instance will have a property named 'categories'
            // and its value will be a collection of Model_Category instances
            )->component(JORK::component('categories', 'Model_Category')
            // setting the cardinality of the connection (semantics described above)
                ->type(JORK::MANY_TO_MANY)
            // for representing the connection in the database, a join table will be used
            // the name of the join table will be 'categories_topics', with 2 columns:
            // the 'topicFk' column will be used as a join column referencing the primary key
            //      of the 't_topics' table
            // the 'categoryFk' column will be used as a join column referencing the primary key
            //      of the 't_categories' table
                ->join_table(JORK::join_table('categories_topics', 'topicFk', 'categoryFk'))
            // creating a component named 'posts'
            )->component(JORK::component('posts', 'Model_Post')
                // its mapping schema is defined on the other side - see the definition
                // of the 'topic' property in the Model_Post class above, no other property
                // has to be set here. In the Model_Post class it is defined as a many-to-one
                // component, which indicates that from the Model_Topic side its a one-to-many
                // component. As a consequence a Model_Topic instance will have a property named
                // 'posts' and its value will be a collection of Model_Post instances.
                ->mapped_by('topic')
                // The on_delete property defines what to do with the posts when a topic is deleted
                // In this case it means that the t_posts.topicFk value should be set to NULL to
                // maintain data integrity
                ->on_delete(JORK::SET_NULL)
            // natural ordering: see later
            )->natural_ordering('name')
            ->embedded_component('modinfo', 'Model_ModInfo');
    }

}
