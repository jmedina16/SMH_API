<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Stats_config extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('stats_config_model');
    }

    public function get_child_stats_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cpid = $this->get('cpid');
        $start_date = $this->get('start_date');
        $end_date = $this->get('end_date');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cpid) || $cpid == null) {

            $this->response(array('error' => 'Missing cpid'), 200);
        }

        if (!isset($start_date) || $start_date == null) {

            $this->response(array('error' => 'Missing start_date'), 200);
        }

        if (!isset($end_date) || $end_date == null) {

            $this->response(array('error' => 'Missing end_date'), 200);
        }

        $result = $this->stats_config_model->get_child_stats($pid, $ks, $cpid, $start_date, $end_date);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

}
