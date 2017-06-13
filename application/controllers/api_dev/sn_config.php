<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Sn_config extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('/sn_dev/sn_config_model');
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

    public function store_facebook_authorization_get() {
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

        $result = $this->sn_config_model->store_facebook_authorization($pid, $ks, $code);

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

    public function remove_facebook_authorization_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->sn_config_model->remove_facebook_authorization($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function facebook_deauthorization_get() {
        $signed_request = $this->get('signed_request');

        if (!isset($signed_request) || $signed_request == null) {

            $this->response(array('error' => 'Missing signed_request'), 200);
        }

        $result = $this->sn_config_model->facebook_deauthorization($signed_request);

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

        $result = $this->sn_config_model->get_youtube_broadcast_id($pid, $eid);

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

    public function sn_routine_get() {
        $result = $this->sn_config_model->sn_routine();

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

    public function sn_livestreams_complete_get() {
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

        $result = $this->sn_config_model->sn_livestreams_complete($pid, $ks, $eid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function create_fb_livestream_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $stream_to = $this->get('stream_to');
        $asset_id = $this->get('asset_id');
        $privacy = $this->get('privacy');
        $create_vod = $this->get('create_vod');
        $cont_streaming = $this->get('cont_streaming');
        $projection = $this->get('projection');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing Partner Id'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($stream_to) || $stream_to == null) {

            $this->response(array('error' => 'Missing Stream To'), 200);
        }

        if (!isset($asset_id) || $asset_id == null) {

            $this->response(array('error' => 'Missing Asset Id'), 200);
        }

        if (!isset($privacy) || $privacy == null) {

            $this->response(array('error' => 'Missing Privacy'), 200);
        }

        if (!isset($create_vod) || $create_vod == null) {

            $this->response(array('error' => 'Missing Create VOD'), 200);
        }

        if (!isset($cont_streaming) || $cont_streaming == null) {

            $this->response(array('error' => 'Missing Cont Streaming'), 200);
        }

        if (!isset($projection) || $projection == null) {

            $this->response(array('error' => 'Missing projection'), 200);
        }

        $result = $this->sn_config_model->create_fb_livestream($pid, $ks, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming, $projection);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function resync_fb_account_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing Partner Id'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->sn_config_model->resync_fb_account($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

    public function get_facebook_embed_get() {
        $pid = $this->get('pid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing Partner Id'), 200);
        }

        $result = $this->sn_config_model->get_facebook_embed($pid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

}
