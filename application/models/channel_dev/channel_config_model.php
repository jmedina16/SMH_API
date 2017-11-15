<?php

error_reporting(1);

class Channel_config_model extends CI_Model {

    protected $_ci;

    public function __construct() {
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function post_schedule($pid, $ks) {
        $success = array('success' => false);
        $schedule = array();
        $live_channels = $this->smportal->get_channels($pid, $ks);
        $schedule['streams'] = $live_channels;
        foreach ($live_channels as $channel) {
            
        }
        syslog(LOG_NOTICE, "SMH DEBUG : post_schedule: " . print_r($schedule, true));

        return $success;
    }

    public function get_live_channel_segments($channel) {
        $this->config = $this->load->database('kaltura', TRUE);
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function verify_service($pid) {
        $has_service = false;
        $this->_ci->curl->create("https://mediaplatform.streamingmediahosting.com/apps/services/v1.0/index.php?pid=" . $pid . "&action=get_services");
        $this->_ci->curl->get();
        $response = json_decode($this->_ci->curl->execute());
        if ($response->social_network) {
            $has_service = true;
        }

        return $has_service;
    }

}
