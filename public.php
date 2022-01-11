<?php
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

 # You should have received a copy of the GNU General Public License
 # along with this program.  If not, see <http://www.gnu.org/licenses/>.
 #
 # -- END LICENSE BLOCK -----------------------------------

 # DISCLAIMER

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
 * Date: 04-11-2019
 * @name plugins_sendinblue_public
 */
require_once(__DIR__ .DIRECTORY_SEPARATOR. 'Sendinblue.php');
class plugins_sendinblue_public extends plugins_sendinblue_db
{
    protected
        $template,
        $data,
        $lang,
        $sanitize,
        $mail,
        $recaptcha = null;

    /**
     * @var SendinBlueManager $SB
     */
    private $SB;

    /**
     * @var string $firstname
     * @var string $lastname
     * @var string $email
     */
    public
        $gRecaptchaResponse,
        $firstname,
        $lastname,
        $email;

	/**
	 * plugins_sendinblue_public constructor.
	 * @param null|frontend_model_template $t
	 */
    public function __construct($t = null) {
        $this->template = $t instanceof frontend_model_template ? $t : new frontend_model_template();
		$this->data = new frontend_model_data($this);
		$this->lang = $this->template->lang;
	}

	/**
	 * Assign data to the defined variable or return the data
	 * @param string $type
	 * @param string|int|null $id
	 * @param string $context
	 * @param boolean $assign
	 * @return mixed
	 */
	private function getItems($type, $id = null, $context = null, $assign = true) {
		return $this->data->getItems($type, $id, $context, $assign);
	}

    // ---
    // --- Google Recaptcha

    /**
     * @throws Exception
     */
    private function recaptchaEnabled() {
        if(class_exists('plugins_recaptcha_public') && $this->amp == false) {
            $pluginModel = new frontend_model_plugins();
            if($pluginModel->isInstalled('recaptcha')) $this->recaptcha = new plugins_recaptcha_public();
        }
    }

    // ---
    // --- Methods

	/**
	 * Set the notification
	 * @param string $type
	 * @param null|string $subContent
	 * @return array
	 */
	private function setNotify($type,$subContent=null) {
		$message = null;
		switch($type){
			case 'warning':
				$warning = array(
					'empty' =>  $this->template->getConfigVars('fields_empty')
				);
				$message = $warning[$subContent];
				break;
			case 'success':
				$message = $this->template->getConfigVars('subscription_success');
				break;
			case 'error':
				$error = array(
					'api' => $this->template->getConfigVars('error_no_api'),
					'list' => $this->template->getConfigVars('error_no_list'),
					'request' => $this->template->getConfigVars('error_request')
				);
				$message = $error[$subContent];
				break;
		}

		return $message === null ? null : ['type' => $type,'content' => $message];
	}

	/**
	 * Display the notification
	 * @param $type
	 * @param null $subContent
	 */
	private function getNotify($type,$subContent=null) {
		$message = $this->setNotify($type,$subContent);
		if($message !== null) {
			$this->template->assign('message',$message);
			$this->template->display('sendinblue/notify/message.tpl');
		}
	}

    /**
     * @param $email
     * @return bool
     */
	public function isSuscriber($email) {
	    if(!empty($email)) {
            $api = $this->getItems('api',null,'one',false);

            // *** If the API key has been set
            if($api) {
                $lists = $this->getItems('active_lists',null,'all',false);

                // *** If there is a list registered for subscription
                if(!empty($lists)) {
                    $sendinblue = new \Drewm\sendinblue($api['api_key']);
                    foreach ($lists as $list) {
                        $id = $list['list_id'];
                        $hash = md5(strtolower($email));
                        //$member = $sendinblue->call('lists/'.$id.'/members/'.$hash, 'GET',['fields' => 'members.id,members.status']);
                        $member = $sendinblue->call('lists/'.$id.'/members/'.$hash, 'GET');

                        // *** If there is an entry for this email and the member status is active
                        if(!empty($member) && $member['status'] === 'subscribed') {
                            return true;
                        }
                    }
                }
            }
        }
	    return false;
    }

    /**
     * Envoi du mail
     */
    protected function send_email() {
        if(!empty($this->email) && $this->lang) {
            $this->template->configLoad();
            $this->sanitize = new filter_sanitize();

            if(!$this->sanitize->mail($this->email)) {
                $this->getNotify('warning','mail');
            }
            else {
                $contact = new plugins_contact_public();
                $this->mail = new frontend_model_mail('sendinblue');
                $sender = $contact->getSender();
                $this->mail->send_email(
                    $this->email,
                    'subscribe',
                    [
                        'email_subscriber' => $this->email,
                        'firstname_subscriber' => $this->firstname,
                        'lastname_subscriber' => $this->lastname
                    ],
                    $this->template->getConfigVars('title_mail_subscribe'),
                    $sender['mail_sender'],
                    $sender['mail_sender']
                );
            }
        }
    }

    // ---

	/**
	 * Controller
	 */
	public function run() {
        $formClean = new form_inputEscape();
        if (http_request::isPost('firstname')) $this->firstname = $formClean->simpleClean($_POST['firstname']);
        if (http_request::isPost('lastname')) $this->lastname = $formClean->simpleClean($_POST['lastname']);
        if (http_request::isPost('email')) $this->email = $formClean->simpleClean($_POST['email']);

        // - Google reCaptcha
        if(http_request::isPost('g-recaptcha-response'))$this->gRecaptchaResponse = $formClean->simpleClean($_POST['g-recaptcha-response']);

		// *** If every fields has been filled
		if($this->firstname !== "" && $this->lastname !== "" && $this->email !== "") {
			$api = $this->getItems('api',[],'one',false);

			// *** If the API key has been set
			if($api) {
				$lists = $this->getItems('iso_lists',$this->template->lang,'all',false);
                $this->SB = new SendinBlueManager($api['api_key']);
				// *** If there is a list registered for subscription
				if(!empty($lists)) {
					foreach ($lists as $list) {
                        $this->recaptchaEnabled();
                        $add = true;
                        if($this->recaptcha->active && !$this->recaptcha->getRecaptcha()) $add = false;
                        if($add) $this->SB->addMemberToList($list['list_id'], $this->email, $this->firstname, $this->lastname);
                        $this->getNotify('success');

						// *** Display the notification depending on the result of the request
						/*if(!$result || $result['status'] === 404) {
							$this->getNotify('error','request');
						}
						else {
						    //$this->send_email();
							$this->getNotify('success');
						}*/
					}
				}
				else {
					$this->getNotify('error','list');
				}
			}
			else {
				$this->getNotify('error','api');
			}
		}
		else {
			$this->getNotify('warning','empty');
		}
	}
}