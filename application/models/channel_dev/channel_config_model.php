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

    public function get_channels($pid, $ks, $start, $length, $draw, $tz, $search) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $live_channels = $this->smportal->get_channels($pid, $ks, $start, $length, $draw, $search);
                $this->config = $this->load->database('kaltura', TRUE);
                foreach ($live_channels['data'] as &$channel) {
                    $live_channel_segment = $this->get_live_channel_segment($pid, $channel['id']);
                    $channel['segments'] = $live_channel_segment['live_channel_segment'];
                }

                $output = array(
                    "orderBy" => $live_channels['orderBy'],
                    "recordsTotal" => intval($live_channels['recordsTotal']),
                    "recordsFiltered" => intval($live_channels['recordsFiltered']),
                    "data" => array(),
                );

                if (isset($live_channels['draw'])) {
                    $output["draw"] = intval($live_channels['draw']);
                }

                foreach ($live_channels['data'] as $channel_segment) {
                    $newDatetime = date('m/d/Y h:i A', $channel_segment['createdAt']);

                    $delete_action = '';
                    $edit_action = '';
                    $preview_action = '';

                    $edit_arr = $channel_segment['id'] . '\',\'' . addslashes($channel_segment['name']) . '\',\'' . addslashes($channel_segment['description']) . '\'';
                    $edit_action = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhCM.editChannel(\'' . $edit_arr . ');">Channel</a></li>';

                    $delete_arr = $channel_segment['id'] . '\',\'' . addslashes($channel_segment['name']);
                    $delete_action = '<li role="presentation" style="border-top: solid 1px #f0f0f0;"><a role="menuitem" tabindex="-1" onclick="smhCM.deleteChannel(\'' . $delete_arr . '\');">Delete</a></li>';

                    $preview_arr = $channel_segment['id'] . '\',\'' . addslashes($channel_segment['name']);
                    $preview_action = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhCM.previewChannel(\'' . $preview_arr . '\');">Preview & Embed</a></li>';

                    $video_count = 0;
                    $thumbnails = '';

                    $segment_ids = array();
                    foreach ($channel_segment['segments'] as $entry) {
                        array_push($segment_ids, $entry['entryId']);
                        $video_count++;
                    }

                    if (!$video_count) {
                        $thumbnails .= '<div style="background-color: #ccc; width: 100%; height: 100%;"></div>';
                    } else {
                        $segment_ids_final = array_slice($segment_ids, 0, 5);
                        $segment_ids_final_count = count($segment_ids_final);
                        foreach ($segment_ids_final as $id) {
                            if ($segment_ids_final_count == 1) {
                                $thumbnails .= '<img onerror="smhMain.imgError(this)" src="/p/' . $pid . '/thumbnail/entry_id/' . $id . '/quality/100/type/3/width/300/height/90" width="100%" height="90" onmouseover="smhCM.thumbRotatorStart(this)" onmouseout="smhCM.thumbRotatorEnd(this)">';
                            } else if ($segment_ids_final_count == 2) {
                                $thumbnails .= '<img onerror="smhMain.imgError(this)" src="/p/' . $pid . '/thumbnail/entry_id/' . $id . '/quality/100/type/3/width/300/height/90" width="50%" height="90" onmouseover="smhCM.thumbRotatorStart(this)" onmouseout="smhCM.thumbRotatorEnd(this)">';
                            } else if ($segment_ids_final_count == 3) {
                                $thumbnails .= '<img onerror="smhMain.imgError(this)" src="/p/' . $pid . '/thumbnail/entry_id/' . $id . '/quality/100/type/3/width/300/height/90" width="33.33%" height="90" onmouseover="smhCM.thumbRotatorStart(this)" onmouseout="smhCM.thumbRotatorEnd(this)">';
                            } else if ($segment_ids_final_count == 4) {
                                $thumbnails .= '<img onerror="smhMain.imgError(this)" src="/p/' . $pid . '/thumbnail/entry_id/' . $id . '/quality/100/type/1/width/300/height/90" width="25%" height="90" onmouseover="smhCM.thumbRotatorStart(this)" onmouseout="smhCM.thumbRotatorEnd(this)">';
                            } else if ($segment_ids_final_count == 5) {
                                $thumbnails .= '<img onerror="smhMain.imgError(this)" src="/p/' . $pid . '/thumbnail/entry_id/' . $id . '/quality/100/type/1/width/300/height/90" width="20%" height="90" onmouseover="smhCM.thumbRotatorStart(this)" onmouseout="smhCM.thumbRotatorEnd(this)">';
                            }
                        }
                    }

                    $actions = '<span class="dropdown header">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                            ' . $edit_action . '  
                            ' . $preview_action . '
                            ' . $delete_action . '
                        </ul>
                    </div>
                    </span>';

                    $channel_list = '<div class="playlist-wrapper">
                                        <div class="thumbnail-holder">' . $thumbnails . '</div>
                                        <div class="videos-num">' . $video_count . ' Videos</div>
                                    </div>';

                    $channel_thumbnail = '<div class="entries-wrapper">
                    <div class="play-wrapper">
                        <a onclick="smhCM.previewEmbed(\'' . $preview_arr . '\');">
                            <i style="top: 18px;" class="play-button"></i></div>
                            <div class="thumbnail-holder"><img onerror="smhMain.imgError(this)" src="/p/' . $pid . '/thumbnail/entry_id/' . $channel_segment['id'] . '/quality/100/type/1/width/300/height/90" width="150" height="110"></div>
                        </a>
                    </div>';

                    $row = array();
                    $row[] = '<input type="checkbox" class="channel-bulk" name="channel_bulk" value="' . $channel_segment['id'] . '" />';
                    $row[] = $channel_thumbnail;
                    $row[] = "<div class='data-break'>" . addslashes($channel_segment['name']) . "</div>";
                    $row[] = "<div class='data-break'>" . $channel_segment['id'] . "</div>";
                    $row[] = $channel_list;
                    $row[] = "<div class='data-break'>" . $newDatetime . "</div>";
                    $row[] = $actions;
                    $output['data'][] = $row;
                }

                $success = $output;
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
                $entry_id = $res['entry_id'];
                $entry_details = $this->smportal->get_entry_details($pid, $entry_id);
                $id = $res['id'];
                $name = $entry_details['name'];
                $description = $entry_details['desc'];
                $status = $res['status'];
                $created_at = $res['created_at'];
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
                $plist_num = 1;
                $time = time();
                $unixtime_to_date = date('n/j/Y H:i', $time);
                $datetime = strtotime($unixtime_to_date);
                $newDatetime = date('Y-m-d H:i:s', $datetime);
                foreach ($live_channel_segments['live_channel_segments'] as $segment) {
//                    array_push($playlists, array('name' => 'pl' . $plist_num, 'playOnStream' => $segment['playOnStream'], 'repeat' => true, 'scheduled' => $newDatetime, 'video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']));
//                    $plist_num++;

                    if ($this->multi_array_search($segment['playOnStream'], $playlists)) {
                        foreach ($playlists as &$playlist) {
                            if ($playlist['playOnStream'] == $segment['playOnStream']) {
                                //$video_src = array('video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']);
                                //array_push($playlist['video_srcs'][$segment['sortValue']], $video_src);
                                $playlist['video_srcs'][$segment['sortValue']] = array('video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']);
                                syslog(LOG_NOTICE, "SMH DEBUG : sortValue " . print_r($playlist,true));
                            }
                        }
                    } else {
                        $video_src = array();
                        $video_src[$segment['sortValue']] = array('video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']);
                        //array_push($video_src[$segment['sortValue']], array('video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']));
                        array_push($playlists, array('name' => 'pl' . $plist_num, 'playOnStream' => $segment['playOnStream'], 'repeat' => true, 'scheduled' => $newDatetime, 'video_srcs' => $video_src));
                        $plist_num++;
                    }
                }
            }

            foreach ($playlists as &$playlist) {
                usort($playlist, function($a, $b) {
                    return $a['video_srcs'] - $b['video_srcs'];
                });
            }
            $schedule['playlists'] = $playlists;
            syslog(LOG_NOTICE, "SMH DEBUG : build_schedule " . print_r($schedule, true));
            $schedule_json = json_encode($schedule, JSON_UNESCAPED_SLASHES);
            $success = array('success' => true, 'schedule' => $schedule_json);
        }
        return $success;
    }

    public function multi_array_search($search_for, $search_in) {
        foreach ($search_in as $element) {
            if (($element === $search_for)) {
                return true;
            } elseif (is_array($element)) {
                $result = $this->multi_array_search($search_for, $element);
                if ($result == true)
                    return true;
            }
        }
        return false;
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
                $channel_id = $res['channel_id'];
                $filename = $this->smportal->get_entry_filename($pid, $res['entry_id']);
                $video_src = 'httpcache1/' . $pid . '/content/' . $filename['filename'];
                $start = $res['start_time'];
                $length = $res['duration'];
                $custom_data = json_decode($res['custom_data'], true);
                $sortValue = $custom_data['segmentConfig'][0]['sortValue'];
                array_push($segments, array('id' => $id, 'playOnStream' => $channel_id, 'video_src' => $video_src, 'start' => $start, 'length' => $length, 'sortValue' => $sortValue));
            }
            $success = array('success' => true, 'live_channel_segments' => $segments);
        }

        return $success;
    }

    public function add_channel($pid, $ks, $eids, $name, $desc) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $add_live_channel = $this->smportal->add_live_channel($pid, $ks, $name, $desc);
                if ($add_live_channel['success']) {
                    $success = array('success' => true);
                    $live_segments = explode(";", $eids);
                    $sort_value = 0;
                    foreach ($live_segments as $segment) {
                        $add_live_segment = $this->smportal->add_live_segment($pid, $ks, $add_live_channel['id'], $segment);
                        if ($add_live_segment['success']) {
                            $this->update_live_segment_custom_data($pid, $add_live_segment['id'], $sort_value);
                            $sort_value++;
                        } else {
                            $success = array('success' => false);
                        }
                    }
                    $schedule = $this->build_schedule($pid, $ks);
                    if ($schedule['success']) {
                        syslog(LOG_NOTICE, "SMH DEBUG : add_segment: " . print_r($schedule['schedule'], true));
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => true);
                    }
                } else {
                    $success = array('success' => false);
                }
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
                $update_live_segment = $this->smportal->update_live_segment($pid, $ks, $sid, $cid, $eid, $name, $desc);
                if ($update_live_segment['success']) {
                    $update_live_segment_custom_data = $this->update_live_segment_custom_data($pid, $sid, $repeat, $scheduled);
                    if ($update_live_segment_custom_data['success']) {
                        $schedule = $this->build_schedule($pid, $ks);
                        if ($schedule['success']) {
                            syslog(LOG_NOTICE, "SMH DEBUG : update_segment: " . print_r($schedule['schedule'], true));
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => true);
                        }
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
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

    public function delete_segment($pid, $ks, $sid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $update_live_segment = $this->smportal->delete_live_segment($pid, $ks, $sid);
                if ($update_live_segment['success']) {
                    $schedule = $this->build_schedule($pid, $ks);
                    if ($schedule['success']) {
                        syslog(LOG_NOTICE, "SMH DEBUG : delete_segment: " . print_r($schedule['schedule'], true));
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => true);
                    }
                } else {
                    $success = array('success' => false);
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

    public function update_live_segment_custom_data($pid, $sid, $sort_value) {
        $success = array('success' => false);
        $segmentConfig = array();
        $config = array();
        array_push($config, array('sortValue' => $sort_value));
        $segmentConfig['segmentConfig'] = $config;
        $data = array(
            'custom_data' => json_encode($segmentConfig)
        );
        $this->config = $this->load->database('kaltura', TRUE);
        $this->config->where('partner_id', $pid);
        $this->config->where('id', $sid);
        $this->config->update('live_channel_segment', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
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
