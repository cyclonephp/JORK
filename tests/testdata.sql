USE jork_test;
/*
drop table if exists t_posts;
drop table if exists t_topics;
drop table if exists t_categories;
drop table if exists t_users;
drop table if exists user_contact_info;
drop table if exists categories_topics;
/**/
create table if not exists t_posts(
    id int primary key auto_increment,
    name text,
    topic_fk int not null,
    user_fk int not null,
    created_at datetime,
    creator_fk int not null,
    modified_at datetime,
    modifier_fk int
);

create table if not exists t_topics (
    id int primary key auto_increment,
    name text,
    created_at datetime not null,
    creator_fk int not null,
    modified_at datetime,
    modifier_fk int
);

create table if not exists t_categories (
    id int primary key auto_increment,
    name text,
    moderator_fk int,
    created_at datetime,
    creator_fk int not null,
    modified_at datetime,
    modifier_fk int
);

create table if not exists t_users (
    id int primary key auto_increment,
    name varchar(64),
    password varchar(32),
    created_at datetime not null
);

create table if not exists user_contact_info (
    user_fk int not null,
    email varchar(128),
    phone_num text
);

create table if not exists categories_topics(
    topic_fk int not null,
    category_fk int not null,
    primary key (topic_fk, category_fk)
);

start transaction;

truncate table t_posts;

truncate table t_topics;

truncate table t_categories;

truncate table t_users;

truncate table user_contact_info;

truncate table categories_topics;


/**/
insert into t_users values
(1, 'user1', 'pwd1', '2010-01-06')
, (2, 'user2', 'pwd2', '2010-01-06')
, (3, 'user3', 'pwd3', '2010-01-06')
, (4, 'user4', 'pwd4', '2010-01-06');

insert into user_contact_info values
(1, 'user1@example.com', '001')
, (2, 'user2@example.com', '002')
, (4, 'user4@example.com', '004');

insert into t_topics (id, name, created_at, creator_fk) values
(1, 'topic 01', '2011-01-06', 1)
, (2, 'topic 02', '2011-01-06', 1)
, (3, 'topic 03', '2011-01-06', 3)
, (4, 'topic 04', '2011-01-06', 4);

insert into t_posts (id, name, topic_fk, user_fk, created_at, creator_fk) values
(1, 't 01 p 01', 1, 1, '2011-01-06', 1)
, (2, 't 01 p 02', 1, 3, '2011-01-06', 3)
, (3, 't 02 p 01', 2, 1, '2011-01-06', 1)
, (4, 't 04 p 01', 4, 2, '2011-01-06', 2);

insert into t_categories (id, c_name, moderator_fk, created_at, creator_fk) values
(1, 'cat 01', NULL, '2011-01-06', 1)
, (2, 'cat 02', 1, '2011-01-06', 1)
, (3, 'cat 03', 2, '2011-01-06', 1);

insert into categories_topics (topic_fk, category_fk) values
(1, 1)
, (1, 2)
, (1, 3)
, (2, 2);

 commit;
