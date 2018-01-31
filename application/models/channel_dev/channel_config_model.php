<?php

error_reporting(1);

class Channel_config_model extends CI_Model {

    protected $_ci;

    public function __construct() {
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
        $this->load->library('when_api');
    }

    public function get_channels($pid, $ks, $category, $ac, $search) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $live_channels = $this->smportal->get_channels($pid, $ks, null, null, null, $search, $category, $ac);
                $data = array();
                $channels = array();
                $channels['channels'] = array();
                $data['data'] = array();
                array_push($channels['channels'], array('key' => 0, 'label' => '<div class="channel_wrapper" title="No Channel Found"><div style="color: #fff; font-size: 17px;">No Channels Found</div></div>'));
                $this->config = $this->load->database('kaltura', TRUE);
                if (count($live_channels['data']) > 0) {
                    $channels['channels'] = array();
                    $data['data'] = array();
                    foreach ($live_channels['data'] as $channel) {
                        $live_status = 'Off Air';
                        $live_channel_segment = $this->get_live_channel_segment($pid, $channel['id']);
                        if ($live_channel_segment['success']) {
                            foreach ($live_channel_segment['live_channel_segment'] as $segment) {
                                $tz_from = 'UTC';
                                $tz_to = 'America/Los_Angeles';
                                $start_dt = new DateTime($segment['start_date'], new DateTimeZone($tz_from));
                                $start_dt->setTimeZone(new DateTimeZone($tz_to));
                                $start_date = $start_dt->format('Y-m-d H:i:s');
                                if ($segment['end_date'] === '9999-02-01 00:00:00') {
                                    $end_date = $segment['end_date'];
                                } else {
                                    $end_dt = new DateTime($segment['end_date'], new DateTimeZone($tz_from));
                                    $end_dt->setTimeZone(new DateTimeZone($tz_to));
                                    $end_date = $end_dt->format('Y-m-d H:i:s');
                                }

                                //array_push($data['data'], array('channel_id' => $channel['id'], 'text' => $segment['name'], 'start_date' => '2018-02-19 23:35:00', 'end_date' => '2018-05-22 23:35:00', 'rec_type' => 'month_2_1_3_#2', 'event_pid' => 0, 'event_length' => 300));
                                //array_push($data['data'], array('channel_id' => $channel['id'], 'text' => $segment['name'], 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => 'day_1___', 'event_pid' => 0, 'event_length' => 600));
                                array_push($data['data'], array('channel_id' => $channel['id'], 'text' => $segment['name'], 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => $segment['rec_type'], 'event_pid' => (int) $segment['event_pid'], 'event_length' => (int) $segment['event_length'], 'entryId' => $segment['entryId'], 'repeat' => (bool) $segment['repeat']));
                            }
                        }
                        //syslog(LOG_NOTICE, "SMH DEBUG : get_channels: " . print_r($channel['thumbnailUrl'], true));
                        //$thumbnail_url = str_replace("http://mediaplatform.streamingmediahosting.com", "", $channel['thumbnailUrl']);
                        $publish = ($channel['pushPublishEnabled']) ? $channel['pushPublishEnabled'] : 0;
                        $edit_arr = $channel['id'] . '\',\'' . htmlspecialchars(addslashes($channel['name']), ENT_QUOTES) . '\',\'' . htmlspecialchars(addslashes($channel['description']), ENT_QUOTES) . '\',\'' . htmlspecialchars(addslashes($channel['tags']), ENT_QUOTES) . '\',\'' . htmlspecialchars(addslashes($channel['referenceId']), ENT_QUOTES) . '\',\'' . htmlspecialchars(addslashes($channel['categories']), ENT_QUOTES) . '\',' . $channel['accessControlId'] . ',' . $channel['status'] . ',' . $publish . ',\'' . $channel['thumbnailUrl'] . '\'';
                        $preview_arr = $channel['id'] . '\',\'' . htmlspecialchars(addslashes($channel['name']), ENT_QUOTES);
                        if ($channel['pushPublishEnabled']) {
                            $live_status = '<i class="fa fa-circle" style="color:#FF0000; font-size: 11px;"></i> LIVE';
                        }
                        array_push($channels['channels'], array('key' => $channel['id'], 'label' => '<div class="channel_wrapper" title="' . $channel['name'] . '" data-channel-id ="' . $channel['id'] . '"><div class="channel-play-wrapper"><a class="channel-link" onclick="smhCM.previewEmbed(\'' . $preview_arr . '\');"><i class="play-button"></i><div class="channel_thumb"><img src="' . $channel['thumbnailUrl'] . '/quality/100/type/1/width/100/height/60" width="100" height="60"></div><div class="channel-status">' . $live_status . '</div></a></div><div class="channel_title">' . $channel['name'] . '</div><div class="clear"></div><div class="channel_tools"><div class="channel_option1" onclick="smhCM.editChannel(\'' . $edit_arr . ');"><i class="fa fa-pencil-square-o"></i></div><div class="channel_option2" onclick="smhCM.deleteChannel(\'' . $channel['id'] . '\', \'' . $channel['name'] . '\');"><i class="fa fa-trash-o"></i></div></div></div>'));
                    }
                }



//                foreach ($live_channels['data'] as &$channel) {
//                    $live_channel_segment = $this->get_live_channel_segment($pid, $channel['id']);
//                    $channel['segments'] = $live_channel_segment['live_channel_segment'];
//                }
//                syslog(LOG_NOTICE, "SMH DEBUG : get_channels: " . print_r($channels, true));
                $data['collections'] = $channels;
                //syslog(LOG_NOTICE, "SMH DEBUG : get_channels: " . print_r($data, true));
                header('Content-Type: application/json');
                $success = json_encode($data, JSON_UNESCAPED_SLASHES);
//                syslog(LOG_NOTICE, "SMH DEBUG : get_channels: " . $success);
//                $this->config = $this->load->database('kaltura', TRUE);
//                foreach ($live_channels['data'] as &$channel) {
//                    $live_channel_segment = $this->get_live_channel_segment($pid, $channel['id']);
//                    $channel['segments'] = $live_channel_segment['live_channel_segment'];
//                }
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function get_channel_entries($pid, $ks, $start, $length, $draw, $tz, $cid, $search) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $this->config = $this->load->database('kaltura', TRUE);
                $live_channel_segments = $this->get_live_channel_segment($pid, $cid);

                $output = array(
                    "orderBy" => '-createdAt',
                    "recordsTotal" => intval(count($live_channel_segments['live_channel_segment'])),
                    "recordsFiltered" => intval(count($live_channel_segments['live_channel_segment'])),
                    "data" => array(),
                );

                if (isset($draw)) {
                    $output["draw"] = intval($draw);
                }

                foreach ($live_channel_segments['live_channel_segment'] as $channel_segment) {
                    $delete_action = '';
                    $edit_action = '';

                    $edit_arr = $channel_segment['id'] . '\',\'' . addslashes($channel_segment['name']) . '\',\'' . addslashes($channel_segment['description']) . '\'';
                    $edit_action = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhCM.editProgram(\'' . $edit_arr . ');">Program</a></li>';

                    $delete_arr = $channel_segment['id'] . '\',\'' . addslashes($channel_segment['name']) . '\',\'' . $cid;
                    $delete_action = '<li role="presentation" style="border-top: solid 1px #f0f0f0;"><a role="menuitem" tabindex="-1" onclick="smhCM.deleteProgram(\'' . $delete_arr . '\');">Delete</a></li>';

                    $actions = '<span class="dropdown header">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                            ' . $edit_action . '  
                            ' . $delete_action . '
                        </ul>
                    </div>
                    </span>';

                    $channel_thumbnail = '<div class="livestream-wrapper">
                        <img onerror="smhMain.imgError(this)" src="/p/' . $pid . '/thumbnail/entry_id/' . $channel_segment['entryId'] . '/quality/100/type/1/width/100/height/60" width="100" height="60">
                    </div>';

                    $tz_from = 'UTC';
                    $tz_to = 'America/Los_Angeles';
                    $start_dt = new DateTime($channel_segment['start_date'], new DateTimeZone($tz_from));
                    $start_dt->setTimeZone(new DateTimeZone($tz_to));
                    $start_date = $start_dt->format('Y-m-d h:i:s A');
                    $end_dt = new DateTime($channel_segment['end_date'], new DateTimeZone($tz_from));
                    $end_dt->setTimeZone(new DateTimeZone($tz_to));
                    $end_date = $end_dt->format('Y-m-d h:i:s A');

                    $row = array();
                    $row[] = '<input type="checkbox" class="channel-bulk" name="channel_bulk" value="' . $channel_segment['id'] . '" />';
                    $row[] = $channel_thumbnail;
                    $row[] = addslashes($channel_segment['name']);
                    $row[] = "<div class='data-break'>" . $start_date . "</div>";
                    $row[] = "<div class='data-break'>" . $end_date . "</div>";
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

    public function get_live_channel_segment($pid, $cid) {
        $success = array('success' => false);
        $this->config = $this->load->database('ch', TRUE);
        $this->config->select('*')
                ->from('program_config')
                ->where('partner_id', $pid)
                ->where('status', 2)
                ->where('channel_id', $cid);

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $segments = array();
            foreach ($result as $res) {
                $entry_id = $res['entry_id'];
                $entry_details = $this->smportal->get_entry_details($pid, $entry_id);
                $id = $res['live_segment_id'];
                $name = $entry_details['name'];
                $description = $entry_details['desc'];
                $status = $res['status'];
                $created_at = $res['created_at'];
                $thumbnail = $entry_details['thumbnailUrl'];
                $start_date = $res['start_date'];
                $end_date = $res['end_date'];
                $rec_type = $res['rec_type'];
                $event_pid = $res['event_pid'];
                $event_length = $res['event_length'];
                $repeat = $res['repeat'];
                array_push($segments, array('id' => $id, 'name' => $name, 'description' => $description, 'entryId' => $entry_id, 'thumbnail' => $thumbnail, 'status' => $status, 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => $rec_type, 'event_pid' => $event_pid, 'event_length' => $event_length, 'created_at' => $created_at, 'repeat' => $repeat));
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
                    if ($this->multi_array_search($segment['playOnStream'], $playlists)) {
                        foreach ($playlists as &$playlist) {
                            if ($playlist['playOnStream'] == $segment['playOnStream']) {
                                $playlist['video_srcs'][$segment['sortValue']] = array('video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']);
                            }
                        }
                    } else {
                        $video_src = array();
                        $video_src[$segment['sortValue']] = array('video_src' => $segment['video_src'], 'start' => $segment['start'], 'length' => $segment['length']);
                        array_push($playlists, array('name' => 'pl' . $plist_num, 'playOnStream' => $segment['playOnStream'], 'repeat' => true, 'scheduled' => $newDatetime, 'video_srcs' => $video_src));
                        $plist_num++;
                    }
                }
            }

            foreach ($playlists as &$playlist) {
                ksort($playlist['video_srcs']);
            }

            $schedule['playlists'] = $playlists;
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

    public function delete_program($pid, $ks, $sid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $delete_live_segment = $this->smportal->delete_live_segment($pid, $ks, $sid);
                if ($delete_live_segment['success']) {
                    $get_program_config_id = $this->get_program_config_id($pid, $sid);
                    if ($get_program_config_id['success']) {
                        $update_program_config_status = $this->update_program_config_status($pid, $get_program_config_id['pcid'], 3);
                        if ($update_program_config_status['success']) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not delete program config');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get program config id');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not delete live segment');
                }
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function add_program($pid, $ks, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $repeat = ($repeat === 'true') ? true : false;
                if ($repeat) {
                    if ($end_date !== '9999-02-01 00:00:00') {
                        $end_date_mod = new DateTime($end_date);
                        $end_date_mod->add(new DateInterval('PT' . $event_length . 'S'));
                        $end_date = $end_date_mod->format('Y-m-d h:i:s A');
                    }
                }

                $collision = $this->collision_detection($pid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);
                if ($collision['collision']) {
                    $success = array('success' => false, 'collision' => true);
                } else {
                    //$success = array('success' => false, 'collision' => false);
                    $add_live_segment = $this->smportal->add_live_segment($pid, $ks, $cid, $eid);
                    if ($add_live_segment['success']) {
                        $add_custom_data = $this->add_live_segment_custom_data($pid, $add_live_segment['id'], $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);
                        if ($add_custom_data['success']) {
                            $add_live_segment_id = $this->add_live_segment_id($pid, $add_live_segment['id'], $add_custom_data['id']);
                            if ($add_live_segment_id['success']) {
                                $success = array('success' => true);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not add custom data id');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not add custom data');
                        }
                    } else {
                        $success = array('success' => false);
                    }
                }
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function collision_detection($pid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length) {
        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection1: start_date: " . print_r($start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection1: end_date: " . print_r($end_date, true));
        $collision = array('collision' => false);
        $tz_from = 'America/Los_Angeles';
        $tz_to = 'UTC';
        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
        if (date("I", strtotime($start_date))) {
            $start_dt->add(new DateInterval('PT1H'));
        }
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
            if (date("I", strtotime($end_date))) {
                $end_dt->add(new DateInterval('PT1H'));
            }
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }


        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection2: start_date: " . print_r($start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection2: end_date: " . print_r($end_date, true));

        $programs = $this->get_program_dates($pid, $cid, $start_date, $end_date);
        $rec_collision = array('collision' => false);
        $non_rec_collision = array('collision' => false);
        if ($repeat) {
            if (count($programs['nonrepeat_programs'] > 0)) {
                $non_rec_collision = $this->when_api->process_non_rec_programs_a($start_date, $end_date, $rec_type, $event_length, $programs['nonrepeat_programs']);
            }
            if (!$non_rec_collision['collision']) {
                if (count($programs['repeat_programs'] > 0)) {
                    $rec_collision = $this->when_api->process_rec_programs_a($start_date, $end_date, $rec_type, $event_length, $programs['repeat_programs']);
                }
            }
        } else {
            if (count($programs['nonrepeat_programs'] > 0)) {
                $non_rec_collision = $this->when_api->process_non_rec_programs_b($start_date, $end_date, $programs['nonrepeat_programs']);
            }
            if (!$non_rec_collision['collision']) {
                if (count($programs['repeat_programs'] > 0)) {
                    $rec_collision = $this->when_api->process_rec_programs_b($start_date, $end_date, $programs['repeat_programs']);
                }
            }
        }
        if ($rec_collision['collision'] || $non_rec_collision['collision']) {
            $collision = array('collision' => true);
        }
        syslog(LOG_NOTICE, "SMH DEBUG : rec_collision: " . print_r($rec_collision, true));
        syslog(LOG_NOTICE, "SMH DEBUG : non_rec_collision: " . print_r($non_rec_collision, true));
        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection: " . print_r($programs, true));
        syslog(LOG_NOTICE, "SMH DEBUG : collision: " . print_r($collision, true));
        return $collision;
    }

    public function get_program_dates($pid, $cid, $start_date, $end_date) {
        $success = array('success' => false);
        $this->config = $this->load->database('ch', TRUE);
        $this->config->select('*')
                ->from('program_config')
                ->where('partner_id', $pid)
                ->where('channel_id', $cid)
                ->where('status', 2)
                ->where('start_date <=', $end_date)
                ->where('end_date >=', $start_date);

        $query = $this->config->get();
        $result = $query->result_array();

        syslog(LOG_NOTICE, "SMH DEBUG : get_program_dates: " . print_r($this->config->last_query(), true));

        if ($query->num_rows() > 0) {
            $programs_repeat = array();
            $programs_nonrepeat = array();
            foreach ($result as $res) {
                if ($res['repeat']) {
                    array_push($programs_repeat, array('start_date' => $res['start_date'], 'end_date' => $res['end_date'], 'rec_type' => $res['rec_type'], 'event_length' => $res['event_length']));
                } else {
                    array_push($programs_nonrepeat, array('start_date' => $res['start_date'], 'end_date' => $res['end_date'], 'event_length' => $res['event_length']));
                }
            }
            $success = array('success' => true, 'repeat_programs' => $programs_repeat, 'nonrepeat_programs' => $programs_nonrepeat);
        }

        return $success;
    }

    public function add_live_segment_id($pid, $sid, $pcid) {
        $success = array('success' => false);
        $segmentConfig = array();
        $config = array();
        array_push($config, array('pcid' => $pcid));
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

    public function update_program_config_status($pid, $pcid, $status) {
        $success = array('success' => false);
        $data = array(
            'status' => $status
        );
        $this->config = $this->load->database('ch', TRUE);
        $this->config->where('partner_id', $pid);
        $this->config->where('id', $pcid);
        $this->config->update('program_config', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_program_config_id($pid, $sid) {
        $success = array('success' => false);
        $this->config = $this->load->database('kaltura', TRUE);
        $this->config->select('*')
                ->from('live_channel_segment')
                ->where('partner_id', $pid)
                ->where('id', $sid);

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $segments = array();
            foreach ($result as $res) {
                $custom_data = json_decode($res['custom_data'], true);
                $pcid = $custom_data['segmentConfig'][0]['pcid'];
            }
            $success = array('success' => true, 'pcid' => $pcid);
        }

        return $success;
    }

    public function add_live_segment_custom_data($pid, $sid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length) {
        $success = array('success' => false);

        $tz_from = 'America/Los_Angeles';
        $tz_to = 'UTC';
        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }

        $data = array(
            'partner_id' => $pid,
            'live_segment_id' => $sid,
            'channel_id' => $cid,
            'entry_id' => $eid,
            'status' => 2,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'repeat' => $repeat,
            'rec_type' => $rec_type,
            'event_pid' => 0,
            'event_length' => (int) $event_length,
            'created_at' => date('Y-m-d H:i:s')
        );
        $this->config = $this->load->database('ch', TRUE);
        $this->config->insert('program_config', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true, 'id' => $this->config->insert_id());
        } else {
            $success = array('success' => false);
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
        $this->_ci->curl->option('SSL_VERIFYPEER', false);
        $this->_ci->curl->option('SSL_VERIFYHOST', false);
        $response = json_decode($this->_ci->curl->execute());
        if ($response->channel_manager) {
            $has_service = true;
        }
        return $has_service;
    }

}
