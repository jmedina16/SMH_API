<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Mem_user extends REST_Controller {

    public function __construct() {

        parent::__construct();
        $this->load->model('mem_user_model');
        $this->config->set_item('global_xss_filtering', false);
    }

    public function list_user_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $draw = $this->get('draw');
        $search = $this->get('search');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $userList = $this->mem_user_model->get_user_list($pid, $ks, $start, $length, $search, $draw);

        if (!$userList) {

            $this->response($userList, 200);
        }

        $this->response($userList, 200); // 200 being the HTTP response code
    }

    public function update_user_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $uid = $this->get('uid');
        $fname = $this->get('fname');
        $lname = $this->get('lname');
        $email = $this->get('email');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        if (!isset($fname) || $fname == null) {

            $this->response(array('error' => 'Missing first name'), 200);
        }

        if (!isset($lname) || $lname == null) {

            $this->response(array('error' => 'Missing last name'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        $updateUser = $this->mem_user_model->update_user($pid, $ks, $uid, $fname, $lname, $email, $tz);

        if (!$updateUser) {

            $this->response($updateUser, 200);
        }

        $this->response($updateUser, 200); // 200 being the HTTP response code
    }

    public function update_user_pswd_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $email = $this->get('email');
        $pass = $this->get('pass');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($pass) || $pass == null) {

            $this->response(array('error' => 'Missing password'), 200);
        }

        $updateUserPswd = $this->mem_user_model->update_pswd($pid, $ks, $email, $pass, $tz);

        if (!$updateUserPswd) {

            $this->response($updateUserPswd, 200);
        }

        $this->response($updateUserPswd, 200); // 200 being the HTTP response code
    }

    public function update_user_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $email = $this->get('email');
        $status = $this->get('status');
        $uid = $this->get('uid');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $updateUserStatus = $this->mem_user_model->update_status($pid, $ks, $email, $status, $uid, $tz);

        if (!$updateUserStatus) {

            $this->response($updateUserStatus, 200);
        }

        $this->response($updateUserStatus, 200); // 200 being the HTTP response code
    }

    public function login_user_get() {
        $un = $this->get('un');
        $pswd = $this->get('pswd');
        $sm_ak = $this->get('sm_ak');
        $type = $this->get('type');
        $entryId = $this->get('entryId');

        if (!isset($un) || $un == null) {

            $this->response(array('error' => 'Missing username'), 200);
        }

        if (!isset($pswd) || $pswd == null) {

            $this->response(array('error' => 'Missing password'), 200);
        }

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryid'), 200);
        }

        $loginUser = $this->mem_user_model->login_user($un, $pswd, $sm_ak, $type, $entryId);

        if (!$loginUser) {

            $this->response($loginUser, 200);
        }

        $this->response($loginUser, 200); // 200 being the HTTP response code
    }

    public function add_user_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $fname = $this->get('fname');
        $lname = $this->get('lname');
        $email = $this->get('email');
        $pass = $this->get('pass');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($fname) || $fname == null) {

            $this->response(array('error' => 'Missing first name'), 200);
        }

        if (!isset($lname) || $lname == null) {

            $this->response(array('error' => 'Missing last name'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($pass) || $pass == null) {

            $this->response(array('error' => 'Missing password'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $addUser = $this->mem_user_model->add_user($pid, $ks, $fname, $lname, $email, $pass, $status, $tz);

        if (!$addUser) {

            $this->response($addUser, 200);
        }

        $this->response($addUser, 200); // 200 being the HTTP response code
    }

    public function register_user_get() {
        $sm_ak = $this->get('sm_ak');
        $fname = $this->get('fname');
        $lname = $this->get('lname');
        $email = $this->get('email');
        $pass = $this->get('pass');
        $tz = $this->get('tz');
        $url = $this->get('url');
        $attrs = $this->get('attrs');
        $type = $this->get('type');
        $entryId = $this->get('entryId');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($fname) || $fname == null) {

            $this->response(array('error' => 'Missing first name'), 200);
        }

        if (!isset($lname) || $lname == null) {

            $this->response(array('error' => 'Missing last name'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($pass) || $pass == null) {

            $this->response(array('error' => 'Missing password'), 200);
        }

        if (!isset($url) || $url == null) {

            $this->response(array('error' => 'Missing url'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        $addUser = $this->mem_user_model->register_user($sm_ak, $fname, $lname, $email, $pass, $tz, $url, $attrs, $type, $entryId);

        if (!$addUser) {

            $this->response($addUser, 200);
        }

        $this->response($addUser, 200); // 200 being the HTTP response code
    }

    public function activate_user_get() {
        $sm_ak = $this->get('sm_ak');
        $akey = $this->get('akey');
        $tz = $this->get('tz');
        $email = $this->get('email');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($akey) || $akey == null) {

            $this->response(array('error' => 'Missing akey'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        $activateUser = $this->mem_user_model->activate_user($sm_ak, $akey, $email, $tz);

        if (!$activateUser) {

            $this->response($activateUser, 200);
        }

        $this->response($activateUser, 200); // 200 being the HTTP response code
    }

    public function delete_user_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $uid = $this->get('uid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $deleteUser = $this->mem_user_model->delete_user($pid, $ks, $uid);

        if (!$deleteUser) {

            $this->response($deleteUser, 200);
        }

        $this->response($deleteUser, 200); // 200 being the HTTP response code
    }

    public function destroy_session_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $uid = $this->get('uid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $destroySession = $this->mem_user_model->destroy_session($pid, $ks, $uid);

        if (!$destroySession) {

            $this->response($destroySession, 200);
        }

        $this->response($destroySession, 200); // 200 being the HTTP response code
    }

    public function list_user_names_get() {

        $pid = $this->get('pid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        $userNames = $this->mem_user_model->list_user_names($pid);

        if (!$userNames) {

            $this->response($userNames, 200);
        }

        $this->response($userNames, 200); // 200 being the HTTP response code
    }

    public function get_user_name_get() {
        $uid = $this->get('uid');
        $sm_ak = $this->get('sm_ak');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $userName = $this->mem_user_model->get_user_name($sm_ak, $uid);

        if (!$userName) {

            $this->response($userName, 200);
        }

        $this->response($userName, 200); // 200 being the HTTP response code
    }

    public function is_logged_in_get() {

        $auth_key = $this->get('auth_key');
        $sm_ak = $this->get('sm_ak');
        $type = $this->get('type');
        $entryId = $this->get('entryId');

        if (!isset($auth_key) || $auth_key == null) {

            $this->response(array('error' => 'Missing auth_key'), 200);
        }

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryid'), 200);
        }

        $user = $this->mem_user_model->is_logged_in($auth_key, $sm_ak, $type, $entryId);

        if (!$user) {

            $this->response($user, 200);
        }

        $this->response($user, 200); // 200 being the HTTP response code
    }

    public function create_auth_key_get() {
        $sm_ak = $this->get('sm_ak');
        $un = $this->get('un');
        $uid = $this->get('user_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($un) || $un == null) {

            $this->response(array('error' => 'Missing user name'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $authKey = $this->mem_user_model->create_auth_key($un, $uid, $sm_ak);

        if (!$authKey) {

            $this->response($authKey, 200);
        }

        $this->response($authKey, 200); // 200 being the HTTP response code
    }

    public function reset_request_get() {
        $sm_ak = $this->get('sm_ak');
        $email = $this->get('email');
        $url = $this->get('url');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($url) || $url == null) {

            $this->response(array('error' => 'Missing url'), 200);
        }

        $reset = $this->mem_user_model->reset_request($email, $sm_ak, $url);

        if (!$reset) {

            $this->response($reset, 200);
        }

        $this->response($reset, 200); // 200 being the HTTP response code
    }

    public function reset_pass_get() {
        $sm_ak = $this->get('sm_ak');
        $email = $this->get('email');
        $reset_token = $this->get('reset_token');
        $pass = $this->get('pass');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($reset_token) || $reset_token == null) {

            $this->response(array('error' => 'Missing reset_token'), 200);
        }

        if (!isset($pass) || $pass == null) {

            $this->response(array('error' => 'Missing password'), 200);
        }

        $reset = $this->mem_user_model->reset_pass($email, $sm_ak, $reset_token, $pass);

        if (!$reset) {

            $this->response($reset, 200);
        }

        $this->response($reset, 200); // 200 being the HTTP response code
    }

    public function reset_email_get() {
        $sm_ak = $this->get('sm_ak');
        $email = $this->get('email');
        $new_email = $this->get('new_email');
        $reset_token = $this->get('reset_token');
        $pass = $this->get('pass');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($new_email) || $new_email == null) {

            $this->response(array('error' => 'Missing new_email'), 200);
        }

        if (!isset($reset_token) || $reset_token == null) {

            $this->response(array('error' => 'Missing reset_token'), 200);
        }

        if (!isset($pass) || $pass == null) {

            $this->response(array('error' => 'Missing password'), 200);
        }

        $reset = $this->mem_user_model->reset_email($email, $new_email, $sm_ak, $reset_token, $pass);

        if (!$reset) {

            $this->response($reset, 200);
        }

        $this->response($reset, 200); // 200 being the HTTP response code
    }

    public function activate_cat_entry_get() {
        $sm_ak = $this->get('sm_ak');
        $entryId = $this->get('entryId');
        $is_logged_in = $this->get('is_logged_in');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        if (!isset($is_logged_in) || $is_logged_in == null) {

            $this->response(array('error' => 'Missing is_logged_in'), 200);
        }

        $inventory = $this->mem_user_model->activate_cat_entry($sm_ak, $entryId, $is_logged_in);

        if (!$inventory) {

            $this->response($inventory, 200);
        }

        $this->response($inventory, 200); // 200 being the HTTP response code
    }

    public function is_active_get() {
        $sm_ak = $this->get('sm_ak');
        $uid = $this->get('uid');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $is_active = $this->mem_user_model->is_active($sm_ak, $uid);

        if (!$is_active) {

            $this->response($is_active, 200);
        }

        $this->response($is_active, 200); // 200 being the HTTP response code
    }

    public function is_not_active_get() {
        $sm_ak = $this->get('sm_ak');
        $uid = $this->get('uid');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $is_active = $this->mem_user_model->is_not_active($sm_ak, $uid);

        if (!$is_active) {

            $this->response($is_active, 200);
        }

        $this->response($is_active, 200); // 200 being the HTTP response code
    }

    public function user_concurrent_status_get() {
        $pid = $this->get('pid');
        $concurrent = $this->get('concurrent');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($concurrent) || $concurrent == null) {

            $this->response(array('error' => 'Missing concurrent'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->mem_user_model->user_concurrent_status($pid, $ks, $concurrent);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_concurrent_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        $result = $this->mem_user_model->get_concurrent_status($pid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function user_activation_skip_status_get() {
        $pid = $this->get('pid');
        $skip = $this->get('skip');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($skip) || $skip == null) {

            $this->response(array('error' => 'Missing skip'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->mem_user_model->user_activation_skip_status($pid, $ks, $skip);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_activation_skip_status_get() {
        $pid = $this->get('pid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        $result = $this->mem_user_model->get_activation_skip_status($pid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_owner_attrs_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {
            $this->response(array('error' => 'Missing pid'), 200);
        }
        if (!isset($ks) || $ks == null) {
            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->mem_user_model->get_owner_attrs($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function update_reg_fields_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $newFields = $this->get('new_fields');
        $updateFields = $this->get('update_fields');
        $removeFields = $this->get('remove_fields');

        if (!isset($pid) || $pid == null) {
            $this->response(array('error' => 'Missing pid'), 200);
        }
        if (!isset($ks) || $ks == null) {
            $this->response(array('error' => 'Missing ks'), 200);
        }
        if (!isset($newFields) || $newFields == null) {
            $this->response(array('error' => 'Missing newFields'), 200);
        }
        if (!isset($updateFields) || $updateFields == null) {
            $this->response(array('error' => 'Missing updateFields'), 200);
        }
        if (!isset($removeFields) || $removeFields == null) {
            $this->response(array('error' => 'Missing removeFields'), 200);
        }


        $result = $this->mem_user_model->update_reg_fields($pid, $ks, $newFields, $updateFields, $removeFields);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function update_user_details_get() {
        $pid = $this->get('pid');
        $uid = $this->get('uid');
        $ks = $this->get('ks');
        $updateFields = $this->get('update_fields');

        if (!isset($pid) || $pid == null) {
            $this->response(array('error' => 'Missing pid'), 200);
        }
        if (!isset($uid) || $uid == null) {
            $this->response(array('error' => 'Missing uid'), 200);
        }
        if (!isset($ks) || $ks == null) {
            $this->response(array('error' => 'Missing ks'), 200);
        }
        if (!isset($updateFields) || $updateFields == null) {
            $this->response(array('error' => 'Missing updateFields'), 200);
        }

        $result = $this->mem_user_model->update_user_details($pid, $uid, $ks, $updateFields);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_user_details_get() {
        $uid = $this->get('uid');
        $sm_ak = $this->get('sm_ak');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $userName = $this->mem_user_model->get_user_details($sm_ak, $uid);

        if (!$userName) {

            $this->response($userName, 200);
        }

        $this->response($userName, 200); // 200 being the HTTP response code
    }

    public function update_fname_get() {
        $fname = $this->get('fname');
        $auth_key = $this->get('auth_key');
        $sm_ak = $this->get('sm_ak');
        $type = $this->get('type');
        $entryId = $this->get('entryId');

        if (!isset($auth_key) || $auth_key == null) {

            $this->response(array('error' => 'Missing auth_key'), 200);
        }

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($fname) || $fname == null) {

            $this->response(array('error' => 'Missing fname'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        $userName = $this->mem_user_model->update_fname($sm_ak, $auth_key, $fname, $type, $entryId);

        if (!$userName) {

            $this->response($userName, 200);
        }

        $this->response($userName, 200); // 200 being the HTTP response code
    }

    public function update_lname_get() {
        $lname = $this->get('lname');
        $auth_key = $this->get('auth_key');
        $sm_ak = $this->get('sm_ak');
        $type = $this->get('type');
        $entryId = $this->get('entryId');

        if (!isset($auth_key) || $auth_key == null) {

            $this->response(array('error' => 'Missing auth_key'), 200);
        }

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($lname) || $lname == null) {

            $this->response(array('error' => 'Missing lname'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        $userName = $this->mem_user_model->update_lname($sm_ak, $auth_key, $lname, $type, $entryId);

        if (!$userName) {

            $this->response($userName, 200);
        }

        $this->response($userName, 200); // 200 being the HTTP response code
    }

    public function reset_psswd_request_get() {
        $sm_ak = $this->get('sm_ak');
        $email = $this->get('email');
        $url = $this->get('url');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($url) || $url == null) {

            $this->response(array('error' => 'Missing url'), 200);
        }

        $reset = $this->mem_user_model->reset_psswd_request($email, $sm_ak, $url);

        if (!$reset) {

            $this->response($reset, 200);
        }

        $this->response($reset, 200); // 200 being the HTTP response code
    }

    public function reset_email_request_get() {
        $sm_ak = $this->get('sm_ak');
        $email = $this->get('email');
        $url = $this->get('url');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($url) || $url == null) {

            $this->response(array('error' => 'Missing url'), 200);
        }

        $reset = $this->mem_user_model->reset_email_request($email, $sm_ak, $url);

        if (!$reset) {

            $this->response($reset, 200);
        }

        $this->response($reset, 200); // 200 being the HTTP response code
    }

}
