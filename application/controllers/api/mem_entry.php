<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Mem_entry extends REST_Controller {

    public function __construct() {

        parent::__construct();
        $this->load->model('mem_entry_model');
    }

    public function list_entry_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $entryList = $this->mem_entry_model->get_entry_list($pid, $ks, $start, $length, $search, $draw);

        if (!$entryList) {

            $this->response($entryList, 200);
        }

        $this->response($entryList, 200); // 200 being the HTTP response code
    }

    public function list_entry_names_get() {

        $pid = $this->get('pid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        $entryNames = $this->mem_entry_model->list_entry_names($pid);

        if (!$entryNames) {

            $this->response($entryNames, 200);
        }

        $this->response($entryNames, 200); // 200 being the HTTP response code
    }

    public function add_entry_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $kentry_id = $this->get('kentry_id');
        $kentry_name = $this->get('kentry_name');
        $ac_id = $this->get('ac_id');
        $ac_name = $this->get('ac_name');
        $media_type = $this->get('media_type');
        $status = $this->get('status');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($kentry_id) || $kentry_id == null) {

            $this->response(array('error' => 'Missing entry'), 200);
        }

        if (!isset($kentry_name) || $kentry_name == null) {

            $this->response(array('error' => 'Missing entry name'), 200);
        }

        if (!isset($ac_id) || $ac_id == null) {

            $this->response(array('error' => 'Missing access control id'), 200);
        }

        if (!isset($media_type) || $media_type == null) {

            $this->response(array('error' => 'Missing media type'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $addEntry = $this->mem_entry_model->add_entry($pid, $ks, $kentry_id, $kentry_name, $ac_id, $ac_name, $media_type, $status);

        if (!$addEntry) {

            $this->response($addEntry, 200);
        }

        $this->response($addEntry, 200); // 200 being the HTTP response code
    }

    public function update_entry_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $kentry_id = $this->get('kentry_id');
        $kentry_name = $this->get('kentry_name');
        $ac_id = $this->get('ac_id');
        $ac_name = $this->get('ac_name');
        $media_type = $this->get('media_type');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($kentry_id) || $kentry_id == null) {

            $this->response(array('error' => 'Missing entry'), 200);
        }

        if (!isset($ac_id) || $ac_id == null) {

            $this->response(array('error' => 'Missing access control id'), 200);
        }

        if (!isset($media_type) || $media_type == null) {

            $this->response(array('error' => 'Missing media type'), 200);
        }

        $updateEntry = $this->mem_entry_model->update_entry($pid, $ks, $kentry_id, $kentry_name, $ac_id, $ac_name, $media_type);

        if (!$updateEntry) {

            $this->response($updateEntry, 200);
        }

        $this->response($updateEntry, 200); // 200 being the HTTP response code
    }

    public function delete_entry_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $kentry_id = $this->get('kentry_id');
        $media_type = $this->get('media_type');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($kentry_id) || $kentry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        if (!isset($media_type) || $media_type == null) {

            $this->response(array('error' => 'Missing media type'), 200);
        }

        $deleteEntry = $this->mem_entry_model->delete_entry($pid, $ks, $kentry_id, $media_type);

        if (!$deleteEntry) {

            $this->response($deleteEntry, 200);
        }

        $this->response($deleteEntry, 200); // 200 being the HTTP response code
    }

    public function delete_platform_entry_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $kentry_id = $this->get('kentry_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($kentry_id) || $kentry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        $deleteEntry = $this->mem_entry_model->delete_platform_entry($pid, $ks, $kentry_id);

        if (!$deleteEntry) {

            $this->response($deleteEntry, 200);
        }

        $this->response($deleteEntry, 200); // 200 being the HTTP response code
    }

    public function update_entry_status_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $kentry_id = $this->get('kentry_id');
        $status = $this->get('status');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($kentry_id) || $kentry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateEntryStatus = $this->mem_entry_model->update_status($pid, $ks, $kentry_id, $status);

        if (!$updateEntryStatus) {

            $this->response($updateEntryStatus, 200);
        }

        $this->response($updateEntryStatus, 200); // 200 being the HTTP response code
    }

    public function get_entry_details_get() {

        $sm_ak = $this->get('sm_ak');
        $kentry_id = $this->get('kentry');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($kentry_id) || $kentry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        $entry = $this->mem_entry_model->get_entry_details($sm_ak, $kentry_id);

        if (!$entry) {

            $this->response($entry, 200);
        }

        $this->response($entry, 200); // 200 being the HTTP response code
    }

    public function get_cat_details_get() {

        $sm_ak = $this->get('sm_ak');
        $kentry_id = $this->get('kentry');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($kentry_id) || $kentry_id == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        $entry = $this->mem_entry_model->get_cat_details($sm_ak, $kentry_id);

        if (!$entry) {

            $this->response($entry, 200);
        }

        $this->response($entry, 200); // 200 being the HTTP response code
    }

    public function check_update_ac_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $playlist_id = $this->get('playlist_id');
        $playlist = $this->get('playlist');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($playlist_id) || $playlist_id == null) {

            $this->response(array('error' => 'Missing playlist_id'), 200);
        }

        if (!isset($playlist) || $playlist == null) {

            $this->response(array('error' => 'Missing playlist'), 200);
        }

        $result = $this->mem_entry_model->check_update_ac($pid, $ks, $playlist_id, $playlist);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function delete_playlist_entry_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $playlist_id = $this->get('playlist_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($playlist_id) || $playlist_id == null) {

            $this->response(array('error' => 'Missing playlist_id'), 200);
        }

        $result = $this->mem_entry_model->delete_playlist_entry($pid, $ks, $playlist_id);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_cat_entries_get() {
        $sm_ak = $this->get('sm_ak');
        $cat_id = $this->get('cat_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($cat_id) || $cat_id == null) {

            $this->response(array('error' => 'Missing category id'), 200);
        }

        $entries = $this->mem_entry_model->get_cat_entries($sm_ak, $cat_id);

        if (!$entries) {

            $this->response($entries, 200);
        }

        $this->response($entries, 200); // 200 being the HTTP response code
    }

    public function update_platform_cat_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cat = $this->get('cat');
        $entry_id = $this->get('entry_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cat) || $cat == null) {

            $this->response(array('error' => 'Missing cat'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        $result = $this->mem_entry_model->update_platform_cat($pid, $ks, $cat, $entry_id);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function update_drag_cat_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cat_id = $this->get('cat_id');
        $entry_id = $this->get('entry_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cat_id) || $cat_id == null) {

            $this->response(array('error' => 'Missing cat_id'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        $result = $this->mem_entry_model->update_drag_cat($pid, $ks, $cat_id, $entry_id);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function delete_platform_cat_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cat_id = $this->get('cat_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cat_id) || $cat_id == null) {

            $this->response(array('error' => 'Missing cat_id'), 200);
        }

        $result = $this->mem_entry_model->delete_platform_cat($pid, $ks, $cat_id);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

}