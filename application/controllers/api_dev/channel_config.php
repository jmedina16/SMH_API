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

}
