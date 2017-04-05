<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Sn_config extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('sn_config_model');
    }

    public function get_sn_config_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->sn_config_model->get_sn_config($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function store_youtube_authorization_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $code = $this->get('code');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($code) || $code == null) {

            $this->response(array('error' => 'Missing code'), 200);
        }

        $result = $this->sn_config_model->store_youtube_authorization($pid, $ks, $code);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function remove_youtube_authorization_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->sn_config_model->remove_youtube_authorization($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function create_sn_livestreams_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $eid = $this->get('eid');
        $platforms = $this->get('platforms');
        $projection = $this->get('projection');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        if (!isset($platforms) || $platforms == null) {

            $this->response(array('error' => 'Missing platforms'), 200);
        }

        if (!isset($projection) || $projection == null) {

            $this->response(array('error' => 'Missing projection'), 200);
        }

        $result = $this->sn_config_model->create_sn_livestreams($pid, $ks, $name, $desc, $eid, $platforms, $projection);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function update_sn_livestreams_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $eid = $this->get('eid');
        $platforms = $this->get('platforms');
        $projection = $this->get('projection');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        if (!isset($platforms) || $platforms == null) {

            $this->response(array('error' => 'Missing platforms'), 200);
        }

        if (!isset($projection) || $projection == null) {

            $this->response(array('error' => 'Missing projection'), 200);
        }

        $result = $this->sn_config_model->update_sn_livestreams($pid, $ks, $name, $desc, $eid, $platforms, $projection);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function update_sn_metadata_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $eid = $this->get('eid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        $result = $this->sn_config_model->update_sn_metadata($pid, $ks, $name, $desc, $eid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function update_sn_thumbnail_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $eid = $this->get('eid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        $result = $this->sn_config_model->update_sn_thumbnail($pid, $ks, $eid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function delete_sn_livestream_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $eid = $this->get('eid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        $result = $this->sn_config_model->delete_sn_livestream($pid, $ks, $eid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function get_youtube_broadcast_id_get($param) {
        $pid = $this->get('pid');
        $eid = $this->get('eid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        $result = $this->sn_config_model->get_youtube_event_ids($pid, $eid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function get_sn_livestreams_get() {
        $pid = $this->get('pid');
        $eid = $this->get('eid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing Partner Id'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing Entry Id'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->sn_config_model->get_sn_livestreams($pid, $ks, $eid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function check_youtube_entries_get() {
        $result = $this->sn_config_model->check_youtube_entries();

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function youtube_entry_complete_get() {
        $pid = $this->get('pid');
        $eid = $this->get('eid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing Partner Id'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing Entry Id'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->sn_config_model->youtube_entry_complete($pid, $ks, $eid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

}
