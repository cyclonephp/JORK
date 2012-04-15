<?php

$here = realpath(__DIR__);

$rval = array(
    'Model_Topic' => require  $here . '/basic/topic.php',
    'Model_Category' => require  $here . '/basic/category.php',
    'Model_Post' => require  $here . '/basic/post.php',
    'Model_User' => require $here . '/basic/user.php',
);
//
//foreach ($rval as $class => $schema) {
//    foreach ($schema->embedded_components as $k => $v) {
//        $emb_inst = call_user_func(array($v, 'inst'));
//        $emb_schema = new \cyclone\jork\schema\EmbeddableSchema($schema, $v);
//        $emb_inst->_schema = $emb_schema;
//        $emb_inst->setup();
//        $emb_schema->table = $schema->table;
//        $v = $emb_schema;
//    }
//}

unset($here);

return $rval;