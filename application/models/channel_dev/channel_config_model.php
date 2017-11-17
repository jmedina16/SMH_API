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
    
    public function get_schedules($pid, $ks){
        
    }

    public function post_schedule($pid, $ks) {
        $success = array('success' => false);
        $live_channels = $this->smportal->get_channels($pid, $ks);
        if (count($live_channels) > 0) {
            $schedule = array();
            $schedule['streams'] = $live_channels;
            $playlists = array();
            $live_channel_segments = $this->get_live_channel_segments($pid, $live_channels);
            if ($live_channel_segments['success']) {
                foreach ($live_channel_segments['live_channel_segments'] as $segment) {
                    array_push($playlists, array('name' => $segment['name'], 'playOnStream' => $segment['playOnStream'], 'repeat' => $segment['repeat'], 'scheduled' => $segment['scheduled'], 'video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']));
                }
            }
            $schedule['playlists'] = $playlists;
            $schedule_json = json_encode($schedule, JSON_UNESCAPED_SLASHES);
            $success = array('success' => true, 'schedule' => $schedule_json);
        }

        return $success;
    }

    public function get_live_channel_segments($pid, $live_channels) {
        $success = array('success' => false);
        $this->config = $this->load->database('kaltura', TRUE);
        $this->config->select('*')
                ->from('live_channel_segment')
                ->where('partner_id', $pid)
                ->where_in('channel_id', $live_channels);

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $segments = array();
            foreach ($result as $res) {
                $name = str_replace(' ', '_', strtolower($res['name']));
                $channel_id = $res['channel_id'];
                $filename = $this->smportal->get_entry_filename($pid, $res['entry_id']);
                $video_src = 'httpcache1/' . $pid . '/content/' . $filename['filename'];
                $start = $res['start_time'];
                $length = $res['duration'];
                $custom_data = json_decode($res['custom_data'], true);
                $repeat = $custom_data['segmentConfig'][0]['repeat'];
                $scheduled = $custom_data['segmentConfig'][0]['scheduled'];
                array_push($segments, array('name' => $name, 'playOnStream' => $channel_id, 'repeat' => $repeat, 'scheduled' => $scheduled, 'video_src' => $video_src, 'start' => $start, 'length' => $length));
            }
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
