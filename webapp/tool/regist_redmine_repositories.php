<?php
/**
 * regist redmine repositories
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

//
// redmine に cloneproject.csv のプロジェクトを登録する
//
// 注意：
// webapp/config/custom/redmine.conf.php に redmine のデータベースアクセス用設定を保存。
// gitosis のリポジトリのトップが /var/lib/gitosis/repositories と決めうちしてる
//

// モジュールの tool ディレクトリで使う時
//$topdir = realpath(dirname(__FILE__)."/../../../../");
//$webappdir = realpath(dirname(__FILE__)."/../../../");
// webapp/tool で使う時
$topdir = realpath("../../");
$webappdir = realpath("..");

//$modlibdir = array(
//    $webappdir."/modules/redmine/libs",
//);

// フレームワークのツール利用環境を設定
require_once $webappdir."/tool/CodeTool.php";
$config_file = $webappdir."/config/custom/config.ini";
$tool = new CodeTool();
$tool->setSyslibDir($topdir."/libs");
$tool->setWebappDir($webappdir);
$tool->setHtdocsDir($topdir."/html");
//$tool->setModlibDirs($modlibdir);
$tool->setupPath();

// ユーザDB接続設定($redmine_db_conf)変数の読み込み
include realpath(dirname(__FILE__)."/../config/custom/redmine.conf.php");

// 設定をロード
$conf_file = $webappdir."/config/custom/config.ini";
$section = $tool->getApplicationEnv();
$tool->loadConfig($conf_file, $section);
$tool->setupDbAdapter(); // for using Model
$tool->setupUserDbAdapter($redmine_db_conf, "second_db");
$tool->setupLooger();
$conf = $tool->getConfig();


// redmine 情報取得
if($argc != 2){
    print "No project id!\n\n";
    print "\tUsage: php ".$argv[0]." project_identifier\n\n";
    exit;
}
$project_identifier = trim($argv[1]);
include dirname(__FILE__)."/redmine.class.php";
$project_manager = new Redmine_Manager_Projects();
$repository_manager = new Redmine_Manager_Repositories();

$project_row = $project_manager->getProjectByIdentifier($project_identifier);
if(!$project_row){
    print "Not found project for project_identifier = $project_identifier\n";
    exit;
}
$repository_rows = $repository_manager->getRepositoriesByProjectId($project_row["id"]);
//print_r($repository_rows);
$repository_urls = array();
foreach($repository_rows as $row){
    $repository_urls[$row["identifier"]] = $row["url"];
}
//print_r($repository_urls);


// cloneproject.csv 情報取得
$cloneproject_csv = $webappdir."/config/custom/cloneproject.csv";
if(!file_exists($cloneproject_csv)){
    print "not found: $cloneproject_csv\n";
    exit;
}
$fp = fopen($cloneproject_csv, "r");
if(!$fp){
    print "cannot open: $cloneproject_csv\n";
    exit;
}
$cloneprojects = array();
$gitosis_top = "/var/lib/gitosis/repositories";
while (($buffer = fgets($fp, 4096)) !== false){
    // CSV を分割して identifier の文字列を追加した配列を取得
    // 0: タイプ
    // 1: リポジトリURL
    // 2: ディレクトリ名
    // 3: redmine 用 identifier 文字列
    $repository_info = $repository_manager->getRepositoryInfoByCloneprojectLine($buffer, $project_identifier, $gitosis_top);
    $cloneprojects[] = $repository_info;
}
fclose($fp);

//print_r($cloneprojects);
//exit;
foreach($cloneprojects as $row){

    // HEAD のリビジョン番号は redmine にアクセスした時自動的に更新してくれるっぽい
    //$heads = $repository_manager->getHeadsByUrl($row[1]);

    $url = $row[1];
    $identifier = $row[3];

    if(in_array($url, $repository_urls) && in_array($identifier, array_keys($repository_urls))){
        continue;
    }
    else if(in_array($url, $repository_urls) && !in_array($identifier, array_keys($repository_urls))){
        $rep = $repository_manager->getRepositoryByUrl($data["url"]);
        $rep["identifier"] = $data["identifier"];
        $data = $rep;
    }
    else if(!in_array($url, $repository_urls) && in_array($identifier, array_keys($repository_urls))){
        $rep = $repository_manager->getRepositoryByIdentifier($data["identifier"]);
        $rep["url"] = $data["url"];
        $rep["root_url"] = $data["root_url"];
        $data = $rep;
    } else {
        $data["project_id"] = $project_row["id"];
        $data["url"] = $url;
        $data["login"] = null;
        $data["password"] = null;
        $data["root_url"] = $url;
        $data["type"] = "Git";
        $data["path_encoding"] = null;
        $data["log_encoding"] = null;
        $data["extra_info"] = "---\nextra_report_last_commit: \"1\"\nheads:\ndb_consistent:\nordering: 1\n\n";
        $data["identifier"] = $identifier;
        $data["is_default"] = 0;
    }

    print "save: $identifier, $url\n";
    try{
        $repository_manager->saveRepository($data);
    }catch(Zend_Exception $e){
        print $e->getMessage();
        throw $e;
    }
}
