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
            $this->get_live_channel_segments($pid, $channel);
        }
        //syslog(LOG_NOTICE, "SMH DEBUG : post_schedule: " . print_r($schedule, true));

        return $success;
    }

    public function get_live_channel_segments($pid, $channel) {
        $success = array('success' => false);
        $this->config = $this->load->database('kaltura', TRUE);
        $this->config->select('*')
                ->from('live_channel_segment')
                ->where('partner_id', $pid)
                ->where('channel_id', $channel);

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $segments = array();
            foreach ($result as $res) {
                $name = str_replace(' ', '_', strtolower($res['name']));
                $entry_id = $res['entry_id'];
                $start_time = $res['start_time'];
                $duration = $res['duration'];
                $custom_data = json_decode($res['custom_data'], true);
                $repeat = $custom_data['segmentConfig'][0]['repeat'];
                $scheduled = $custom_data['segmentConfig'][0]['scheduled'];
                array_push($segments, array('name' => $name, 'entry_id' => $entry_id, 'start_time' => $start_time, 'duration' => $duration, 'repeat' => $repeat, 'scheduled' => $scheduled));
            }
            syslog(LOG_NOTICE, "SMH DEBUG : get_live_channel_segments: " . print_r($segments, true));
            $success = array('success' => true, 'live_channel_segments' => $segments);
        }

        return $success;
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
