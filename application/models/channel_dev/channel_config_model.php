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

    public function get_public_channels($pid) {
        $imgs_url = '';
        $cdn = json_decode($this->getCDN($pid), true);
        if ($cdn[0]['edgecast'] || $cdn[0]['custom']) {
            $imgs_url = 'https://ecimages.streamingmediahosting.com';
        } else if ($cdn[0]['highwinds']) {
            $imgs_url = 'https://hwimages.streamingmediahosting.com';
        }
        $ks = $this->smportal->impersonate($pid);
        $tz_from = 'UTC';
        $tz_to = $this->get_int_timezone($pid, $ks);
        $live_channels = $this->smportal->get_public_channels($pid, $ks);
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
                        $start_dt = new DateTime($segment['start_date'], new DateTimeZone($tz_from));
                        $start_dt->setTimeZone(new DateTimeZone($tz_to));
                        $start_date = $start_dt->format('Y-m-d H:i:s');
                        if ($segment['end_date'] === '9999-02-01 00:00:00') {
                            $end_date = $segment['end_date'];
                        } else {
                            $rec_type = explode("#", $segment['rec_type']);
                            $extra = $rec_type[1];
                            if ($extra && $extra != 'no') {
                                $event_length = (int) $segment['event_length'];
                                $end_dt = new DateTime($segment['end_date'], new DateTimeZone($tz_from));
                                $end_dt->sub(new DateInterval('PT' . $event_length . 'S'));
                                $end_dt->setTimeZone(new DateTimeZone($tz_to));
                                $end_date = $end_dt->format('Y-m-d H:i:s');
                            } else {
                                $end_dt = new DateTime($segment['end_date'], new DateTimeZone($tz_from));
                                $end_dt->setTimeZone(new DateTimeZone($tz_to));
                                $end_date = $end_dt->format('Y-m-d H:i:s');
                            }
                        }
                        $entry_desc = ($segment['description']) ? $segment['description'] : '';
                        $thumbnail_url = str_replace("http://mediaplatform.streamingmediahosting.com", $imgs_url, $segment['thumbnail']);
                        array_push($data['data'], array('channel_id' => $channel['id'], 'channel_name' => $channel['name'], 'text' => $segment['name'], 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => $segment['rec_type'], 'event_pid' => (int) $segment['event_pid'], 'event_length' => (int) $segment['event_length'], 'entryId' => $segment['entryId'], 'entry_desc' => $entry_desc, 'thumbnail_url' => $thumbnail_url));
                    }
                }
                $publish = ($channel['pushPublishEnabled']) ? $channel['pushPublishEnabled'] : 0;
                $ch_thumbnail_url = str_replace("http://mediaplatform.streamingmediahosting.com", $imgs_url, $channel['thumbnailUrl']);
                array_push($channels['channels'], array('key' => $channel['id'], 'label' => '<div class="channel_wrapper" title="' . $channel['name'] . '" data-channel-id ="' . $channel['id'] . '" onclick="smhS.viewChannel(\'' . $channel['id'] . '\');"><div class="channel-play-wrapper"><div class="channel_thumb"><img src="' . $ch_thumbnail_url . '/quality/100/type/1/width/100/height/60" width="100" height="60"></div></div><div class="channel_title">' . $channel['name'] . '</div><div class="clear"></div></div>'));
            }
        }
        $data['collections'] = $channels;
        header('Content-Type: application/json');
        return $data;
    }

    public function get_channels($pid, $ks, $category, $ac, $search, $tz) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $tz_from = 'UTC';
                $get_timezone = $this->get_timezone($pid, $ks, $tz);
                $tz_to = $get_timezone['timezone'];
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
                                $start_dt = new DateTime($segment['start_date'], new DateTimeZone($tz_from));
                                $start_dt->setTimeZone(new DateTimeZone($tz_to));
                                $start_date = $start_dt->format('Y-m-d H:i:s');
                                if ($segment['end_date'] === '9999-02-01 00:00:00') {
                                    $end_date = $segment['end_date'];
                                } else {
                                    $rec_type = explode("#", $segment['rec_type']);
                                    $extra = $rec_type[1];
                                    if ($extra && $extra != 'no') {
                                        $event_length = (int) $segment['event_length'];
                                        $end_dt = new DateTime($segment['end_date'], new DateTimeZone($tz_from));
                                        $end_dt->sub(new DateInterval('PT' . $event_length . 'S'));
                                        $end_dt->setTimeZone(new DateTimeZone($tz_to));
                                        $end_date = $end_dt->format('Y-m-d H:i:s');
                                    } else {
                                        $end_dt = new DateTime($segment['end_date'], new DateTimeZone($tz_from));
                                        $end_dt->setTimeZone(new DateTimeZone($tz_to));
                                        $end_date = $end_dt->format('Y-m-d H:i:s');
                                    }
                                }

                                //array_push($data['data'], array('channel_id' => $channel['id'], 'text' => $segment['name'], 'start_date' => '2018-02-19 23:35:00', 'end_date' => '2018-05-22 23:35:00', 'rec_type' => 'month_2_1_3_#2', 'event_pid' => 0, 'event_length' => 300));
                                //array_push($data['data'], array('channel_id' => $channel['id'], 'text' => $segment['name'], 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => 'day_1___', 'event_pid' => 0, 'event_length' => 600));
                                array_push($data['data'], array('channel_id' => $channel['id'], 'text' => $segment['name'], 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => $segment['rec_type'], 'event_pid' => (int) $segment['event_pid'], 'event_length' => (int) $segment['event_length'], 'entryId' => $segment['entryId'], 'repeat' => (bool) $segment['repeat'], 'pcid' => (int) $segment['pcid'], 'live_segment_id' => (int) $segment['id']));
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
                        array_push($channels['channels'], array('key' => $channel['id'], 'label' => '<div class="channel_wrapper" title="' . $channel['name'] . '" data-channel-id ="' . $channel['id'] . '"><div class="channel-play-wrapper"><a class="channel-link" onclick="smhCM.previewEmbed(\'' . $preview_arr . '\');"><i class="play-button"></i><div class="channel_thumb"><img src="' . str_replace("http", "https", $channel['thumbnailUrl']) . '/quality/100/type/1/width/100/height/60" width="100" height="60"></div><div class="channel-status">' . $live_status . '</div></a></div><div class="channel_title">' . $channel['name'] . '</div><div class="clear"></div><div class="channel_tools"><div class="channel_option1" onclick="smhCM.editChannel(\'' . $edit_arr . ');"><i class="fa fa-pencil-square-o"></i></div><div class="channel_option2" onclick="smhCM.deleteChannel(\'' . $channel['id'] . '\', \'' . $channel['name'] . '\');"><i class="fa fa-trash-o"></i></div></div></div>'));
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
                $live_channel_segments = $this->get_live_channel_segment_entries($pid, $cid, $start, $length, $search);

                $output = array(
                    "orderBy" => '-createdAt',
                    "recordsTotal" => intval($live_channel_segments['recordsTotal']),
                    "recordsFiltered" => intval($live_channel_segments['recordsTotal']),
                    "data" => array(),
                );

                if (isset($draw)) {
                    $output["draw"] = intval($draw);
                }

                $tz_from = 'UTC';
                $tz_to = $this->get_int_timezone($pid, $ks);

                foreach ($live_channel_segments['live_channel_segment'] as $channel_segment) {
                    $delete_action = '';
                    $edit_action = '';
                    $start_dt = new DateTime($channel_segment['start_date'], new DateTimeZone($tz_from));
                    $start_dt->setTimeZone(new DateTimeZone($tz_to));
                    $start_date = $start_dt->format('Y-m-d h:i:s A');
                    $edit_start_date = $start_dt->format('Y-m-d H:i:s');

                    if ($channel_segment['end_date'] === '9999-02-01 00:00:00') {
                        $end_date = 'No End Date';
                        $edit_end_date = $channel_segment['end_date'];
                    } else {
                        $rec_type = explode("#", $channel_segment['rec_type']);
                        $extra = $rec_type[1];
                        if ($extra && $extra != 'no') {
                            $event_length = (int) $channel_segment['event_length'];
                            $end_dt = new DateTime($channel_segment['end_date'], new DateTimeZone($tz_from));
                            $end_dt->sub(new DateInterval('PT' . $event_length . 'S'));
                            $end_dt->setTimeZone(new DateTimeZone($tz_to));
                            $end_date = $end_dt->format('Y-m-d h:i:s A');
                            $edit_end_date = $end_dt->format('Y-m-d H:i:s');
                        } else {
                            $end_dt = new DateTime($channel_segment['end_date'], new DateTimeZone($tz_from));
                            $end_dt->setTimeZone(new DateTimeZone($tz_to));
                            $end_date = $end_dt->format('Y-m-d h:i:s A');
                            $edit_end_date = $end_dt->format('Y-m-d H:i:s');
                        }
                    }

                    $repeat = ($channel_segment['repeat']) ? 1 : 0;
                    $edit_arr = $channel_segment['id'] . ',' . $channel_segment['pcid'] . ',\'' . $cid . '\',\'' . $channel_segment['entryId'] . '\',\'' . $channel_segment['name'] . '\',' . $channel_segment['event_length'] . ',' . $repeat . ',\'' . $channel_segment['rec_type'] . '\',\'' . $edit_start_date . '\',\'' . $edit_end_date . '\'';
                    $edit_action = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhCM.editChannelProgram(' . $edit_arr . ');">Program</a></li>';

                    $delete_arr = $channel_segment['id'] . '\',\'' . addslashes($channel_segment['name']) . '\',\'' . $cid . '\',\'channel';
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

//                    $tz_from = 'UTC';
//                    $tz_to = 'America/Los_Angeles';
//                    $start_dt = new DateTime($channel_segment['start_date'], new DateTimeZone($tz_from));
//                    $start_dt->setTimeZone(new DateTimeZone($tz_to));
//                    $start_date = $start_dt->format('Y-m-d h:i:s A');
//                    $end_dt = new DateTime($channel_segment['end_date'], new DateTimeZone($tz_from));
//                    $end_dt->setTimeZone(new DateTimeZone($tz_to));
//                    $end_date = $end_dt->format('Y-m-d h:i:s A');

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
                $pcid = (int) $res['id'];
                $id = (int) $res['live_segment_id'];
                $name = $entry_details['name'];
                $description = $entry_details['desc'];
                $status = (int) $res['status'];
                $created_at = $res['created_at'];
                $thumbnail = $entry_details['thumbnailUrl'];
                $start_date = $res['start_date'];
                $end_date = $res['end_date'];
                $rec_type = $res['rec_type'];
                $event_pid = (int) $res['event_pid'];
                $event_length = (int) $res['event_length'];
                $repeat = ($res['repeat']) ? true : false;
                array_push($segments, array('pcid' => $pcid, 'id' => $id, 'name' => $name, 'description' => $description, 'entryId' => $entry_id, 'thumbnail' => $thumbnail, 'status' => $status, 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => $rec_type, 'event_pid' => $event_pid, 'event_length' => $event_length, 'created_at' => $created_at, 'repeat' => (bool) $repeat));
            }
            $success = array('success' => true, 'live_channel_segment' => $segments);
        }

        return $success;
    }

    public function get_live_channel_segment_entries($pid, $cid, $start, $length, $search) {
        $success = array('success' => false);
        $this->config = $this->load->database('ch', TRUE);
        if (isset($start) && $length != '-1') {
            $this->config->limit($this->config->escape_str($length), $this->config->escape_str($start));
        }

        $this->config->select('*')
                ->from('program_config')
                ->where('partner_id', $pid)
                ->where('status', 2)
                ->where('channel_id', $cid);

        $columns = array('entry_id', 'start_date', 'end_date');
        if ($search != "") {
            $where = '';
            for ($i = 0; $i < count($columns); $i++) {
                if ($i < count($columns) - 1) {
                    $where .= $columns[$i] . " LIKE '%" . $search . "%' OR ";
                } else {
                    $where .= $columns[$i] . " LIKE '%" . $search . "%'";
                }
            }
            $this->config->where('partner_id = "' . $pid . '" AND (' . $where . ')');
        }

        $query = $this->config->get();
        $result = $query->result_array();

        $this->config->select('FOUND_ROWS() AS found_rows');
        $filteredTotal = $this->config->get()->row()->found_rows;

        /* Total data set length */
        $count = $this->config->query('SELECT count(*) AS `Count` FROM program_config WHERE partner_id = "' . $pid . '" AND status = 2 AND channel_id = "' . $cid . '"');
        $countQuery = $count->result_array();
        $total = $countQuery[0]['Count'];

        if ($query->num_rows() > 0) {
            $segments = array();
            foreach ($result as $res) {
                $entry_id = $res['entry_id'];
                $entry_details = $this->smportal->get_entry_details($pid, $entry_id);
                $pcid = (int) $res['id'];
                $id = (int) $res['live_segment_id'];
                $name = $entry_details['name'];
                $description = $entry_details['desc'];
                $status = (int) $res['status'];
                $created_at = $res['created_at'];
                $thumbnail = $entry_details['thumbnailUrl'];
                $start_date = $res['start_date'];
                $end_date = $res['end_date'];
                $rec_type = $res['rec_type'];
                $event_pid = (int) $res['event_pid'];
                $event_length = (int) $res['event_length'];
                $repeat = ($res['repeat']) ? true : false;
                array_push($segments, array('pcid' => $pcid, 'id' => $id, 'name' => $name, 'description' => $description, 'entryId' => $entry_id, 'thumbnail' => $thumbnail, 'status' => $status, 'start_date' => $start_date, 'end_date' => $end_date, 'rec_type' => $rec_type, 'event_pid' => $event_pid, 'event_length' => $event_length, 'created_at' => $created_at, 'repeat' => (bool) $repeat));
            }
            $success = array('success' => true, 'live_channel_segment' => $segments, 'recordsFiltered' => $filteredTotal, 'recordsTotal' => $total);
        }

        return $success;
    }

    public function push_schedule($pid, $ks) {
        $success = array('success' => false);
        $schedule = $this->build_account_schedule($pid, $ks);
        if ($schedule['success']) {
            if (count($schedule['schedule']) > 0) {
                $url = 'http://10.5.22.94:1935/ott/update';
                $this->curlPost($url, json_encode($schedule['schedule']));
                $success = array('success' => true, 'schedule' => $schedule['schedule']);
                syslog(LOG_NOTICE, "SMH DEBUG : push_schedule: " . json_encode($schedule['schedule']));
                syslog(LOG_NOTICE, "SMH DEBUG : push_schedule: " . print_r($schedule['schedule'], true));
            } else {
                $schedule = array();
                $schedule['account'] = (int) $pid;
                $schedule['ks'] = $ks;
                $schedule['streams'] = array();
                $schedule['playlists'] = array();
                $url = 'http://10.5.22.94:1935/ott/update';
                $this->curlPost($url, json_encode($schedule));
                $success = array('success' => true, 'schedule' => $schedule);
                syslog(LOG_NOTICE, "SMH DEBUG : push_schedule: " . print_r($schedule, true));
            }
        }
        return $success;
    }

    public function disable_schedule($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $schedule = array();
            $schedule['account'] = (int) $pid;
            $schedule['ks'] = $ks;
            $schedule['streams'] = array();
            $schedule['playlists'] = array();
            $success = array('success' => true, 'schedule' => $schedule);
            syslog(LOG_NOTICE, "SMH DEBUG : push_schedule: " . print_r($schedule, true));
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function get_schedules() {
        $schedules = array();
        $build_schedules = $this->build_schedules();
        if ($build_schedules['success']) {
            if (count($build_schedules['schedules']) > 0) {
                $schedules = $build_schedules['schedules'];
            } else {
                $schedules = 'No schedules found';
            }
        }
        syslog(LOG_NOTICE, "SMH DEBUG : get_schedules: " . print_r($schedules, true));
        return $schedules;
    }

    public function build_schedules() {
        $success = array('success' => false);
        $accounts = $this->get_active_cm_accounts();
        if (count($accounts['partner_ids']) > 0) {
            $final_schedules = array();
            $schedules = array();
            foreach ($accounts['partner_ids'] as $partner_id) {
                $live_channels = $this->get_channel_ids($partner_id);
                if (count($live_channels) > 0) {
                    $date = new DateTime('now');
                    $date->setTimezone(new DateTimeZone('UTC'));
                    $start_date = $date->format('Y-m-d 00:00:00');
                    $end_date = $date->format('Y-m-d 23:59:59');
                    $plist_num = 1;
                    $ready_channels = array();
                    $playlist = array();
                    $now_date = $date->format('Y-m-d H:i:s');
                    foreach ($live_channels as $channel) {
                        $programs = $this->get_program_dates($partner_id, null, $channel, $start_date, $end_date);
                        if (count($programs['nonrepeat_programs']) > 0) {
                            foreach ($programs['nonrepeat_programs'] as $nonrepeat_program) {
                                if ($nonrepeat_program['end_date'] >= $now_date) {
                                    $video_srcs = array();
                                    $entry_details = $this->smportal->get_entry_details($partner_id, $nonrepeat_program['entry_id']);
                                    if ($entry_details['type'] === 1 || $entry_details['type'] === 7) {
                                        $video_src = $this->buildVideoSrcs($partner_id, $nonrepeat_program['entry_id'], $entry_details['type'], $entry_details['duration'], $nonrepeat_program['event_length'], $nonrepeat_program['start_date'], $now_date);
                                        array_push($video_srcs, $video_src);
                                    } else if ($entry_details['type'] === 5) {
                                        //TODO Playlist
                                    }
                                    array_push($playlist, array('name' => 'pl' . $plist_num, 'playOnStream' => $channel, 'repeat' => false, 'scheduled' => $nonrepeat_program['start_date'], 'video_srcs' => $video_srcs));
                                    $plist_num++;
                                }
                            }
                        }
                        if (count($programs['repeat_programs']) > 0) {
                            foreach ($programs['repeat_programs'] as $repeat_programs) {
                                $rec_programs = $this->when_api->process_rec_programs_build_schedule($repeat_programs['start_date'], $repeat_programs['end_date'], $start_date, $end_date, $repeat_programs['rec_type'], $repeat_programs['event_length']);
                                if (count($rec_programs['date_range_found']) > 0) {
                                    if ($rec_programs['date_range_found']['end_date'] >= $now_date) {
                                        $video_srcs = array();
                                        $entry_details = $this->smportal->get_entry_details($partner_id, $repeat_programs['entry_id']);
                                        if ($entry_details['type'] === 1 || $entry_details['type'] === 7) {
                                            $video_src = $this->buildVideoSrcs($partner_id, $repeat_programs['entry_id'], $entry_details['type'], $entry_details['duration'], $repeat_programs['event_length'], $rec_programs['date_range_found']['start_date'], $now_date);
                                            array_push($video_srcs, $video_src);
                                        } else if ($entry_details['type'] === 5) {
                                            //TODO Playlist
                                        }
                                        array_push($playlist, array('name' => 'pl' . $plist_num, 'playOnStream' => $channel, 'repeat' => false, 'scheduled' => $rec_programs['date_range_found']['start_date'], 'video_srcs' => $video_srcs));
                                        $plist_num++;
                                        //syslog(LOG_NOTICE, "SMH DEBUG : build_schedules: " . print_r($repeat_programs, true));                                     
                                    }
                                }
                            }
                        }
                        if (count($playlist) > 0 && $this->multi_array_search($channel, $playlist)) {
                            array_push($ready_channels, $channel);
                        }
                    }
                    if (count($ready_channels) > 0) {
                        $schedules['account'] = (int) $partner_id;
                        $schedules['streams'] = array();
                        $schedules['streams'] = $ready_channels;
                        $schedules['playlists'] = $playlist;
                        array_push($final_schedules, $schedules);
                    } else {
                        $schedules['account'] = (int) $partner_id;
                        $schedules['streams'] = array();
                        $schedules['playlists'] = array();
                        array_push($final_schedules, $schedules);
                    }
                } else {
                    $schedules['account'] = (int) $partner_id;
                    $schedules['streams'] = array();
                    $schedules['playlists'] = array();
                    array_push($final_schedules, $schedules);
                }
            }
            $success = array('success' => true, 'schedules' => $final_schedules);
        } else {
            $success = array('success' => false, 'error' => 'No accounts found with channel manager service');
        }
        return $success;
    }

    public function buildVideoSrcs($pid, $entryId, $entryType, $entryDuration, $event_length, $start_date, $now_date) {
        $sources = array();
        $video_src = '';
        $start = 0;
        if ($entryType === 1) {
            $flavor = $this->smportal->ott_get_flavor($pid, $entryId);
            $file_path = $this->ott_get_raw_file($pid, $flavor['flavorId'], $flavor['flavorVersion']);
            $filename = $flavor['fileExt'] . ':' . $file_path['file_path'];
            $video_src = 'httpcache1/' . $pid . '/content/' . $filename;
            if ($now_date >= $start_date) {
                $date1 = new DateTime($start_date);
                $date2 = new DateTime($now_date);
                $diffInSeconds = $date2->getTimestamp() - $date1->getTimestamp();
                $start = $diffInSeconds;
            }
        } else if ($entryType === 7) {
            $streamName = $this->smportal->get_stream_name($pid, $entryId);
            $video_src = $streamName;
            $start = -2;
        }

        $length = ((int) $entryDuration === (int) $event_length) ? -1 : $event_length;
        $sources['video_src'] = $video_src;
        $sources['start'] = (int) $start;
        $sources['length'] = (int) $length;
        return $sources;
    }

    public function ott_get_raw_file($pid, $flavorId, $flavorVersion) {
        $success = array('success' => false);
        $this->config = $this->load->database('kaltura', TRUE);
        $this->config->select('*')
                ->from('file_sync')
                ->where('partner_id', $pid)
                ->where('object_id', $flavorId)
                ->where('version', $flavorVersion)
                ->where('status', 2);

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $file_path = '';
            foreach ($result as $res) {
                $fp_explode = explode('/', $res['file_path']);
                $file_path = end($fp_explode);
            }
            $success = array('success' => true, 'file_path' => $file_path);
        }

        return $success;
    }

    public function get_channel_ids($pid) {
        $channel_ids = array();
        $this->config = $this->load->database('kaltura', TRUE);
        $this->config->select('*')
                ->from('entry')
                ->where('partner_id', $pid)
                ->where('type', 8)
                ->where('status', 2)
                ->where('custom_data LIKE \'%s:20:"push_publish_enabled";i:1%\'')
                ->order_by("created_at", "desc");
        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {

            foreach ($result as $res) {
                array_push($channel_ids, $res['id']);
            }
            syslog(LOG_NOTICE, "SMH DEBUG : get_channel_ids: " . print_r($channel_ids, true));
        }

        return $channel_ids;
    }

    public function get_active_cm_accounts() {
        $success = array('success' => false);
        $this->config = $this->load->database('accounts', TRUE);
        $this->config->select('am.uid, am.publisher_id, am.status, ms.channel_manager')
                ->from('master_services AS ms, accounts_master AS am')
                ->where('am.uid = ms.uid')
                ->where('ms.channel_manager = 1')
                ->where('am.status = 1');

        $query = $this->config->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            $partner_ids = array();
            foreach ($result as $res) {
                $pid = $res['publisher_id'];
                array_push($partner_ids, $pid);
            }
            $success = array('success' => true, 'partner_ids' => $partner_ids);
        }

        return $success;
    }

    public function build_account_schedule($partner_id, $ks) {
        $success = array('success' => false);
        $schedule = array();
        $live_channels = $this->get_channel_ids($partner_id);
        if (count($live_channels) > 0) {
            $date = new DateTime('now');
            $date->setTimezone(new DateTimeZone('UTC'));
            $start_date = $date->format('Y-m-d 00:00:00');
            $end_date = $date->format('Y-m-d 23:59:59');
            $plist_num = 1;
            $ready_channels = array();
            $playlist = array();
            $now_date = $date->format('Y-m-d H:i:s');
            foreach ($live_channels as $channel) {
                $programs = $this->get_program_dates($partner_id, null, $channel, $start_date, $end_date);
                if (count($programs['nonrepeat_programs']) > 0) {
                    foreach ($programs['nonrepeat_programs'] as $nonrepeat_program) {
                        if ($nonrepeat_program['end_date'] >= $now_date) {
                            $video_srcs = array();
                            $entry_details = $this->smportal->get_entry_details($partner_id, $nonrepeat_program['entry_id']);
                            if ($entry_details['type'] === 1 || $entry_details['type'] === 7) {
                                $video_src = $this->buildVideoSrcs($partner_id, $nonrepeat_program['entry_id'], $entry_details['type'], $entry_details['duration'], $nonrepeat_program['event_length'], $nonrepeat_program['start_date'], $now_date);
                                array_push($video_srcs, $video_src);
                            } else if ($entry_details['type'] === 5) {
                                //TODO Playlist
                            }
                            array_push($playlist, array('name' => 'pl' . $plist_num, 'playOnStream' => $channel, 'repeat' => false, 'scheduled' => $nonrepeat_program['start_date'], 'video_srcs' => $video_srcs));
                            $plist_num++;
                        }
                    }
                }
                if (count($programs['repeat_programs']) > 0) {
                    foreach ($programs['repeat_programs'] as $repeat_programs) {
                        $rec_programs = $this->when_api->process_rec_programs_build_schedule($repeat_programs['start_date'], $repeat_programs['end_date'], $start_date, $end_date, $repeat_programs['rec_type'], $repeat_programs['event_length']);
                        if (count($rec_programs['date_range_found']) > 0) {
                            if ($rec_programs['date_range_found']['end_date'] >= $now_date) {
                                $video_srcs = array();
                                $entry_details = $this->smportal->get_entry_details($partner_id, $repeat_programs['entry_id']);
                                if ($entry_details['type'] === 1 || $entry_details['type'] === 7) {
                                    $video_src = $this->buildVideoSrcs($partner_id, $repeat_programs['entry_id'], $entry_details['type'], $entry_details['duration'], $repeat_programs['event_length'], $rec_programs['date_range_found']['start_date'], $now_date);
                                    array_push($video_srcs, $video_src);
                                } else if ($entry_details['type'] === 5) {
                                    //TODO Playlist
                                }
                                array_push($playlist, array('name' => 'pl' . $plist_num, 'playOnStream' => $channel, 'repeat' => false, 'scheduled' => $rec_programs['date_range_found']['start_date'], 'video_srcs' => $video_srcs));
                                $plist_num++;
                                //syslog(LOG_NOTICE, "SMH DEBUG : build_schedules: " . print_r($repeat_programs, true));                               
                            }
                        }
                    }
                }
                if (count($playlist) > 0 && $this->multi_array_search($channel, $playlist)) {
                    array_push($ready_channels, $channel);
                }
            }
            if (count($ready_channels) > 0) {
                $schedule['account'] = (int) $partner_id;
                $schedule['ks'] = $ks;
                $schedule['streams'] = array();
                $schedule['streams'] = $ready_channels;
                $schedule['playlists'] = $playlist;
            }
        }
        $success = array('success' => true, 'schedule' => $schedule);
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
                array_push($segments, array('id' => $id));
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
                if (strpos($sid, ',') !== false) {
                    $sid_explode = array_map('intval', explode(',', $sid));
                    foreach ($sid_explode as $lsid) {
                        $delete_live_segment = $this->smportal->delete_live_segment($pid, $ks, $lsid);
                        if ($delete_live_segment['success']) {
                            $get_program_config_id = $this->get_program_config_id($pid, $lsid);
                            if ($get_program_config_id['success']) {
                                $update_program_config_status = $this->update_program_config_status($pid, $get_program_config_id['pcid'], 3);
                                if ($update_program_config_status['success']) {
                                    $push_schedule = $this->push_schedule($pid, $ks);
                                    if ($push_schedule['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not push schedule');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not delete program config');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not get program config id');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not delete live segment');
                        }
                    }
                } else {
                    $delete_live_segment = $this->smportal->delete_live_segment($pid, $ks, $sid);
                    if ($delete_live_segment['success']) {
                        $get_program_config_id = $this->get_program_config_id($pid, $sid);
                        if ($get_program_config_id['success']) {
                            $update_program_config_status = $this->update_program_config_status($pid, $get_program_config_id['pcid'], 3);
                            if ($update_program_config_status['success']) {
                                $push_schedule = $this->push_schedule($pid, $ks);
                                if ($push_schedule['success']) {
                                    $success = array('success' => true);
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not push schedule');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not delete program config');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not get program config id');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not delete live segment');
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

                $collision = $this->collision_detection($pid, $ks, null, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);
                if ($collision['collision']) {
                    $success = array('success' => false, 'collision' => true);
                } else {
                    //$success = array('success' => false, 'collision' => false);
                    $add_live_segment = $this->smportal->add_live_segment($pid, $ks, $cid, $eid);
                    if ($add_live_segment['success']) {
                        $add_custom_data = $this->add_live_segment_custom_data($pid, $ks, $add_live_segment['id'], $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);
                        if ($add_custom_data['success']) {
                            $add_live_segment_id = $this->add_live_segment_id($pid, $add_live_segment['id'], $add_custom_data['id']);
                            if ($add_live_segment_id['success']) {
                                $push_schedule = $this->push_schedule($pid, $ks);
                                if ($push_schedule['success']) {
                                    $success = array('success' => true);
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not push schedule');
                                }
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

    public function update_program($pid, $ks, $lsid, $pcid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length) {
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

                $collision = $this->collision_detection($pid, $ks, $pcid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);
                if ($collision['collision']) {
                    $success = array('success' => false, 'collision' => true);
                } else {
                    //$success = array('success' => false, 'collision' => false);
                    $update_live_segment = $this->smportal->update_live_segment($pid, $ks, $lsid, $cid, $eid);
                    if ($update_live_segment['success']) {
                        $update_custom_data = $this->update_live_segment_custom_data($pid, $ks, $pcid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length);
                        if ($update_custom_data['success']) {
                            $push_schedule = $this->push_schedule($pid, $ks);
                            if ($push_schedule['success']) {
                                $success = array('success' => true);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not push schedule');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not update custom data');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update live segment');
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

    public function collision_detection($pid, $ks, $pcid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length) {
        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection1: start_date: " . print_r($start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection1: end_date: " . print_r($end_date, true));
        $collision = array('collision' => false);
        $tz = $this->get_int_timezone($pid, $ks);
        $tz_from = $tz;
        $tz_to = 'UTC';
        $when_tz_from = 'UTC';
        $when_tz_to = $tz;

        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection: tz_to: " . print_r($tz, true));

        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
//        if (date("I", strtotime($start_date))) {
//            $start_dt->add(new DateInterval('PT1H'));
//        }
        $start_date = $start_dt->format('Y-m-d H:i:s');

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
//            if (date("I", strtotime($end_date))) {
//                $end_dt->add(new DateInterval('PT1H'));
//            }
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }


        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection2: start_date: " . print_r($start_date, true));
        syslog(LOG_NOTICE, "SMH DEBUG : collision_detection2: end_date: " . print_r($end_date, true));

        $programs = $this->get_program_dates($pid, $pcid, $cid, $start_date, $end_date);
        $rec_collision = array('collision' => false);
        $non_rec_collision = array('collision' => false);
        if ($repeat) {
            if (count($programs['nonrepeat_programs'] > 0)) {
                $non_rec_collision = $this->when_api->process_non_rec_programs_a($start_date, $end_date, $rec_type, $event_length, $programs['nonrepeat_programs'], $when_tz_from, $when_tz_to);
            }
            if (!$non_rec_collision['collision']) {
                if (count($programs['repeat_programs'] > 0)) {
                    $rec_collision = $this->when_api->process_rec_programs_a($start_date, $end_date, $rec_type, $event_length, $programs['repeat_programs'], $when_tz_from, $when_tz_to);
                }
            }
        } else {
            if (count($programs['nonrepeat_programs'] > 0)) {
                $non_rec_collision = $this->when_api->process_non_rec_programs_b($start_date, $end_date, $programs['nonrepeat_programs'], $when_tz_from, $when_tz_to);
            }
            if (!$non_rec_collision['collision']) {
                if (count($programs['repeat_programs'] > 0)) {
                    $rec_collision = $this->when_api->process_rec_programs_b($start_date, $end_date, $programs['repeat_programs'], $when_tz_from, $when_tz_to);
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

    public function get_program_dates($pid, $pcid, $cid, $start_date, $end_date) {
        $success = array('success' => false);
        $this->config = $this->load->database('ch', TRUE);
        if ($pcid) {
            $this->config->select('*')
                    ->from('program_config')
                    ->where('partner_id', $pid)
                    ->where('channel_id', $cid)
                    ->where('id !=', $pcid)
                    ->where('status', 2)
                    ->where('start_date <=', $end_date)
                    ->where('end_date >=', $start_date);
        } else {
            $this->config->select('*')
                    ->from('program_config')
                    ->where('partner_id', $pid)
                    ->where('channel_id', $cid)
                    ->where('status', 2)
                    ->where('start_date <=', $end_date)
                    ->where('end_date >=', $start_date);
        }

        $query = $this->config->get();
        $result = $query->result_array();

        syslog(LOG_NOTICE, "SMH DEBUG : get_program_dates: " . print_r($this->config->last_query(), true));

        if ($query->num_rows() > 0) {
            $programs_repeat = array();
            $programs_nonrepeat = array();
            foreach ($result as $res) {
                if ($res['repeat']) {
                    array_push($programs_repeat, array('start_date' => $res['start_date'], 'end_date' => $res['end_date'], 'rec_type' => $res['rec_type'], 'event_length' => $res['event_length'], 'entry_id' => $res['entry_id']));
                } else {
                    array_push($programs_nonrepeat, array('start_date' => $res['start_date'], 'end_date' => $res['end_date'], 'event_length' => $res['event_length'], 'entry_id' => $res['entry_id']));
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

    public function add_live_segment_custom_data($pid, $ks, $sid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length) {
        $success = array('success' => false);

        $tz_from = $this->get_int_timezone($pid, $ks);
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

    public function update_live_segment_custom_data($pid, $ks, $pcid, $cid, $eid, $start_date, $end_date, $repeat, $rec_type, $event_length) {
        $success = array('success' => false);

        syslog(LOG_NOTICE, "SMH DEBUG : update_live_segment_custom_data: start_date1: " . print_r($start_date, true));

        $tz_from = $this->get_int_timezone($pid, $ks);
        $tz_to = 'UTC';
        $start_dt = new DateTime($start_date, new DateTimeZone($tz_from));
        $start_dt->setTimeZone(new DateTimeZone($tz_to));
        $start_date = $start_dt->format('Y-m-d H:i:s');

        syslog(LOG_NOTICE, "SMH DEBUG : update_live_segment_custom_data: start_date2: " . print_r($start_date, true));

        if ($end_date !== '9999-02-01 00:00:00') {
            $end_dt = new DateTime($end_date, new DateTimeZone($tz_from));
            $end_dt->setTimeZone(new DateTimeZone($tz_to));
            $end_date = $end_dt->format('Y-m-d H:i:s');
        }

        $data = array(
            'channel_id' => $cid,
            'entry_id' => $eid,
            'status' => 2,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'repeat' => $repeat,
            'rec_type' => $rec_type,
            'event_pid' => 0,
            'event_length' => (int) $event_length,
            'updated_at' => date('Y-m-d H:i:s')
        );
        $this->config = $this->load->database('ch', TRUE);
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

    public function delete_channel($pid, $ks, $cid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $live_channel_segments = $this->get_live_channel_segments($pid, $cid);
                if ($live_channel_segments['success']) {
                    foreach ($live_channel_segments['live_channel_segments'] as $segment) {
                        $delete_live_segment = $this->smportal->delete_live_segment($pid, $ks, $segment['id']);
                        if ($delete_live_segment['success']) {
                            $get_program_config_id = $this->get_program_config_id($pid, $segment['id']);
                            if ($get_program_config_id['success']) {
                                $this->update_program_config_status($pid, $get_program_config_id['pcid'], 3);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not get program config id');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not delete live segment');
                        }
                    }
                    $delete_channel_resp = $this->smportal->delete_live_channel($pid, $ks, $cid);
                    if ($delete_channel_resp['success']) {
                        $push_schedule = $this->push_schedule($pid, $ks);
                        if ($push_schedule['success']) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not push schedule');
                        }
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $delete_channel_resp = $this->smportal->delete_live_channel($pid, $ks, $cid);
                    if ($delete_channel_resp['success']) {
                        $push_schedule = $this->push_schedule($pid, $ks);
                        if ($push_schedule['success']) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not push schedule');
                        }
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

    public function update_channel_status($pid, $ks, $cid, $status) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $update_channel_status = $this->smportal->update_channel_status($pid, $ks, $cid, $status);
                if ($update_channel_status['success']) {
                    $push_schedule = $this->push_schedule($pid, $ks);
                    if ($push_schedule['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not push schedule');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not update channel status');
                }
            } else {
                $success = array('success' => false, 'message' => 'Channel Manager service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        return $success;
    }

    public function get_timezone($pid, $ks, $tz) {
        $success = array('success' => false);
        $timezone = '';
        $tz_resp = $this->smportal->get_timezone($pid, $ks, $tz);
        if ($tz_resp) {
            $timezone = $tz_resp;
        }
        $success = array('success' => true, 'timezone' => $timezone);
        return $success;
    }

    public function get_int_timezone($pid, $ks) {
        $timezone = $this->smportal->get_int_timezone($pid, $ks);
        return $timezone;
    }

    public function update_timezone($pid, $ks, $tz) {
        $success = array('success' => false);
        $timezone = $this->smportal->set_new_timezone($pid, $ks, $tz);
        $success = array('success' => true, 'timezone' => $timezone);
        return $success;
    }

    public function getCDN($pid) {
        $url = 'http://ecapps.streamingmediahosting.com/apps/scripts/getCDN.php?action=get_cdn&pid=' . $pid;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function curlPost($url, $data) {
        syslog(LOG_NOTICE, "SMH DEBUG : curlPostJson1: " . print_r($data, true));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ch_error = curl_error($ch);
        syslog(LOG_NOTICE, "SMH DEBUG : curlStatus: " . $status);
        if ($ch_error) {
            syslog(LOG_NOTICE, "SMH DEBUG : curlError: " . print_r($ch_error, true));
        }
        syslog(LOG_NOTICE, "SMH DEBUG : curlPostJson2: " . print_r($response, true));
        curl_close($ch);

        return $status;
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
