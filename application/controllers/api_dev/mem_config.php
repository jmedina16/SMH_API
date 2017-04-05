<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Mem_config extends REST_Controller {

    public function __construct() {

        parent::__construct();
        $this->load->model('/mem_dev/mem_config_model');
    }

    public function add_email_reg_config_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $reg_subject = str_replace(array("\n", "\\n"), "", $this->get('reg_subject'));
        $reg_body = str_replace(array("\n", "\\n"), "", $this->get('reg_body'));
        $reg_default = $this->get('reg_default');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($reg_subject) || $reg_subject == null) {

            $this->response(array('error' => 'Missing register subject'), 200);
        }

        if (!isset($reg_body) || $reg_body == null) {

            $this->response(array('error' => 'Missing register body'), 200);
        }

        if (!isset($reg_default) || $reg_default == null) {

            $this->response(array('error' => 'Missing default setting'), 200);
        }

        $emailConfig = $this->mem_config_model->addRegEmailConfig($pid, $ks, $reg_subject, $reg_body, $reg_default);

        if (!$emailConfig) {

            $this->response($emailConfig, 200);
        }

        $this->response($emailConfig, 200); // 200 being the HTTP response code
    }

    public function add_email_pswd_config_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $pswd_subject = str_replace(array("\n", "\\n"), "", $this->get('pswd_subject'));
        $pswd_body = str_replace(array("\n", "\\n"), "", $this->get('pswd_body'));
        $pswd_default = $this->get('pswd_default');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($pswd_subject) || $pswd_subject == null) {

            $this->response(array('error' => 'Missing password subject'), 200);
        }

        if (!isset($pswd_body) || $pswd_body == null) {

            $this->response(array('error' => 'Missing password body'), 200);
        }

        if (!isset($pswd_default) || $pswd_default == null) {

            $this->response(array('error' => 'Missing default setting'), 200);
        }

        $emailConfig = $this->mem_config_model->addPswdEmailConfig($pid, $ks, $pswd_subject, $pswd_body, $pswd_default);

        if (!$emailConfig) {

            $this->response($emailConfig, 200);
        }

        $this->response($emailConfig, 200); // 200 being the HTTP response code
    }

    public function get_email_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $email = $this->mem_config_model->get_email($pid, $ks);

        if (!$email) {

            $this->response($email, 200);
        }

        $this->response($email, 200); // 200 being the HTTP response code
    }

    public function get_email_config_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $email = $this->mem_config_model->get_email_config($pid, $ks);

        if (!$email) {

            $this->response($email, 200);
        }

        $this->response($email, 200); // 200 being the HTTP response code
    }

    public function update_email_config_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $from_name = $this->get('from_name');
        $from_email = $this->get('from_email');
        $use_default = $this->get('use_default');
        $smtp_server = $this->get('smtp_server');
        $smtp_port = $this->get('smtp_port');
        $smtp_auth = $this->get('smtp_auth');
        $smtp_pass = $this->get('smtp_pass');
        $smtp_secure = $this->get('smtp_secure');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($from_name) || $from_name == null) {

            $this->response(array('error' => 'Missing from_name'), 200);
        }

        if (!isset($from_email) || $from_email == null) {

            $this->response(array('error' => 'Missing from_email'), 200);
        }

        $setupResponse = $this->mem_config_model->update_email_config($pid, $ks, $from_name, $from_email, $use_default, $smtp_server, $smtp_port, $smtp_auth, $smtp_pass, $smtp_secure);

        if (!$setupResponse) {

            $this->response($setupResponse, 200);
        }

        $this->response($setupResponse, 200); // 200 being the HTTP response code
    }

    public function get_player_details_get() {
        $pid = $this->get('pid');
        $uiconf = $this->get('uiconf');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($uiconf) || $uiconf == null) {

            $this->response(array('error' => 'Missing uiconf'), 200);
        }

        $setupResponse = $this->mem_config_model->get_player_details($pid, $uiconf);

        if (!$setupResponse) {

            $this->response($setupResponse, 200);
        }

        $this->response($setupResponse, 200); // 200 being the HTTP response code      
    }

    public function list_ac_type_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $draw = $this->get('draw');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $acListType = $this->mem_config_model->list_ac_type($pid, $ks, $start, $length, $draw);

        if (!$acListType) {

            $this->response($acListType, 200);
        }

        $this->response($acListType, 200); // 200 being the HTTP response code
    }

    public function setup_player_get() {
        $sm_ak = $this->get('sm_ak');
        $entry_id = $this->get('entry_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        $setup = $this->mem_config_model->setup_player($sm_ak, $entry_id);

        if (!$setup) {

            $this->response($setup, 200);
        }

        $this->response($setup, 200); // 200 being the HTTP response code  
    }

    public function w_get_thumb_get() {
        $sm_ak = $this->get('sm_ak');
        $entry_id = $this->get('entry_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        $thumb = $this->mem_config_model->w_get_thumb($sm_ak, $entry_id);

        if (!$thumb) {

            $this->response($thumb, 200);
        }

        $this->response($thumb, 200); // 200 being the HTTP response code
    }

    public function get_thumb_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $entry_id = $this->get('entry_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $thumb = $this->mem_config_model->get_thumb($pid, $ks, $entry_id);

        if (!$thumb) {

            $this->response($thumb, 200);
        }

        $this->response($thumb, 200); // 200 being the HTTP response code
    }

    public function get_cat_thumb_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cat_id = $this->get('cat_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($cat_id) || $cat_id == null) {

            $this->response(array('error' => 'Missing category id'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $thumb = $this->mem_config_model->get_cat_thumb($pid, $ks, $cat_id);

        if (!$thumb) {

            $this->response($thumb, 200);
        }

        $this->response($thumb, 200); // 200 being the HTTP response code
    }

    public function w_get_cat_thumb_get() {
        $sm_ak = $this->get('sm_ak');
        $cat_id = $this->get('cat_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($cat_id) || $cat_id == null) {

            $this->response(array('error' => 'Missing category id'), 200);
        }

        $thumb = $this->mem_config_model->w_get_cat_thumb($sm_ak, $cat_id);

        if (!$thumb) {

            $this->response($thumb, 200);
        }

        $this->response($thumb, 200); // 200 being the HTTP response code
    }

}