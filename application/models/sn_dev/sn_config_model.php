<?php

error_reporting(1);

class Sn_config_model extends CI_Model {

    protected $_ci;

    public function __construct() {
// Open the correct DB connection
        $this->config = $this->load->database('sn_dev', TRUE);
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
        $this->load->library('google_client_api');
        $this->load->library('facebook_client_api');
    }

    public function update_sn_config($pid, $platform, $status) {
        $success = array('success' => false);
        if ($this->check_platform_status($pid)) {
            $result = $this->update_platform_status($pid, $platform, $status);
        } else {
            $result = $this->insert_platform_status($pid, $platform, $status);
        }
        if ($result['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
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
                $facebook = $this->facebook_platform($valid['pid'], $ks);
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

    public function facebook_platform($pid, $ks) {
        $facebook_auth = $this->validate_facebook_token($pid);
        $auth = ($facebook_auth['success']) ? true : false;
        if ($auth) {
            $user_details = $this->get_fb_account_details($pid, $facebook_auth['access_token']);
            $livestream_settings = $this->get_fb_ls_settings($pid);
            $details = ($user_details['success']) ? $user_details['user_details'] : null;
            $facebook = array('platform' => 'facebook_live', 'authorized' => $auth, 'user_details' => $details, 'stream_to' => $livestream_settings['stream_to'], 'settings' => $livestream_settings['settings']);
        } else {
            $facebook = array('platform' => 'facebook_live', 'authorized' => $auth, 'user_details' => null, 'stream_to' => null, 'settings' => null, 'redirect_url' => $this->facebook_client_api->getRedirectURL($pid, $ks));
        }
        return $facebook;
    }

    public function validate_facebook_token($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_profile')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $access_token = $res['user_access_token'];
            }
            $token_valid = $this->facebook_client_api->checkAuthToken($access_token);
            if ($token_valid['success']) {
                $success = array('success' => true, 'access_token' => $token_valid['access_token']);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_ls_settings($pid) {
        $success = array('success' => false);
        $account_details = $this->get_fb_user_name($pid);
        $pages = $this->get_fb_pages($pid);
        $settings = $this->get_fb_settings($pid);
        $stream_to_arr = array();
        array_push($stream_to_arr, array('type' => 1, 'id' => $account_details['id'], 'name' => $account_details['user_name']));
        foreach ($pages['pages'] as $page) {
            array_push($stream_to_arr, array('type' => 2, 'id' => $page['id'], 'name' => $page['name']));
        }
        $success = array('success' => true, 'stream_to' => $stream_to_arr, 'settings' => $settings['settings']);
        return $success;
    }

    public function get_fb_account_details($pid, $access_token) {
        $success = array('success' => false);
        $account_details = $this->get_fb_user_name($pid);
        $account_pic = $this->facebook_client_api->get_account_pic($access_token, $account_details['user_id']);
        if ($account_details['success'] && $account_pic['success']) {
            $user_details = array('user_name' => $account_details['user_name'], 'user_thumb' => $account_pic['user_pic']);
            $success = array('success' => true, 'user_details' => $user_details);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_user_name($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_profile')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $name = $res['name'];
                $user_id = $res['user_id'];
                $id = $res['id'];
            }
            $success = array('success' => true, 'user_name' => $name, 'user_id' => $user_id, 'id' => $id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_pages($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_pages')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $pages = array();
            foreach ($result as $res) {
                $id = $res['id'];
                $name = $res['name'];
                array_push($pages, array('id' => $id, 'name' => $name));
            }
            $success = array('success' => true, 'pages' => $pages);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_settings')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $settings = array();
            foreach ($result as $res) {
                $stream_to = $res['stream_to'];
                $asset_id = $res['asset_Id'];
                $privacy = $res['privacy'];
                $create_vod = $res['create_vod'];
                $cont_streaming = $res['cont_streaming'];
                array_push($settings, array('stream_to' => $stream_to, 'asset_id' => $asset_id, 'privacy' => $privacy, 'create_vod' => $create_vod, 'cont_streaming' => $cont_streaming));
            }
            $success = array('success' => true, 'settings' => $settings);
        } else {
            $success = array('success' => false);
        }
        return $success;
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
        $youtube_auth = $this->validate_youtube_token($pid);
        $auth = ($youtube_auth['success']) ? true : false;
        if ($auth) {
            $is_ls_enabled = $this->is_youtube_ls_enabled($pid, $youtube_auth['access_token']);
            $channel_details = $this->get_yt_account_details($pid, $youtube_auth['access_token']);
            $embed = $this->get_youtube_embed_status($pid);
            $ls_enabled = ($is_ls_enabled['success']) ? true : false;
            $details = ($channel_details['success']) ? $channel_details['channel_details'] : null;
            $embed_status = ($embed['success']) ? $embed['embed_status'] : false;
            $youtube = array('platform' => 'youtube_live', 'authorized' => $auth, 'ls_enabled' => $ls_enabled, 'embed_status' => $embed_status, 'channel_details' => $details);
        } else {
            $youtube = array('platform' => 'youtube_live', 'authorized' => $auth, 'ls_enabled' => false, 'embed_status' => false, 'channel_details' => null, 'redirect_url' => $this->google_client_api->getRedirectURL($pid, $ks));
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
                $token['access_token'] = $this->smcipher->decrypt($res['access_token']);
                $token['refresh_token'] = $this->smcipher->decrypt($res['refresh_token']);
                $token['token_type'] = $res['token_type'];
                $token['expires_in'] = $res['expires_in'];
                $token['created'] = $res['created'];
            }
            $tokens_valid = $this->google_client_api->checkAuthToken($token);
            if ($tokens_valid['success'] && ($tokens_valid['message'] == 'valid_access_token')) {
                $success = array('success' => true, 'access_token' => $tokens_valid['access_token']);
            }
            if ($tokens_valid['success'] && ($tokens_valid['message'] == 'new_access_token')) {
                $access_token = $this->update_youtube_tokens($pid, $tokens_valid['access_token']);
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

    public function is_youtube_ls_enabled($pid, $access_token) {
        $success = array('success' => false);
        $is_enabled = $this->google_client_api->is_ls_enabled($access_token);
        if ($is_enabled['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_yt_account_details($pid, $access_token) {
        $success = array('success' => false);
        $account_details = $this->google_client_api->get_account_details($access_token);
        if ($account_details['success']) {
            $channel_details = array('channel_title' => $account_details['channel_title'], 'channel_thumb' => $account_details['channel_thumb']);
            $success = array('success' => true, 'channel_details' => $channel_details);
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
                        $access_token = $this->smcipher->decrypt($res['access_token']);
                    }
                    $tokens_valid = $this->google_client_api->removeAuth($access_token);
                    if ($tokens_valid['success']) {
                        $remove_yt_live = $this->remove_youtube_live($pid);
                        if ($remove_yt_live['success']) {
                            $update_status = $this->update_sn_config($pid, 'youtube', 0);
                            if ($update_status['success']) {
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

    public function store_facebook_authorization($pid, $ks, $code) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $tokens = $this->facebook_client_api->getTokens($code);
                if ($this->check_facebook($valid['pid'])) {
                    $result = $this->update_facebook_tokens($valid['pid'], $tokens);
                } else {
                    $result = $this->insert_facebook_tokens($valid['pid'], $tokens);
                }
                if ($result['success']) {
                    $update_status = $this->update_sn_config($pid, 'facebook', 1);
                    if ($update_status['success']) {
                        $success = array('success' => true);
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

    public function remove_facebook_authorization($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $this->config->select('*')
                        ->from('facebook_user_profile')
                        ->where('partner_id', $valid['pid']);

                $query = $this->config->get();
                $result = $query->result_array();
                if ($query->num_rows() > 0) {
                    foreach ($result as $res) {
                        $access_token = $res['user_access_token'];
                    }
                }
                $user = $this->get_fb_user_name($pid);
                if ($user['success']) {
                    $remove = $this->facebook_client_api->removeAuth($access_token, $user['user_id']);
                    if ($remove['success']) {
                        $remove_profile = $this->remove_fb_profile($pid);
                        if ($remove_profile['success']) {
                            $remove_settings = $this->remove_fb_settings($pid);
                            if ($remove_settings['success']) {
                                $remove_pages = $this->remove_fb_pages($pid);
                                if ($remove_pages['success']) {
                                    $remove_livestream = $this->remove_fb_livestream($pid);
                                    if ($remove_livestream['success']) {
                                        $remove_live_entries = $this->remove_fb_live_entries($pid);
                                        if ($remove_live_entries['success']) {
                                            $update_status = $this->update_sn_config($pid, 'facebook', 0);
                                            if ($update_status['success']) {
                                                $success = array('success' => true);
                                            } else {
                                                $success = array('success' => false);
                                            }
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not remove facebook live entries');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not remove facebook user livestream');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not remove facebook user pages');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not remove facebook user settings');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not remove facebook user profile');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not remove authorization');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get user details');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function remove_fb_profile($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('facebook_user_profile');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function remove_fb_settings($pid) {
        $success = array('success' => false);
        if ($this->check_fb_settings($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('facebook_user_settings');
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function remove_fb_pages($pid) {
        $success = array('success' => false);
        if ($this->check_fb_pages($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('facebook_user_pages');
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function remove_fb_livestream($pid) {
        $success = array('success' => false);
        if ($this->check_fb_livestream($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('facebook_live_streams');
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function remove_fb_live_entries($pid) {
        $success = array('success' => false);
        $entries = $this->get_fb_live_entries($pid);
        if (count($entries['entries']) > 0) {
            foreach ($entries['entries'] as $entry) {
                $partnerData = $this->smportal->get_entry_partnerData($pid, $entry);
                if ($partnerData['success']) {
                    $platforms = $this->getPlatforms(json_decode($partnerData['partnerData']));
                    $update_sn_config = $this->set_facebook_config_false($platforms['platforms']);
                    $partnerData = $this->update_sn_partnerData($pid, $entry, $update_sn_config['sn_config']);
                    if ($partnerData['success']) {
                        $remove_fb_live_entry = $this->remove_fb_live_entry($pid, $entry);
                        if ($remove_fb_live_entry['success']) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not remove facebook live entry');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                    }
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function check_fb_pages($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_user_pages')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_fb_live_entries($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_live_entries')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $entries = array();
            foreach ($result as $res) {
                $id = $res['entryId'];
                array_push($entries, $id);
            }
            $success = array('success' => true, 'entries' => $entries);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_facebook_tokens($pid, $tokens) {
        $success = array('success' => false);
        $user_response = $this->update_facebook_profile($pid, $tokens['user']);
        if ($user_response['success']) {
            if (count($tokens['pages']) > 0) {
                $pages_response = $this->update_facebook_pages($pid, $tokens['pages']);
                if ($pages_response['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => true);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_facebook_tokens($pid, $tokens) {
        $success = array('success' => false);
        $user_response = $this->insert_facebook_profile($pid, $tokens['user']);
        if ($user_response['success']) {
            if (count($tokens['pages']) > 0) {
                $pages_response = $this->insert_facebook_pages($pid, $tokens['pages']);
                if ($pages_response['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => true);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_facebook_profile($pid, $user) {
        $success = array('success' => false);
        $data = array(
            'user_id' => $user['user_id'],
            'name' => $user['user_name'],
            'user_access_token' => $user['access_token'],
            'updated_at' => date("Y-m-d h:i:s")
        );
        $this->config->where('partner_id', $pid);
        $this->config->update('facebook_user_profile', $data);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_facebook_profile($pid, $user) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'user_id' => $user['user_id'],
            'name' => $user['user_name'],
            'user_access_token' => $user['access_token'],
            'created_at' => date("Y-m-d h:i:s")
        );
        $this->config->insert('facebook_user_profile', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_facebook_pages($pid, $pages) {
        $success = array('success' => false);
        $remove_pages = $this->remove_facebook_pages($pid);
        if ($remove_pages['success']) {
            foreach ($pages as $page) {
                $data = array(
                    'partner_id' => $pid,
                    'page_id' => $page['page_id'],
                    'name' => $page['page_name'],
                    'page_access_token' => $page['access_token'],
                    'created_at' => date("Y-m-d h:i:s")
                );
                $this->config->insert('facebook_user_pages', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function remove_facebook_pages($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('facebook_user_pages');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_facebook_pages($pid, $pages) {
        $success = array('success' => false);
        foreach ($pages as $page) {
            $data = array(
                'partner_id' => $pid,
                'page_id' => $page['page_id'],
                'name' => $page['page_name'],
                'page_access_token' => $page['access_token'],
                'created_at' => date("Y-m-d h:i:s")
            );
            $this->config->insert('facebook_user_pages', $data);
            $this->config->limit(1);
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            }
        }
        return $success;
    }

    public function create_fb_livestream($pid, $ks, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $access_token = $this->validate_facebook_token($valid['pid']);
                if ($access_token['success']) {
                    $get_asset = $this->get_asset($pid, $stream_to, $asset_id, $access_token['access_token']);
                    if ($get_asset['success']) {
                        $livestream = $this->facebook_client_api->createLiveStream($get_asset['asset'], $privacy, $create_vod, $cont_streaming);
                        if ($livestream['success']) {
                            $add_fb_livestream = $this->add_fb_livestream($pid, $livestream['address'], $livestream['stream_name'], $livestream['embed_code'], $livestream['live_id']);
                            if ($add_fb_livestream['success']) {
                                $add_fb_settings = $this->add_fb_settings($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming);
                                if ($add_fb_settings['success']) {
                                    $success = array('success' => true);
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not add Facebook settings');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not add Facebook live stream');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Facebook: could not create live stream');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get asset Id');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Facebook: invalid access token');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function add_fb_settings($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming) {
        if ($this->check_fb_settings($pid)) {
            $result = $this->update_fb_settings($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming);
        } else {
            $result = $this->insert_fb_settings($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming);
        }
        if ($result['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function check_fb_settings($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_user_settings')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function update_fb_settings($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming) {
        $success = array('success' => false);
        $data = array(
            'stream_to' => $stream_to,
            'asset_id' => $asset_id,
            'privacy' => $privacy,
            'create_vod' => ($create_vod == 'true') ? true : false,
            'cont_streaming' => ($cont_streaming == 'true') ? true : false,
            'updated_at' => date("Y-m-d h:i:s")
        );
        $this->config->where('partner_id', $pid);
        $this->config->update('facebook_user_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function insert_fb_settings($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'stream_to' => $stream_to,
            'asset_id' => $asset_id,
            'privacy' => $privacy,
            'create_vod' => ($create_vod == 'true') ? true : false,
            'cont_streaming' => ($cont_streaming == 'true') ? true : false,
            'created_at' => date("Y-m-d h:i:s")
        );

        $this->config->insert('facebook_user_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function add_fb_livestream($pid, $address, $stream_name, $embed_code, $live_id) {
        if ($this->check_fb_livestream($pid)) {
            $result = $this->update_fb_livestream($pid, $address, $stream_name, $embed_code, $live_id);
        } else {
            $result = $this->insert_fb_livestream($pid, $address, $stream_name, $embed_code, $live_id);
        }
        if ($result['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_fb_livestream($pid, $address, $stream_name, $embed_code, $live_id) {
        $success = array('success' => false);
        $data = array(
            'live_id' => $live_id,
            'streamName' => $stream_name,
            'ingestionAddress' => $this->finalize_address_url($address),
            'status' => 'ready',
            'embed_code' => $embed_code,
            'updated_at' => date("Y-m-d h:i:s")
        );
        $this->config->where('partner_id', $pid);
        $this->config->update('facebook_live_streams', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function insert_fb_livestream($pid, $address, $stream_name, $embed_code, $live_id) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'live_id' => $live_id,
            'streamName' => $stream_name,
            'ingestionAddress' => $this->finalize_address_url($address),
            'status' => 'ready',
            'embed_code' => $embed_code,
            'created_at' => date("Y-m-d h:i:s")
        );

        $this->config->insert('facebook_live_streams', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_fb_livestream($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_live_streams')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_asset($pid, $stream_to, $asset_id, $access_token) {
        $success = array('success' => false);
        if ($stream_to == 1) {
            $user_id = $this->get_fb_user_name($pid);
            $asset = array('asset_type' => 'user', 'asset_id' => $user_id['user_id'], 'access_token' => $access_token);
            $success = array('success' => true, 'asset' => $asset);
        } else {
            $page = $this->get_fb_page($pid, $asset_id);
            $asset = array('asset_type' => 'page', 'asset_id' => $page['page_id'], 'access_token' => $page['access_token']);
            $success = array('success' => true, 'asset' => $asset);
        }
        return $success;
    }

    public function get_fb_page($pid, $asset_id) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_pages')
                ->where('partner_id', $pid)
                ->where('id', $asset_id);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $page_id = $res['page_id'];
                $access_token = $res['page_access_token'];
            }
            $success = array('success' => true, 'page_id' => $page_id, 'access_token' => $access_token);
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
                    $update_status = $this->update_sn_config($pid, 'youtube', 1);
                    if ($update_status['success']) {
                        $success = array('success' => true);
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

    public function insert_youtube_tokens($pid, $tokens) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'access_token' => $this->smcipher->encrypt($tokens['access_token']),
            'refresh_token' => $this->smcipher->encrypt($tokens['refresh_token']),
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
            'access_token' => $this->smcipher->encrypt($tokens['access_token']),
            'refresh_token' => $this->smcipher->encrypt($tokens['refresh_token']),
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

    public function check_facebook($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_user_profile')
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
                $youtube_broadcast_id = null;
                $facebook_live_id = null;
                $youtube_embed = true;
                if ($snConfig['facebook'] || $snConfig['youtube']) {
                    if ($snConfig['youtube']) {
                        $access_token = $this->validate_youtube_token($valid['pid']);
                        if ($access_token['success']) {
                            $thumbnail = array('use_default' => true);
                            $livestream = $this->google_client_api->createLiveStream($access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
                            if ($livestream['success']) {
                                $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                                if ($insert_live_event['success']) {
                                    $update_embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
                                    if ($update_embed_status['success']) {
                                        $youtube_broadcast_id = $livestream['liveBroadcastId'];
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                                }
                            } else if (isset($livestream['retry'])) {
                                $youtube_embed = false;
                                $livestream = $this->google_client_api->createLiveStream($access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
                                if ($livestream['success']) {
                                    $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                                    if ($insert_live_event['success']) {
                                        $update_embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
                                        if ($update_embed_status['success']) {
                                            $youtube_broadcast_id = $livestream['liveBroadcastId'];
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'YouTube: could not create Live Event');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'YouTube: could not create Live Event');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                        }
                    }
                    if ($snConfig['facebook']) {
                        $access_token = $this->validate_facebook_token($valid['pid']);
                        if ($access_token['success']) {
                            $livestream_ids = $this->get_fb_livestream($pid);
                            $add_live_entry = $this->add_fb_live_entry($pid, $eid, $livestream_ids['id']);
                            if ($add_live_entry['success']) {
                                $facebook_live_id = $livestream_ids['live_id'];
                            } else {
                                $success = array('success' => false, 'message' => 'Could not insert Facebook Live Entry');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Facebook: invalid access token');
                        }
                    }
                    $update_sn_config = $this->insert_into_sn_config($youtube_broadcast_id, $facebook_live_id, $snConfig['sn_config']);
                    $partnerData = $this->update_sn_partnerData($pid, $eid, $update_sn_config['sn_config']);
                    if ($partnerData['success']) {
                        $platf = $this->getPlatforms(json_decode($partnerData['partnerData']));
                        $configSettings = $this->buildConfigSettings($platf);
                        $success = array('success' => true, 'configSettings' => $configSettings, 'youtube_embed_status' => $youtube_embed);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update entry partnerData');
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

    public function remove_fb_live_entry($pid, $eid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '" AND entryId = "' . $eid . '"');
        $this->config->delete('facebook_live_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function add_fb_live_entry($pid, $eid, $id) {
        $success = array('success' => false);
        if ($this->check_fb_live_entry($pid, $eid)) {
            $result = $this->update_fb_live_entry($pid, $eid, $id);
        } else {
            $result = $this->insert_fb_live_entry($pid, $eid, $id);
        }
        if ($result['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_fb_live_entry($pid, $eid, $live_id) {
        $success = array('success' => false);
        $data = array(
            'live_stream_id' => $live_id,
            'updated_at' => date("Y-m-d h:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->update('facebook_live_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function insert_fb_live_entry($pid, $eid, $live_id) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'live_stream_id' => $live_id,
            'created_at' => date("Y-m-d h:i:s")
        );
        $this->config->insert('facebook_live_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function check_fb_live_entry($pid, $eid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_live_entries')
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

    public function get_fb_livestream($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_live_streams')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $id = $res['id'];
                $live_id = $res['live_id'];
            }
            $success = array('success' => true, 'id' => $id, 'live_id' => $live_id);
        } else {
            $success = array('success' => false);
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
                if ($platform['platform'] == 'facebook_live') {
                    if ($platform['status']) {
                        array_push($platforms_preview_embed_arr, "facebook:1:" . $platform['liveId']);
                    } else {
                        array_push($platforms_preview_embed_arr, "facebook:0");
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

    public function insert_into_sn_config($youtube_broadcast_id, $facebook_live_id, $platforms_config) {
        $new_platforms_config = array();
        foreach ($platforms_config as $platform) {
            if ($platform['platform'] == 'facebook_live') {
                if ($platform['status'] && $facebook_live_id) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status'], 'liveId' => $facebook_live_id));
                } else if ($platform['status'] && $platform['liveId'] && !$facebook_live_id) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status'], 'liveId' => $platform['liveId']));
                } else if ($platform['status'] && !$platform['liveId'] && !$facebook_live_id) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => false));
                } else {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status']));
                }
            } else if ($platform['platform'] == 'youtube_live') {
                if ($platform['status'] && $youtube_broadcast_id) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status'], 'broadcastId' => $youtube_broadcast_id));
                } else if ($platform['status'] && $platform['broadcastId'] && !$youtube_broadcast_id) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status'], 'broadcastId' => $platform['broadcastId']));
                } else if ($platform['status'] && !$platform['broadcastId'] && !$youtube_broadcast_id) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => false));
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

    public function set_facebook_config_false($platforms_config) {
        $new_platforms_config = array();
        foreach ($platforms_config as $platform) {
            if ($platform['platform'] == 'facebook_live') {
                array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => false));
            } else if ($platform['platform'] == 'youtube_live') {
                if ($platform['status']) {
                    array_push($new_platforms_config, array('platform' => $platform['platform'], 'status' => $platform['status'], 'broadcastId' => $platform['broadcastId'], 'embed' => $platform['embed']));
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
            'liveBroadcastId' => $this->smcipher->encrypt($liveBroadcastId),
            'liveStreamId' => $this->smcipher->encrypt($liveStreamId),
            'streamName' => $this->smcipher->encrypt($streamName),
            'ingestionAddress' => $this->smcipher->encrypt($this->finalize_address_url($ingestionAddress)),
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
        $facebook = false;
        $youtube = false;
        $res = '240p';
        foreach ($platforms['platforms'] as $platform) {
            if ($platform['platform'] == 'smh') {
                array_push($platforms_config, array('platform' => 'smh', 'status' => $platform['status']));
            } else if ($platform['platform'] == 'facebook_live') {
                if ($platform['status']) {
                    $facebook = true;
                }
                array_push($platforms_config, array('platform' => 'facebook_live', 'status' => $platform['status']));
            } else if ($platform['platform'] == 'youtube_live') {
                if ($platform['status']) {
                    $youtube = true;
                    $res = $platform['config']['res'];
                }
                array_push($platforms_config, array('platform' => 'youtube_live', 'status' => $platform['status']));
            }
        }
        $success = array('success' => true, 'sn_config' => $platforms_config, 'youtube' => $youtube, 'youtube_res' => $res, 'facebook' => $facebook);
        return $success;
    }

    public function update_sn_livestreams($pid, $ks, $name, $desc, $eid, $platforms, $projection) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $snConfig = $this->buildSnConfig($platforms);
                $youtube_success = array();
                $facebook_success = array();
                $youtube_broadcast_id = null;
                $youtube_embed = true;
                $facebook_live_id = null;

                $update_youtube_live_stream = $this->update_youtube_live_stream($pid, $ks, $name, $desc, $eid, $snConfig, $projection);
                if ($update_youtube_live_stream['success']) {
                    $youtube_success['youtube_success'] = true;
                    $youtube_broadcast_id = $update_youtube_live_stream['broadcast_id'];
                    $youtube_embed = $update_youtube_live_stream['youtube_embed'];
                } else {
                    $youtube_success['youtube_success'] = false;
                    $youtube_success['youtube_message'] = $update_youtube_live_stream['message'];
                }

                $update_facebook_live_stream = $this->update_facebook_live_stream($pid, $eid, $snConfig);
                if ($update_facebook_live_stream['success']) {
                    $facebook_success['facebook_success'] = true;
                    $facebook_live_id = $update_facebook_live_stream['live_id'];
                } else {
                    $facebook_success['facebook_success'] = false;
                    $facebook_success['facebook_message'] = $update_facebook_live_stream['message'];
                }

                $update_sn_config = $this->insert_into_sn_config($youtube_broadcast_id, $facebook_live_id, $snConfig['sn_config']);
                $partnerData = $this->update_sn_partnerData($pid, $eid, $update_sn_config['sn_config']);
                if ($partnerData['success']) {
                    if ($youtube_success['youtube_success'] && $facebook_success['facebook_success']) {
                        $success = array('success' => true, 'youtube_embed_status' => $youtube_embed);
                    } else if (!$youtube_success['youtube_success'] && $facebook_success['facebook_success']) {
                        $success = array('success' => false, 'youtube_embed_status' => false, 'message' => $youtube_success['youtube_message']);
                    } else if ($youtube_success['youtube_success'] && !$facebook_success['facebook_success']) {
                        $success = array('success' => false, 'youtube_embed_status' => $youtube_embed, 'message' => $facebook_success['facebook_message']);
                    } else if (!$youtube_success['youtube_success'] && !$facebook_success['facebook_success']) {
                        $success = array('success' => false, 'youtube_embed_status' => false, 'message' => $youtube_success['youtube_message'] . ' & ' . $facebook_success['facebook_message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function update_facebook_live_stream($pid, $eid, $snConfig) {
        $success = array('success' => false);
        if ($snConfig['facebook']) {
            $access_token = $this->validate_facebook_token($pid);
            if ($access_token['success']) {
                $livestream_ids = $this->get_fb_livestream($pid);
                $add_live_entry = $this->add_fb_live_entry($pid, $eid, $livestream_ids['id']);
                if ($add_live_entry['success']) {
                    $success = array('success' => true, 'live_id' => $livestream_ids['live_id']);
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert Facebook Live Entry');
                }
            } else {
                $success = array('success' => false, 'message' => 'Facebook: invalid access token');
            }
        } else if (!$snConfig['facebook']) {
            $remove_live_entry = $this->remove_fb_live_entry($pid, $eid);
            if ($remove_live_entry['success']) {
                $success = array('success' => true, 'live_id' => null);
            } else {
                $success = array('success' => false, 'message' => 'Could not insert Facebook Live Entry');
            }
        }
        return $success;
    }

    public function update_youtube_live_stream($pid, $ks, $name, $desc, $eid, $snConfig, $projection) {
        $success = array('success' => false);
        $youtube_embed = true;
        if ($snConfig['youtube']) {
            $access_token = $this->validate_youtube_token($pid);
            if ($access_token['success']) {
                $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
                if ($youtube_ids['success']) {
                    $livestream = $this->google_client_api->updateLiveStream($access_token['access_token'], $snConfig['youtube_res'], $name, $eid, $youtube_ids['bid'], $youtube_ids['lid']);
                    if ($livestream['success']) {
                        $update_live_event = $this->update_youtube_live_event($pid, $eid, $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                        if ($update_live_event['success']) {
                            $update_embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
                            if ($update_embed_status['success']) {
                                $success = array('success' => true, 'broadcast_id' => $youtube_ids['bid'], 'youtube_embed' => $youtube_embed);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'YouTube: could not update Live Event');
                    }
                } else {
                    $thumbnail = $this->smportal->get_default_thumb($pid, $eid, $ks);
                    $livestream = $this->google_client_api->createLiveStream($access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
                    if ($livestream['success']) {
                        $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                        if ($insert_live_event['success']) {
                            $update_embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
                            if ($update_embed_status['success']) {
                                $success = array('success' => true, 'broadcast_id' => $livestream['liveBroadcastId'], 'youtube_embed' => $youtube_embed);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                        }
                    } else if (isset($livestream['retry'])) {
                        $youtube_embed = false;
                        $livestream = $this->google_client_api->createLiveStream($access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
                        if ($livestream['success']) {
                            $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                            if ($insert_live_event['success']) {
                                $update_embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
                                if ($update_embed_status['success']) {
                                    $success = array('success' => true, 'broadcast_id' => $livestream['liveBroadcastId'], 'youtube_embed' => $youtube_embed);
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'YouTube: could not create Live Event');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'YouTube: could not create Live Event');
                    }
                }
            } else {
                $success = array('success' => false, 'message' => 'YouTube: invalid access token');
            }
        } else if (!$snConfig['youtube']) {
            $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
            if ($youtube_ids['success']) {
                $access_token = $this->validate_youtube_token($valid['pid']);
                if ($access_token['success']) {
                    $removeLiveStream = $this->google_client_api->removeLiveStream($access_token['access_token'], $youtube_ids['bid'], $youtube_ids['lid']);
                    if ($removeLiveStream['success']) {
                        $removeLiveEvent = $this->removeLiveEvent($pid, $eid);
                        if ($removeLiveEvent['success']) {
                            $success = array('success' => true, 'broadcast_id' => null, 'youtube_embed' => $youtube_embed);
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
                $success = array('success' => true, 'broadcast_id' => null, 'youtube_embed' => $youtube_embed);
            }
        }
        return $success;
    }

    public function update_youtube_live_event($pid, $eid, $lid, $streamName, $ingestionAddress) {
        $success = array('success' => false);
        $data = array(
            'liveStreamId' => $this->smcipher->encrypt($lid),
            'streamName' => $this->smcipher->encrypt($streamName),
            'ingestionAddress' => $this->smcipher->encrypt($this->finalize_address_url($ingestionAddress))
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
            'liveBroadcastId' => $this->smcipher->encrypt($bid)
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
                $bid = $this->smcipher->decrypt($res['liveBroadcastId']);
                $lid = $this->smcipher->decrypt($res['liveStreamId']);
                $proj = $res['projection'];
            }
            $success = array('success' => true, 'bid' => $bid, 'lid' => $lid, 'proj' => $proj);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_youtube_embed_status($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('youtube_live')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $embed_status = ($res['embed']) ? true : false;
            }
            $success = array('success' => true, 'embed_status' => $embed_status);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_youtube_embed_status($pid, $embed_status) {
        $success = array('success' => false);
        $data = array(
            'embed' => $embed_status
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_live', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true, 'embed_status' => $embed_status);
        } else {
            $success = array('success' => true, 'embed_status' => $embed_status);
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
                $platforms_status = $this->get_entry_platforms_status($pid, $eid);
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

    public function get_entry_platforms_status($pid, $eid) {
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
                    if ($platform['platform'] == 'facebook_live') {
                        $platforms_status['facebook'] = $platform['status'];
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
                $platforms_status = $this->get_entry_platforms_status($pid, $eid);
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
                $platforms_status = $this->get_entry_platforms_status($pid, $eid);
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
                        }
                        if ($platforms_status['platforms_status']['facebook']) {
                            $remove_live_entry = $this->remove_fb_live_entry($pid, $eid);
                            if ($remove_live_entry['success']) {
                                $success = array('success' => true);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not insert Facebook Live Entry');
                            }
                        }
                        if (!$platforms_status['platforms_status']['youtube'] && !$platforms_status['platforms_status']['facebook']) {
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
                    if ($platforms->platform == "facebook_live") {
                        if ($platforms->status) {
                            $platform = array('platform' => 'facebook_live', 'status' => $platforms->status, 'liveId' => $platforms->liveId);
                            array_push($result['platforms'], $platform);
                        } else {
                            $platform = array('platform' => 'facebook_live', 'status' => $platforms->status);
                            array_push($result['platforms'], $platform);
                        }
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
                $multiBitrate_status = array();
                $multiBitrate = $this->smportal->get_highest_bitrate($pid, $ks, $eid);
                array_push($multiBitrate_status, $multiBitrate['multiBitrate']);
                $youtube_status = $this->get_youtube_status($pid);
                $facebook_status = $this->get_facebook_status($pid);
                if ($youtube_status['status'] || $facebook_status['status']) {
                    $platforms_status = $this->get_entry_platforms_status($pid, $eid);
                    if ($platforms_status['success']) {
                        if (count($platforms_status['platforms_status'])) {
                            array_push($platforms, array('platform' => 'edgecast', 'status' => $platforms_status['platforms_status']['smh']));
                            if ($facebook_status['status']) {
                                if ($platforms_status['platforms_status']['facebook']) {
                                    $ingestionSettings = $this->get_facebook_ingestion_settings($pid);
                                    if ($ingestionSettings['success']) {
                                        $update_facebook_ls_status = $this->update_facebook_ls_status($pid, 'live');
                                        if ($update_facebook_ls_status['success']) {
                                            array_push($platforms, array('platform' => 'facebook', 'status' => $platforms_status['platforms_status']['facebook'], 'ingestionSettings' => $ingestionSettings['ingestionSettings']));
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not update facebook live stream status');
                                        }
                                    } else {
                                        array_push($platforms, array('platform' => 'facebook', 'status' => $platforms_status['platforms_status']['facebook']));
                                    }
                                } else {
                                    array_push($platforms, array('platform' => 'facebook', 'status' => $platforms_status['platforms_status']['facebook']));
                                }
                            } else {
                                array_push($platforms, array('platform' => 'facebook', 'status' => false));
                            }

                            if ($youtube_status['status']) {
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
                            } else {
                                array_push($platforms, array('platform' => 'youtube', 'status' => false));
                            }

                            $success = array('success' => true, 'platforms' => $platforms, 'multiBitrate' => $multiBitrate_status);
                        } else {
                            array_push($platforms, array('platform' => 'edgecast', 'status' => true));
                            array_push($platforms, array('platform' => 'facebook', 'status' => false));
                            array_push($platforms, array('platform' => 'youtube', 'status' => false));
                            $success = array('success' => true, 'platforms' => $platforms, 'multiBitrate' => $multiBitrate_status);
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get platforms status');
                    }
                } else {
                    array_push($platforms, array('platform' => 'edgecast', 'status' => true));
                    array_push($platforms, array('platform' => 'facebook', 'status' => false));
                    array_push($platforms, array('platform' => 'youtube', 'status' => false));
                    $success = array('success' => true, 'platforms' => $platforms, 'multiBitrate' => $multiBitrate_status);
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

    public function get_facebook_ingestion_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_live_streams')
                ->where('partner_id', $pid);

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
                $streamName = $this->smcipher->decrypt($res['streamName']);
                $ingestionAddress = $this->smcipher->decrypt($res['ingestionAddress']);
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

    public function update_facebook_ls_status($pid, $status) {
        $success = array('success' => false);
        $data = array(
            'status' => $status,
            'updated_at' => date("Y-m-d h:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('facebook_live_streams', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
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

    public function is_youtube_live($pid, $eid) {
        $success = false;
        $this->config->select('*')
                ->from('youtube_live_entries')
                ->where('partner_id', $pid)
                ->where('entryId', $eid)
                ->where('status', 'live');
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function is_facebook_live($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_live_streams')
                ->where('partner_id', $pid)
                ->where('status', 'live');
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function sn_livestreams_complete($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $is_youtube_live = $this->is_youtube_live($pid, $eid);
            $is_facebook_live = $this->is_facebook_live($pid);
            if ($is_youtube_live) {
                $complete_youtube = $this->youtube_entry_complete($pid, $ks, $eid);
                if ($complete_youtube['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => $complete_youtube['message']);
                }
            }
            if ($is_facebook_live) {
                $get_user_settings = $this->get_facebook_user_settings($pid);
                if ($get_user_settings['success']) {
                    $create_new_livestream = $this->create_new_fb_livestream($pid, $get_user_settings['userSettings'][0]['stream_to'], $get_user_settings['userSettings'][0]['asset_id'], $get_user_settings['userSettings'][0]['privacy'], $get_user_settings['userSettings'][0]['create_vod'], $get_user_settings['userSettings'][0]['cont_streaming']);
                    if ($create_new_livestream['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => $create_new_livestream['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get Facebook user settings');
                }
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function get_facebook_user_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_settings')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $userSettings = array();
            foreach ($result as $res) {
                $stream_to = $res['stream_to'];
                $asset_id = $res['asset_Id'];
                $privacy = $res['privacy'];
                $create_vod = ($res['create_vod']) ? 'true' : 'false';
                $cont_streaming = ($res['cont_streaming']) ? 'true' : 'false';
            }
            array_push($userSettings, array('asset_id' => $asset_id, 'stream_to' => $stream_to, 'privacy' => $privacy, 'create_vod' => $create_vod, 'cont_streaming' => $cont_streaming));
            $success = array('success' => true, 'userSettings' => $userSettings);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function create_new_fb_livestream($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming) {
        $success = array('success' => false);
        $access_token = $this->validate_facebook_token($pid);
        if ($access_token['success']) {
            $get_asset = $this->get_asset($pid, $stream_to, $asset_id, $access_token['access_token']);
            if ($get_asset['success']) {
                $livestream = $this->facebook_client_api->createLiveStream($get_asset['asset'], $privacy, $create_vod, $cont_streaming);
                if ($livestream['success']) {
                    $add_fb_livestream = $this->add_fb_livestream($pid, $livestream['address'], $livestream['stream_name'], $livestream['embed_code'], $livestream['live_id']);
                    if ($add_fb_livestream['success']) {
                        $add_fb_settings = $this->add_fb_settings($pid, $stream_to, $asset_id, $privacy, $create_vod, $cont_streaming);
                        if ($add_fb_settings['success']) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not add Facebook settings');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not add Facebook live stream');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Facebook: could not create live stream');
                }
            } else {
                $success = array('success' => false, 'message' => 'Could not get asset Id');
            }
        } else {
            $success = array('success' => false, 'message' => 'Facebook: invalid access token');
        }

        return $success;
    }

    public function youtube_entry_complete($pid, $ks, $eid) {
        $success = array('success' => false);
        $event_ids = $this->get_youtube_event_ids($pid, $eid);
        if ($event_ids['success']) {
            $access_token = $this->validate_youtube_token($pid);
            if ($access_token['success']) {
                $transitionLiveStream = $this->google_client_api->transitionLiveStream($access_token['access_token'], $event_ids['bid'], 'complete');
                if ($transitionLiveStream['success']) {
                    $updateLiveStreamStatus = $this->update_youtube_entry_status($pid, $eid, 'complete');
                    if ($updateLiveStreamStatus['success']) {
                        $youtube_embed = true;
                        $thumbnail = $this->smportal->get_default_thumb($pid, $eid, $ks);
                        $entry_details = $this->smportal->get_entry_details($pid, $eid);
                        $createNewBroadCast = $this->google_client_api->createNewBroadcast($access_token['access_token'], $event_ids['bid'], $event_ids['lid'], $entry_details['name'], $entry_details['desc'], $thumbnail, $event_ids['proj'], $youtube_embed);
                        if ($createNewBroadCast['success']) {
                            $updateBid = $this->update_youtube_live_event_bid($pid, $eid, $createNewBroadCast['liveBroadcastId']);
                            if ($updateBid['success']) {
                                $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
                                if ($partnerData['success']) {
                                    $platforms = $this->getPlatforms(json_decode($partnerData['partnerData']));
                                    $updated_config = $this->insert_into_sn_config($createNewBroadCast['liveBroadcastId'], null, $platforms['platforms']);
                                    $partnerData = $this->update_sn_partnerData($pid, $eid, $updated_config['sn_config']);
                                    if ($partnerData['success']) {
                                        $update_embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
                                        if ($update_embed_status['success']) {
                                            $success = array('success' => true);
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not update entry partnerData');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not get partnerData');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not update liveBroadcastId');
                            }
                        } else if (isset($createNewBroadCast['retry'])) {
                            $youtube_embed = false;
                            $createNewBroadCast = $this->google_client_api->createNewBroadcast($access_token['access_token'], $event_ids['bid'], $event_ids['lid'], $entry_details['name'], $entry_details['desc'], $thumbnail, $event_ids['proj'], $youtube_embed);
                            if ($createNewBroadCast['success']) {
                                $updateBid = $this->update_youtube_live_event_bid($pid, $eid, $createNewBroadCast['liveBroadcastId']);
                                if ($updateBid['success']) {
                                    $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
                                    if ($partnerData['success']) {
                                        $platforms = $this->getPlatforms(json_decode($partnerData['partnerData']));
                                        $updated_config = $this->insert_into_sn_config($createNewBroadCast['liveBroadcastId'], null, $platforms['platforms']);
                                        $partnerData = $this->update_sn_partnerData($pid, $eid, $updated_config['sn_config']);
                                        if ($partnerData['success']) {
                                            $update_embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
                                            if ($update_embed_status['success']) {
                                                $success = array('success' => true);
                                            } else {
                                                $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                                            }
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

        return $success;
    }

    public function finalize_address_url($url) {
        $parse_url = parse_url($url);
        $scheme = $parse_url['scheme'];
        $host = $parse_url['host'];
        $port = ($parse_url['port']) ? $parse_url['port'] : 1935;
        $path = rtrim($parse_url['path'], '/');
        $final_url = $scheme . '://' . $host . ':' . $port . $path;
        return $final_url;
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
