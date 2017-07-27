<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Cache_config extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('cache_config_model');
    }

    public function purge_cache_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $asset = $this->get('asset');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($asset) || $asset == null) {

            $this->response(array('error' => 'Missing asset'), 200);
        }

        $result = $this->cache_config_model->purge_cache($pid, $ks, $asset);

        if (!$result) {

            $this->response($result, 200);
        }

        $this->response($result, 200); // 200 being the HTTP response code
    }

}
