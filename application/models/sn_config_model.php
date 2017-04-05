<?php

error_reporting(1);

class Sn_config_model extends CI_Model {

    protected $_ci;

    public function __construct() {
        // Open the correct DB connection
        $this->config = $this->load->database('sn', TRUE);
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
        $this->load->library('google_client_api');
    }

    public function update_sn_config($pid, $ks, $platform, $status) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                if ($this->check_platform_status($valid['pid'])) {
                    $result = $this->update_platform_status($valid['pid'], $platform, $status);
                } else {
                    $result = $this->insert_platform_status($valid['pid'], $platform, $status);
                }
                if ($result['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function check_platform_status($pid) {
        $success = false;
        $this->config->select('*')
                ->from('platform_status')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function insert_platform_status($pid, $platform, $status) {
        $success = array('success' => false);
        if ($platform == 'facebook') {
            $data = array(
                'partner_id' => $pid,
                'facebook_live' => $status,
                'youtube_live' => 0
            );
        } else if ($platform == 'youtube') {
            $data = array(
                'partner_id' => $pid,
                'facebook_live' => 0,
                'youtube_live' => $status,
            );
        }

        $this->config->insert('platform_status', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_platform_status($pid, $platform, $status) {
        $success = array('success' => false);
        if ($platform == 'facebook') {
            $data = array(
                'facebook_live' => $status,
            );
        } else if ($platform == 'youtube') {
            $data = array(
                'youtube_live' => $status,
            );
        }

        $this->config->where('partner_id', $pid);
        $this->config->update('platform_status', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function get_sn_config($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms = array();
                $facebook = $this->facebook_platform($valid['pid']);
                $youtube = $this->youtube_platform($valid['pid'], $ks);
                array_push($platforms, $facebook, $youtube);
                $success = array('success' => true, 'platforms' => $platforms);
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function facebook_platform($pid) {
        $fb_status = $this->get_facebook_status($pid);
        $status = ($fb_status['success']) ? $fb_status['status'] : 0;
        $fb = array('platform' => 'facebook_live', 'status' => $status);
        return $fb;
    }

    public function get_facebook_status($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('platform_status')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $status = $res['facebook_live'];
            }
            $success = array('success' => true, 'status' => $status);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function youtube_platform($pid, $ks) {
        $youtube_status = $this->get_youtube_status($pid);
        $youtube_auth = $this->validate_youtube_token($pid);
        $status = ($youtube_status['success']) ? $youtube_status['status'] : 0;
        $auth = ($youtube_auth['success']) ? true : false;
        $youtube = array('platform' => 'youtube_live', 'status' => $status, 'authorized' => $auth);
        if (!$auth) {
            $youtube['redirect_url'] = $this->google_client_api->getRedirectURL($pid, $ks);
        }
        return $youtube;
    }

    public function get_youtube_status($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('platform_status')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $status = $res['youtube_live'];
            }
            $success = array('success' => true, 'status' => $status);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function validate_youtube_token($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('youtube_live')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $token = array();
            foreach ($result as $res) {
                $token['access_token'] = $res['access_token'];
                $token['refresh_token'] = $res['refresh_token'];
                $token['token_type'] = $res['token_type'];
                $token['expires_in'] = $res['expires_in'];
                $token['created'] = $res['created'];
            }
            $tokens_valid = $this->google_client_api->checkAuthToken($token);
            if ($tokens_valid['success'] && ($tokens_valid['message'] == 'valid_access_token')) {
                $success = array('success' => true, 'access_token' => $tokens_valid['access_token']);
            }
            if ($tokens_valid['success'] && ($tokens_valid['message'] == 'new_access_token')) {
                $access_token = $this->update_youtube_access_token($pid, $tokens_valid['access_token']);
                if ($access_token['success']) {
                    $success = array('success' => true, 'access_token' => $tokens_valid['access_token']);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function remove_youtube_authorization($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $this->config->select('*')
                        ->from('youtube_live')
                        ->where('partner_id', $valid['pid']);

                $query = $this->config->get();
                $result = $query->result_array();
                if ($query->num_rows() > 0) {
                    foreach ($result as $res) {
                        $access_token = $res['access_token'];
                    }
                    $tokens_valid = $this->google_client_api->removeAuth($access_token);
                    if ($tokens_valid['success']) {
                        $remove_yt_live = $this->remove_youtube_live($pid);
                        if ($remove_yt_live['success']) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function remove_youtube_live($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('youtube_live');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function store_youtube_authorization($pid, $ks, $code) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $tokens = $this->google_client_api->getTokens($code);
                if ($this->check_youtube($valid['pid'])) {
                    $result = $this->update_youtube_tokens($valid['pid'], $tokens);
                } else {
                    $result = $this->insert_youtube_tokens($valid['pid'], $tokens);
                }
                if ($result['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function insert_youtube_tokens($pid, $tokens) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'created' => $tokens['created']
        );
        $this->config->insert('youtube_live', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_youtube_tokens($pid, $tokens) {
        $success = array('success' => false);
        $data = array(
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'token_type' => $tokens['token_type'],
            'expires_in' => $tokens['expires_in'],
            'created' => $tokens['created']
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_live', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function update_youtube_access_token($pid, $token) {
        $success = array('success' => false);
        $data = array(
            'access_token' => $token['access_token'],
            'refresh_token' => $token['refresh_token'],
            'token_type' => $token['token_type'],
            'expires_in' => $token['expires_in'],
            'created' => $token['created']
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_live', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function check_youtube($pid) {
        $success = false;
        $this->config->select('*')
                ->from('youtube_live')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function create_sn_livestreams($pid, $ks, $name, $desc, $eid, $platforms, $projection) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $snConfig = $this->buildSnConfig($platforms);
                if ($snConfig['youtube']) {
                    $access_token = $this->validate_youtube_token($valid['pid']);
                    if ($access_token['success']) {
                        $thumbnail = array('use_default' => true);
                        $livestream = $this->google_client_api->createLiveStream($access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection);
                        if ($livestream['success']) {
                            $updated_config = $this->insert_youtube_broadcast_id($livestream['liveBroadcastId'], $snConfig['sn_config']);
                            $partnerData = $this->update_sn_partnerData($pid, $eid, $updated_config['sn_config']);
                            if ($partnerData['success']) {
                                $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                                if ($insert_live_event['success']) {
                                    $platf = $this->getPlatforms(json_decode($partnerData['partnerData']));
                                    $configSettings = $this->buildConfigSettings($platf);
                                    $success = array('success' => true, 'configSettings' => $configSettings);
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'YouTube: could not create Live Event');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                    }
                } else {
                    $partnerData = $this->update_sn_partnerData($pid, $eid, $snConfig['sn_config']);
                    if ($partnerData['success']) {
                        $platf = $this->getPlatforms(json_decode($partnerData['partnerData']));
                        $configSettings = $this->buildConfigSettings($platf);
                        $success = array('success' => true, 'configSettings' => $configSettings);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                    }
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function buildConfigSettings($platforms) {
        $platforms_preview_embed_arr = array();
        if ($platforms['snConfig']) {
            foreach ($platforms['platforms'] as $platform) {
                if ($platform['platform'] == 'smh') {
                    if ($platform['status']) {
                        array_push($platforms_preview_embed_arr, "smh:1");
                    } else {
                        array_push($platforms_preview_embed_arr, "smh:0");
                    }
                }
                if ($platform['platform'] == 'youtube_live') {
                    if ($platform['status']) {
                        array_push($platforms_preview_embed_arr, "youtube:1:" . $platform['broadcastId']);
                    } else {
                        array_push($platforms_preview_embed_arr, "youtube:0");
                    }
                }
            }
            $platforms_preview_embed = implode(";", $platforms_preview_embed_arr);
        }
        return $platforms_preview_embed;
    }

    public function insert_youtube_broadcast_id($bid, $platforms_config) {
        $new_platforms_config = array();
        foreach ($platforms_config as $platform) {
            if ($platform['platform'] == 'youtube_live') {
                if ($platform['status']) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status'], 'broadcastId' => $bid));
                } else {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status']));
                }
            } else {
                array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status']));
            }
        }
        $success = array('success' => true, 'sn_config' => $new_platforms_config);
        return $success;
    }

    public function insert_youtube_live_event($pid, $eid, $liveBroadcastId, $liveStreamId, $streamName, $ingestionAddress, $projection) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'liveBroadcastId' => $liveBroadcastId,
            'liveStreamId' => $liveStreamId,
            'streamName' => $streamName,
            'ingestionAddress' => $ingestionAddress,
            'projection' => $projection
        );
        $this->config->insert('youtube_live_events', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_sn_partnerData($pid, $eid, $platforms_config) {
        $success = array('success' => false);
        $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
        if ($partnerData['success']) {
            $temp_partnerData = json_decode($partnerData['partnerData'], true);
            $temp_partnerData['snConfig'] = $platforms_config;
            $update_partnerData = $this->smportal->update_entry_partnerData($pid, $eid, $temp_partnerData);
            if ($update_partnerData['success']) {
                $success = array('success' => true, 'partnerData' => $update_partnerData['partnerData']);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function buildSnConfig($platforms) {
        $success = array('success' => false);
        $platforms_config = array();
        $platforms = json_decode($platforms, true);
        $youtube = false;
        $res = '240p';
        foreach ($platforms['platforms'] as $platform) {
            if ($platform['platform'] == 'smh') {
                array_push($platforms_config, array('platform' => 'smh', 'status' => $platform['status']));
            } else if ($platform['platform'] == 'youtube_live') {
                if ($platform['status']) {
                    $youtube = true;
                    $res = $platform['config']['res'];
                }
                array_push($platforms_config, array('platform' => 'youtube_live', 'status' => $platform['status']));
            }
        }
        $success = array('success' => true, 'sn_config' => $platforms_config, 'youtube' => $youtube, 'youtube_res' => $res);
        return $success;
    }

    public function update_sn_livestreams($pid, $ks, $name, $desc, $eid, $platforms, $projection) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $snConfig = $this->buildSnConfig($platforms);
                if ($snConfig['youtube']) {
                    $access_token = $this->validate_youtube_token($valid['pid']);
                    if ($access_token['success']) {
                        $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
                        if ($youtube_ids['success']) {
                            $livestream = $this->google_client_api->updateLiveStream($access_token['access_token'], $snConfig['youtube_res'], $name, $eid, $youtube_ids['bid'], $youtube_ids['lid']);
                            if ($livestream['success']) {
                                $updated_config = $this->insert_youtube_broadcast_id($youtube_ids['bid'], $snConfig['sn_config']);
                                $partnerData = $this->update_sn_partnerData($pid, $eid, $updated_config['sn_config']);
                                if ($partnerData['success']) {
                                    $update_live_event = $this->update_youtube_live_event($pid, $eid, $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                                    if ($update_live_event['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'YouTube: could not update Live Event');
                            }
                        } else {
                            $thumbnail = $this->smportal->get_default_thumb($pid, $eid, $ks);
                            $livestream = $this->google_client_api->createLiveStream($access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection);
                            if ($livestream['success']) {
                                $updated_config = $this->insert_youtube_broadcast_id($livestream['liveBroadcastId'], $snConfig['sn_config']);
                                $partnerData = $this->update_sn_partnerData($pid, $eid, $updated_config['sn_config']);
                                if ($partnerData['success']) {
                                    $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                                    if ($insert_live_event['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'YouTube: could not create Live Event');
                            }
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                    }
                } else {
                    $partnerData = $this->update_sn_partnerData($pid, $eid, $snConfig['sn_config']);
                    if ($partnerData['success']) {
                        $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
                        if ($youtube_ids['success']) {
                            $access_token = $this->validate_youtube_token($valid['pid']);
                            if ($access_token['success']) {
                                $removeLiveStream = $this->google_client_api->removeLiveStream($access_token['access_token'], $youtube_ids['bid'], $youtube_ids['lid']);
                                if ($removeLiveStream['success']) {
                                    $removeLiveEvent = $this->removeLiveEvent($pid, $eid);
                                    if ($removeLiveEvent['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not remove live event');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'YouTube: Could not remove livestream');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                            }
                        } else {
                            $success = array('success' => true);
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                    }
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function update_youtube_live_event($pid, $eid, $lid, $streamName, $ingestionAddress) {
        $success = array('success' => false);
        $data = array(
            'liveStreamId' => $lid,
            'streamName' => $streamName,
            'ingestionAddress' => $ingestionAddress
        );
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->update('youtube_live_events', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_youtube_live_event_bid($pid, $eid, $bid) {
        $success = array('success' => false);
        $data = array(
            'liveBroadcastId' => $bid
        );
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->update('youtube_live_events', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_youtube_event_ids($pid, $eid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('youtube_live_events')
                ->where('partner_id', $pid)
                ->where('entryId', $eid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $bid = $res['liveBroadcastId'];
                $lid = $res['liveStreamId'];
                $proj = $res['projection'];
            }
            $success = array('success' => true, 'bid' => $bid, 'lid' => $lid, 'proj' => $proj);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function removeLiveEvent($pid, $eid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '" AND entryId = "' . $eid . '"');
        $this->config->delete('youtube_live_events');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_sn_metadata($pid, $ks, $name, $desc, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
                            if ($youtube_ids['success']) {
                                $access_token = $this->validate_youtube_token($valid['pid']);
                                if ($access_token['success']) {
                                    $updateMetaData = $this->google_client_api->updateMetaData($access_token['access_token'], $youtube_ids['bid'], $name, $desc);
                                    if ($updateMetaData['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'YouTube: Could not update metadata');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                                }
                            } else {
                                $success = array('success' => true);
                            }
                        } else {
                            $success = array('success' => true, 'message' => 'Social network: nothing to update');
                        }
                    } else {
                        $success = array('success' => true, 'message' => 'Social network config not present');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get platforms status');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function get_platforms_status($pid, $eid) {
        $success = array('success' => false);
        $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
        if ($partnerData['success']) {
            $temp_partnerData = json_decode($partnerData['partnerData'], true);
            $platforms_status = array();
            if (isset($temp_partnerData['snConfig'])) {
                foreach ($temp_partnerData['snConfig'] as $platform) {
                    if ($platform['platform'] == 'smh') {
                        $platforms_status['smh'] = $platform['status'];
                    }
                    if ($platform['platform'] == 'youtube_live') {
                        $platforms_status['youtube'] = $platform['status'];
                    }
                }
            }
            $success = array('success' => true, 'platforms_status' => $platforms_status);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_sn_thumbnail($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
                            if ($youtube_ids['success']) {
                                $access_token = $this->validate_youtube_token($valid['pid']);
                                if ($access_token['success']) {
                                    $thumbnail = $this->smportal->get_default_thumb($pid, $eid, $ks);
                                    $updateThumbnail = $this->google_client_api->updateThumbnail($access_token['access_token'], $youtube_ids['bid'], $thumbnail);
                                    if ($updateThumbnail['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'YouTube: Could not update metadata');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                                }
                            } else {
                                $success = array('success' => true);
                            }
                        } else {
                            $success = array('success' => true, 'message' => 'Social network: nothing to update');
                        }
                    } else {
                        $success = array('success' => true, 'message' => 'Social network config not present');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get platforms status');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function delete_sn_livestream($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
                            if ($youtube_ids['success']) {
                                $access_token = $this->validate_youtube_token($valid['pid']);
                                if ($access_token['success']) {
                                    $removeLiveStream = $this->google_client_api->removeLiveStream($access_token['access_token'], $youtube_ids['bid'], $youtube_ids['lid']);
                                    if ($removeLiveStream['success']) {
                                        $removeLiveEvent = $this->removeLiveEvent($pid, $eid);
                                        if ($removeLiveEvent['success']) {
                                            $success = array('success' => true);
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not remove live event');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'YouTube: Could not remove livestream');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                                }
                            } else {
                                $success = array('success' => true);
                            }
                        } else {
                            $success = array('success' => true, 'message' => 'Social network: nothing to update');
                        }
                    } else {
                        $success = array('success' => true, 'message' => 'Social network config not present');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get platforms status');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function getPlatforms($json) {
        $result = array();
        $result['platforms'] = array();
        foreach ($json as $key => $value) {
            if ($key == 'snConfig') {
                $result['snConfig'] = true;
                foreach ($value as $platforms) {
                    if ($platforms->platform == "smh") {
                        $platform = array('platform' => 'smh', 'status' => $platforms->status);
                        array_push($result['platforms'], $platform);
                    }
                    if ($platforms->platform == "youtube_live") {
                        if ($platforms->status) {
                            $platform = array('platform' => 'youtube_live', 'status' => $platforms->status, 'broadcastId' => $platforms->broadcastId);
                            array_push($result['platforms'], $platform);
                        } else {
                            $platform = array('platform' => 'youtube_live', 'status' => $platforms->status);
                            array_push($result['platforms'], $platform);
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function get_sn_livestreams($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms = array();
                $youtube_status = $this->get_youtube_status($pid);
                if ($youtube_status['status']) {
                    $platforms_status = $this->get_platforms_status($pid, $eid);
                    if ($platforms_status['success']) {
                        if (count($platforms_status['platforms_status'])) {
                            array_push($platforms, array('platform' => 'edgecast', 'status' => $platforms_status['platforms_status']['smh']));
                            if ($platforms_status['platforms_status']['youtube']) {
                                $ingestionSettings = $this->get_youtube_ingestion_settings($pid, $eid);
                                if ($ingestionSettings['success']) {
                                    $insert_youtube_entry = $this->insert_youtube_entry($pid, $eid, 'ready');
                                    if ($insert_youtube_entry['success']) {
                                        array_push($platforms, array('platform' => 'youtube', 'status' => $platforms_status['platforms_status']['youtube'], 'ingestionSettings' => $ingestionSettings['ingestionSettings']));
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not insert youtube entry');
                                    }
                                } else {
                                    array_push($platforms, array('platform' => 'youtube', 'status' => $platforms_status['platforms_status']['youtube']));
                                }
                            } else {
                                array_push($platforms, array('platform' => 'youtube', 'status' => $platforms_status['platforms_status']['youtube']));
                            }
                            $success = array('success' => true, 'platforms' => $platforms);
                        } else {
                            array_push($platforms, array('platform' => 'edgecast', 'status' => true));
                            array_push($platforms, array('platform' => 'youtube', 'status' => false));
                            $success = array('success' => true, 'platforms' => $platforms);
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get platforms status');
                    }
                } else {
                    array_push($platforms, array('platform' => 'edgecast', 'status' => true));
                    array_push($platforms, array('platform' => 'youtube', 'status' => false));
                    $success = array('success' => true, 'platforms' => $platforms);
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }
        header('Content-type: application/json');
        return $success;
    }

    public function get_youtube_ingestion_settings($pid, $eid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('youtube_live_events')
                ->where('partner_id', $pid)
                ->where('entryId', $eid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $ingestionSettings = array();
            foreach ($result as $res) {
                $streamName = $res['streamName'];
                $ingestionAddress = $res['ingestionAddress'];
            }
            array_push($ingestionSettings, array('streamName' => $streamName, 'ingestionAddress' => $ingestionAddress));
            $success = array('success' => true, 'ingestionSettings' => $ingestionSettings);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_youtube_entry($pid, $eid, $status) {
        $success = array('success' => false);
        if ($this->check_youtube_entry($pid, $eid)) {
            $result = $this->update_youtube_entry_status($pid, $eid, $status);
        } else {
            $result = $this->insert_youtube_entry_status($pid, $eid, $status);
        }
        if ($result['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_youtube_entry($pid, $eid) {
        $success = false;
        $this->config->select('*')
                ->from('youtube_live_entries')
                ->where('partner_id', $pid)
                ->where('entryId', $eid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function insert_youtube_entry_status($pid, $eid, $status) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'status' => $status,
            'created_at' => date("Y-m-d h:i:s")
        );
        $this->config->insert('youtube_live_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_youtube_entry_status($pid, $eid, $status) {
        $success = array('success' => false);
        $data = array(
            'status' => $status,
            'updated_at' => date("Y-m-d h:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->update('youtube_live_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function check_youtube_entries() {
        $entries = $this->get_youtube_entries();
        if ($entries['success']) {
            if (count($entries['ready_entries'])) {
                $this->transitionLiveStream($entries['ready_entries'], 'live');
            }
            if (count($entries['complete_entries'])) {
                $this->removeCompletedEntries();
            }
        }
        return array('success' => true);
    }

    public function removeCompletedEntries() {
        $success = array('success' => false);
        $this->config->where('status', 'complete');
        $this->config->delete('youtube_live_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function transitionLiveStream($entries, $status) {
        $success = array('success' => false);
        foreach ($entries as $entry) {
            $pid = $entry['pid'];
            $eid = $entry['eid'];
            $event_ids = $this->get_youtube_event_ids($pid, $eid);
            if ($event_ids['success']) {
                $access_token = $this->validate_youtube_token($pid);
                if ($access_token['success']) {
                    $transitionLiveStream = $this->google_client_api->transitionLiveStream($access_token['access_token'], $event_ids['bid'], $status);
                    if ($transitionLiveStream['success']) {
                        $updateLiveStreamStatus = $this->update_youtube_entry_status($pid, $eid, $status);
                        if ($updateLiveStreamStatus['success']) {
                            $success = array('success' => true);
                        }
                    }
                }
            }
        }
        return $success;
    }

    public function update_youtube_entries_status($entries) {
        $success = array('success' => false);
        foreach ($entries as $entry) {
            $pid = $entry['pid'];
            $eid = $entry['eid'];
            $event_ids = $this->get_youtube_event_ids($pid, $eid);
            if ($event_ids['success']) {
                $access_token = $this->validate_youtube_token($pid);
                if ($access_token['success']) {
                    $liveStreamStatus = $this->google_client_api->getLiveStreamStatus($access_token['access_token'], $event_ids['bid']);
                    if ($liveStreamStatus['success']) {
                        $updateLiveStreamStatus = $this->update_youtube_entry_status($pid, $eid, $liveStreamStatus['status']);
                        if ($updateLiveStreamStatus['success']) {
                            $success = array('success' => true);
                        }
                    }
                }
            }
        }
        return $success;
    }

    public function get_youtube_entries() {
        $success = array('success' => false);
        $ready = array();
        $testing = array();
        $complete = array();
        $this->config->select('*')
                ->from('youtube_live_entries');

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $pid = $res['partner_id'];
                $eid = $res['entryId'];
                $status = $res['status'];
            }
            if ($status == 'ready') {
                array_push($ready, array('pid' => $pid, 'eid' => $eid, 'status' => $status));
            } else if ($status == 'complete') {
                array_push($complete, array('pid' => $pid, 'eid' => $eid, 'status' => $status));
            }
            $success = array('success' => true, 'ready_entries' => $ready, 'complete_entries' => $complete);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function youtube_entry_complete($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $event_ids = $this->get_youtube_event_ids($pid, $eid);
            if ($event_ids['success']) {
                $access_token = $this->validate_youtube_token($pid);
                if ($access_token['success']) {
                    $transitionLiveStream = $this->google_client_api->transitionLiveStream($access_token['access_token'], $event_ids['bid'], 'complete');
                    if ($transitionLiveStream['success']) {
                        $updateLiveStreamStatus = $this->update_youtube_entry_status($pid, $eid, 'complete');
                        if ($updateLiveStreamStatus['success']) {
                            $thumbnail = $this->smportal->get_default_thumb($pid, $eid, $ks);
                            $entry_details = $this->smportal->get_entry_details($pid, $eid);
                            $createNewBroadCast = $this->google_client_api->createNewBroadcast($access_token['access_token'], $event_ids['bid'], $event_ids['lid'], $entry_details['name'], $entry_details['desc'], $thumbnail, $event_ids['proj']);
                            if ($createNewBroadCast['success']) {
                                $updateBid = $this->update_youtube_live_event_bid($pid, $eid, $createNewBroadCast['liveBroadcastId']);
                                if ($updateBid['success']) {
                                    $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
                                    if ($partnerData['success']) {
                                        $platforms = $this->getPlatforms(json_decode($partnerData['partnerData']));
                                        $updated_config = $this->insert_youtube_broadcast_id($createNewBroadCast['liveBroadcastId'], $platforms['platforms']);
                                        $partnerData = $this->update_sn_partnerData($pid, $eid, $updated_config['sn_config']);
                                        if ($partnerData['success']) {
                                            $success = array('success' => true);
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not get partnerData');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not update liveBroadcastId');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not create and bind new broadcast');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not update entry status to complete');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not transition status to complete');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get access token');
                }
            } else {
                $success = array('success' => false, 'message' => 'Could not get event ids');
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
        if ($response->social_network) {
            $has_service = true;
        }

        return $has_service;
    }

}
