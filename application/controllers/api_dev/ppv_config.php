<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Ppv_config extends REST_Controller {

    public function __construct() {

        parent::__construct();
        $this->load->model('/ppv_dev/ppv_config_model');
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

        $emailConfig = $this->ppv_config_model->addRegEmailConfig($pid, $ks, $reg_subject, $reg_body, $reg_default);

        if (!$emailConfig) {

            $this->response($emailConfig, 200);
        }

        $this->response($emailConfig, 200); // 200 being the HTTP response code
    }

    public function add_email_ty_config_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $ty_subject = str_replace(array("\n", "\\n"), "", $this->get('ty_subject'));
        $ty_body = str_replace(array("\n", "\\n"), "", $this->get('ty_body'));
        $ty_default = $this->get('ty_default');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($ty_subject) || $ty_subject == null) {

            $this->response(array('error' => 'Missing thank you subject'), 200);
        }

        if (!isset($ty_body) || $ty_body == null) {

            $this->response(array('error' => 'Missing thank you body'), 200);
        }

        if (!isset($ty_default) || $ty_default == null) {

            $this->response(array('error' => 'Missing default setting'), 200);
        }

        $tyConfig = $this->ppv_config_model->addTyEmailConfig($pid, $ks, $ty_subject, $ty_body, $ty_default);

        if (!$tyConfig) {

            $this->response($tyConfig, 200);
        }

        $this->response($tyConfig, 200); // 200 being the HTTP response code
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

        $emailConfig = $this->ppv_config_model->addPswdEmailConfig($pid, $ks, $pswd_subject, $pswd_body, $pswd_default);

        if (!$emailConfig) {

            $this->response($emailConfig, 200);
        }

        $this->response($emailConfig, 200); // 200 being the HTTP response code
    }

    public function add_gateway_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $gate_name = $this->get('gate_name');
        $gate_status = $this->get('gate_status');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($gate_name) || $gate_name == null) {

            $this->response(array('error' => 'Missing gateway name'), 200);
        }

        if (!isset($gate_status) || $gate_status == null) {

            $this->response(array('error' => 'Missing gateway status'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $gateway = $this->ppv_config_model->addGateway($pid, $ks, $gate_name, $gate_status);

        if (!$gateway) {

            $this->response($gateway, 200);
        }

        $this->response($gateway, 200); // 200 being the HTTP response code
    }

    public function get_gateways_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $gateway = $this->ppv_config_model->getGateways($pid, $ks);

        if (!$gateway) {

            $this->response($gateway, 200);
        }

        $this->response($gateway, 200); // 200 being the HTTP response code       
    }

    public function get_pp_config_get() {
        $sm_ak = $this->get('sm_ak');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        $gateway = $this->ppv_config_model->get_pp_config($sm_ak);

        if (!$gateway) {

            $this->response($gateway, 200);
        }

        $this->response($gateway, 200); // 200 being the HTTP response code        
    }

    public function add_paypal_config_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $api_user_id = $this->get('api_user_id');
        $api_pswd = $this->get('api_pswd');
        $api_sig = $this->get('api_sig');
        $currency = $this->get('currency');
        $setup = $this->get('setup');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($api_user_id) || $api_user_id == null) {

            $this->response(array('error' => 'Missing api_user_id'), 200);
        }

        if (!isset($api_pswd) || $api_pswd == null) {

            $this->response(array('error' => 'Missing api_pswd'), 200);
        }

        if (!isset($api_sig) || $api_sig == null) {

            $this->response(array('error' => 'Missing api_sig'), 200);
        }

        if (!isset($currency) || $currency == null) {

            $this->response(array('error' => 'Missing currency'), 200);
        }

        if (!isset($setup) || $setup == null) {

            $this->response(array('error' => 'Missing setup'), 200);
        }

        $paypalConfig = $this->ppv_config_model->add_paypal_config($pid, $ks, $api_user_id, $api_pswd, $api_sig, $currency, $setup);

        if (!$paypalConfig) {

            $this->response($paypalConfig, 200);
        }

        $this->response($paypalConfig, 200); // 200 being the HTTP response code
    }

    public function add_authnet_config_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $api_login_id = $this->get('api_login_id');
        $transaction_key = $this->get('transaction_key');
        $currency = $this->get('currency');
        $setup = $this->get('setup');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($api_login_id) || $api_login_id == null) {

            $this->response(array('error' => 'Missing api_login_id'), 200);
        }

        if (!isset($transaction_key) || $transaction_key == null) {

            $this->response(array('error' => 'Missing transaction_key'), 200);
        }

        if (!isset($currency) || $currency == null) {

            $this->response(array('error' => 'Missing currency'), 200);
        }

        if (!isset($setup) || $setup == null) {

            $this->response(array('error' => 'Missing setup'), 200);
        }

        $authnetConfig = $this->ppv_config_model->add_authnet_config($pid, $ks, $api_login_id, $transaction_key, $currency, $setup);

        if (!$authnetConfig) {

            $this->response($authnetConfig, 200);
        }

        $this->response($authnetConfig, 200); // 200 being the HTTP response code
    }

    public function list_ac_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $iDisplayStart = $this->get('iDisplayStart');
        $iDisplayLength = $this->get('iDisplayLength');
        $iSortCol_0 = $this->get('iSortCol_0');
        $sSortDir_0 = $this->get('sSortDir_0');
        $sEcho = $this->get('sEcho');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $acList = $this->ppv_config_model->list_ac($pid, $ks, $iDisplayStart, $iDisplayLength, $iSortCol_0, $sSortDir_0, $sEcho);

        if (!$acList) {

            $this->response($acList, 200);
        }

        $this->response($acList, 200); // 200 being the HTTP response code
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

        $acListType = $this->ppv_config_model->list_ac_type($pid, $ks, $start, $length, $draw);

        if (!$acListType) {

            $this->response($acListType, 200);
        }

        $this->response($acListType, 200); // 200 being the HTTP response code
    }

    public function add_ac_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $preview = $this->get('preview');
        $preview_time = $this->get('preview_time');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        $acAdd = $this->ppv_config_model->add_ac($pid, $ks, $name, $desc, $preview, $preview_time);

        if (!$acAdd) {

            $this->response($acAdd, 200);
        }

        $this->response($acAdd, 200); // 200 being the HTTP response code
    }

    public function delete_ac_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $id = $this->get('id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($id) || $id == null) {

            $this->response(array('error' => 'Missing access control id'), 200);
        }

        $acDelete = $this->ppv_config_model->delete_ac($pid, $ks, $id);

        if (!$acDelete) {

            $this->response($acDelete, 200);
        }

        $this->response($acDelete, 200); // 200 being the HTTP response code
    }

    public function update_ac_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $id = $this->get('id');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $preview = $this->get('preview');
        $preview_time = $this->get('preview_time');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        $acUpdate = $this->ppv_config_model->update_ac($pid, $ks, $id, $name, $desc, $preview, $preview_time);

        if (!$acUpdate) {

            $this->response($acUpdate, 200);
        }

        $this->response($acUpdate, 200); // 200 being the HTTP response code
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

        $email = $this->ppv_config_model->get_email($pid, $ks);

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

        $email = $this->ppv_config_model->get_email_config($pid, $ks);

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

        $setupResponse = $this->ppv_config_model->update_email_config($pid, $ks, $from_name, $from_email, $use_default, $smtp_server, $smtp_port, $smtp_auth, $smtp_pass, $smtp_secure);

        if (!$setupResponse) {

            $this->response($setupResponse, 200);
        }

        $this->response($setupResponse, 200); // 200 being the HTTP response code
    }

    public function update_setup_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $setup = $this->get('setup');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($setup) || $setup == null) {

            $this->response(array('error' => 'Missing setup'), 200);
        }

        $setupResponse = $this->ppv_config_model->deactivate_gateways($pid, $ks, $setup);

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

        $setupResponse = $this->ppv_config_model->get_player_details($pid, $uiconf);

        if (!$setupResponse) {

            $this->response($setupResponse, 200);
        }

        $this->response($setupResponse, 200); // 200 being the HTTP response code      
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

        $setup = $this->ppv_config_model->setup_player($sm_ak, $entry_id);

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

        $thumb = $this->ppv_config_model->w_get_thumb($sm_ak, $entry_id);

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

        $thumb = $this->ppv_config_model->get_thumb($pid, $ks, $entry_id);

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

        $thumb = $this->ppv_config_model->get_cat_thumb($pid, $ks, $cat_id);

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

        $thumb = $this->ppv_config_model->w_get_cat_thumb($sm_ak, $cat_id);

        if (!$thumb) {

            $this->response($thumb, 200);
        }

        $this->response($thumb, 200); // 200 being the HTTP response code
    }

}