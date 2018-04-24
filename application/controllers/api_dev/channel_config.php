<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Channel_config extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('/channel_dev/channel_config_model');
    }

    public function push_schedule_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->channel_config_model->push_schedule($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function disable_schedule_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->channel_config_model->disable_schedule($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_schedules_get() {
        $result = $this->channel_config_model->get_schedules();

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_channels_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $category = $this->get('category');
        $ac = $this->get('ac');
        $search = $this->get('search');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($tz) || $tz == null) {

            $this->response(array('error' => 'Missing tz'), 200);
        }

        $result = $this->channel_config_model->get_channels($pid, $ks, $category, $ac, $search, $tz);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_channel_entries_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $draw = $this->get('draw');
        $tz = $this->get('tz');
        $cid = $this->get('cid');
        $search = $this->get('search');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($start) || $start == null) {

            $this->response(array('error' => 'Missing start'), 200);
        }

        if (!isset($length) || $length == null) {

            $this->response(array('error' => 'Missing length'), 200);
        }

        if (!isset($draw) || $draw == null) {

            $this->response(array('error' => 'Missing draw'), 200);
        }

        if (!isset($tz) || $tz == null) {

            $this->response(array('error' => 'Missing tz'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        $result = $this->channel_config_model->get_channel_entries($pid, $ks, $start, $length, $draw, $tz, $cid, $search);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function delete_channel_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cid = $this->get('cid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        $result = $this->channel_config_model->delete_channel($pid, $ks, $cid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function add_program_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cid = $this->get('cid');
        $eid = $this->get('eid');
        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');
        $repeat = $this->get('repeat');
        $rec_type = $this->get('rec_type');
        $event_length = $this->get('event_length');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        if (!isset($start_date) || $start_date == null) {

            $this->response(array('error' => 'Missing start_date'), 200);
        }

        if (!isset($end_date) || $end_date == null) {

            $this->response(array('error' => 'Missing end_date'), 200);
        }

        if (!isset($repeat) || $repeat == null) {

            $this->response(array('error' => 'Missing repeat'), 200);
        }

        if (!isset($event_length) || $event_length == null) {

            $this->response(array('error' => 'Missing event_length'), 200);
        }

        $result = $this->channel_config_model->add_program($pid, $ks, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function update_program_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $lsid = $this->get('lsid');
        $pcid = $this->get('pcid');
        $cid = $this->get('cid');
        $eid = $this->get('eid');
        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');
        $repeat = $this->get('repeat');
        $rec_type = $this->get('rec_type');
        $event_length = $this->get('event_length');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($lsid) || $lsid == null) {

            $this->response(array('error' => 'Missing lsid'), 200);
        }

        if (!isset($pcid) || $pcid == null) {

            $this->response(array('error' => 'Missing pcid'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        if (!isset($start_date) || $start_date == null) {

            $this->response(array('error' => 'Missing start_date'), 200);
        }

        if (!isset($end_date) || $end_date == null) {

            $this->response(array('error' => 'Missing end_date'), 200);
        }

        if (!isset($repeat) || $repeat == null) {

            $this->response(array('error' => 'Missing repeat'), 200);
        }

        if (!isset($event_length) || $event_length == null) {

            $this->response(array('error' => 'Missing event_length'), 200);
        }

        $result = $this->channel_config_model->update_program($pid, $ks, $lsid, $pcid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function delete_program_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $sid = $this->get('sid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($sid) || $sid == null) {

            $this->response(array('error' => 'Missing sid'), 200);
        }

        $result = $this->channel_config_model->delete_program($pid, $ks, $sid);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_timezone_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($tz) || $tz == null) {

            $this->response(array('error' => 'Missing tz'), 200);
        }

        $result = $this->channel_config_model->get_timezone($pid, $ks, $tz);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function update_timezone_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($tz) || $tz == null) {

            $this->response(array('error' => 'Missing tz'), 200);
        }

        $result = $this->channel_config_model->update_timezone($pid, $ks, $tz);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_public_channels_get() {
        $pid = $this->get('pid');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($tz) || $tz == null) {

            $this->response(array('error' => 'Missing tz'), 200);
        }

        $result = $this->channel_config_model->get_public_channels($pid, $tz);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function update_channel_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cid = $this->get('cid');
        $status = $this->get('status');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $result = $this->channel_config_model->update_channel_status($pid, $ks, $cid, $status);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function push_routine_get() {
        $result = $this->channel_config_model->push_routine();

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code 
    }

}
