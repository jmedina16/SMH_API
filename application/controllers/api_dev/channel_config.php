<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Channel_config extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('/channel_dev/channel_config_model');
    }

    public function post_schedule_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->channel_config_model->post_schedule($pid, $ks);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function get_schedules_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $result = $this->channel_config_model->get_schedules($pid, $ks);

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

    public function add_segment_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cid = $this->get('cid');
        $eid = $this->get('eid');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $repeat = $this->get('repeat');
        $scheduled = $this->get('scheduled');

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

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($repeat) || $repeat == null) {

            $this->response(array('error' => 'Missing repeat'), 200);
        }

        if (!isset($scheduled) || $scheduled == null) {

            $this->response(array('error' => 'Missing scheduled'), 200);
        }

        $result = $this->channel_config_model->add_segment($pid, $ks, $cid, $eid, $name, $desc, $repeat, $scheduled);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

    public function update_segment_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cid = $this->get('cid');
        $eid = $this->get('eid');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $repeat = $this->get('repeat');
        $scheduled = $this->get('scheduled');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($sid) || $sid == null) {

            $this->response(array('error' => 'Missing sid'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($eid) || $eid == null) {

            $this->response(array('error' => 'Missing eid'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($repeat) || $repeat == null) {

            $this->response(array('error' => 'Missing repeat'), 200);
        }

        if (!isset($scheduled) || $scheduled == null) {

            $this->response(array('error' => 'Missing scheduled'), 200);
        }

        $result = $this->channel_config_model->update_segment($pid, $ks, $sid, $cid, $eid, $name, $desc, $repeat, $scheduled);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

}
