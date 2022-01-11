<?php
require_once ('db.php');
/*
 # -- BEGIN LICENSE BLOCK ----------------------------------
 #
 # This file is part of MAGIX CMS.
 # MAGIX CMS, The content management system optimized for users
 # Copyright (C) 2008 - 2013 magix-cms.com <support@magix-cms.com>
 #
 # OFFICIAL TEAM :
 #
 #   * Gerits Aurelien (Author - Developer) <aurelien@magix-cms.com> <contact@aurelien-gerits.be>
 #
 # Redistributions of files must retain the above copyright notice.
 # This program is free software: you can redistribute it and/or modify
 # it under the terms of the GNU General Public License as published by
 # the Free Software Foundation, either version 3 of the License, or
 # (at your option) any later version.
 #
 # This program is distributed in the hope that it will be useful,
 # but WITHOUT ANY WARRANTY; without even the implied warranty of
 # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 # GNU General Public License for more details.
 #
 # You should have received a copy of the GNU General Public License
 # along with this program.  If not, see <http://www.gnu.org/licenses/>.
 #
 # -- END LICENSE BLOCK -----------------------------------
 #
 # DISCLAIMER
 #
 # Do not edit or add to this file if you wish to upgrade MAGIX CMS to newer
 # versions in the future. If you wish to customize MAGIX CMS for your
 # needs please refer to http://www.magix-cms.com for more information.
 */
 /**
 * MAGIX CMS
 * @category sendinblue
 * @package plugins
 * @copyright MAGIX CMS Copyright (c) 2008 - 2019 Gerits Aurelien,
 * http://www.magix-cms.com,  http://www.magix-cjquery.com
 * @license Dual licensed under the MIT or GPL Version 3 licenses.
 * @version 2.0
 * Author: Salvatore Di Salvo
 * Date: 10-08-2021
 * @name plugins_sendinblue_admin
 */
require_once(__DIR__ .DIRECTORY_SEPARATOR. 'Sendinblue.php');
class plugins_sendinblue_admin extends plugins_sendinblue_db
{
	/**
	 * @var object
	 */
	protected $controller,
		$data,
		$template,
		$message,
		$modelLanguage,
		$collectionLanguage,
        $formClean;

    /**
     * @var SendinBlueManager $SB
     */
	private $SB;

	/**
	 * Les variables globales
	 * @var integer $edit
	 * @var string $action
	 * @var string $tabs
	 */
	public $edit = 0,
		$edit_type = '',
		$action = '',
		$tabs = '';

	/**
	 * @var int $id
	 * @var int $id_api
	 * @var int $page
	 * @var int $offset
	 */
	public $id = 0,
		$id_api = 0,
		$page = 0,
		$offset = 25;

	/**
     * @var string $api_key
     * @var string $list_id
     * @var string $name_list
	 */
	public $list_id = '',
		$name_list = '';

    /**
     * @var array $content
     */
	public $content = [];

    /**
	 * Construct class
	 */
	public function __construct() {
        $this->template = new backend_model_template();
        //$this->modelLanguage = new backend_model_language($this->template);
        //$this->collectionLanguage = new component_collections_language();
        //$this->data = new backend_model_data($this);
		//$this->plugins = new backend_controller_plugins();
		//$this->message = new component_core_message($this->template);
		//$this->settings = new backend_model_setting();
		//$this->setting = $this->settings->getSetting();
		//$this->header = new http_header();
	}

    /**
     * Method to override the name of the plugin in the admin menu
     * @return string
     */
    public function getExtensionName()
    {
        return $this->template->getConfigVars('sendinblue_plugin');
    }

    /**
     * Assign data to the defined variable or return the data
     * @param string $type
     * @param string|int|array|null $id
     * @param string|null $context
     * @param boolean $assign
     * @return mixed
     */
    private function getItems(string $type, $id = null, string $context = null, bool $assign = true) {
        return $this->data->getItems($type, $id, $context, $assign);
    }

	// --- init

    /**
     *
     */
    private function init() {
        $this->modelLanguage = new backend_model_language($this->template);
        $this->collectionLanguage = new component_collections_language();
        $this->message = new component_core_message($this->template);
        $this->data = new backend_model_data($this);
        $this->formClean = new form_inputEscape();
    }

    /**
     *
     */
	private function parseRequest() {
        //$formClean = new form_inputEscape();

        // --- GET
        if(http_request::isGet('controller')) $this->controller = $this->formClean->simpleClean($_GET['controller']);
        if (http_request::isGet('edit')) $this->edit = $this->formClean->numeric($_GET['edit']);
        //if (http_request::isGet('action')) $this->action = $this->formClean->simpleClean($_GET['action']);
        //elseif (http_request::isPost('action')) $this->action = $this->formClean->simpleClean($_POST['action']);
        if (http_request::isPost('edit_type')) $this->edit_type = $this->formClean->simpleClean($_POST['edit_type']);
        if (http_request::isGet('tabs')) $this->tabs = $this->formClean->simpleClean($_GET['tabs']);
        if (http_request::isGet('page')) $this->page = intval($this->formClean->simpleClean($_GET['page']));
        $this->offset = (http_request::isGet('offset')) ? intval($this->formClean->simpleClean($_GET['offset'])) : 25;

        // --- ADD or EDIT
        if (http_request::isPost('id')) $this->id = intval($this->formClean->simpleClean($_POST['id']));
        if (http_request::isPost('id_api')) $this->id_api = $this->formClean->numeric($_POST['id_api']);
        if (http_request::isPost('list_id')) $this->list_id = $this->formClean->simpleClean($_POST['list_id']);
        if (http_request::isPost('content')) $this->content = $this->formClean->arrayClean($_POST['content']);

        // --- Config
        if (http_request::isPost('api_key')) $this->api_key = (string)$this->formClean->simpleClean($_POST['api_key']);
    }

    // ---
    // --- Methods

	/**
	 * @param $data
	 * @return array
	 */
	private function setItemContentData($data){
		$arr = array();
		foreach ($data as $page) {
			if (!array_key_exists($page['id_list'], $arr)) {
				$arr[$page['id_list']] = array();
				$arr[$page['id_list']]['id_list'] = $page['id_list'];
				$arr[$page['id_list']]['list_id'] = $page['list_id'];
			}
			$arr[$page['id_list']]['content'][$page['id_lang']] = array(
				'id_lang' => $page['id_lang'],
				'name_list' => $page['name_list'],
				'active' => $page['active']
			);
		}
		return $arr;
	}

	/**
	 * @param string $id_list
	 */
	private function saveContent(string $id_list)
	{
		$langs = $this->modelLanguage->setLanguage();

		foreach ($langs as $id => $iso) {
			$content = isset($this->content[$id]) ? $this->content[$id] : array();
			$content['id_lang'] = $id;
			$content['id_list'] = $id_list;
			$content['active'] = isset($content['active']) ? 1 : 0;
			if(!isset($content['name_list']) || empty($content['name_list'])) $content['name_list'] = $this->name_list;
			else $this->name_list = $content['name_list'];
			$params = array(
				'type' => 'content',
				'data' => $content
			);

			$contentList = $this->getItems('content',array('id_list'=>$id_list, 'id_lang'=>$id),'one',false);

			if($contentList) {
				$this->upd($params);
			}
			else {
				$this->add($params);
			}
		}
	}

    // ---
	// --- Database actions

	/**
	 * Insert data
	 * @param array $config
	 */
	private function add(array $config)
	{
		switch ($config['type']) {
			case 'list':
			case 'content':
				parent::insert(
					array('type' => $config['type']),
					$config['data']
				);
				break;
		}
	}

	/**
	 * Update data
	 * @param array $config
	 */
	private function upd(array $config)
	{
		switch ($config['type']) {
			case 'api':
			case 'content':
				parent::update(
					array('type' => $config['type']),
					$config['data']
				);
				break;
		}
	}

	/**
	 * Delete a record
	 * @param $config
	 */
	private function del($config)
	{
		switch ($config['type']) {
			case 'list':
				parent::delete(
					array('type' => $config['type']),
					$config['data']
				);
				$this->message->json_post_response(true,'delete',array('id' => $this->id));
				break;
		}
	}

	// ---

	/**
	 * Execute the plugin
	 */
	public function run()
	{
	    $this->init();
		$defaultLanguage = $this->collectionLanguage->fetchData(['context'=>'one','type'=>'default']);
		$api = $this->getItems('api',null,'one');
        if($api['api_key'] !== null && !empty($api)) $this->SB = new SendinBlueManager($api['api_key']);

        if (http_request::isGet('action')) $this->action = (string)$this->formClean->simpleClean($_GET['action']);
        elseif (http_request::isPost('action')) $this->action = (string)$this->formClean->simpleClean($_POST['action']);
		if($this->action) {
		    $this->parseRequest();

			switch ($this->action) {
				case 'add':
					if(!empty($this->content)) {
						list($this->list_id, $this->name_list) = explode('|', $this->list_id);
						$this->add([
                            'type' => 'list',
                            'data' => [
                                'id_api' => $api['id_api'],
                                'list_id' => $this->list_id
                            ]
                        ]);

						$list = $this->getItems('root',null,'one',false);

						if ($list['id_list']) {
							$this->saveContent($list['id_list']);
							$this->message->json_post_response(true,'add_redirect');
						}
					}
					else {
						$this->modelLanguage->getLanguage();
                        $lists = $this->SB->getLists();
                        $this->template->assign('lists',$lists['lists']);

						$lists = $this->getItems('lists',$defaultLanguage['id_lang'],'all',false);
                        array_walk($lists, function(&$item, $key) { $item = $item['list_id']; });
						$this->template->assign('clists',$lists);

						$this->template->display('add.tpl');
					}
					break;
				case 'edit':
					if($this->id_api) {
						$this->upd([
                            'type' => 'api',
                            'data' => [
                                'id' => $this->id_api,
                                'key' => $this->api_key
                            ]
                        ]);
						$this->message->json_post_response(true, $this->edit_type === 'update' ? 'update' : 'add_redirect', ['result' => $this->id_api]);
					}
					elseif (isset($this->id) && !empty($this->content)) {
						$this->saveContent($this->id);
						$this->message->json_post_response(true, 'update', ['result'=>$this->id]);
					}
					else {
						$this->modelLanguage->getLanguage();
						$data = $this->getItems('data',$this->edit,'all',false);
						$editData = $this->setItemContentData($data);
						$this->template->assign('list',$editData[$this->edit]);

                        $membersList = $this->SB->getMembers($editData[$this->edit]['list_id'], $this->offset, $this->page);

                        $this->template->assign('nbp',ceil($membersList['count'] / $this->offset));
                        $this->template->assign('membersList',$membersList);

						$scheme = [
                            'email' => [
                                'type' => 'text',
                                'title' => 'email_list'
                            ],
                            /*'language' => [
                                'type' => 'text',
                                'title' => 'lang_list'
                            ]*/
                        ];
						$this->template->assign('scheme',$scheme);
						$this->template->display('edit.tpl');
					}
					break;
				case 'delete':
					if(isset($this->id)) {
						$this->del([
                            'type' => 'list',
                            'data' => ['id' => $this->id]
                        ]);
					}
					break;
			}
		}
		else {
			if(!empty($api)) {
				$this->getItems('lists',$defaultLanguage['id_lang'],'all');
				$assign = [
                    'id_list',
                    'list_id' => ['title' => 'list_id'],
                    'name_list' => ['title' => 'name']
                ];
				$this->data->getScheme(['mc_sendinblue_list','mc_sendinblue_content'],['id_list','list_id','name_list'],$assign);
			}
			$this->template->display('index.tpl');
		}
	}
}