<?php

use cyclone\jork;
use cyclone as cy;


class JORK_Naming_ServiceTest extends JORK_MapperTest {

    public function testGetSchema() {
        $service = new jork\NamingService;
        $service->set_alias('Model_User', 'user');
        $schema = $service->get_schema('user.posts');
        $this->assertEquals($schema, jork\model\AbstractModel::schema_by_class('Model_Post'));

        $service->set_alias('user.posts', 'post');
        $schema = $service->get_schema('post.topic');
        $this->assertEquals($schema, jork\model\AbstractModel::schema_by_class('Model_Topic'));

        $schema = $service->get_schema('post.topic.name');
        $this->assertEquals(cy\JORK::primitive('name', 'string'), $schema);
    }

    public function testTableAlias() {
        $service = new jork\NamingService;
        $service->set_alias('Model_Post', 'post');
        $service->set_alias('Model_Post', 'post2');
        $this->assertEquals('t_posts_0', $service->table_alias('post', 't_posts'));
        $this->assertEquals('t_posts_0', $service->table_alias('post', 't_posts'));
        $this->assertEquals('t_posts_1', $service->table_alias('post2', 't_posts'));
    }

    public function testImplRoot() {
        $srv = new jork\NamingService;
        $srv->set_implicit_root('Model_User');
        $this->assertEquals($srv->get_schema(NULL)
                , jork\model\AbstractModel::schema_by_class('Model_User'));
    }
}