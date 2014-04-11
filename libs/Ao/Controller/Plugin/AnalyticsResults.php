<?php
/**
 * Open Web Analytics Data Plugin
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
 * @package       Ao.Controller.Plugin
 * @since
 * @license       GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Open Web Analytics のデータを取得 
 * [以下の前提]
 * bootstrap.php で owa の include ファイル類があるディレクトリにパスが通っていること
 */
class Ao_Controller_Plugin_AnalyticsResults extends Zend_Controller_Plugin_Abstract
{
    private $_registry = null;
    private $_logger = null;
    private $_config = null;
    private $_dispatchCount = 0;

    public function __construct()
    {
        $this->_registry = Zend_Registry::getInstance();
        $this->_logger = $this->_registry["logger"];
        $this->_config = $this->_registry["config"];
    }

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function routeShutdown(Zend_Controller_Request_Abstract $reques)
    {
    }
 
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
    }
 
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $mod = $this->getRequest()->getModuleName();
        $ctr = $this->getRequest()->getControllerName();
        $act = $this->getRequest()->getActionName();
        if(isset($this->_config->site->analytics->site_id)){
            $params = array('do'          => 'getResultSet',
                'metrics'     => 'visitDuration,bounces,repeatVisitors,newVisitors,visits,pageViews',
                'dimensions'  => 'date,browserType',
                //'constraints' => 'browserType=Firefox 3.5',
                'startDate'   => '20111202',
                'endDate'     => '20111203',
                'sort'        => 'date-,browserType',
                'limit'       => 10,
                'siteId'      => $this->_config->site->analytics->site_id
            );
            $result_set = owa_coreAPI::executeApiCommand($params);
            $this->view->analytics_results = $result_set;
            //print_r($result_set->resultsRows);exit;
        }
    }
 
    public function dispatchLoopShutdown()
    {
    }
}
