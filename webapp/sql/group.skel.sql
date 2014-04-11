DROP TABLE IF EXISTS <PREFIX>_group;
CREATE TABLE <PREFIX>_group (
    id int not null auto_increment,
    uid int not null,
    parent_id int not null,
    name varchar(255) not null,
    note text,
    depth tinyint not null default 0,
    sort tinyint,
    is_last tinyint(1) default 0,
    created timestamp,
    modified timestamp,
    delete_flag tinyint(1) default 0,
    primary key(id)
) ENGINE=InnoDB DEFAULT CHARSET=<CHARSET>;

INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(1, 1,0, '○○株式会社');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(2, 1,1, '経営管理部');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(3, 1,1, '総務部');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(4, 1,1, '経理部');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(5, 1,1, '情報システム部');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(6, 1,1, '営業部');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(7, 1,1, '事業部');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(8, 1,3, '総務統括課');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(9, 1,3, '総務施設課');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(10, 1,4, '財務経理課');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(11, 1,4, '経営管理課');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(12, 1,4, '経理企画課');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(13, 1,5, '情報システム課');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(14, 1,5, '技術システム課');
INSERT INTO <PREFIX>_group (id, uid, parent_id, name) values(15, 1,5, 'ICT統合推進課');
ALTER TABLE <PREFIX>_group AUTO_INCREMENT=15;

