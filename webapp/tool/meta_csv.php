<?php
/**
 * make sql, recipe files by meta.csv
 *
 * PHP 5
 *
 * Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 * @copyright     Copyright 2011-2012, Bmath Web Application Platform Project. (http://bmath.jp)
 * @link          http://bmath.jp Bmath Web Application Platform Project
 * @package       Ao.webapp.tool
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author        aotake (aotake@bmath.org)
 */

/*
 * meta.csv を読み込んで sqlファイルと recipe ファイルを作成する
 *
 * [note] 現在は meta.xml を metaprocessor.php で処理している
 */

require_once "Meta.php";
if($argc < 2){
    print "Usage: ".$argv[0]." <metafile_path>\n";
    exit;
}
$metafile = $argv[1];
$m = new Meta();
$m->load($metafile);
//print_r($m->tables());
//print_r($m->recipe());
//print_r($m->db());
//exit;
$m->generateSqlSkelton();
$m->generateRecipefile();
$m->showNextStep();


/** 以下メタファイルサンプル *****

# テーブル・レシピ作成用メタファイル
[[module]]
name:tabi
[[/module]]

[[db]]
type: mysql
version: 5.x
charset: utf8
engine: InnoDB
[[/db]]

[[table: category, note: カテゴリ管理]]
#----------+--------------+--------+--------+--------+--------------+--------------
# colname  | data type    |is null | key    | default| extra        | label
#----------+--------------+--------+--------+--------+--------------+--------------
id,          int,          not null, primary,        ,auto_increment, 主キー
uid,         int,          not null,        ,        ,              , ユーザID
parent_id,   int,          not null,        ,        ,              , 親カテゴリ
title,       varchar(255), not null,        ,        ,              , カテゴリ名
note,        text,                 ,        ,        ,              , 備考
created,     timestamp,            ,        ,        ,              , 登録日
modified,    timestamp,            ,        ,        ,              , 最終更新日
delete_flag, int,                  ,        ,        ,              , 削除フラグ
[[/table]]

[[table: image, note: 画像管理]]
#----------+--------------+--------+--------+--------+--------------+-----------------------
# colname  | data type    |   null | key    | default| extra        | label
#----------+--------------+--------+--------+--------+--------------+-----------------------
id,          int,          not null, primary,        ,auto_increment, 主キー
uid,         int,          not null,        ,        ,              , ユーザID
target,      varchar(32),          ,        ,        ,              , ターゲットテーブル名
target_id,   int,                  ,        ,        ,              , ターゲットテーブルID
title,       varchar(255),         ,        ,        ,              , 画像タイトル
note,        text,                 ,        ,        ,              , 備考
sort,        int,                  ,        ,        ,              , 並び順
file_name,   varchar(255),         ,        ,        ,              , 保存ファイル名
orig_name,   varchar(255),         ,        ,        ,              , オリジナルファイル名
file_size,   int,                  ,        ,        ,              , ファイルサイズ
mime_type,   varchar(64),          ,        ,        ,              , MIME-TYPE
exif,        text,                 ,        ,        ,              , EXIF情報
created,     timestamp,            ,        ,        ,              , 登録日
modified,    timestamp,            ,        ,        ,              , 最終更新日
delete_flag, int,                  ,        ,        ,              , 削除フラグ
[[/table]]

[[table: contents, note: コンテンツテキスト管理]]
#----------+--------------+--------+--------+--------+--------------+-----------------------
# colname  | data type    |   null | key    | default| extra        | label
#----------+--------------+--------+--------+--------+--------------+-----------------------
id,          int,          not null, primary,        ,auto_increment, 主キー
uid,         int,          not null,        ,        ,              , ユーザID
category_id, int,          not null,        ,        ,              , カテゴリID
annotation , text,                 ,        ,        ,              , 注釈
body,        text,                 ,        ,        ,              , 本文
explanation, text,                 ,        ,        ,              , 解説
created,     timestamp,            ,        ,        ,              , 登録日
modified,    timestamp,            ,        ,        ,              , 最終更新日
delete_flag, int,                  ,        ,        ,              , 削除フラグ
[[/table]]

[[recipe]]
category all
image    vo,model,form,validator,manager
contents all
[[/recipe]]

/* ここまで*/
