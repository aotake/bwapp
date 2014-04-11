<?php
/**
 * redmine class
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
// Redmine のリポジトリ情報にアクセスするためのクラス
//
// 外部データベースのアダプタ
class Redmine_Model_Base extends Ao_Model_Abstract{
    protected function _setupDatabaseAdapter()
    {
        $this->_db = Zend_Registry::get("second_db");
    }
}
// モデル
class Redmine_Model_Repositories extends Redmine_Model_Base
{
    protected $_name = "repositories";
}
class Redmine_Model_Projects extends Redmine_Model_Base
{
    protected $_name = "projects";
}

// マネージャ
class Redmine_Manager_Repositories extends Manager
{
    protected $model;
    public function __construct()
    {
        $this->model = new Redmine_Model_Repositories();
    }
    public function getRepositoryVo($data = null)
    {
        if(is_array($data)){
            return $this->model->getVo($data);
        } else {
            return $this->model->getVo();
        }
    }
    public function getRepositoriesByProjectId($project_id)
    {
        $model = new Redmine_Model_Repositories();
        $select = $model->select()
                    ->where("project_id = ?", (int)$project_id)
                    ;
        $items = $model->fetchAll($select, 1); // Vo がないので Array で返す
        if(!$items){
            return null;
        }
        return $items;
    }
    public function getHeadsByUrl($url)
    {
        if(is_dir($url)){
            $path = $url;
        }
        else if(preg_match("/(.+)\.git$/", $url, $match) && is_dir($match[1])){
            $path = $url;
        } else {
            return false;
        }

        $content = shell_exec("/bin/cat $path/refs/heads/master");
        return $content;
    }
    public function getRepositoryByUrl($url)
    {
        $model = new Redmine_Model_Repositories();
        $select = $model->select()
                    ->where("url = ?", $url)
                    ;
        $items = $model->fetchAll($select, 1); // Vo がないので Array で返す
        if(!$items){
            return null;
        }
        return current($items);
    }
    public function getRepositoryByIdentifier($identifier)
    {
        $model = new Redmine_Model_Repositories();
        $select = $model->select()
                    ->where("identifier = ?", $identifier)
                    ;
        $items = $model->fetchAll($select, 1); // Vo がないので Array で返す
        if(!$items){
            return null;
        }
        return current($items);
    }
    public function getRepositoryInfoByCloneprojectLine($buffer, $project_identifier, $gitosis_top = "/var/lib/gitosis/repositories")
    {
        $tmp = array_map("trim", explode(",", $buffer));
        // gitosis にアクセスする "ssh://ユーザ名@ホスト名" の部分を除去して
        // gitosis のリポジトリトップへのパスを付け足す
        $tmp[1] = preg_replace("/ssh:\/\/[^@]+@[^\/]+(\/.+)/", "$gitosis_top\\1", $tmp[1]);
        // リポジトリのパスが .git で終わっていない場合 ".git" を付け足す
        if(!preg_match("/.+\.git$/", $tmp[1])){
            $tmp[1] .= ".git";
        }

        // redmine 用 identifier 作成
        switch($tmp[0]){
        case "webapp_config":
            $identifier = $project_identifier."-webapp-config"; break;
        case "layout":
            $identifier = $project_identifier."-layout-".basename($tmp[1],".git"); break;
        case "module":
            if(preg_match("/bmathlog_custom/", $tmp[1])){
                $identifier = $project_identifier."-module-custom-".$tmp[2];
            }
            else if(preg_match("/bmathlog_module/", $tmp[1])){
                $identifier = $project_identifier."-module-original-".$tmp[2];
            }
            break;
        case "module_config":
            $identifier = $project_identifier."-module-config-".basename(dirname($tmp[1])); break;
        case "templates_custom":
            $identifier = $project_identifier."-templates-".basename(dirname($tmp[1])); break;
        case "custom_controller":
            $identifier = $project_identifier."-controller-".basename(dirname($tmp[1])); break;
        case "custom_libs":
            $identifier = $project_identifier."-libs-".basename(dirname($tmp[1])); break;
        default:
            $identifier = null; break;
        }
        $tmp[3] = $identifier;
        return $tmp;
    }

    /**
     * 保存
     *
     * @params array $data データ
     * @return int
     */
    public function saveRepository($data)
    {
        $id = $this->model->save($data);
    }
}
class Redmine_Manager_Projects extends Manager
{
    protected $model;
    public function __construct()
    {
        $this->model = new Redmine_Model_Projects();
    }
    public function getProjectByIdentifier($identifier)
    {
        $model = new Redmine_Model_Projects();
        $select = $model->select()
                    ->where("identifier = ?", (string)$identifier)
                    ;
        $items = $model->fetchAll($select, 1); // Vo がないので Array で返す
        if(!$items){
            return null;
        }
        return current($items);
    }

    /**
     * 保存
     *
     * @params array $data データ
     * @return int
     */
    public function saveProject($data)
    {
        $id = $this->model->save($data);
    }
}

