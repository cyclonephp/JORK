<?php
 // just some brainstorming


// the followings are equivalent
JORK::from('Model_User user')->with('user.posts');
// does not have to specify root entity if this is obvious
JORK::from('Model_User user')->with('posts');
// do not have to specify alias if there is only one root entity
JORK::from('Model_User')->with('posts');



// alias is required
JORK::from('Model_User user', 'Model_Topic topic');
// equivalent
JORK::from('Model_User user')->from('Model_Topic topic');
// must throw an exception
JORK::from('Model_User', 'Model_Topic topic');


// specifying properties to be selected
JORK::select('user.id', 'user.name')->from('Model_User user');
// don't have to specify owner if it's obvious
JORK::select('id', 'name')->from('Model_User user');


// maybe some aliasing?
JORK::select('id uid', 'name uname')->from('Model_User');


// components can also be selected
JORK::select('id', 'name', 'posts')->from('Model_Topic');
//equivalent to
JORK::select('id', 'name')->from('Model_Topic')->with('posts');


// only posts and categories are loaded fully, topics are skeleton models
JORK::from('Model_Category')->with_only('topics.posts');


JORK::select('user')->from('Model_User user', 'Model_User u2')
                ->where('user.created_at', '>', 'u2.created_at');

// only categories where there are topics
JORK::from('Model_Category')->with('topics topic')->where('topic.active', '=', true);

// empty categories are loaded too
JORK::from('Model_Category cat')
    ->with(JORK::from('cat.topics topic')->where('topic.active', '=', true));

JORK::select(array(
    'user' => array('Model_User', 'id', 'name'),
    'topic' => array('Model_Topic', 'id', 'title', 'posts')
))->from('Model_User user', 'Model_Topic topic');

JORK::select('user{id,name}', 'topic{id,title,posts}')->from('Model_User user', 'Model_Topic topic');