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

    public function get_schedules($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $live_channels = $this->smportal->get_channels($pid, $ks);
                $this->config = $this->load->database('kaltura', TRUE);
                foreach ($live_channels as &$channel) {
                    $live_channel_segment = $this->get_live_channel_segment($pid, $channel['id']);
                    $channel['segments'] = $live_channel_segment['live_channel_segment'];
                }
                syslog(LOG_NOTICE, "SMH DEBUG : get_schedules: " . print_r($live_channels, true));
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function get_live_channel_segment($pid, $segment) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('live_channel_segment')
                ->where('partner_id', $pid)
                ->where_in('channel_id', $segment);

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $segments = array();
            foreach ($result as $res) {
                $id = $res['id'];
                $name = $res['name'];
                $description = $res['description'];
                $status = $res['status'];
                $created_at = $res['created_at'];
                $entry_id = $res['entry_id'];
                $entry_details = $this->smportal->get_entry_details($pid, $entry_id);
                $thumbnail = $entry_details['thumbnailUrl'];
                $start = $res['start_time'];
                $length = $res['duration'];
                $custom_data = json_decode($res['custom_data'], true);
                $repeat = $custom_data['segmentConfig'][0]['repeat'];
                $scheduled = $custom_data['segmentConfig'][0]['scheduled'];
                array_push($segments, array('id' => $id, 'name' => $name, 'description' => $description, 'entryId' => $entry_id, 'thumbnail' => $thumbnail, 'status' => $status, 'repeat' => $repeat, 'scheduled' => $scheduled, 'start' => $start, 'length' => $length, 'created_at' => $created_at));
            }
            $success = array('success' => true, 'live_channel_segment' => $segments);
        }

        return $success;
    }

    public function post_schedule($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $schedule = $this->build_schedule($pid, $ks);
                if ($schedule['success']) {
                    $success = array('success' => true, 'schedule' => $schedule['schedule']);
                }
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function build_schedule($pid, $ks) {
        $success = array('success' => false);
        $live_channels = $this->smportal->get_channel_ids($pid, $ks);
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
                ->where('status', 2)
                ->where_in('channel_id', $live_channels);

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $segments = array();
            foreach ($result as $res) {
                $id = $res['id'];
                $name = str_replace(' ', '_', strtolower($res['name']));
                $channel_id = $res['channel_id'];
                $filename = $this->smportal->get_entry_filename($pid, $res['entry_id']);
                $video_src = 'httpcache1/' . $pid . '/content/' . $filename['filename'];
                $start = $res['start_time'];
                $length = $res['duration'];
                $custom_data = json_decode($res['custom_data'], true);
                $repeat = $custom_data['segmentConfig'][0]['repeat'];
                $scheduled = $custom_data['segmentConfig'][0]['scheduled'];
                array_push($segments, array('id' => $id, 'name' => $name, 'playOnStream' => $channel_id, 'repeat' => $repeat, 'scheduled' => $scheduled, 'video_src' => $video_src, 'start' => $start, 'length' => $length));
            }
            $success = array('success' => true, 'live_channel_segments' => $segments);
        }

        return $success;
    }

    public function add_segment($pid, $ks, $cid, $eid, $name, $desc, $repeat, $scheduled) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $add_live_segment = $this->smportal->add_live_segment($pid, $ks, $cid, $eid, $name, $desc);
                if ($add_live_segment['success']) {
                    $segmentConfig = array();
                    $config = array();
                    array_push($config, array('repeat' => $repeat, 'scheduled' => $scheduled));
                    $segmentConfig['segmentConfig'] = $config;
                    $data = array(
                        'custom_data' => json_encode($segmentConfig)
                    );
                    $this->config = $this->load->database('kaltura', TRUE);
                    $this->config->where('partner_id', $pid);
                    $this->config->where('id', $add_live_segment['id']);
                    $this->config->update('live_channel_segment', $data);
                    $this->config->limit(1);
                    if ($this->config->affected_rows() > 0) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                }
                //syslog(LOG_NOTICE, "SMH DEBUG : delete_channel: " . print_r($live_channel_segment, true));
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function update_segment($pid, $ks, $sid, $cid, $eid, $name, $desc, $repeat, $scheduled) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {

                //syslog(LOG_NOTICE, "SMH DEBUG : delete_channel: " . print_r($live_channel_segment, true));
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function delete_channel($pid, $ks, $cid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $live_channel_segments = $this->get_live_channel_segments($pid, $cid);
                if ($live_channel_segments['success']) {
                    foreach ($live_channel_segments['live_channel_segments'] as $segment) {
                        $this->smportal->delete_live_segment($pid, $ks, $segment['id']);
                    }
                    $delete_channel_resp = $this->smportal->delete_live_channel($pid, $ks, $cid);
                    if ($delete_channel_resp['success']) {
                        $schedule = $this->build_schedule($pid, $ks);
                        if ($schedule['success']) {
                            syslog(LOG_NOTICE, "SMH DEBUG : delete_channel: " . print_r($schedule['schedule'], true));
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => true);
                        }
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $delete_channel_resp = $this->smportal->delete_live_channel($pid, $ks, $cid);
                    if ($delete_channel_resp['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                }
                //syslog(LOG_NOTICE, "SMH DEBUG : delete_channel: " . print_r($live_channel_segment, true));
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
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
        if ($response->channel_manager) {
            $has_service = true;
        }

        return $has_service;
    }

}
