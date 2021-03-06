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
        $this->load->library('twitch_client_api');
        $this->load->library('weibo_client_api');
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
                'youtube_live' => 0,
                'twitch' => 0,
                'weibo' => 0
            );
        } else if ($platform == 'youtube') {
            $data = array(
                'partner_id' => $pid,
                'facebook_live' => 0,
                'youtube_live' => $status,
                'twitch' => 0,
                'weibo' => 0
            );
        } else if ($platform == 'twitch') {
            $data = array(
                'partner_id' => $pid,
                'facebook_live' => 0,
                'youtube_live' => 0,
                'twitch' => $status,
                'weibo' => 0
            );
        } else if ($platform == 'weibo') {
            $data = array(
                'partner_id' => $pid,
                'facebook_live' => 0,
                'youtube_live' => 0,
                'twitch' => 0,
                'weibo' => $status
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
        } else if ($platform == 'twitch') {
            $data = array(
                'twitch' => $status,
            );
        } else if ($platform == 'weibo') {
            $data = array(
                'weibo' => $status,
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

    public function get_sn_config($pid, $ks, $projection) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms = array();
                $facebook = $this->facebook_platform($valid['pid'], $ks);
                $youtube = $this->youtube_platform($valid['pid'], $ks, $projection);
                $twitter = $this->twitter_platform($valid['pid'], $ks);
                $twitch = $this->twitch_platform($valid['pid'], $ks);
                $weibo = $this->weibo_platform($valid['pid'], $ks, $projection);
                array_push($platforms, $facebook, $youtube, $twitter, $twitch, $weibo);
                $success = array('success' => true, 'platforms' => $platforms);
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function weibo_platform($pid, $ks, $projection) {
        $weibo_auth = $this->validate_weibo_token($pid);
        $auth = ($weibo_auth['success']) ? true : false;
        if ($auth) {
            $this->weibo_client_api->createLiveStream($pid, $weibo_auth['access_token'], 'test', 400, 333, NULL, 1, NULL, 0, 0);
            $user_details = $this->get_weibo_account_details($pid);
            $details = ($user_details['success']) ? $user_details['user_details'] : null;
            $weibo = array('platform' => 'weibo', 'authorized' => $auth, 'user_details' => $details);
        } else {
            $weibo = array('platform' => 'weibo', 'authorized' => false, 'user_details' => null, 'redirect_url' => $this->weibo_client_api->getRedirectURL($pid, $ks, $projection));
        }
        return $weibo;
    }

    public function twitter_platform($pid, $ks) {
        $twitter = array('platform' => 'twitter', 'authorized' => false, 'channel_details' => null, 'settings' => null, 'redirect_url' => null);
        return $twitter;
    }

    public function twitch_platform($pid, $ks) {
        $twitch_auth = $this->validate_twitch_token($pid);
        $auth = ($twitch_auth['success']) ? true : false;
        if ($auth) {
            $settings = $this->get_twch_settings($pid);
            $channel_details = $this->get_twch_channel_details($pid);
            $details = ($channel_details['success']) ? $channel_details['channel_details'] : null;
            $twitch = array('platform' => 'twitch', 'authorized' => $auth, 'channel_details' => $details, 'settings' => $settings['settings']);
        } else {
            $twitch = array('platform' => 'twitch', 'authorized' => $auth, 'channel_details' => null, 'settings' => null, 'redirect_url' => $this->twitch_client_api->getRedirectURL($pid, $ks));
        }
        return $twitch;
    }

    public function get_weibo_account_details($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('weibo_user_profile')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $name = $res['name'];
                $thumbnail = $res['thumbnail'];
            }
            $user_details = array('user_name' => $name, 'user_thumb' => $thumbnail);
            $success = array('success' => true, 'user_details' => $user_details);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function validate_weibo_token($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('weibo_user_profile')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $access_token = $this->smcipher->decrypt($res['access_token']);
            }
            $token_valid = $this->weibo_client_api->checkAuthToken($pid, $access_token);
            if ($token_valid['success']) {
                $success = array('success' => true, 'access_token' => $token_valid['access_token']);
            } else {
                $weibo_invalidation_removal = $this->weibo_invalidation_removal($pid);
                if ($weibo_invalidation_removal['success']) {
                    $success = array('success' => false);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function weibo_invalidation_removal($pid) {
        $success = array('success' => false);
        $remove_settings = $this->remove_weibo_settings($pid);
        if ($remove_settings['success']) {
            $update_status = $this->update_sn_config($pid, 'weibo', 0);
            if ($update_status['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not remove weibo user settings');
        }
        return $success;
    }

    public function remove_weibo_settings($pid) {
        $success = array('success' => false);
        if ($this->check_weibo_settings($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('weibo_profile_settings');
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

    public function check_weibo_settings($pid) {
        $success = false;
        $this->config->select('*')
                ->from('weibo_profile_settings')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_twch_channel_details($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('twitch_channel')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $id = $this->smcipher->decrypt($res['channel_id']);
                $name = $res['name'];
                $logo = $res['logo'];
            }
            $user_details = array('channel_id' => $id, 'channel_name' => $name, 'channel_logo' => $logo);
            $success = array('success' => true, 'channel_details' => $user_details);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function validate_twitch_token($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('twitch_channel')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $token = array();
            foreach ($result as $res) {
                $token['access_token'] = $this->smcipher->decrypt($res['access_token']);
                $token['refresh_token'] = $this->smcipher->decrypt($res['refresh_token']);
            }
            $tokens_valid = $this->twitch_client_api->checkAuthToken($pid, $token);
            if ($tokens_valid['success'] && ($tokens_valid['message'] == 'valid_access_token')) {
                $success = array('success' => true, 'access_token' => $tokens_valid['access_token']);
            }
            if ($tokens_valid['success'] && ($tokens_valid['message'] == 'new_access_token')) {
                $access_token = $this->update_twitch_tokens($pid, $tokens_valid);
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

    public function facebook_platform($pid, $ks) {
        $facebook_auth = $this->validate_facebook_token($pid);
        $auth = ($facebook_auth['success']) ? true : false;
        if ($auth) {
            $user_details = $this->get_fb_account_details($pid);
            $livestream_settings = $this->get_fb_ls_settings($pid);
            $details = ($user_details['success']) ? $user_details['user_details'] : null;
            $facebook = array('platform' => 'facebook_live', 'authorized' => $auth, 'user_details' => $details, 'publish_to' => $livestream_settings['publish_to'], 'settings' => $livestream_settings['settings']);
        } else {
            $facebook = array('platform' => 'facebook_live', 'authorized' => $auth, 'user_details' => null, 'publish_to' => null, 'settings' => null, 'redirect_url' => $this->facebook_client_api->getRedirectURL($pid, $ks));
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
                $access_token = $this->smcipher->decrypt($res['user_access_token']);
            }
            $token_valid = $this->facebook_client_api->checkAuthToken($pid, $access_token);
            if ($token_valid['success']) {
                $success = array('success' => true, 'access_token' => $token_valid['access_token']);
            } else {
                $facebook_invalidation_removal = $this->facebook_invalidation_removal($pid);
                if ($facebook_invalidation_removal['success']) {
                    $success = array('success' => false);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function facebook_invalidation_removal($pid) {
        $success = array('success' => false);
        $remove_settings = $this->remove_fb_publish_settings($pid);
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
        return $success;
    }

    public function get_fb_ls_settings($pid) {
        $success = array('success' => false);
        $account_details = $this->get_fb_user_name($pid);
        $pages = $this->get_fb_pages($pid);
        $groups = $this->get_fb_groups($pid);
        $events = $this->get_fb_events($pid);
        $settings = $this->get_fb_publish_settings($pid);
        $publish_to_arr = array();
        array_push($publish_to_arr, array('type' => 1, 'id' => $account_details['id'], 'name' => $account_details['user_name']));
        foreach ($pages['pages'] as $page) {
            array_push($publish_to_arr, array('type' => 2, 'id' => $page['id'], 'name' => $page['name']));
        }
        foreach ($groups['groups'] as $group) {
            array_push($publish_to_arr, array('type' => 3, 'id' => $group['id'], 'name' => $group['name']));
        }
        foreach ($events['events'] as $event) {
            array_push($publish_to_arr, array('type' => 4, 'id' => $event['id'], 'name' => $event['name']));
        }
        $success = array('success' => true, 'publish_to' => $publish_to_arr, 'settings' => $settings['settings']);
        return $success;
    }

    public function get_fb_account_details($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_profile')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $name = $res['name'];
                $thumbnail = $res['thumbnail'];
            }
            $user_details = array('user_name' => $name, 'user_thumb' => $thumbnail);
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
                $user_id = $this->smcipher->decrypt($res['user_id']);
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

    public function get_fb_page_asset_id($pid, $page_id) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_pages')
                ->where('page_id', $page_id)
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $id = $res['id'];
            }
            $success = array('success' => true, 'id' => $id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_groups($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_groups')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $groups = array();
            foreach ($result as $res) {
                $id = $res['id'];
                $name = $res['name'];
                array_push($groups, array('id' => $id, 'name' => $name));
            }
            $success = array('success' => true, 'groups' => $groups);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_group_asset_id($pid, $group_id) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_groups')
                ->where('group_id', $group_id)
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $id = $res['id'];
            }
            $success = array('success' => true, 'id' => $id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_events($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_events')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $events = array();
            foreach ($result as $res) {
                $id = $res['id'];
                $name = $res['name'];
                array_push($events, array('id' => $id, 'name' => $name));
            }
            $success = array('success' => true, 'events' => $events);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_event_asset_id($pid, $event_id) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_events')
                ->where('event_id', $event_id)
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $id = $res['id'];
            }
            $success = array('success' => true, 'id' => $id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_publish_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_publish_settings')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $settings = array();
            foreach ($result as $res) {
                $publish_to = $res['publish_to'];
                $asset_id = $res['asset_Id'];
                $privacy = $res['privacy'];
                $create_vod = $res['create_vod'];
                $cont_streaming = $res['cont_streaming'];
                $auto_upload = $res['auto_upload'];
                array_push($settings, array('publish_to' => $publish_to, 'asset_id' => $asset_id, 'privacy' => $privacy, 'create_vod' => $create_vod, 'cont_streaming' => $cont_streaming, 'auto_upload' => $auto_upload));
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

    public function get_twitch_status($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('platform_status')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $status = $res['twitch'];
            }
            $success = array('success' => true, 'status' => $status);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function youtube_platform($pid, $ks, $projection) {
        $youtube_auth = $this->validate_youtube_token($pid);
        $auth = ($youtube_auth['success']) ? true : false;
        if ($auth) {
            $channel_details = $this->get_youtube_channel_details($pid);
            $settings = $this->get_yt_settings($pid);
            $details = ($channel_details['success']) ? $channel_details['channel_details'] : null;
            $embed_status = ($settings['success']) ? $settings['embed_status'] : false;
            $auto_upload = ($settings['success']) ? $settings['auto_upload'] : false;
            $is_verified = $this->get_youtube_verification_status($pid, $youtube_auth['access_token'], $channel_details['channel_details']['is_verified']);
            $ls_enabled = $this->get_youtube_ls_enabled_status($pid, $youtube_auth['access_token'], $channel_details['channel_details']['ls_enabled']);
            $youtube = array('platform' => 'youtube_live', 'authorized' => $auth, 'is_verified' => $is_verified, 'ls_enabled' => $ls_enabled, 'embed_status' => $embed_status, 'auto_upload' => $auto_upload, 'channel_details' => $details);
        } else {
            $youtube = array('platform' => 'youtube_live', 'authorized' => $auth, 'is_verified' => false, 'ls_enabled' => false, 'embed_status' => false, 'auto_upload' => null, 'channel_details' => null, 'redirect_url' => $this->google_client_api->getRedirectURL($pid, $ks, $projection));
        }
        return $youtube;
    }

    public function is_youtube_ls_enabled($pid, $access_token) {
        $success = array('success' => false);
        $is_enabled = $this->google_client_api->is_ls_enabled($pid, $access_token);
        if ($is_enabled['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_youtube_ls_enabled_status($pid, $access_token, $current_status) {
        if ($current_status) {
            $ls_enabled = true;
        } else {
            $update_youtube_ls_enabled = $this->update_youtube_ls_enabed($pid, $access_token);
            if ($update_youtube_ls_enabled['success']) {
                if ($update_youtube_ls_enabled['ls_enabled']) {
                    $ls_enabled = true;
                } else {
                    $ls_enabled = false;
                }
            } else {
                $ls_enabled = false;
            }
        }
        return $ls_enabled;
    }

    public function get_youtube_verification_status($pid, $access_token, $current_status) {
        if ($current_status == 'allowed') {
            $is_verified = true;
        } else {
            $update_youtube_verification = $this->update_youtube_verification($pid, $access_token);
            if ($update_youtube_verification['success']) {
                if ($update_youtube_verification['is_verified'] == 'allowed') {
                    $is_verified = true;
                } else {
                    $is_verified = false;
                }
            } else {
                $is_verified = false;
            }
        }
        return $is_verified;
    }

    public function get_yt_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('youtube_channel_settings')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $embed_status = ($res['embed']) ? true : false;
                $auto_upload = ($res['auto_upload']) ? true : false;
                $projection = $res['projection'];
            }
            $success = array('success' => true, 'embed_status' => $embed_status, 'auto_upload' => $auto_upload, 'projection' => $projection);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_twch_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('twitch_channel_settings')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $auto_upload = ($res['auto_upload']) ? true : false;
            }
            $settings = array('auto_upload' => $auto_upload);
            $success = array('success' => true, 'settings' => $settings);
        } else {
            $success = array('success' => false);
        }
        return $success;
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
                ->from('youtube_channel')
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
            $tokens_valid = $this->google_client_api->checkAuthToken($pid, $token);
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

    public function update_youtube_ls_enabed($pid, $access_token) {
        $success = array('success' => false);
        $is_youtube_ls_enabled = $this->is_youtube_ls_enabled($pid, $access_token);
        if ($is_youtube_ls_enabled['success']) {
            $update_youtube_channel_ls_enabled = $this->update_youtube_channel_ls_enabled($pid, true);
            if ($update_youtube_channel_ls_enabled['success']) {
                $success = array('success' => true, 'ls_enabled' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $update_youtube_channel_ls_enabled = $this->update_youtube_channel_ls_enabled($pid, false);
            if ($update_youtube_channel_ls_enabled['success']) {
                $success = array('success' => true, 'ls_enabled' => false);
            } else {
                $success = array('success' => false);
            }
        }
        return $success;
    }

    public function update_youtube_channel_ls_enabled($pid, $ls_enabled) {
        $success = array('success' => false);
        $data = array(
            'ls_enabled' => $ls_enabled
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_channel', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function update_youtube_verification($pid, $access_token) {
        $success = array('success' => false);
        $verification = $this->google_client_api->get_verification($pid, $access_token);
        if ($verification['success']) {
            $update_youtube_channel_verification = $this->update_youtube_channel_verification($pid, $verification['is_verified']);
            if ($update_youtube_channel_verification['success']) {
                $success = array('success' => true, 'is_verified' => $verification['is_verified']);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_youtube_channel_verification($pid, $is_verified) {
        $success = array('success' => false);
        $data = array(
            'is_verified' => $is_verified
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_channel', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function retrieve_weibo_profile_details($pid, $access_token) {
        $success = array('success' => false);
        $profile_details = $this->weibo_client_api->get_account_details($pid, $access_token);
        if ($profile_details['success']) {
            $user_details = array('name' => $profile_details['name'], 'user_thumb' => $profile_details['user_thumb'], 'user_id' => $profile_details['user_id']);
            $success = array('success' => true, 'profile_details' => $user_details);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function retrieve_youtube_channel_details($pid, $access_token) {
        $success = array('success' => false);
        $account_details = $this->google_client_api->get_account_details($pid, $access_token);
        if ($account_details['success']) {
            $is_youtube_ls_enabled = $this->is_youtube_ls_enabled($pid, $access_token);
            $ls_enabled = ($is_youtube_ls_enabled['success']) ? true : false;
            $channel_details = array('channel_title' => $account_details['channel_title'], 'channel_thumb' => $account_details['channel_thumb'], 'channel_id' => $account_details['channel_id'], 'is_verified' => $account_details['is_verified'], 'ls_enabled' => $ls_enabled);
            $success = array('success' => true, 'channel_details' => $channel_details);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_youtube_channel_details($pid) {
        $success = array('success' => false);
        $name = '';
        $thumbnail = '';
        $this->config->select('*')
                ->from('youtube_channel')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $name = $res['name'];
                $thumbnail = $res['thumbnail'];
                $is_verified = $res['is_verified'];
                $ls_enabled = $res['ls_enabled'];
            }
            $channel_details = array('channel_title' => $name, 'channel_thumb' => $thumbnail, 'is_verified' => $is_verified, 'ls_enabled' => $ls_enabled);
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
                        ->from('youtube_channel')
                        ->where('partner_id', $valid['pid']);

                $query = $this->config->get();
                $result = $query->result_array();
                if ($query->num_rows() > 0) {
                    foreach ($result as $res) {
                        $access_token = $this->smcipher->decrypt($res['access_token']);
                    }
                    $remove_yt_live_events = $this->remove_yt_live_events($pid);
                    if ($remove_yt_live_events['success']) {
                        $removeAuth = $this->google_client_api->removeAuth($pid, $access_token);
                        if ($removeAuth['success']) {
                            $remove_yt_channel = $this->remove_youtube_channel($pid);
                            if ($remove_yt_channel['success']) {
                                $remove_yt_channel_settings = $this->remove_youtube_channel_settings($pid);
                                if ($remove_yt_channel_settings['success']) {
                                    $remove_youtube_upload_queues = $this->remove_youtube_upload_queues($pid);
                                    if ($remove_youtube_upload_queues['success']) {
                                        $remove_youtube_vod_entries = $this->remove_youtube_vod_entries($pid);
                                        if ($remove_youtube_vod_entries['success']) {
                                            $update_status = $this->update_sn_config($pid, 'youtube', 0);
                                            if ($update_status['success']) {
                                                $success = array('success' => true);
                                            } else {
                                                $success = array('success' => false, 'message' => 'Could not update platform status');
                                            }
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not remove vod entries');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not remove upload queues');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => $remove_yt_channel_settings['message']);
                                }
                            } else {
                                $success = array('success' => false, 'message' => $remove_yt_channel['message']);
                            }
                        } else {
                            $success = array('success' => false, 'message' => $removeAuth['message']);
                        }
                    } else {
                        $success = array('success' => false, 'message' => $remove_yt_live_events['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'YouTube access token not found');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function remove_youtube_channel($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('youtube_channel');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false, 'message' => 'Could not remove YouTube access token');
        }
        return $success;
    }

    public function remove_youtube_channel_settings($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('youtube_channel_settings');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function remove_youtube_upload_queues($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->where('platform = "youtube"');
        $this->config->delete('upload_queue');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function remove_youtube_vod_entries($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->delete('youtube_vod_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function remove_db_youtube_vod_entry($pid, $eid) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->delete('youtube_vod_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function remove_db_twitch_vod_entry($pid, $eid) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->delete('twitch_vod_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function store_twitch_authorization($pid, $ks, $code) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $tokens = $this->twitch_client_api->getTokens($pid, $code);
                if ($this->check_twitch($valid['pid'])) {
                    $result = $this->update_twitch_tokens($valid['pid'], $tokens);
                } else {
                    $result = $this->insert_twitch_tokens($valid['pid'], $tokens);
                }
                if ($result['success']) {
                    $channel = $this->retrieve_twitch_channel_details($pid, $tokens['access_token']);
                    if ($channel['success']) {
                        $update_twitch_channel_details = $this->update_twitch_channel_details($pid, $channel['channel_details']['channel_name'], $channel['channel_details']['channel_id'], $channel['channel_details']['channel_logo']);
                        if ($update_twitch_channel_details['success']) {
                            $channel_stream = $this->twitch_client_api->get_channel_details($pid, $tokens['access_token']);
                            if ($channel_stream['success']) {
                                $twch_channel_stream = $this->insert_twch_channel_stream($pid, $channel_stream['channel_stream']['ingestId'], $channel_stream['channel_stream']['channelName'], $channel_stream['channel_stream']['streamName'], $channel_stream['channel_stream']['ingestAddress']);
                                if ($twch_channel_stream['success']) {
                                    $init_twitch_channel_settings = $this->init_twitch_channel_settings($pid);
                                    if ($init_twitch_channel_settings['success']) {
                                        $update_partner_notification = $this->smportal->update_partner_notification($pid, $ks);
                                        if ($update_partner_notification['success']) {
                                            $update_status = $this->update_sn_config($pid, 'twitch', 1);
                                            if ($update_status['success']) {
                                                $success = array('success' => true);
                                            } else {
                                                $success = array('success' => false);
                                            }
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not update partner notification');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not init channel settings');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not insert Twitch channel details');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not get Twitch channel details');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert channel details');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get channel details');
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

    public function insert_twch_channel_stream($pid, $ingestId, $channelName, $streamName, $address) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'channel_name' => $channelName,
            'ingestId' => $ingestId,
            'streamName' => $this->smcipher->encrypt($streamName),
            'ingestionAddress' => $this->smcipher->encrypt($this->finalize_address_url($address)),
            'status' => 'ready',
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('twitch_channel_streams', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_twch_channel_stream($pid, $ingestId, $channelName, $streamName, $address) {
        $success = array('success' => false);
        $data = array(
            'channel_name' => $channelName,
            'ingestId' => $ingestId,
            'streamName' => $this->smcipher->encrypt($streamName),
            'ingestionAddress' => $this->smcipher->encrypt($this->finalize_address_url($address)),
            'status' => 'ready',
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('twitch_channel_streams', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_twitch_tokens($pid, $tokens) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'access_token' => $this->smcipher->encrypt($tokens['access_token']),
            'refresh_token' => $this->smcipher->encrypt($tokens['refresh_token']),
            'created_at' => date("Y-m-d H:i:s")
        );
        $this->config->insert('twitch_channel', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_twitch_tokens($pid, $tokens) {
        $success = array('success' => false);
        $data = array(
            'access_token' => $this->smcipher->encrypt($tokens['access_token']),
            'refresh_token' => $this->smcipher->encrypt($tokens['refresh_token']),
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('twitch_channel', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function retrieve_twitch_channel_details($pid, $access_token) {
        $success = array('success' => false);
        $account_details = $this->twitch_client_api->get_account_details($pid, $access_token);
        if ($account_details['success']) {
            $channel_details = array('channel_name' => $account_details['channel_name'], 'channel_logo' => $account_details['channel_logo'], 'channel_id' => $account_details['channel_id']);
            $success = array('success' => true, 'channel_details' => $channel_details);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_twitch_channel_details($pid, $name, $id, $logo) {
        $success = array('success' => false);
        $data = array(
            'name' => $this->config->escape_str($name),
            'channel_id' => $this->smcipher->encrypt($id),
            'logo' => $logo,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('twitch_channel', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function remove_twitch_authorization($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $this->config->select('*')
                        ->from('twitch_channel')
                        ->where('partner_id', $valid['pid']);

                $query = $this->config->get();
                $result = $query->result_array();
                if ($query->num_rows() > 0) {
                    foreach ($result as $res) {
                        $access_token = $this->smcipher->decrypt($res['access_token']);
                    }
                    $removeAuth = $this->twitch_client_api->removeAuth($pid, $access_token);
                    if ($removeAuth['success']) {
                        $remove_channel = $this->remove_twitch_channel($pid);
                        if ($remove_channel['success']) {
                            $remove_twitch_channel_stream = $this->remove_twitch_channel_stream($pid);
                            if ($remove_twitch_channel_stream['success']) {
                                $remove_live_entries = $this->remove_twch_channel_entries($pid);
                                if ($remove_live_entries['success']) {
                                    $remove_twitch_channel_settings = $this->remove_twitch_channel_settings($pid);
                                    if ($remove_twitch_channel_settings['success']) {
                                        $update_status = $this->update_sn_config($pid, 'twitch', 0);
                                        if ($update_status['success']) {
                                            $success = array('success' => true);
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not update platform status');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not remove twitch channel settings');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not remove twitch channel entries');
                                }
                            } else {
                                $success = array('success' => false, 'message' => $remove_twitch_channel_stream['message']);
                            }
                        } else {
                            $success = array('success' => false, 'message' => $remove_channel['message']);
                        }
                    } else {
                        $success = array('success' => false, 'message' => $removeAuth['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Twitch access token not found');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function remove_twch_channel_entries($pid) {
        $success = array('success' => false);
        $vr = array();
        $vr['vrSettings'] = false;
        $entries = $this->get_twch_channel_entries($pid);
        if (count($entries['entries']) > 0) {
            foreach ($entries['entries'] as $eid) {
                $remove_twch_channel_entry = $this->remove_twch_channel_entry($pid, $eid);
                if ($remove_twch_channel_entry['success']) {
                    $update_sn_live_config = $this->update_sn_live_config($pid, $eid, 'twitch', false, null);
                    if ($update_sn_live_config['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update live configuration');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not remove twitch channel entry');
                }
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function remove_twitch_channel($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('twitch_channel');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false, 'message' => 'Could not remove Twitch channel');
        }
        return $success;
    }

    public function remove_twitch_channel_stream($pid) {
        $success = array('success' => false);
        if ($this->check_twch_channel_stream($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('twitch_channel_streams');
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Could not remove Twitch channel stream');
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function remove_twitch_channel_settings($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('twitch_channel_settings');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function check_twch_channel_stream($pid) {
        $success = false;
        $this->config->select('*')
                ->from('twitch_channel_streams')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function store_facebook_authorization($pid, $ks, $code) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $tokens = $this->facebook_client_api->getTokens($pid, $code);
                if ($this->check_facebook($valid['pid'])) {
                    $result = $this->update_facebook_tokens($valid['pid'], $tokens);
                } else {
                    $result = $this->insert_facebook_tokens($valid['pid'], $tokens);
                }
                if ($result['success']) {
                    $update_partner_notification = $this->smportal->update_partner_notification($pid, $ks);
                    if ($update_partner_notification['success']) {
                        $update_status = $this->update_sn_config($pid, 'facebook', 1);
                        if ($update_status['success']) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update partner notification');
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

    public function facebook_deauthorization($signed_request) {
        $success = array('success' => false);
        $user_id = $this->facebook_client_api->get_user_id($signed_request);
        if ($user_id['success']) {
            $pid = $this->get_fb_pid_from_uid($user_id['user_id']);
            if ($pid['success']) {
                $remove_profile = $this->remove_fb_profile($pid['pid']);
                if ($remove_profile['success']) {
                    $remove_settings = $this->remove_fb_publish_settings($pid['pid']);
                    if ($remove_settings['success']) {
                        $remove_pages = $this->remove_fb_pages($pid['pid']);
                        if ($remove_pages['success']) {
                            $remove_livestream = $this->remove_fb_livestream($pid['pid']);
                            if ($remove_livestream['success']) {
                                $remove_live_entries = $this->remove_fb_live_entries($pid['pid']);
                                if ($remove_live_entries['success']) {
                                    $remove_facebook_upload_queues = $this->remove_facebook_upload_queues($pid['pid']);
                                    if ($remove_facebook_upload_queues['success']) {
                                        $remove_facebook_vod_entries = $this->remove_facebook_vod_entries($pid['pid']);
                                        if ($remove_facebook_vod_entries['success']) {
                                            $update_status = $this->update_sn_config($pid['pid'], 'facebook', 0);
                                            if ($update_status['success']) {
                                                $success = array('success' => true);
                                            } else {
                                                $success = array('success' => false, 'message' => 'Could not update platform status');
                                            }
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not remove vod entries');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not remove upload queues');
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
                $success = array('success' => false, 'message' => 'Could not get facebook partner id');
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not get facebook user id');
        }
        return $success;
    }

    public function get_fb_pid_from_uid($uid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_profile')
                ->where('user_id', $uid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $pid = $res['partner_id'];
            }
            $success = array('success' => true, 'pid' => $pid);
        } else {
            $success = array('success' => false);
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
                        $access_token = $this->smcipher->decrypt($res['user_access_token']);
                    }
                }
                $user = $this->get_fb_user_name($pid);
                if ($user['success']) {
                    $remove = $this->facebook_client_api->removeAuth($pid, $access_token, $user['user_id']);
                    if ($remove['success']) {
                        $remove_profile = $this->remove_fb_profile($pid);
                        if ($remove_profile['success']) {
                            $remove_settings = $this->remove_fb_publish_settings($pid);
                            if ($remove_settings['success']) {
                                $remove_pages = $this->remove_fb_pages($pid);
                                if ($remove_pages['success']) {
                                    $remove_groups = $this->remove_fb_groups($pid);
                                    if ($remove_groups['success']) {
                                        $remove_events = $this->remove_fb_events($pid);
                                        if ($remove_events['success']) {
                                            $remove_livestream = $this->remove_fb_livestream($pid);
                                            if ($remove_livestream['success']) {
                                                $remove_live_entries = $this->remove_fb_live_entries($pid);
                                                if ($remove_live_entries['success']) {
                                                    $remove_facebook_upload_queues = $this->remove_facebook_upload_queues($pid);
                                                    if ($remove_facebook_upload_queues['success']) {
                                                        $remove_facebook_vod_entries = $this->remove_facebook_vod_entries($pid);
                                                        if ($remove_facebook_vod_entries['success']) {
                                                            $update_status = $this->update_sn_config($pid, 'facebook', 0);
                                                            if ($update_status['success']) {
                                                                $success = array('success' => true);
                                                            } else {
                                                                $success = array('success' => false);
                                                            }
                                                        } else {
                                                            $success = array('success' => false, 'message' => 'Could not remove vod entries');
                                                        }
                                                    } else {
                                                        $success = array('success' => false, 'message' => 'Could not remove upload queues');
                                                    }
                                                } else {
                                                    $success = array('success' => false, 'message' => 'Could not remove facebook live entries');
                                                }
                                            } else {
                                                $success = array('success' => false, 'message' => 'Could not remove facebook user livestream');
                                            }
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not remove facebook user events');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not remove facebook user groups');
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

    public function remove_fb_publish_settings($pid) {
        $success = array('success' => false);
        if ($this->check_fb_publish_settings($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('facebook_publish_settings');
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

    public function remove_fb_groups($pid) {
        $success = array('success' => false);
        if ($this->check_fb_groups($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('facebook_user_groups');
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

    public function remove_fb_events($pid) {
        $success = array('success' => false);
        if ($this->check_fb_events($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('facebook_user_events');
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

    public function remove_yt_live_events($pid) {
        $success = array('success' => false);
        $vr = array();
        $vr['vrSettings'] = false;
        $events = $this->get_yt_live_events($pid);
        if (count($events['events']) > 0) {
            foreach ($events['events'] as $eid) {
                $delete_youtube_livestream = $this->delete_youtube_livestream($pid, $eid);
                if ($delete_youtube_livestream['success']) {
                    $update_sn_live_config = $this->update_sn_live_config($pid, $eid, 'youtube_live', false, null);
                    if ($update_sn_live_config['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update live configuration');
                    }
                } else {
                    $success = array('success' => false, 'message' => $delete_youtube_livestream['message']);
                }
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function remove_fb_live_entries($pid) {
        $success = array('success' => false);
        $vr = array();
        $vr['vrSettings'] = false;
        $entries = $this->get_fb_live_entries($pid);
        if (count($entries['entries']) > 0) {
            foreach ($entries['entries'] as $eid) {
                $remove_fb_live_entry = $this->remove_fb_live_entry($pid, $eid);
                if ($remove_fb_live_entry['success']) {
                    $update_sn_live_config = $this->update_sn_live_config($pid, $eid, 'facebook_live', false, null);
                    if ($update_sn_live_config['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update live configuration');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not remove facebook live entry');
                }
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function remove_facebook_upload_queues($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->where('platform = "facebook"');
        $this->config->delete('upload_queue');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function remove_facebook_vod_entries($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->delete('facebook_vod_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
        }
        return $success;
    }

    public function remove_db_facebook_vod_entry($pid, $eid) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->delete('facebook_vod_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'message' => 'Nothing removed');
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

    public function check_fb_groups($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_user_groups')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_fb_events($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_user_events')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_yt_live_events($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('youtube_live_events')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $events = array();
            foreach ($result as $res) {
                $id = $res['entryId'];
                array_push($events, $id);
            }
            $success = array('success' => true, 'events' => $events);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_facebook_embed($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_live_streams')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $embed_code = $res['embed_code'];
            }
            $doc = new DOMDocument();
            $doc->loadHTML($embed_code);
            $iframe = $doc->getElementsByTagName('iframe');
            foreach ($iframe as $node) {
                if ($node->hasAttributes()) {
                    foreach ($node->attributes as $a) {
                        if ($a->name == 'src') {
                            $src = $a->value;
                        }
                    }
                }
            }
            $src = explode('&', $src);
            $src = $src[0];
            $success = array('success' => true, 'src' => $src);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_twch_channel_entries($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('twitch_channel_entries')
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
                if ($this->check_fb_pages($pid)) {
                    $remove_pages = $this->remove_facebook_pages($pid);
                    if ($remove_pages['success']) {
                        $pages_response = $this->insert_facebook_pages($pid, $tokens['pages']);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not remove facebook pages');
                    }
                } else {
                    $pages_response = $this->insert_facebook_pages($pid, $tokens['pages']);
                }
                if ($pages_response['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => true);
            }
            if (count($tokens['groups']) > 0) {
                if ($this->check_fb_groups($pid)) {
                    $remove_groups = $this->remove_facebook_groups($pid);
                    if ($remove_groups['success']) {
                        $groups_response = $this->insert_facebook_groups($pid, $tokens['groups']);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not remove facebook groups');
                    }
                } else {
                    $groups_response = $this->insert_facebook_groups($pid, $tokens['groups']);
                }
                if ($groups_response['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => true);
            }
            if (count($tokens['events']) > 0) {
                if ($this->check_fb_events($pid)) {
                    $remove_events = $this->remove_facebook_events($pid);
                    if ($remove_events['success']) {
                        $events_response = $this->insert_facebook_events($pid, $tokens['events']);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not remove facebook events');
                    }
                } else {
                    $events_response = $this->insert_facebook_events($pid, $tokens['events']);
                }
                if ($events_response['success']) {
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
            if (count($tokens['groups']) > 0) {
                $groups_response = $this->insert_facebook_groups($pid, $tokens['groups']);
                if ($groups_response['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => true);
            }
            if (count($tokens['events']) > 0) {
                $events_response = $this->insert_facebook_events($pid, $tokens['events']);
                if ($events_response['success']) {
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
            'user_id' => $this->smcipher->encrypt($user['user_id']),
            'name' => $user['user_name'],
            'thumbnail' => $user['user_thumbnail'],
            'user_access_token' => $this->smcipher->encrypt($user['access_token']),
            'updated_at' => date("Y-m-d H:i:s")
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
            'user_id' => $this->smcipher->encrypt($user['user_id']),
            'name' => $user['user_name'],
            'thumbnail' => $user['user_thumbnail'],
            'user_access_token' => $this->smcipher->encrypt($user['access_token']),
            'created_at' => date("Y-m-d H:i:s")
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
                'page_id' => $this->smcipher->encrypt($page['page_id']),
                'name' => $page['page_name'],
                'page_access_token' => $this->smcipher->encrypt($page['access_token']),
                'created_at' => date("Y-m-d H:i:s")
            );
            $this->config->insert('facebook_user_pages', $data);
            $this->config->limit(1);
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            }
        }
        return $success;
    }

    public function remove_facebook_groups($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('facebook_user_groups');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_facebook_groups($pid, $groups) {
        $success = array('success' => false);
        foreach ($groups as $group) {
            $data = array(
                'partner_id' => $pid,
                'group_id' => $this->smcipher->encrypt($group['group_id']),
                'name' => $group['group_name'],
                'created_at' => date("Y-m-d H:i:s")
            );
            $this->config->insert('facebook_user_groups', $data);
            $this->config->limit(1);
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            }
        }
        return $success;
    }

    public function remove_facebook_events($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('facebook_user_events');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_facebook_events($pid, $events) {
        $success = array('success' => false);
        foreach ($events as $event) {
            $data = array(
                'partner_id' => $pid,
                'event_id' => $this->smcipher->encrypt($event['event_id']),
                'name' => $event['event_name'],
                'created_at' => date("Y-m-d H:i:s")
            );
            $this->config->insert('facebook_user_events', $data);
            $this->config->limit(1);
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            }
        }
        return $success;
    }

    public function create_fb_livestream($pid, $ks, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $auto_upload, $projection) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $access_token = $this->validate_facebook_token($valid['pid']);
                if ($access_token['success']) {
                    $get_asset = $this->get_asset($pid, $publish_to, $asset_id, $access_token['access_token']);
                    if ($get_asset['success']) {
                        $livestream = $this->facebook_client_api->createLiveStream($pid, $get_asset['asset'], $privacy, $create_vod, $cont_streaming, $projection);
                        if ($livestream['success']) {
                            $add_fb_livestream = $this->add_fb_livestream($pid, $livestream['address'], $livestream['stream_name'], $livestream['embed_code'], $livestream['live_id']);
                            if ($add_fb_livestream['success']) {
                                $add_fb_settings = $this->add_fb_settings($pid, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $auto_upload, $projection);
                                if ($add_fb_settings['success']) {
                                    $success = array('success' => true);
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not add Facebook settings');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not add Facebook live stream');
                            }
                        } else {
                            $success = array('success' => false, 'message' => $livestream['message']);
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

    public function add_fb_settings($pid, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $auto_upload, $projection) {
        if ($this->check_fb_publish_settings($pid)) {
            $result = $this->update_fb_publish_settings($pid, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $auto_upload, $projection);
        } else {
            $result = $this->insert_fb_publish_settings($pid, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $auto_upload, $projection);
        }
        if ($result['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function check_fb_publish_settings($pid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_publish_settings')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function update_fb_publish_settings($pid, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $auto_upload, $projection) {
        $success = array('success' => false);
        $data = array(
            'publish_to' => $publish_to,
            'asset_id' => $asset_id,
            'privacy' => $privacy,
            'create_vod' => ($create_vod == 'true') ? true : false,
            'cont_streaming' => ($cont_streaming == 'true') ? true : false,
            'auto_upload' => ($auto_upload == 'true') ? true : false,
            'projection' => $projection,
            'updated_at' => date("Y-m-d H:i:s")
        );
        $this->config->where('partner_id', $pid);
        $this->config->update('facebook_publish_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function update_fb_publish_settings_asset_id($pid, $asset_id) {
        $success = array('success' => false);
        $data = array(
            'asset_id' => $asset_id,
            'updated_at' => date("Y-m-d H:i:s")
        );
        $this->config->where('partner_id', $pid);
        $this->config->update('facebook_publish_settings', $data);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function insert_fb_publish_settings($pid, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $auto_upload, $projection) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'publish_to' => $publish_to,
            'asset_id' => $asset_id,
            'privacy' => $privacy,
            'create_vod' => ($create_vod == 'true') ? true : false,
            'cont_streaming' => ($cont_streaming == 'true') ? true : false,
            'auto_upload' => ($auto_upload == 'true') ? true : false,
            'projection' => $projection,
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('facebook_publish_settings', $data);
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
            'live_id' => $this->smcipher->encrypt($live_id),
            'streamName' => $this->smcipher->encrypt($stream_name),
            'ingestionAddress' => $this->smcipher->encrypt($this->finalize_address_url($address)),
            'status' => 'ready',
            'embed_code' => $embed_code,
            'updated_at' => date("Y-m-d H:i:s")
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
            'live_id' => $this->smcipher->encrypt($live_id),
            'streamName' => $this->smcipher->encrypt($stream_name),
            'ingestionAddress' => $this->smcipher->encrypt($this->finalize_address_url($address)),
            'status' => 'ready',
            'embed_code' => $embed_code,
            'created_at' => date("Y-m-d H:i:s")
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

    public function get_asset($pid, $publish_to, $asset_id, $access_token) {
        $success = array('success' => false);
        if ($publish_to == 1) {
            $user_id = $this->get_fb_user_name($pid);
            $asset = array('asset_type' => 'user', 'asset_id' => $user_id['user_id'], 'access_token' => $access_token);
            $success = array('success' => true, 'asset' => $asset);
        } else if ($publish_to == 2) {
            $page = $this->get_fb_page($pid, $asset_id);
            $asset = array('asset_type' => 'page', 'asset_id' => $page['page_id'], 'access_token' => $page['access_token']);
            $success = array('success' => true, 'asset' => $asset);
        } else if ($publish_to == 3) {
            $group = $this->get_fb_group($pid, $asset_id);
            $asset = array('asset_type' => 'group', 'asset_id' => $group['group_id'], 'access_token' => $access_token);
            $success = array('success' => true, 'asset' => $asset);
        } else if ($publish_to == 4) {
            $event = $this->get_fb_event($pid, $asset_id);
            $asset = array('asset_type' => 'event', 'asset_id' => $event['event_id'], 'access_token' => $access_token);
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
                $page_id = $this->smcipher->decrypt($res['page_id']);
                $access_token = $this->smcipher->decrypt($res['page_access_token']);
            }
            $success = array('success' => true, 'page_id' => $page_id, 'access_token' => $access_token);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_group($pid, $asset_id) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_groups')
                ->where('partner_id', $pid)
                ->where('id', $asset_id);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $group_id = $this->smcipher->decrypt($res['group_id']);
            }
            $success = array('success' => true, 'group_id' => $group_id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_fb_event($pid, $asset_id) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_user_events')
                ->where('partner_id', $pid)
                ->where('id', $asset_id);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $event_id = $this->smcipher->decrypt($res['event_id']);
            }
            $success = array('success' => true, 'event_id' => $event_id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function store_weibo_authorization($pid, $ks, $code, $projection) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $token = $this->weibo_client_api->getTokens($pid, $code);
                if ($this->check_weibo($valid['pid'])) {
                    $result = $this->update_weibo_tokens($valid['pid'], $token);
                } else {
                    $result = $this->insert_weibo_tokens($valid['pid'], $token);
                }
                if ($result['success']) {
                    $profile = $this->retrieve_weibo_profile_details($pid, $token['access_token']);
                    if ($profile['success']) {
                        $update_weibo_profile_details = $this->update_weibo_profiile_details($pid, $profile['profile_details']['name'], $profile['profile_details']['user_thumb'], $profile['profile_details']['user_id']);
                        if ($update_weibo_profile_details['success']) {
                            $init_weibo_profile_settings = $this->init_weibo_profile_settings($pid, $projection);
                            if ($init_weibo_profile_settings['success']) {
                                $update_status = $this->update_sn_config($pid, 'weibo', 1);
                                if ($update_status['success']) {
                                    $success = array('success' => true);
                                } else {
                                    $success = array('success' => false);
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not init channel settings');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert channel details');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get profile details');
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

    public function remove_weibo_authorization($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $this->config->select('*')
                        ->from('weibo_user_profile')
                        ->where('partner_id', $valid['pid']);

                $query = $this->config->get();
                $result = $query->result_array();
                if ($query->num_rows() > 0) {
                    foreach ($result as $res) {
                        $access_token = $this->smcipher->decrypt($res['access_token']);
                    }
                }
                $remove = $this->weibo_client_api->removeAuth($pid, $access_token);
                if ($remove['success']) {
                    $remove_profile = $this->remove_weibo_profile($pid);
                    if ($remove_profile['success']) {
                        $remove_settings = $this->remove_weibo_publish_settings($pid);
                        if ($remove_settings['success']) {
                            $update_status = $this->update_sn_config($pid, 'weibo', 0);
                            if ($update_status['success']) {
                                $success = array('success' => true);
                            } else {
                                $success = array('success' => false);
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not remove weibo user settings');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not remove weibo user profile');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not remove authorization');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function remove_weibo_profile($pid) {
        $success = array('success' => false);
        $this->config->where('partner_id = "' . $pid . '"');
        $this->config->delete('weibo_user_profile');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function remove_weibo_publish_settings($pid) {
        $success = array('success' => false);
        if ($this->check_weibo_publish_settings($pid)) {
            $this->config->where('partner_id = "' . $pid . '"');
            $this->config->delete('weibo_profile_settings');
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

    public function check_weibo_publish_settings($pid) {
        $success = false;
        $this->config->select('*')
                ->from('weibo_profile_settings')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function store_youtube_authorization($pid, $ks, $code, $projection) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $tokens = $this->google_client_api->getTokens($pid, $code);
                if ($this->check_youtube($valid['pid'])) {
                    $result = $this->update_youtube_tokens($valid['pid'], $tokens);
                } else {
                    $result = $this->insert_youtube_tokens($valid['pid'], $tokens);
                }
                if ($result['success']) {
                    $channel = $this->retrieve_youtube_channel_details($pid, $tokens['access_token']);
                    if ($channel['success']) {
                        $update_youtube_channel_details = $this->update_youtube_channel_details($pid, $channel['channel_details']['channel_title'], $channel['channel_details']['channel_thumb'], $channel['channel_details']['channel_id'], $channel['channel_details']['is_verified'], $channel['channel_details']['ls_enabled']);
                        if ($update_youtube_channel_details['success']) {
                            $init_youtube_channel_settings = $this->init_youtube_channel_settings($pid, $projection);
                            if ($init_youtube_channel_settings['success']) {
                                $update_partner_notification = $this->smportal->update_partner_notification($pid, $ks);
                                if ($update_partner_notification['success']) {
                                    $update_status = $this->update_sn_config($pid, 'youtube', 1);
                                    if ($update_status['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false);
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not update partner notification');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not init channel settings');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert channel details');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get channel details');
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

    public function insert_weibo_tokens($pid, $token) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'access_token' => $this->smcipher->encrypt($token['access_token']),
            'expires_in' => $token['expires_in'],
            'created_at' => date("Y-m-d H:i:s")
        );
        $this->config->insert('weibo_user_profile', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_weibo_tokens($pid, $token) {
        $success = array('success' => false);
        $data = array(
            'access_token' => $this->smcipher->encrypt($token['access_token']),
            'expires_in' => $token['expires_in'],
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('weibo_user_profile', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
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
        $this->config->insert('youtube_channel', $data);
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
        $this->config->update('youtube_channel', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function update_youtube_channel_details($pid, $name, $thumbnail, $id, $is_verified, $ls_enabled) {
        $success = array('success' => false);
        $data = array(
            'name' => $this->config->escape_str($name),
            'thumbnail' => $this->config->escape_str($thumbnail),
            'channel_id' => $this->smcipher->encrypt($id),
            'is_verified' => $is_verified,
            'ls_enabled' => $ls_enabled
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_channel', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function update_weibo_profiile_details($pid, $name, $thumbnail, $id) {
        $success = array('success' => false);
        $data = array(
            'name' => $this->config->escape_str($name),
            'user_id' => $this->smcipher->encrypt($id),
            'thumbnail' => $this->config->escape_str($thumbnail),
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('weibo_user_profile', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function init_weibo_profile_settings($pid, $projection) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'projection' => $projection,
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('weibo_profile_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_weibo($pid) {
        $success = false;
        $this->config->select('*')
                ->from('weibo_user_profile')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_youtube($pid) {
        $success = false;
        $this->config->select('*')
                ->from('youtube_channel')
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

    public function check_twitch($pid) {
        $success = false;
        $this->config->select('*')
                ->from('twitch_channel')
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
        $vr = array();
        $vr['vrSettings'] = false;
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $config = array();
                $snConfig = $this->build_live_sn_config($platforms);
                $youtube_success = array('platform' => 'youtube', 'success' => false, 'message' => 'Was not asked to create a live stream');
                $facebook_success = array('platform' => 'facebook', 'success' => false, 'message' => 'Was not asked to create a live stream');
                $twitch_success = array('platform' => 'twitch', 'success' => false, 'message' => 'Was not asked to create a live stream');
                $youtube_embed = true;

                if ($snConfig['smh']) {
                    $smh_config = $this->create_live_sn_config('smh', true, null);
                    array_push($config, $smh_config['config']);
                } else {
                    $smh_config = $this->create_live_sn_config('smh', false, null);
                    array_push($config, $smh_config['config']);
                }

                if ($snConfig['youtube']) {
                    $create_youtube_live_stream = $this->create_youtube_live_stream($pid, $name, $desc, $eid, $snConfig, $projection);
                    if ($create_youtube_live_stream['success']) {
                        $youtube_success['success'] = true;
                        $youtube_embed = $create_youtube_live_stream['youtube_embed'];
                        $youtube_live_config = $this->create_live_sn_config('youtube_live', true, $create_youtube_live_stream['broadcast_id']);
                        array_push($config, $youtube_live_config['config']);
                    } else {
                        $youtube_success['success'] = false;
                        $youtube_success['message'] = $create_youtube_live_stream['message'];
                        $youtube_live_config = $this->create_live_sn_config('youtube_live', false, null);
                        array_push($config, $youtube_live_config['config']);
                    }
                } else {
                    $youtube_live_config = $this->create_live_sn_config('youtube_live', false, null);
                    array_push($config, $youtube_live_config['config']);
                }

                if ($snConfig['facebook']) {
                    $create_facebook_live_stream = $this->create_facebook_live_stream($pid, $eid);
                    if ($create_facebook_live_stream['success']) {
                        $facebook_success['success'] = true;
                        $facebook_live_config = $this->create_live_sn_config('facebook_live', true, $create_facebook_live_stream['live_id']);
                        array_push($config, $facebook_live_config['config']);
                    } else {
                        $facebook_success['success'] = false;
                        $facebook_success['message'] = $create_facebook_live_stream['message'];
                        $facebook_live_config = $this->create_live_sn_config('facebook_live', false, null);
                        array_push($config, $facebook_live_config['config']);
                    }
                } else {
                    $facebook_live_config = $this->create_live_sn_config('facebook_live', false, null);
                    array_push($config, $facebook_live_config['config']);
                }

                if ($snConfig['twitch']) {
                    $create_twitch_channel_stream = $this->create_twitch_channel_stream($pid, $eid);
                    if ($create_twitch_channel_stream['success']) {
                        $twitch_success['success'] = true;
                        $twitch_live_config = $this->create_live_sn_config('twitch', true, $create_twitch_channel_stream['live_id']);
                        array_push($config, $twitch_live_config['config']);
                    } else {
                        $twitch_success['success'] = false;
                        $twitch_success['message'] = $create_twitch_channel_stream['message'];
                        $twitch_live_config = $this->create_live_sn_config('twitch', false, null);
                        array_push($config, $twitch_live_config['config']);
                    }
                } else {
                    $twitch_live_config = $this->create_live_sn_config('twitch', false, null);
                    array_push($config, $twitch_live_config['config']);
                }

                $partnerData = $this->update_sn_partnerData($pid, $eid, $config, $vr);
                if ($partnerData['success']) {
                    $platforms_responses = array();
                    array_push($platforms_responses, $youtube_success);
                    array_push($platforms_responses, $facebook_success);
                    array_push($platforms_responses, $twitch_success);
                    $platf = $this->get_live_platforms(json_decode($partnerData['partnerData']));
                    $configSettings = $this->buildConfigSettings($platf);
                    $success = array('success' => true, 'configSettings' => $configSettings, 'youtube_embed_status' => $youtube_embed, 'platforms_responses' => $platforms_responses);
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

    public function create_twitch_channel_stream($pid, $eid) {
        $success = array('success' => false);
        $access_token = $this->validate_twitch_token($pid);
        if ($access_token['success']) {
            $livestream_ids = $this->get_twch_channel_stream($pid);
            $add_live_entry = $this->add_twch_channel_entry($pid, $eid, $livestream_ids['id']);
            if ($add_live_entry['success']) {
                $success = array('success' => true, 'live_id' => $livestream_ids['live_id']);
            } else {
                $success = array('success' => false, 'message' => 'Could not insert Twitch Channel Entry');
            }
        } else {
            $success = array('success' => false, 'message' => 'Twitch: invalid access token');
        }
        return $success;
    }

    public function create_youtube_live_stream($pid, $name, $desc, $eid, $snConfig, $projection) {
        $success = array('success' => false);
        $youtube_embed = true;
        $access_token = $this->validate_youtube_token($pid);
        if ($access_token['success']) {
            $thumbnail = array('use_default' => true);
            $livestream = $this->google_client_api->createLiveStream($pid, $access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
            if ($livestream['success']) {
                $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                if ($insert_live_event['success']) {
                    $add_youtube_emebed_status = $this->add_youtube_emebed_status($pid, $youtube_embed);
                    if ($add_youtube_emebed_status['success']) {
                        $success = array('success' => true, 'broadcast_id' => $livestream['liveBroadcastId'], 'youtube_embed' => $youtube_embed);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                }
            } else if (isset($livestream['retry'])) {
                $youtube_embed = false;
                $livestream = $this->google_client_api->createLiveStream($pid, $access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
                if ($livestream['success']) {
                    $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                    if ($insert_live_event['success']) {
                        $add_youtube_emebed_status = $this->add_youtube_emebed_status($pid, $youtube_embed);
                        if ($add_youtube_emebed_status['success']) {
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
            } else if (isset($livestream['blocked'])) {
                $success = array('success' => false, 'message' => 'YouTube: you may have reached your daily broadcasting limit');
            } else {
                $success = array('success' => false, 'message' => 'YouTube: could not create Live Event');
            }
        } else {
            $success = array('success' => false, 'message' => 'YouTube: invalid access token');
        }
        return $success;
    }

    public function create_facebook_live_stream($pid, $eid) {
        $success = array('success' => false);
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
        return $success;
    }

    public function remove_fb_live_entry($pid, $eid) {
        $success = array('success' => false);
        if ($this->check_fb_live_entry($pid, $eid)) {
            $this->config->where('partner_id = "' . $pid . '" AND entryId = "' . $eid . '"');
            $this->config->delete('facebook_live_entries');
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

    public function remove_twch_channel_entry($pid, $eid) {
        $success = array('success' => false);
        if ($this->check_twch_channel_entry($pid, $eid)) {
            $this->config->where('partner_id = "' . $pid . '" AND entryId = "' . $eid . '"');
            $this->config->delete('twitch_channel_entries');
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

    public function add_twch_channel_entry($pid, $eid, $id) {
        $success = array('success' => false);
        if ($this->check_twch_channel_entry($pid, $eid)) {
            $result = $this->update_twch_channel_entry($pid, $eid, $id);
        } else {
            $result = $this->insert_twch_channel_entry($pid, $eid, $id);
        }
        if ($result['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_twch_channel_entry($pid, $eid) {
        $success = false;
        $this->config->select('*')
                ->from('twitch_channel_entries')
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

    public function update_twch_channel_entry($pid, $eid, $live_id) {
        $success = array('success' => false);
        $data = array(
            'channel_stream_id' => $live_id,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->update('twitch_channel_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function insert_twch_channel_entry($pid, $eid, $live_id) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'channel_stream_id' => $live_id,
            'created_at' => date("Y-m-d H:i:s")
        );
        $this->config->insert('twitch_channel_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
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
            'updated_at' => date("Y-m-d H:i:s")
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
            'created_at' => date("Y-m-d H:i:s")
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
                $live_id = $this->smcipher->decrypt($res['live_id']);
            }
            $success = array('success' => true, 'id' => $id, 'live_id' => $live_id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_twch_channel_stream($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('twitch_channel_streams')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $id = $res['id'];
                $live_id = $res['channel_name'];
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
                        array_push($platforms_preview_embed_arr, "youtube:1:" . $platform['liveId']);
                    } else {
                        array_push($platforms_preview_embed_arr, "youtube:0");
                    }
                }
                if ($platform['platform'] == 'twitch') {
                    if ($platform['status']) {
                        array_push($platforms_preview_embed_arr, "twitch:1:" . $platform['liveId']);
                    } else {
                        array_push($platforms_preview_embed_arr, "twitch:0");
                    }
                }
            }
            $platforms_preview_embed = implode(";", $platforms_preview_embed_arr);
        }
        return $platforms_preview_embed;
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
            'projection' => $projection,
            'created_at' => date("Y-m-d H:i:s")
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

    public function update_sn_partnerData($pid, $eid, $platforms_config, $vr) {
        $success = array('success' => false);
        $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
        if ($partnerData['success']) {
            $temp_partnerData = json_decode($partnerData['partnerData'], true);
            $temp_partnerData['snConfig'] = $platforms_config;
            if ($vr['vrSettings']) {
                $temp_partnerData['vrSettings'] = $vr['settings'];
            } else {
                unset($temp_partnerData['vrSettings']);
            }
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

    public function build_live_sn_config($platforms) {
        $success = array('success' => false);
        $platforms = json_decode($platforms, true);
        $smh = false;
        $facebook = false;
        $youtube = false;
        $twitch = false;
        $res = '240p';
        foreach ($platforms['platforms'] as $platform) {
            if ($platform['platform'] == 'smh') {
                if ($platform['status']) {
                    $smh = true;
                }
            } else if ($platform['platform'] == 'facebook_live') {
                if ($platform['status']) {
                    $facebook = true;
                }
            } else if ($platform['platform'] == 'youtube_live') {
                if ($platform['status']) {
                    $youtube = true;
                    $res = $platform['config']['res'];
                }
            } else if ($platform['platform'] == 'twitch') {
                if ($platform['status']) {
                    $twitch = true;
                }
            }
        }
        $success = array('success' => true, 'smh' => $smh, 'youtube' => $youtube, 'youtube_res' => $res, 'facebook' => $facebook, 'twitch' => $twitch);
        return $success;
    }

    public function update_sn_livestreams($pid, $ks, $name, $desc, $eid, $platforms, $projection) {
        $success = array('success' => false);
        $vr = array();
        $vr['vrSettings'] = false;
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $config = array();
                $snConfig = $this->build_live_sn_config($platforms);
                $youtube_success = array('platform' => 'youtube', 'success' => false, 'message' => 'Was not asked to create or update a live stream');
                $facebook_success = array('platform' => 'facebook', 'success' => false, 'message' => 'Was not asked to create or update a live stream');
                $youtube_embed = true;
                $twitch_success = array('platform' => 'twitch', 'success' => false, 'message' => 'Was not asked to create or update a live stream');

                if ($snConfig['smh']) {
                    $smh_config = $this->create_live_sn_config('smh', true, null);
                    array_push($config, $smh_config['config']);
                } else {
                    $smh_config = $this->create_live_sn_config('smh', false, null);
                    array_push($config, $smh_config['config']);
                }

                $update_youtube_live_stream = $this->update_youtube_live_stream($pid, $ks, $name, $desc, $eid, $snConfig, $projection);
                if ($update_youtube_live_stream['success']) {
                    $youtube_success['success'] = true;
                    $youtube_embed = $update_youtube_live_stream['youtube_embed'];
                    if ($update_youtube_live_stream['broadcast_id']) {
                        $youtube_live_config = $this->create_live_sn_config('youtube_live', true, $update_youtube_live_stream['broadcast_id']);
                        array_push($config, $youtube_live_config['config']);
                    } else {
                        $youtube_live_config = $this->create_live_sn_config('youtube_live', false, null);
                        array_push($config, $youtube_live_config['config']);
                    }
                } else {
                    $youtube_success['success'] = false;
                    $youtube_success['message'] = $update_youtube_live_stream['message'];
                    $youtube_live_config = $this->create_live_sn_config('youtube_live', false, null);
                    array_push($config, $youtube_live_config['config']);
                }

                $update_facebook_live_stream = $this->update_facebook_live_stream($pid, $eid, $snConfig);
                if ($update_facebook_live_stream['success']) {
                    $facebook_success['success'] = true;
                    if ($update_facebook_live_stream['live_id']) {
                        $facebook_live_config = $this->create_live_sn_config('facebook_live', true, $update_facebook_live_stream['live_id']);
                        array_push($config, $facebook_live_config['config']);
                    } else {
                        $facebook_live_config = $this->create_live_sn_config('facebook_live', false, null);
                        array_push($config, $facebook_live_config['config']);
                    }
                } else {
                    $facebook_success['success'] = false;
                    $facebook_success['message'] = $update_facebook_live_stream['message'];
                    $facebook_live_config = $this->create_live_sn_config('facebook_live', false, null);
                    array_push($config, $facebook_live_config['config']);
                }

                $update_twitch_channel_stream = $this->update_twitch_channel_stream($pid, $eid, $snConfig);
                if ($update_twitch_channel_stream['success']) {
                    $twitch_success['success'] = true;
                    if ($update_twitch_channel_stream['live_id']) {
                        $twitch_live_config = $this->create_live_sn_config('twitch', true, $update_twitch_channel_stream['live_id']);
                        array_push($config, $twitch_live_config['config']);
                    } else {
                        $twitch_live_config = $this->create_live_sn_config('twitch', false, null);
                        array_push($config, $twitch_live_config['config']);
                    }
                } else {
                    $twitch_success['success'] = false;
                    $twitch_success['message'] = $update_twitch_channel_stream['message'];
                    $twitch_live_config = $this->create_live_sn_config('twitch', false, null);
                    array_push($config, $twitch_live_config['config']);
                }

                $partnerData = $this->update_sn_partnerData($pid, $eid, $config, $vr);
                if ($partnerData['success']) {
                    $platforms_responses = array();
                    array_push($platforms_responses, $youtube_success);
                    array_push($platforms_responses, $facebook_success);
                    array_push($platforms_responses, $twitch_success);
                    $success = array('success' => true, 'youtube_embed_status' => $youtube_embed, 'platforms_responses' => $platforms_responses);
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

    public function update_twitch_channel_stream($pid, $eid, $snConfig) {
        $success = array('success' => false);
        if ($snConfig['twitch']) {
            $access_token = $this->validate_twitch_token($pid);
            if ($access_token['success']) {
                $livestream_ids = $this->get_twch_channel_stream($pid);
                $add_live_entry = $this->add_twch_channel_entry($pid, $eid, $livestream_ids['id']);
                if ($add_live_entry['success']) {
                    $success = array('success' => true, 'live_id' => $livestream_ids['live_id']);
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert Twitch Channel Entry');
                }
            } else {
                $success = array('success' => false, 'message' => 'Twitch: invalid access token');
            }
        } else if (!$snConfig['twitch']) {
            $remove_live_entry = $this->remove_twch_channel_entry($pid, $eid);
            if ($remove_live_entry['success']) {
                $success = array('success' => true, 'live_id' => null);
            } else {
                $success = array('success' => false, 'message' => 'Could not insert Twitch Channel Entry');
            }
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
                    $livestream = $this->google_client_api->updateLiveStream($pid, $access_token['access_token'], $snConfig['youtube_res'], $name, $eid, $youtube_ids['bid'], $youtube_ids['lid']);
                    if ($livestream['success']) {
                        $update_live_event = $this->update_youtube_live_event($pid, $eid, $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                        if ($update_live_event['success']) {
                            $add_youtube_emebed_status = $this->add_youtube_emebed_status($pid, $youtube_embed);
                            if ($add_youtube_emebed_status['success']) {
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
                    $livestream = $this->google_client_api->createLiveStream($pid, $access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
                    if ($livestream['success']) {
                        $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                        if ($insert_live_event['success']) {
                            $add_youtube_emebed_status = $this->add_youtube_emebed_status($pid, $youtube_embed);
                            if ($add_youtube_emebed_status['success']) {
                                $success = array('success' => true, 'broadcast_id' => $livestream['liveBroadcastId'], 'youtube_embed' => $youtube_embed);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert YouTube Live Event');
                        }
                    } else if (isset($livestream['retry'])) {
                        $youtube_embed = false;
                        $livestream = $this->google_client_api->createLiveStream($pid, $access_token['access_token'], $name, $desc, $snConfig['youtube_res'], $eid, $thumbnail, $projection, $youtube_embed);
                        if ($livestream['success']) {
                            $insert_live_event = $this->insert_youtube_live_event($pid, $eid, $livestream['liveBroadcastId'], $livestream['liveStreamId'], $livestream['streamName'], $livestream['ingestionAddress'], $projection);
                            if ($insert_live_event['success']) {
                                $add_youtube_emebed_status = $this->add_youtube_emebed_status($pid, $youtube_embed);
                                if ($add_youtube_emebed_status['success']) {
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
                    } else if (isset($livestream['blocked'])) {
                        $success = array('success' => false, 'message' => 'YouTube: you may have reached your daily broadcasting limit');
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
                $access_token = $this->validate_youtube_token($pid);
                if ($access_token['success']) {
                    $removeLiveStream = $this->google_client_api->removeLiveStream($pid, $access_token['access_token'], $youtube_ids['bid'], $youtube_ids['lid']);
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
            'ingestionAddress' => $this->smcipher->encrypt($this->finalize_address_url($ingestionAddress)),
            'updated_at' => date("Y-m-d H:i:s")
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
            'liveBroadcastId' => $this->smcipher->encrypt($bid),
            'updated_at' => date("Y-m-d H:i:s")
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

    public function get_youtube_broadcast_id($pid, $eid) {
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
            }
            $success = array('success' => true, 'bid' => $bid);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_youtube_embed_status($pid, $embed_status) {
        $success = array('success' => false);
        $data = array(
            'embed' => $embed_status,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_channel_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true, 'embed_status' => $embed_status);
        } else {
            $success = array('success' => true, 'embed_status' => $embed_status);
        }
        return $success;
    }

    public function insert_youtube_embed_status($pid, $embed_status) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'embed' => $embed_status,
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('youtube_channel_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true, 'embed_status' => $embed_status);
        } else {
            $success = array('success' => true, 'embed_status' => $embed_status);
        }
        return $success;
    }

    public function init_youtube_channel_settings($pid, $projection) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'embed' => false,
            'auto_upload' => false,
            'projection' => $projection,
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('youtube_channel_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function init_twitch_channel_settings($pid) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'auto_upload' => false,
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('twitch_channel_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_twitch_channel_settings($pid, $ks, $auto_upload) {
        $success = array('success' => false);
        $auto_upload = ($auto_upload == 'true') ? true : false;
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $update_twitch_auto_upload = $this->update_twitch_auto_upload($pid, $auto_upload);
                if ($update_twitch_auto_upload['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => 'Could not update auto upload status');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function update_twitch_auto_upload($pid, $auto_upload) {
        $success = array('success' => false);
        $data = array(
            'auto_upload' => $auto_upload,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('twitch_channel_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_youtube_channel_settings($pid, $ks, $auto_upload) {
        $success = array('success' => false);
        $auto_upload = ($auto_upload == 'true') ? true : false;
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $update_youtube_auto_upload = $this->update_youtube_auto_upload($pid, $auto_upload);
                if ($update_youtube_auto_upload['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => 'Could not update auto upload status');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function update_youtube_auto_upload($pid, $auto_upload) {
        $success = array('success' => false);
        $data = array(
            'auto_upload' => $auto_upload,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('youtube_channel_settings', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_youtube_channel_settings($pid) {
        $success = false;
        $this->config->select('*')
                ->from('youtube_channel_settings')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function add_youtube_emebed_status($pid, $youtube_embed) {
        $success = array('success' => false);
        $check_youtube_channel_settings = $this->check_youtube_channel_settings($pid);
        if ($check_youtube_channel_settings) {
            $embed_status = $this->update_youtube_embed_status($pid, $youtube_embed);
        } else {
            $embed_status = $this->insert_youtube_embed_status($pid, $youtube_embed);
        }
        if ($embed_status['success']) {
            $success = array('success' => true);
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

    public function update_vod_sn_metadata($pid, $ks, $name, $desc, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_vod_entry_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $success = $this->update_youtube_vod_metadata($pid, $name, $desc, $eid);
                        } else {
                            $success = array('success' => true, 'message' => 'Social network: nothing to update');
                        }
                        if ($platforms_status['platforms_status']['facebook']) {
                            $success = $this->update_facebook_vod_metadata($pid, $name, $desc, $eid);
                        } else {
                            $success = array('success' => true, 'message' => 'Social network: nothing to update');
                        }
                        if ($platforms_status['platforms_status']['twitch']) {
                            $success = $this->update_twitch_vod_metadata($pid, $name, $desc, $eid);
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

    public function update_live_sn_metadata($pid, $ks, $name, $desc, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_live_entry_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $success = $this->update_youtube_live_metadata($pid, $name, $desc, $eid);
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

    public function update_youtube_live_metadata($pid, $name, $desc, $eid) {
        $success = array('success' => false);
        $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
        if ($youtube_ids['success']) {
            $access_token = $this->validate_youtube_token($pid);
            if ($access_token['success']) {
                $updateMetaData = $this->google_client_api->updateLiveMetaData($pid, $access_token['access_token'], $youtube_ids['bid'], $name, $desc);
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
        return $success;
    }

    public function update_twitch_vod_metadata($pid, $name, $desc, $eid) {
        $success = array('success' => false);
        $twitch_video = $this->get_twitch_vod_id($pid, $eid);
        $access_token = $this->validate_twitch_token($pid);
        if ($access_token['success']) {
            $updateMetaData = $this->twitch_client_api->updateVodMetaData($access_token['access_token'], $twitch_video['videoId'], $name, $desc);
            if ($updateMetaData['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Twitch: Could not update metadata');
            }
        } else {
            $success = array('success' => false, 'message' => 'Twitch: invalid access token');
        }
        return $success;
    }

    public function update_facebook_vod_metadata($pid, $name, $desc, $eid) {
        $success = array('success' => false);
        $facebook_video = $this->get_facebook_vod_id($pid, $eid);
        $access_token = $this->validate_facebook_token($pid);
        if ($access_token['success']) {
            $updateMetaData = $this->facebook_client_api->updateVodMetaData($pid, $access_token['access_token'], $facebook_video['videoId'], $name, $desc);
            if ($updateMetaData['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Facebook: Could not update metadata');
            }
        } else {
            $success = array('success' => false, 'message' => 'Facebook: invalid access token');
        }
        return $success;
    }

    public function update_youtube_vod_metadata($pid, $name, $desc, $eid) {
        $success = array('success' => false);
        $youtube_video = $this->get_youtube_vod_id($pid, $eid);
        $access_token = $this->validate_youtube_token($pid);
        if ($access_token['success']) {
            $updateMetaData = $this->google_client_api->updateVodMetaData($pid, $access_token['access_token'], $youtube_video['videoId'], $name, $desc);
            if ($updateMetaData['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'YouTube: Could not update metadata');
            }
        } else {
            $success = array('success' => false, 'message' => 'YouTube: invalid access token');
        }
        return $success;
    }

    public function get_live_entry_platforms_status($pid, $eid) {
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
                    if ($platform['platform'] == 'twitch') {
                        $platforms_status['twitch'] = $platform['status'];
                    }
                }
            }
            $success = array('success' => true, 'platforms_status' => $platforms_status);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_vod_entry_platforms_status($pid, $eid) {
        $success = array('success' => false);
        $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
        if ($partnerData['success']) {
            $temp_partnerData = json_decode($partnerData['partnerData'], true);
            $platforms_status = array();
            if (isset($temp_partnerData['snConfig'])) {
                foreach ($temp_partnerData['snConfig'] as $platform) {
                    if ($platform['platform'] == 'facebook') {
                        $platforms_status['facebook'] = $platform['status'];
                    }
                    if ($platform['platform'] == 'youtube') {
                        $platforms_status['youtube'] = $platform['status'];
                    }
                    if ($platform['platform'] == 'twitch') {
                        $platforms_status['twitch'] = $platform['status'];
                    }
                }
            }
            $success = array('success' => true, 'platforms_status' => $platforms_status);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function upload_queued_video_to_youtube($pid, $eid) {
        $success = array('success' => false);
        $has_service = $this->verify_service($pid);
        if ($has_service) {
//            $platforms_status = $this->get_live_entry_platforms_status($pid, $eid);
//            if ($platforms_status['success']) {
//                if (count($platforms_status['platforms_status'])) {
//                    if ($platforms_status['platforms_status']['youtube']) {
            $success = $this->upload_youtube_video($pid, $eid);
//                    } else {
//                        $success = array('success' => true, 'message' => 'Social network: nothing to update');
//                    }
//                } else {
//                    $success = array('success' => true, 'message' => 'Social network config not present');
//                }
//            } else {
//                $success = array('success' => false, 'message' => 'Could not get platforms status');
//            }
        } else {
            $success = array('success' => false, 'message' => 'Social network service not active');
        }
        return $success;
    }

    public function upload_twitch_video($pid, $entry_details, $video_path) {
        $success = array('success' => false);
        $access_token = $this->validate_twitch_token($pid);
        if ($access_token['success']) {
            $channel_details = $this->get_twch_channel_details($pid);
            $upload_video = $this->twitch_client_api->uploadVideo($access_token['access_token'], $channel_details['channel_details']['channel_id'], $entry_details['name'], $entry_details['desc'], $video_path);
            if ($upload_video['success']) {
                $success = array('success' => true, 'videoId' => $upload_video['videoId']);
            } else {
                $success = array('success' => false, 'message' => 'Twitch: could not upload video');
            }
        } else {
            $success = array('success' => false, 'message' => 'Twitch: invalid access token');
        }
        return $success;
    }

    public function upload_youtube_video($pid, $entry_details, $video_path) {
        $success = array('success' => false);
        $access_token = $this->validate_youtube_token($pid);
        if ($access_token['success']) {
            $upload_video = $this->google_client_api->uploadVideo($pid, $access_token['access_token'], $entry_details['name'], $entry_details['desc'], $video_path);
            if ($upload_video['success']) {
                $success = array('success' => true, 'videoId' => $upload_video['videoId']);
            } else {
                $success = array('success' => false, 'message' => 'YouTube: could not upload video');
            }
        } else {
            $success = array('success' => false, 'message' => 'YouTube: invalid access token');
        }
        return $success;
    }

    public function remove_youtube_video($pid, $videoId) {
        $success = array('success' => false);
        $access_token = $this->validate_youtube_token($pid);
        if ($access_token['success']) {
            $remove_video = $this->google_client_api->removeVideo($pid, $access_token['access_token'], $videoId);
            if ($remove_video['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'YouTube: could not remove video');
            }
        } else {
            $success = array('success' => false, 'message' => 'YouTube: invalid access token');
        }
        return $success;
    }

    public function remove_facebook_video($pid, $videoId) {
        $success = array('success' => false);
        $access_token = $this->validate_facebook_token($pid);
        if ($access_token['success']) {
            $remove_video = $this->facebook_client_api->removeVideo($pid, $access_token['access_token'], $videoId);
            if ($remove_video['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Facebook: could not remove video');
            }
        } else {
            $success = array('success' => false, 'message' => 'Facebook: invalid access token');
        }
        return $success;
    }

    public function remove_twitch_video($pid, $videoId) {
        $success = array('success' => false);
        $access_token = $this->validate_twitch_token($pid);
        if ($access_token['success']) {
            $remove_video = $this->twitch_client_api->removeVideo($access_token['access_token'], $videoId);
            if ($remove_video['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Twitch: could not remove video');
            }
        } else {
            $success = array('success' => false, 'message' => 'Twitch: invalid access token');
        }
        return $success;
    }

    public function delete_sn_entry($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_vod_entry_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $success = $this->delete_youtube_vod_entry($pid, $eid);
                        }
                        if ($platforms_status['platforms_status']['facebook']) {
                            $success = $this->delete_facebook_vod_entry($pid, $eid);
                        }
                        if ($platforms_status['platforms_status']['twitch']) {
                            $success = $this->delete_twitch_vod_entry($pid, $eid);
                        }
                        if (!$platforms_status['platforms_status']['youtube'] && !$platforms_status['platforms_status']['facebook'] && !$platforms_status['platforms_status']['twitch']) {
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

    public function delete_twitch_vod_entry($pid, $eid) {
        $success = array('success' => false);
        $twitch_video = $this->get_twitch_vod_id($pid, $eid);
        $remove_twitch_video = $this->remove_twitch_video($pid, $twitch_video['videoId']);
        if ($remove_twitch_video['success']) {
            $remove_db_twitch_vod_entry = $this->remove_db_twitch_vod_entry($pid, $eid);
            if ($remove_db_twitch_vod_entry['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Could not remove Twitch video');
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not delete Twitch video');
        }
        return $success;
    }

    public function delete_facebook_vod_entry($pid, $eid) {
        $success = array('success' => false);
        $facebook_video = $this->get_facebook_vod_id($pid, $eid);
        $remove_facebook_video = $this->remove_facebook_video($pid, $facebook_video['videoId']);
        if ($remove_facebook_video['success']) {
            $remove_db_facebook_vod_entry = $this->remove_db_facebook_vod_entry($pid, $eid);
            if ($remove_db_facebook_vod_entry['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Could not remove Facebook video');
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not delete Facebook video');
        }
        return $success;
    }

    public function delete_youtube_vod_entry($pid, $eid) {
        $success = array('success' => false);
        $youtube_video = $this->get_youtube_vod_id($pid, $eid);
        $remove_youtube_video = $this->remove_youtube_video($pid, $youtube_video['videoId']);
        if ($remove_youtube_video['success']) {
            $remove_db_youtube_vod_entry = $this->remove_db_youtube_vod_entry($pid, $eid);
            if ($remove_db_youtube_vod_entry['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Could not remove YouTube video');
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not delete YouTube video');
        }
        return $success;
    }

    public function upload_facebook_video($pid, $entry_details, $video_path) {
        $success = array('success' => false);
        $access_token = $this->validate_facebook_token($pid);
        if ($access_token['success']) {
            $get_user_settings = $this->get_facebook_publish_settings($pid);
            if ($get_user_settings['success']) {
                $get_asset = $this->get_asset($pid, $get_user_settings['userSettings'][0]['publish_to'], $get_user_settings['userSettings'][0]['asset_id'], $access_token['access_token']);
                if ($get_asset['success']) {
                    $upload_video = $this->facebook_client_api->uploadVideo($pid, $get_asset['asset'], $entry_details['name'], $entry_details['desc'], $get_user_settings['userSettings'][0]['privacy'], $video_path);
                    if ($upload_video['success']) {
                        $success = array('success' => true, 'videoId' => $upload_video['videoId']);
                    } else {
                        $success = array('success' => false, 'message' => 'Facebook: could not upload video');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get asset Id');
                }
            } else {
                $success = array('success' => false, 'message' => 'Could not get Facebook user settings');
            }
        } else {
            $success = array('success' => false, 'message' => 'Facebook: invalid access token');
        }
        return $success;
    }

    public function update_sn_thumbnail($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_live_entry_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $success = $this->update_youtube_thumbnail($pid, $eid, $ks);
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

    public function update_youtube_thumbnail($pid, $eid, $ks) {
        $success = array('success' => false);
        $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
        if ($youtube_ids['success']) {
            $access_token = $this->validate_youtube_token($pid);
            if ($access_token['success']) {
                $thumbnail = $this->smportal->get_default_thumb($pid, $eid, $ks);
                $updateThumbnail = $this->google_client_api->updateThumbnail($pid, $access_token['access_token'], $youtube_ids['bid'], $thumbnail);
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
        return $success;
    }

    public function delete_sn_livestream($pid, $ks, $eid) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms_status = $this->get_live_entry_platforms_status($pid, $eid);
                if ($platforms_status['success']) {
                    if (count($platforms_status['platforms_status'])) {
                        if ($platforms_status['platforms_status']['youtube']) {
                            $success = $this->delete_youtube_livestream($pid, $eid);
                        }
                        if ($platforms_status['platforms_status']['facebook']) {
                            $success = $this->delete_facebook_livestream($pid, $eid);
                        }
                        if ($platforms_status['platforms_status']['twitch']) {
                            $success = $this->remove_twch_channel_entry($pid, $eid);
                        }
                        if (!$platforms_status['platforms_status']['youtube'] && !$platforms_status['platforms_status']['facebook'] && !$platforms_status['platforms_status']['twitch']) {
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

    public function delete_youtube_livestream($pid, $eid) {
        $success = array('success' => false);
        $youtube_ids = $this->get_youtube_event_ids($pid, $eid);
        if ($youtube_ids['success']) {
            $access_token = $this->validate_youtube_token($pid);
            if ($access_token['success']) {
                $removeLiveStream = $this->google_client_api->removeLiveStream($pid, $access_token['access_token'], $youtube_ids['bid'], $youtube_ids['lid']);
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
        return $success;
    }

    public function delete_facebook_livestream($pid, $eid) {
        $success = array('success' => false);
        $remove_live_entry = $this->remove_fb_live_entry($pid, $eid);
        if ($remove_live_entry['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false, 'message' => 'Could not insert Facebook Live Entry');
        }
        return $success;
    }

    public function get_live_platforms($json) {
        $result = array();
        $result['platforms'] = array();
        foreach ($json as $key => $value) {
            if ($key == 'snConfig') {
                $result['snConfig'] = true;
                foreach ($value as $platforms) {
                    if ($platforms->status) {
                        if ($platforms->liveId) {
                            $platform = array('platform' => $platforms->platform, 'status' => $platforms->status, 'liveId' => $platforms->liveId);
                            array_push($result['platforms'], $platform);
                        } else {
                            $platform = array('platform' => $platforms->platform, 'status' => $platforms->status);
                            array_push($result['platforms'], $platform);
                        }
                    } else {
                        $platform = array('platform' => $platforms->platform, 'status' => $platforms->status);
                        array_push($result['platforms'], $platform);
                    }
                }
            }
        }
        return $result;
    }

    public function get_vod_platforms($json) {
        $result = array();
        $result['platforms'] = array();
        foreach ($json as $key => $value) {
            if ($key == 'snConfig') {
                $result['snConfig'] = true;
                foreach ($value as $platforms) {
                    if ($platforms->status) {
                        $platform = array('platform' => $platforms->platform, 'status' => $platforms->status, 'upload_status' => $platforms->upload_status, 'videoId' => $platforms->videoId);
                        array_push($result['platforms'], $platform);
                    } else {
                        $platform = array('platform' => $platforms->platform, 'status' => $platforms->status);
                        array_push($result['platforms'], $platform);
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
                $twitch_status = $this->get_twitch_status($pid);
                if ($youtube_status['status'] || $facebook_status['status'] || $twitch_status['status']) {
                    $platforms_status = $this->get_live_entry_platforms_status($pid, $eid);
                    if ($platforms_status['success']) {
                        if (count($platforms_status['platforms_status'])) {
                            array_push($platforms, array('platform' => 'edgecast', 'status' => $platforms_status['platforms_status']['smh']));
                            $entry_details = $this->smportal->get_entry_details($pid, $eid);

                            $build_fb_ingestion = $this->build_fb_ingestion($pid, $eid, $entry_details, $facebook_status, $platforms_status);
                            if ($build_fb_ingestion['success']) {
                                array_push($platforms, $build_fb_ingestion['fb_platform']);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not build Facebook Ingestion');
                            }

                            $build_yt_ingestion = $this->build_yt_ingestion($pid, $eid, $youtube_status, $platforms_status);
                            if ($build_yt_ingestion['success']) {
                                array_push($platforms, $build_yt_ingestion['yt_platform']);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not build YouTube Ingestion');
                            }

                            $build_twch_ingestion = $this->build_twch_ingestion($pid, $eid, $entry_details, $twitch_status, $platforms_status);
                            if ($build_twch_ingestion['success']) {
                                array_push($platforms, $build_twch_ingestion['twch_platform']);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not build Twitch Ingestion');
                            }

                            $success = array('success' => true, 'platforms' => $platforms, 'multiBitrate' => $multiBitrate_status);
                        } else {
                            array_push($platforms, array('platform' => 'edgecast', 'status' => true));
                            array_push($platforms, array('platform' => 'facebook', 'status' => false));
                            array_push($platforms, array('platform' => 'youtube', 'status' => false));
                            array_push($platforms, array('platform' => 'twitch', 'status' => false));
                            $success = array('success' => true, 'platforms' => $platforms, 'multiBitrate' => $multiBitrate_status);
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get platforms status');
                    }
                } else {
                    array_push($platforms, array('platform' => 'edgecast', 'status' => true));
                    array_push($platforms, array('platform' => 'facebook', 'status' => false));
                    array_push($platforms, array('platform' => 'youtube', 'status' => false));
                    array_push($platforms, array('platform' => 'twitch', 'status' => false));
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

    public function build_twch_ingestion($pid, $eid, $entry_details, $twitch_status, $platforms_status) {
        $success = array('success' => false);
        $twch_platform = array();
        if ($twitch_status['status']) {
            if ($platforms_status['platforms_status']['twitch']) {
                $ingestionSettings = $this->get_twitch_ingestion_settings($pid);
                if ($ingestionSettings['success']) {
                    $update_twch_ls_details = $this->update_twch_ls_details($pid, $entry_details);
                    if ($update_twch_ls_details['success']) {
                        $update_twitch_ls_status = $this->update_twitch_ls_status($pid, 'live');
                        if ($update_twitch_ls_status['success']) {
                            array_push($twch_platform, array('platform' => 'twitch', 'status' => $platforms_status['platforms_status']['twitch'], 'ingestionSettings' => $ingestionSettings['ingestionSettings']));
                        } else {
                            $success = array('success' => false, 'message' => 'Could not update twitch live stream status');
                        }
                    } else {
                        array_push($twch_platform, array('platform' => 'twitch', 'status' => false));
                    }
                } else {
                    array_push($twch_platform, array('platform' => 'twitch', 'status' => false));
                }
            } else {
                array_push($twch_platform, array('platform' => 'twitch', 'status' => false));
            }
        } else {
            array_push($twch_platform, array('platform' => 'twitch', 'status' => false));
        }
        $success = array('success' => true, 'twch_platform' => $twch_platform[0]);
        return $success;
    }

    public function get_twitch_ingestion_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('twitch_channel_streams')
                ->where('partner_id', $pid);

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

    public function update_twitch_ls_status($pid, $status) {
        $success = array('success' => false);
        $data = array(
            'status' => $status,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->update('twitch_channel_streams', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function update_twch_ls_details($pid, $entry_details) {
        $success = array('success' => false);
        $access_token = $this->validate_twitch_token($pid);
        if ($access_token['success']) {
            $twch_channel_details = $this->get_twch_channel_details($pid);
            $updateLiveStream = $this->twitch_client_api->updateChannel($twch_channel_details['channel_details']['channel_id'], $entry_details['name'], $access_token['access_token']);
            if ($updateLiveStream['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Twitch: Could not update live stream');
            }
        } else {
            $success = array('success' => false, 'message' => 'Twitch: invalid access token');
        }
        return $success;
    }

    public function build_fb_ingestion($pid, $eid, $entry_details, $facebook_status, $platforms_status) {
        $success = array('success' => false);
        $fb_platform = array();
        if ($facebook_status['status']) {
            if ($platforms_status['platforms_status']['facebook']) {
                $update_fb_ls_details = $this->update_fb_ls_details($pid, $entry_details);
                if ($update_fb_ls_details['success']) {
                    $ingestionSettings = $this->get_facebook_ingestion_settings($pid);
                    if ($ingestionSettings['success']) {
                        $update_facebook_ls_status = $this->update_facebook_ls_status($pid, 'live');
                        if ($update_facebook_ls_status['success']) {
                            array_push($fb_platform, array('platform' => 'facebook', 'status' => $platforms_status['platforms_status']['facebook'], 'ingestionSettings' => $ingestionSettings['ingestionSettings']));
                        } else {
                            $success = array('success' => false, 'message' => 'Could not update facebook live stream status');
                        }
                    } else {
                        array_push($fb_platform, array('platform' => 'facebook', 'status' => false));
                    }
                } else {
                    array_push($fb_platform, array('platform' => 'facebook', 'status' => false));
                }
            } else {
                array_push($fb_platform, array('platform' => 'facebook', 'status' => false));
            }
        } else {
            array_push($fb_platform, array('platform' => 'facebook', 'status' => false));
        }
        $success = array('success' => true, 'fb_platform' => $fb_platform[0]);
        return $success;
    }

    public function update_fb_ls_details($pid, $entry_details) {
        $success = array('success' => false);
        $get_fb_livestream = $this->get_fb_livestream($pid);
        if ($get_fb_livestream['success']) {
            $access_token = $this->validate_facebook_token($pid);
            if ($access_token['success']) {
                $updateLiveStream = $this->facebook_client_api->updateLiveStream($pid, $get_fb_livestream['live_id'], $entry_details['name'], $entry_details['desc'], $access_token['access_token']);
                if ($updateLiveStream['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => 'Facebook: Could not update live stream');
                }
            } else {
                $success = array('success' => false, 'message' => 'Facebook: invalid access token');
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not get Facebook live stream id');
        }
        return $success;
    }

    public function build_yt_ingestion($pid, $eid, $youtube_status, $platforms_status) {
        $success = array('success' => false);
        $yt_platform = array();
        if ($youtube_status['status']) {
            if ($platforms_status['platforms_status']['youtube']) {
                $ingestionSettings = $this->get_youtube_ingestion_settings($pid, $eid);
                if ($ingestionSettings['success']) {
                    $insert_youtube_entry = $this->insert_youtube_entry($pid, $eid, 'ready');
                    if ($insert_youtube_entry['success']) {
                        array_push($yt_platform, array('platform' => 'youtube', 'status' => $platforms_status['platforms_status']['youtube'], 'ingestionSettings' => $ingestionSettings['ingestionSettings']));
                    } else {
                        $success = array('success' => false, 'message' => 'Could not insert youtube entry');
                    }
                } else {
                    array_push($yt_platform, array('platform' => 'youtube', 'status' => false));
                }
            } else {
                array_push($yt_platform, array('platform' => 'youtube', 'status' => false));
            }
        } else {
            array_push($yt_platform, array('platform' => 'youtube', 'status' => false));
        }
        $success = array('success' => true, 'yt_platform' => $yt_platform[0]);
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
            'created_at' => date("Y-m-d H:i:s")
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
            'updated_at' => date("Y-m-d H:i:s")
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
            'updated_at' => date("Y-m-d H:i:s")
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

    public function sn_routine() {
        $success = array('success' => false);
        $run_live_routine = $this->run_live_routine();
        if ($run_live_routine['success']) {
            $run_vod_routine = $this->run_vod_routine();
            if ($run_vod_routine['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => $run_vod_routine['message']);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function run_live_routine() {
        $success = array('success' => false);
        $check_youtube_live_entries = $this->check_youtube_live_entries();
        if ($check_youtube_live_entries['success']) {
            $check_facebook_livestreams = $this->check_facebook_livestreams();
            if ($check_facebook_livestreams['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        }
        return $success;
    }

    public function run_vod_routine() {
        $success = array('success' => false);
        $process_pending_uploads = $this->process_pending_completed_uploads();
        if ($process_pending_uploads['success']) {
            $process_upload_queue = $this->process_upload_queue();
            if ($process_upload_queue['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => $process_upload_queue['message']);
            }
        }
        return $success;
    }

    public function process_pending_completed_uploads() {
        date_default_timezone_set('America/Los_Angeles');
        $success = array('success' => false);
        $uploads = $this->get_uploads();
        if ($uploads['success']) {
            if (count($uploads['pending_entries']) > 0) {
                foreach ($uploads['pending_entries'] as $pending) {
                    $entry = $this->smportal->get_entry_details($pending['pid'], $pending['eid']);
                    if ($entry['status'] == 2) {
                        $this->update_upload_queue_status($pending['pid'], $pending['eid'], $pending['platform'], 'ready');
                    }
                }
            }
            if (count($uploads['completed_entries']) > 0) {
                foreach ($uploads['completed_entries'] as $completed) {
                    $date = strtotime($completed['created_at']);
                    $dateOneWeekAgo = strtotime("-1 week");
                    if ($date <= $dateOneWeekAgo) {
                        $this->removeCompletedQueuedUploadEntry($completed['pid'], $completed['eid']);
                    }
                }
            }
            $success = array('success' => true);
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function removeQueuedPlatformUploadEntry($pid, $eid, $platform) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->where('platform', $platform);
        $this->config->delete('upload_queue');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function removeCompletedQueuedUploadEntry($pid, $eid) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->delete('upload_queue');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function process_upload_queue() {
        $success = array('success' => false);
        $currently_uploading = $this->check_if_uploading();
        if (!$currently_uploading) {
            $get_ready_upload = $this->get_ready_upload();
            if (count($get_ready_upload['ready_upload']) > 0) {
                $entry_details = $this->smportal->get_entry_details($get_ready_upload['ready_upload']['pid'], $get_ready_upload['ready_upload']['eid']);
                $entry_path = $this->smportal->get_entry_path($get_ready_upload['ready_upload']['pid'], $get_ready_upload['ready_upload']['eid']);
                if ($get_ready_upload['ready_upload']['platform'] == 'youtube') {
                    $process_youtube_upload_queue = $this->process_youtube_upload_queue($get_ready_upload['ready_upload']['pid'], $get_ready_upload['ready_upload']['eid'], $get_ready_upload['ready_upload']['projection'], $entry_details, $entry_path);
                    if ($process_youtube_upload_queue['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => $process_youtube_upload_queue['message']);
                    }
                } else if ($get_ready_upload['ready_upload']['platform'] === 'facebook') {
                    $process_facebook_upload_queue = $this->process_facebook_upload_queue($get_ready_upload['ready_upload']['pid'], $get_ready_upload['ready_upload']['eid'], $get_ready_upload['ready_upload']['projection'], $entry_details, $entry_path);
                    if ($process_facebook_upload_queue['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => $process_facebook_upload_queue['message']);
                    }
                } else if ($get_ready_upload['ready_upload']['platform'] === 'twitch') {
                    $process_twitch_upload_queue = $this->process_twitch_upload_queue($get_ready_upload['ready_upload']['pid'], $get_ready_upload['ready_upload']['eid'], $entry_details, $entry_path);
                    if ($process_twitch_upload_queue['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => $process_twitch_upload_queue['message']);
                    }
                }
            } else {
                $success = array('success' => true);
            }
        } else {
            $success = array('success' => true);
        }

        return $success;
    }

    public function getEntryVrSettings($pid, $eid) {
        $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
        $vr_settings = json_decode($partnerData['partnerData']);
        $result = array();
        $result['vrSettings'] = false;
        $result['settings'] = array();
        foreach ($vr_settings as $key => $value) {
            if ($key == 'vrSettings') {
                $result['vrSettings'] = true;
                foreach ($value as $setting) {
                    $result['settings']['stereo_mode'] = $setting->stereo_mode;
                }
            }
        }
        return $result;
    }

    public function process_facebook_upload_queue($pid, $eid, $projection, $entry_details, $entry_path) {
        $success = array('success' => false);
        if ($projection == '360') {
            $entryVrSettings = $this->getEntryVrSettings($pid, $eid);
            $stereo_mode = '';
            if ($entryVrSettings['vrSettings']) {
                $stereo_mode = $entryVrSettings['settings']['stereo_mode'];
            } else {
                $stereo_mode = 'none';
            }
            ob_start();
            passthru('/usr/bin/python2.7 /var/www/vhosts/api/application/libraries/spatial-media/spatialmedia -i --stereo ' . $stereo_mode . ' ' . $entry_path['original_path'] . ' ' . $entry_path['threesixty_tmp_path']);
            ob_get_clean();
            $video_path = $entry_path['threesixty_tmp_path'];
        } else {
            $video_path = $entry_path['original_path'];
        }
        $update_facebook_upload_status = $this->update_platform_upload_status($pid, $eid, 'facebook', 'uploading', 'pending');
        if ($update_facebook_upload_status['success']) {
            $upload_facebook_video = $this->upload_facebook_video($pid, $entry_details, $video_path);
            if ($upload_facebook_video['success']) {
                $this->config = $this->load->database('sn_dev', TRUE);
                $this->config->reconnect();
                $insert_entry_to_facebook_vod = $this->insert_entry_to_facebook_vod($pid, $eid, $upload_facebook_video['videoId'], $projection);
                if ($insert_entry_to_facebook_vod['success']) {
                    $update_facebook_upload_status = $this->update_platform_upload_status($pid, $eid, 'facebook', 'completed', $upload_facebook_video['videoId']);
                    if ($update_facebook_upload_status['success']) {
                        if ($projection == '360') {
                            $res = @unlink($entry_path['threesixty_tmp_path']);
                            if ($res) {
                                $success = array('success' => true);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not delete temp 360 file');
                            }
                        } else {
                            $success = array('success' => true);
                        }
                    } else {
                        $success = array('success' => false, 'message' => $update_facebook_upload_status['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert entry into Facebook vod');
                }
            } else {
                $success = array('success' => false, 'message' => 'Could not upload video to Facebook');
            }
        } else {
            $success = array('success' => false, 'message' => $update_facebook_upload_status['message']);
        }
        return $success;
    }

    public function process_youtube_upload_queue($pid, $eid, $projection, $entry_details, $entry_path) {
        $success = array('success' => false);
        if ($projection == '360') {
            $entryVrSettings = $this->getEntryVrSettings($pid, $eid);
            $stereo_mode = '';
            if ($entryVrSettings['vrSettings']) {
                $stereo_mode = $entryVrSettings['settings']['stereo_mode'];
            } else {
                $stereo_mode = 'none';
            }
            ob_start();
            passthru('/usr/bin/python2.7 /var/www/vhosts/api/application/libraries/spatial-media/spatialmedia -i --stereo ' . $stereo_mode . ' ' . $entry_path['original_path'] . ' ' . $entry_path['threesixty_tmp_path']);
            ob_get_clean();
            $video_path = $entry_path['threesixty_tmp_path'];
        } else {
            $video_path = $entry_path['original_path'];
        }
        $update_youtube_upload_status = $this->update_platform_upload_status($pid, $eid, 'youtube', 'uploading', 'pending');
        if ($update_youtube_upload_status['success']) {
            $upload_youtube_video = $this->upload_youtube_video($pid, $entry_details, $video_path);
            if ($upload_youtube_video['success']) {
                $this->config = $this->load->database('sn_dev', TRUE);
                $this->config->reconnect();
                $insert_entry_to_youtube_vod = $this->insert_entry_to_youtube_vod($pid, $eid, $upload_youtube_video['videoId'], $projection);
                if ($insert_entry_to_youtube_vod['success']) {
                    $update_youtube_upload_status = $this->update_platform_upload_status($pid, $eid, 'youtube', 'completed', $upload_youtube_video['videoId']);
                    if ($update_youtube_upload_status['success']) {
                        if ($projection == '360') {
                            $res = @unlink($entry_path['threesixty_tmp_path']);
                            if ($res) {
                                $success = array('success' => true);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not delete temp 360 file');
                            }
                        } else {
                            $success = array('success' => true);
                        }
                    } else {
                        $success = array('success' => false, 'message' => $update_youtube_upload_status['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert entry into YouTube vod');
                }
            } else {
                $success = array('success' => false, 'message' => 'Could not upload video to YouTube');
            }
        } else {
            $success = array('success' => false, 'message' => $update_youtube_upload_status['message']);
        }
        return $success;
    }

    public function process_twitch_upload_queue($pid, $eid, $entry_details, $entry_path) {
        $success = array('success' => false);
        $video_path = $entry_path['original_path'];
        $update_twitch_upload_status = $this->update_platform_upload_status($pid, $eid, 'twitch', 'uploading', 'pending');
        if ($update_twitch_upload_status['success']) {
            $upload_twitch_video = $this->upload_twitch_video($pid, $entry_details, $video_path);
            if ($upload_twitch_video['success']) {
                $this->config = $this->load->database('sn_dev', TRUE);
                $this->config->reconnect();
                $insert_entry_to_twitch_vod = $this->insert_entry_to_twitch_vod($pid, $eid, $upload_twitch_video['videoId']);
                if ($insert_entry_to_twitch_vod['success']) {
                    $update_twitch_upload_status = $this->update_platform_upload_status($pid, $eid, 'twitch', 'completed', $upload_twitch_video['videoId']);
                    if ($update_twitch_upload_status['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => $update_twitch_upload_status['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert entry into Twitch vod');
                }
            } else {
                $success = array('success' => false, 'message' => 'Could not upload video to Twitch');
            }
        } else {
            $success = array('success' => false, 'message' => $update_twitch_upload_status['message']);
        }
        return $success;
    }

    public function update_platform_upload_status($pid, $eid, $sn_platform, $upload_status, $videoId) {
        $config = array();
        $vr = array();
        $vr['vrSettings'] = false;
        $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
        $vod_platforms = $this->get_vod_platforms(json_decode($partnerData['partnerData']));
        foreach ($vod_platforms['platforms'] as $platform) {
            if ($platform['platform'] !== $sn_platform) {
                if ($platform['status']) {
                    $platform_config = $this->create_vod_sn_config($platform['platform'], true, $platform['upload_status'], $platform['videoId']);
                    array_push($config, $platform_config['config']);
                } else {
                    $platform_config = $this->create_vod_sn_config($platform['platform'], false, null, null);
                    array_push($config, $platform_config['config']);
                }
            }
        }
        $create_config = $this->create_vod_sn_config($sn_platform, true, $upload_status, $videoId);
        array_push($config, $create_config['config']);

        $partnerData = $this->update_sn_partnerData($pid, $eid, $config, $vr);
        if ($partnerData['success']) {
            $update_upload_queue_status = $this->update_upload_queue_status($pid, $eid, $sn_platform, $upload_status);
            if ($update_upload_queue_status['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Could not update upload status');
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not update entry partnerData');
        }

        return $success;
    }

    public function update_sn_vod_config($pid, $ks, $eid, $snConfig, $projection, $stereo_mode) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $platforms = json_decode($snConfig, true);
                $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
                $vod_platforms = $this->get_vod_platforms(json_decode($partnerData['partnerData']));
                $youtube_status = false;
                $facebook_status = false;
                $twitch_status = false;
                $config = array();
                $vr = array();
                $vr['vrSettings'] = false;
                foreach ($platforms['platforms'] as $platform) {
                    if ($platform['platform'] == 'facebook') {
                        if ($platform['status']) {
                            $facebook_status = true;
                        }
                    } else if ($platform['platform'] == 'youtube') {
                        if ($platform['status']) {
                            $youtube_status = true;
                        }
                    } else if ($platform['platform'] == 'twitch') {
                        if ($platform['status']) {
                            $twitch_status = true;
                        }
                    }
                }

                $facebook_config = $this->process_facebook_vod_config($pid, $eid, $projection, $facebook_status, $vod_platforms);
                array_push($config, $facebook_config);

                $youtube_config = $this->process_youtube_vod_config($pid, $eid, $projection, $youtube_status, $vod_platforms);
                array_push($config, $youtube_config);

                $twitch_config = $this->process_twitch_vod_config($pid, $eid, 'rectangular', $twitch_status, $vod_platforms);
                array_push($config, $twitch_config);

                if ($stereo_mode != '') {
                    $vr['vrSettings'] = true;
                    $vr['settings'] = array();
                    $settings = array('stereo_mode' => $stereo_mode);
                    array_push($vr['settings'], $settings);
                } else {
                    $vr['vrSettings'] = false;
                }

                $partnerData = $this->update_sn_partnerData($pid, $eid, $config, $vr);
                if ($partnerData['success']) {
                    $success = array('success' => true);
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

    public function process_facebook_vod_config($pid, $eid, $projection, $facebook_status, $vod_platforms) {
        $config = array();
        if (count($vod_platforms['platforms']) > 0) {
            $current_facebook_status = '';
            $current_facebook_upload_status = '';
            $current_facebook_videoId = '';
            foreach ($vod_platforms['platforms'] as $platform) {
                if ($platform['platform'] == 'facebook') {
                    $current_facebook_status = $platform['status'];
                    $current_facebook_upload_status = $platform['upload_status'];
                    $current_facebook_videoId = $platform['videoId'];
                }
            }
            $facebook_upload_exists = $this->check_if_upload_queue_exists($pid, $eid, 'facebook');
            $facebook_vod_exists = $this->check_if_facebook_vod_exists($pid, $eid);
            if ($facebook_status) {
                if (!$facebook_upload_exists && !$facebook_vod_exists) {
                    $facebook_config = $this->create_vod_sn_config('facebook', $facebook_status, 'ready', 'pending');
                    array_push($config, $facebook_config['config']);
                    $this->insert_video_to_upload_queue($pid, $eid, $projection, 'facebook', 'ready');
                } else {
                    $facebook_config = $this->create_vod_sn_config('facebook', $current_facebook_status, $current_facebook_upload_status, $current_facebook_videoId);
                    array_push($config, $facebook_config['config']);
                }
            } else if (!$facebook_status) {
                $is_uploading = $this->check_if_platform_uploading($eid, 'facebook');
                if ($is_uploading) {
                    $facebook_config = $this->create_vod_sn_config('facebook', $current_facebook_status, $current_facebook_upload_status, $current_facebook_videoId);
                    array_push($config, $facebook_config['config']);
                } else {
                    if ($facebook_upload_exists) {
                        $this->removeQueuedPlatformUploadEntry($pid, $eid, 'facebook');
                    }
                    if ($facebook_vod_exists) {
                        $facebook_video = $this->get_facebook_vod_id($pid, $eid);
                        $remove_facebook_video = $this->remove_facebook_video($pid, $facebook_video['videoId']);
                        if ($remove_facebook_video['success']) {
                            $this->remove_db_facebook_vod_entry($pid, $eid);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not delete Facebook video');
                        }
                    }
                    $facebook_config = $this->create_vod_sn_config('facebook', $facebook_status, null, null);
                    array_push($config, $facebook_config['config']);
                }
            }
        } else {
            if ($facebook_status) {
                $facebook_config = $this->create_vod_sn_config('facebook', $facebook_status, 'ready', 'pending');
                array_push($config, $facebook_config['config']);
                $this->insert_video_to_upload_queue($pid, $eid, $projection, 'facebook', 'ready');
            } else if (!$facebook_status) {
                $facebook_config = $this->create_vod_sn_config('facebook', $facebook_status, null, null);
                array_push($config, $facebook_config['config']);
            }
        }

        return $config[0];
    }

    public function process_youtube_vod_config($pid, $eid, $projection, $youtube_status, $vod_platforms) {
        $config = array();
        if (count($vod_platforms['platforms']) > 0) {
            $current_youtube_status = '';
            $current_youtube_upload_status = '';
            $current_youtube_videoId = '';
            foreach ($vod_platforms['platforms'] as $platform) {
                if ($platform['platform'] == 'youtube') {
                    $current_youtube_status = $platform['status'];
                    $current_youtube_upload_status = $platform['upload_status'];
                    $current_youtube_videoId = $platform['videoId'];
                }
            }
            $youtube_upload_exists = $this->check_if_upload_queue_exists($pid, $eid, 'youtube');
            $youtube_vod_exists = $this->check_if_youtube_vod_exists($pid, $eid);
            if ($youtube_status) {
                if (!$youtube_upload_exists && !$youtube_vod_exists) {
                    $youtube_config = $this->create_vod_sn_config('youtube', $youtube_status, 'ready', 'pending');
                    array_push($config, $youtube_config['config']);
                    $this->insert_video_to_upload_queue($pid, $eid, $projection, 'youtube', 'ready');
                } else {
                    $youtube_config = $this->create_vod_sn_config('youtube', $current_youtube_status, $current_youtube_upload_status, $current_youtube_videoId);
                    array_push($config, $youtube_config['config']);
                }
            } else if (!$youtube_status) {
                $is_uploading = $this->check_if_platform_uploading($eid, 'youtube');
                if ($is_uploading) {
                    $youtube_config = $this->create_vod_sn_config('youtube', $current_youtube_status, $current_youtube_upload_status, $current_youtube_videoId);
                    array_push($config, $youtube_config['config']);
                } else {
                    if ($youtube_upload_exists) {
                        $this->removeQueuedPlatformUploadEntry($pid, $eid, 'youtube');
                    }
                    if ($youtube_vod_exists) {
                        $youtube_video = $this->get_youtube_vod_id($pid, $eid);
                        $remove_youtube_video = $this->remove_youtube_video($pid, $youtube_video['videoId']);
                        if ($remove_youtube_video['success']) {
                            $this->remove_db_youtube_vod_entry($pid, $eid);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not delete YouTube video');
                        }
                    }
                    $youtube_config = $this->create_vod_sn_config('youtube', $youtube_status, null, null);
                    array_push($config, $youtube_config['config']);
                }
            }
        } else {
            if ($youtube_status) {
                $youtube_config = $this->create_vod_sn_config('youtube', $youtube_status, 'ready', 'pending');
                array_push($config, $youtube_config['config']);
                $this->insert_video_to_upload_queue($pid, $eid, $projection, 'youtube', 'ready');
            } else if (!$youtube_status) {
                $youtube_config = $this->create_vod_sn_config('youtube', $youtube_status, null, null);
                array_push($config, $youtube_config['config']);
            }
        }
        return $config[0];
    }

    public function process_twitch_vod_config($pid, $eid, $projection, $twitch_status, $vod_platforms) {
        $config = array();
        if (count($vod_platforms['platforms']) > 0) {
            $current_twitch_status = '';
            $current_twitch_upload_status = '';
            $current_twitch_videoId = '';
            foreach ($vod_platforms['platforms'] as $platform) {
                if ($platform['platform'] == 'twitch') {
                    $current_twitch_status = $platform['status'];
                    $current_twitch_upload_status = $platform['upload_status'];
                    $current_twitch_videoId = $platform['videoId'];
                }
            }
            $twitch_upload_exists = $this->check_if_upload_queue_exists($pid, $eid, 'twitch');
            $twitch_vod_exists = $this->check_if_twitch_vod_exists($pid, $eid);
            if ($twitch_status) {
                if (!$twitch_upload_exists && !$twitch_vod_exists) {
                    $twitch_config = $this->create_vod_sn_config('twitch', $twitch_status, 'ready', 'pending');
                    array_push($config, $twitch_config['config']);
                    $this->insert_video_to_upload_queue($pid, $eid, $projection, 'twitch', 'ready');
                } else {
                    $twitch_config = $this->create_vod_sn_config('twitch', $current_twitch_status, $current_twitch_upload_status, $current_twitch_videoId);
                    array_push($config, $twitch_config['config']);
                }
            } else if (!$twitch_status) {
                $is_uploading = $this->check_if_platform_uploading($eid, 'twitch');
                if ($is_uploading) {
                    $twitch_config = $this->create_vod_sn_config('twitch', $current_twitch_status, $current_twitch_upload_status, $current_twitch_videoId);
                    array_push($config, $twitch_config['config']);
                } else {
                    if ($twitch_upload_exists) {
                        $this->removeQueuedPlatformUploadEntry($pid, $eid, 'twitch');
                    }
                    if ($twitch_vod_exists) {
                        $twitch_video = $this->get_twitch_vod_id($pid, $eid);
                        $remove_twitch_video = $this->remove_twitch_video($pid, $twitch_video['videoId']);
                        if ($remove_twitch_video['success']) {
                            $this->remove_db_twitch_vod_entry($pid, $eid);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not delete Twitch video');
                        }
                    }
                    $twitch_config = $this->create_vod_sn_config('twitch', $twitch_status, null, null);
                    array_push($config, $twitch_config['config']);
                }
            }
        } else {
            if ($twitch_status) {
                $twitch_config = $this->create_vod_sn_config('twitch', $twitch_status, 'ready', 'pending');
                array_push($config, $twitch_config['config']);
                $this->insert_video_to_upload_queue($pid, $eid, $projection, 'twitch', 'ready');
            } else if (!$twitch_status) {
                $twitch_config = $this->create_vod_sn_config('twitch', $twitch_status, null, null);
                array_push($config, $twitch_config['config']);
            }
        }
        return $config[0];
    }

    public function create_vod_sn_config($platform, $status, $upload_status, $video_id) {
        $config = array();
        if ($status) {
            $config = array('platform' => $platform, 'status' => $status, "upload_status" => $upload_status, 'videoId' => $video_id);
        } else {
            $config = array('platform' => $platform, 'status' => $status);
        }
        $success = array('success' => true, 'config' => $config);
        return $success;
    }

    public function create_live_sn_config($platform, $status, $live_id) {
        $config = array();
        if ($status && $live_id) {
            $config = array('platform' => $platform, 'status' => $status, 'liveId' => $live_id);
        } else if ($status && !$live_id) {
            $config = array('platform' => $platform, 'status' => $status);
        } else {
            $config = array('platform' => $platform, 'status' => $status);
        }
        $success = array('success' => true, 'config' => $config);
        return $success;
    }

    public function insert_entry_to_twitch_vod($pid, $eid, $vid) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'videoId' => $vid,
            'created_at' => date("Y-m-d H:i:s")
        );
        $this->config->insert('twitch_vod_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_entry_to_youtube_vod($pid, $eid, $vid, $projection) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'videoId' => $vid,
            'projection' => $projection,
            'created_at' => date("Y-m-d H:i:s")
        );
        $this->config->insert('youtube_vod_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function insert_entry_to_facebook_vod($pid, $eid, $vid, $projection) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'videoId' => $vid,
            'projection' => $projection,
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('facebook_vod_entries', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_upload_queue_status($pid, $eid, $platform, $status) {
        $success = array('success' => false);
        $data = array(
            'status' => $status,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->where('platform', $platform);
        $this->config->update('upload_queue', $data);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => true, 'notice' => 'no changes were made');
        }
        return $success;
    }

    public function get_ready_upload() {
        $success = array('success' => false);
        $ready = array();
        $this->config->select('*')
                ->from('upload_queue')
                ->where('status', 'ready')
                ->order_by("id", "asc")
                ->limit(1);
        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $pid = $res['partner_id'];
                $eid = $res['entryId'];
                $projection = $res['projection'];
                $platform = $res['platform'];
            }
            $ready['pid'] = $pid;
            $ready['eid'] = $eid;
            $ready['platform'] = $platform;
            $ready['projection'] = $projection;
            $success = array('success' => true, 'ready_upload' => $ready);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_uploads() {
        $success = array('success' => false);
        $pending = array();
        $completed = array();
        $this->config->select('*')
                ->from('upload_queue');

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $pid = $res['partner_id'];
                $eid = $res['entryId'];
                $projection = $res['projection'];
                $platform = $res['platform'];
                $status = $res['status'];
                $created_at = $res['created_at'];
                if ($status == 'pending') {
                    array_push($pending, array('pid' => $pid, 'eid' => $eid, 'projection' => $projection, 'platform' => $platform));
                } else if ($status == 'completed') {
                    array_push($completed, array('pid' => $pid, 'eid' => $eid, 'projection' => $projection, 'platform' => $platform, 'created_at' => $created_at));
                }
            }
            $success = array('success' => true, 'pending_entries' => $pending, 'completed_entries' => $completed);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_facebook_livestreams() {
        $success = array('success' => false);
        $livestreams = $this->get_fb_livestreams();
        if ($livestreams['success']) {
            $update_expired_fb_livestreams = $this->update_expired_fb_livestreams($livestreams['livestreams']);
            if ($update_expired_fb_livestreams['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => true);
        }
        return $success;
    }

    public function update_expired_fb_livestreams($livestreams) {
        date_default_timezone_set('America/Los_Angeles');
        $success = array('success' => true);
        $dateTwentyThreeHoursAgo = strtotime("-23 hours");
        foreach ($livestreams as $livestream) {
            $date = strtotime($livestream['date']);
            if ($date <= $dateTwentyThreeHoursAgo) {
                $get_user_settings = $this->get_facebook_publish_settings($livestream['pid']);
                if ($get_user_settings['success']) {
                    $create_new_livestream = $this->create_new_fb_livestream($livestream['pid'], $get_user_settings['userSettings'][0]['publish_to'], $get_user_settings['userSettings'][0]['asset_id'], $get_user_settings['userSettings'][0]['privacy'], $get_user_settings['userSettings'][0]['create_vod'], $get_user_settings['userSettings'][0]['cont_streaming'], $get_user_settings['userSettings'][0]['projection']);
                    if ($create_new_livestream['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => $create_new_livestream['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get Facebook user settings');
                }
            }
        }
        return $success;
    }

    public function get_fb_livestreams() {
        $success = array('success' => false);
        $livestreams = array();
        $this->config->select('*')
                ->from('facebook_live_streams');

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $id = $res['id'];
                $pid = $res['partner_id'];
                $lid = $this->smcipher->decrypt($res['live_id']);
                $status = $res['status'];
                if ($res['updated_at']) {
                    $date = $res['updated_at'];
                } else {
                    $date = $res['created_at'];
                }
            }
            if ($status == 'ready') {
                array_push($livestreams, array('id' => $id, 'pid' => $pid, 'lid' => $lid, 'date' => $date));
            }
            $success = array('success' => true, 'livestreams' => $livestreams);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_youtube_live_entries() {
        $success = array('success' => false);
        $entries = $this->get_youtube_entries();
        if ($entries['success']) {
            if (count($entries['ready_entries'])) {
                $this->transitionLiveStream($entries['ready_entries'], 'live');
            }
            if (count($entries['complete_entries'])) {
                $this->removeCompletedEntries();
            }
        }
        $success = array('success' => true);
        return $success;
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

    public function removeLiveEntry($pid, $eid) {
        $success = array('success' => false);
        $this->config->where('partner_id', $pid);
        $this->config->where('entryId', $eid);
        $this->config->delete('youtube_live_entries');
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function removeReadyEntry($pid, $eid) {
        $success = array('success' => false);
        if ($this->check_youtube_entry($pid, $eid)) {
            $this->config->where('entryId', $eid);
            $this->config->where('status', 'ready');
            $this->config->delete('youtube_live_entries');
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

    public function transitionLiveStream($entries, $status) {
        $success = array('success' => false);
        foreach ($entries as $entry) {
            $pid = $entry['pid'];
            $eid = $entry['eid'];
            $event_ids = $this->get_youtube_event_ids($pid, $eid);
            if ($event_ids['success']) {
                $access_token = $this->validate_youtube_token($pid);
                if ($access_token['success']) {
                    $transitionLiveStream = $this->google_client_api->transitionLiveStream($pid, $access_token['access_token'], $event_ids['bid'], $status);
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
                    $liveStreamStatus = $this->google_client_api->getLiveStreamStatus($pid, $access_token['access_token'], $event_ids['bid']);
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
                if ($status == 'ready') {
                    array_push($ready, array('pid' => $pid, 'eid' => $eid, 'status' => $status));
                } else if ($status == 'complete') {
                    array_push($complete, array('pid' => $pid, 'eid' => $eid, 'status' => $status));
                }
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

    public function is_twitch_live($pid) {
        $success = false;
        $this->config->select('*')
                ->from('twitch_channel_streams')
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
            $is_twitch_live = $this->is_twitch_live($pid);
            if ($is_youtube_live) {
                $complete_youtube = $this->youtube_entry_complete($pid, $ks, $eid);
                if ($complete_youtube['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => $complete_youtube['message']);
                }
            } else {
                $removeReadyEntry = $this->removeReadyEntry($pid, $eid);
                if ($removeReadyEntry['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => 'Could not remove ready Entry');
                }
            }
            if ($is_facebook_live) {
                $get_user_settings = $this->get_facebook_publish_settings($pid);
                if ($get_user_settings['success']) {
                    $create_new_livestream = $this->create_new_fb_livestream($pid, $get_user_settings['userSettings'][0]['publish_to'], $get_user_settings['userSettings'][0]['asset_id'], $get_user_settings['userSettings'][0]['privacy'], $get_user_settings['userSettings'][0]['create_vod'], $get_user_settings['userSettings'][0]['cont_streaming'], $get_user_settings['userSettings'][0]['projection']);
                    if ($create_new_livestream['success']) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => $create_new_livestream['message']);
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Could not get Facebook user settings');
                }
            }
            if ($is_twitch_live) {
                $update_twitch_ls_status = $this->update_twitch_ls_status($pid, 'ready');
                if ($update_twitch_ls_status['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => 'Could not update Twitch live status');
                }
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function get_facebook_publish_settings($pid) {
        $success = array('success' => false);
        $this->config->select('*')
                ->from('facebook_publish_settings')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            $userSettings = array();
            foreach ($result as $res) {
                $publish_to = $res['publish_to'];
                $asset_id = $res['asset_Id'];
                $privacy = $res['privacy'];
                $create_vod = ($res['create_vod']) ? 'true' : 'false';
                $cont_streaming = ($res['cont_streaming']) ? 'true' : 'false';
                $auto_upload = ($res['auto_upload']) ? true : false;
                $projection = $res['projection'];
            }
            array_push($userSettings, array('asset_id' => $asset_id, 'publish_to' => $publish_to, 'privacy' => $privacy, 'create_vod' => $create_vod, 'cont_streaming' => $cont_streaming, 'auto_upload' => $auto_upload, 'projection' => $projection));
            $success = array('success' => true, 'userSettings' => $userSettings);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function create_new_fb_livestream($pid, $publish_to, $asset_id, $privacy, $create_vod, $cont_streaming, $projection) {
        $success = array('success' => false);
        $access_token = $this->validate_facebook_token($pid);
        if ($access_token['success']) {
            $get_asset = $this->get_asset($pid, $publish_to, $asset_id, $access_token['access_token']);
            if ($get_asset['success']) {
                $livestream = $this->facebook_client_api->createLiveStream($pid, $get_asset['asset'], $privacy, $create_vod, $cont_streaming, $projection);
                if ($livestream['success']) {
                    $add_fb_livestream = $this->add_fb_livestream($pid, $livestream['address'], $livestream['stream_name'], $livestream['embed_code'], $livestream['live_id']);
                    if ($add_fb_livestream['success']) {
                        $success = array('success' => true);
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
                $transitionLiveStream = $this->google_client_api->transitionLiveStream($pid, $access_token['access_token'], $event_ids['bid'], 'complete');
                if ($transitionLiveStream['success']) {
                    $updateLiveStreamStatus = $this->update_youtube_entry_status($pid, $eid, 'complete');
                    if ($updateLiveStreamStatus['success']) {
                        $youtube_embed = true;
                        $thumbnail = $this->smportal->get_default_thumb($pid, $eid, $ks);
                        $entry_details = $this->smportal->get_entry_details($pid, $eid);
                        $createNewBroadCast = $this->google_client_api->createNewBroadcast($pid, $access_token['access_token'], $event_ids['bid'], $event_ids['lid'], $entry_details['name'], $entry_details['desc'], $thumbnail, $event_ids['proj'], $youtube_embed);
                        if ($createNewBroadCast['success']) {
                            $updateBid = $this->update_youtube_live_event_bid($pid, $eid, $createNewBroadCast['liveBroadcastId']);
                            if ($updateBid['success']) {
                                $update_sn_live_config = $this->update_sn_live_config($pid, $eid, 'youtube_live', true, $createNewBroadCast['liveBroadcastId']);
                                if ($update_sn_live_config['success']) {
                                    $add_youtube_emebed_status = $this->add_youtube_emebed_status($pid, $youtube_embed);
                                    if ($add_youtube_emebed_status['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not update live configuration');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not update liveBroadcastId');
                            }
                        } else if (isset($createNewBroadCast['retry'])) {
                            $youtube_embed = false;
                            $createNewBroadCast = $this->google_client_api->createNewBroadcast($pid, $access_token['access_token'], $event_ids['bid'], $event_ids['lid'], $entry_details['name'], $entry_details['desc'], $thumbnail, $event_ids['proj'], $youtube_embed);
                            if ($createNewBroadCast['success']) {
                                $updateBid = $this->update_youtube_live_event_bid($pid, $eid, $createNewBroadCast['liveBroadcastId']);
                                if ($updateBid['success']) {
                                    $update_sn_live_config = $this->update_sn_live_config($pid, $eid, 'youtube_live', true, $createNewBroadCast['liveBroadcastId']);
                                    if ($update_sn_live_config['success']) {
                                        $add_youtube_emebed_status = $this->add_youtube_emebed_status($pid, $youtube_embed);
                                        if ($add_youtube_emebed_status['success']) {
                                            $success = array('success' => true);
                                        } else {
                                            $success = array('success' => false, 'message' => 'Could not update YouTube embed status');
                                        }
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not update live configuration');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not update liveBroadcastId');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Could not create and bind new broadcast');
                            }
                        } else if (isset($createNewBroadCast['blocked'])) {
                            $removeLiveStream = $this->google_client_api->removeLiveStreamObject($pid, $access_token['access_token'], $event_ids['bid'], $event_ids['lid']);
                            if ($removeLiveStream['success']) {
                                $removeLiveEvent = $this->removeLiveEvent($pid, $eid);
                                if ($removeLiveEvent['success']) {
                                    $update_sn_live_config = $this->update_sn_live_config($pid, $eid, 'youtube_live', false, null);
                                    if ($update_sn_live_config['success']) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false, 'message' => 'Could not update live configuration');
                                    }
                                } else {
                                    $success = array('success' => false, 'message' => 'Could not remove live event');
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'YouTube: Could not remove livestream');
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
                $removeLiveEntry = $this->removeLiveEntry($pid, $eid);
                if ($removeLiveEntry['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => 'Could not remove YouTube live entry');
                }
            }
        } else {
            $success = array('success' => false, 'message' => 'Could not get event ids');
        }

        return $success;
    }

    public function update_sn_live_config($pid, $eid, $sn_platform, $sn_status, $liveId) {
        $config = array();
        $vr = array();
        $vr['vrSettings'] = false;
        $partnerData = $this->smportal->get_entry_partnerData($pid, $eid);
        $live_platforms = $this->get_live_platforms(json_decode($partnerData['partnerData']));
        foreach ($live_platforms['platforms'] as $platform) {
            if ($platform['platform'] !== $sn_platform) {
                if ($platform['status']) {
                    if ($platform['liveId']) {
                        $platform_config = $this->create_live_sn_config($platform['platform'], true, $platform['liveId']);
                        array_push($config, $platform_config['config']);
                    } else {
                        $platform_config = $this->create_live_sn_config($platform['platform'], true, null);
                        array_push($config, $platform_config['config']);
                    }
                } else {
                    $platform_config = $this->create_vod_sn_config($platform['platform'], false, null);
                    array_push($config, $platform_config['config']);
                }
            }
        }
        $create_config = $this->create_live_sn_config($sn_platform, $sn_status, $liveId);
        array_push($config, $create_config['config']);

        $partnerData = $this->update_sn_partnerData($pid, $eid, $config, $vr);
        if ($partnerData['success']) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false, 'message' => 'Could not update entry partnerData');
        }

        return $success;
    }

    public function resync_twch_account($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $access_token = $this->validate_twitch_token($pid);
                if ($access_token['success']) {
                    $channel = $this->retrieve_twitch_channel_details($pid, $access_token['access_token']);
                    if ($channel['success']) {
                        $update_twitch_channel_details = $this->update_twitch_channel_details($pid, $channel['channel_details']['channel_name'], $channel['channel_details']['channel_id'], $channel['channel_details']['channel_logo']);
                        if ($update_twitch_channel_details['success']) {
                            $channel_stream = $this->twitch_client_api->get_channel_details($pid, $access_token['access_token']);
                            $update_twch_channel_stream = $this->update_twch_channel_stream($pid, $channel_stream['channel_stream']['ingestId'], $channel_stream['channel_stream']['channelName'], $channel_stream['channel_stream']['streamName'], $channel_stream['channel_stream']['ingestAddress']);
                            if ($update_twch_channel_stream['success']) {
                                $channel_details = array('channel_name' => $channel['channel_details']['channel_name'], 'channel_logo' => $channel['channel_details']['channel_logo']);
                                $success = array('success' => true, 'channel_details' => $channel_details);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not update channel stream');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert channel details');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get channel details');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Twitch: invalid access token');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function resync_yt_account($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $access_token = $this->validate_youtube_token($pid);
                if ($access_token['success']) {
                    $channel = $this->retrieve_youtube_channel_details($pid, $access_token['access_token']);
                    if ($channel['success']) {
                        $update_youtube_channel_details = $this->update_youtube_channel_details($pid, $channel['channel_details']['channel_title'], $channel['channel_details']['channel_thumb'], $channel['channel_details']['channel_id'], $channel['channel_details']['is_verified'], $channel['channel_details']['ls_enabled']);
                        if ($update_youtube_channel_details['success']) {
                            $channel_details = array('channel_name' => $channel['channel_details']['channel_title'], 'channel_thumbnail' => $channel['channel_details']['channel_thumb']);
                            $success = array('success' => true, 'channel_details' => $channel_details);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not insert channel details');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Could not get channel details');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'YouTube: invalid access token');
                }
            } else {
                $success = array('success' => false, 'message' => 'Social network service not active');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function resync_fb_account($pid, $ks) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $has_service = $this->verify_service($pid);
            if ($has_service) {
                $access_token = $this->validate_facebook_token($pid);
                $found_settings = false;
                $publish_to = 0;
                if ($access_token['success']) {
                    $get_fb_publish_settings = $this->get_fb_publish_settings($pid);
                    if ($get_fb_publish_settings['success']) {
                        $found_settings = true;
                    }
                    if ($found_settings) {
                        $publish_to = $get_fb_publish_settings['settings'][0]['publish_to'];
                        $asset_id = $get_fb_publish_settings['settings'][0]['asset_id'];
                        $page_found = false;
                        $group_found = false;
                        $event_found = false;
                        if ($publish_to == 2) {
                            $get_fb_page = $this->get_fb_page($pid, $asset_id);
                            $page_id = $get_fb_page['page_id'];
                        } else if ($publish_to == 3) {
                            $get_fb_group = $this->get_fb_group($pid, $asset_id);
                            $group_id = $get_fb_group['group_id'];
                        } else if ($publish_to == 4) {
                            $get_fb_event = $this->get_fb_event($pid, $asset_id);
                            $event_id = $get_fb_event['event_id'];
                        }
                    }

                    $get_user_details = $this->facebook_client_api->get_user_details($pid, $access_token['access_token']);
                    $user_name = $get_user_details['user_name'];
                    $user_id = $get_user_details['user_id'];
                    $account_pic = $this->facebook_client_api->get_account_pic($pid, $access_token['access_token'], $user_id);
                    $user = array('user_id' => $user_id, 'user_name' => $user_name, 'user_thumbnail' => $account_pic['user_pic'], 'access_token' => $access_token['access_token']);
                    $update_facebook_profile = $this->update_facebook_profile($pid, $user);

                    $get_pages_details = $this->facebook_client_api->get_pages_details($pid, $access_token['access_token'], $user_id);
                    $pages = $get_pages_details['pages'];
                    if ($this->check_fb_pages($pid)) {
                        $remove_pages = $this->remove_facebook_pages($pid);
                        if ($remove_pages['success']) {
                            $insert_facebook_pages = $this->insert_facebook_pages($pid, $pages);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not remove facebook pages');
                        }
                    } else {
                        $insert_facebook_pages = $this->insert_facebook_pages($pid, $pages);
                    }
                    if ($publish_to == 2) {
                        if (count($pages) > 0) {
                            foreach ($pages as $page) {
                                if ($page_id == $page['page_id']) {
                                    $page_found = true;
                                }
                            }
                            if ($page_found) {
                                $get_fb_page_asset_id = $this->get_fb_page_asset_id($pid, $page_id);
                                $new_page_asset_id = $get_fb_page_asset_id['id'];
                                $this->update_fb_publish_settings_asset_id($pid, $new_page_asset_id);
                            }
                        }
                    }

                    $get_groups_details = $this->facebook_client_api->get_groups_details($pid, $access_token['access_token'], $user_id);
                    $groups = $get_groups_details['groups'];
                    if ($this->check_fb_groups($pid)) {
                        $remove_groups = $this->remove_facebook_groups($pid);
                        if ($remove_groups['success']) {
                            $insert_facebook_groups = $this->insert_facebook_groups($pid, $groups);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not remove facebook groups');
                        }
                    } else {
                        $insert_facebook_groups = $this->insert_facebook_groups($pid, $groups);
                    }
                    if ($publish_to == 3) {
                        if (count($groups) > 0) {
                            foreach ($groups as $group) {
                                if ($group_id == $group['group_id']) {
                                    $group_found = true;
                                }
                            }
                            if ($group_found) {
                                $get_fb_group_asset_id = $this->get_fb_group_asset_id($pid, $group_id);
                                $new_group_asset_id = $get_fb_group_asset_id['id'];
                                $this->update_fb_publish_settings_asset_id($pid, $new_group_asset_id);
                            }
                        }
                    }

                    $get_events_details = $this->facebook_client_api->get_events_details($pid, $access_token['access_token'], $user_id);
                    $events = $get_events_details['events'];
                    if ($this->check_fb_events($pid)) {
                        $remove_events = $this->remove_facebook_events($pid);
                        if ($remove_events['success']) {
                            $insert_facebook_events = $this->insert_facebook_events($pid, $events);
                        } else {
                            $success = array('success' => false, 'message' => 'Could not remove facebook events');
                        }
                    } else {
                        $insert_facebook_events = $this->insert_facebook_events($pid, $events);
                    }
                    if ($publish_to == 4) {
                        if (count($events) > 0) {
                            foreach ($events as $event) {
                                if ($event_id == $event['event_id']) {
                                    $event_found = true;
                                }
                            }
                            if ($event_found) {
                                $get_fb_event_asset_id = $this->get_fb_event_asset_id($pid, $event_id);
                                $new_event_asset_id = $get_fb_event_asset_id['id'];
                                $this->update_fb_publish_settings_asset_id($pid, $new_event_asset_id);
                            }
                        }
                    }

                    if (($publish_to == 2 || $publish_to == 3 || $publish_to == 4) && (!$page_found && !$group_found && !$event_found)) {
                        $profile_details = array('user_name' => $user_name, 'user_thumbnail' => $account_pic['user_pic']);
                        $remove_fb_publish_settings = $this->remove_fb_publish_settings($pid);
                        if ($remove_fb_publish_settings['success']) {
                            $remove_livestream = $this->remove_fb_livestream($pid);
                            if ($remove_livestream['success']) {
                                $livestream_settings = $this->get_fb_ls_settings($pid);
                                $success = array('success' => true, 'publish_to' => $livestream_settings['publish_to'], 'settings' => $livestream_settings['settings'], 'profile_details' => $profile_details);
                            } else {
                                $success = array('success' => false, 'message' => 'Could not remove facebook user livestream');
                            }
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $profile_details = array('user_name' => $user_name, 'user_thumbnail' => $account_pic['user_pic']);
                        $livestream_settings = $this->get_fb_ls_settings($pid);
                        $success = array('success' => true, 'publish_to' => $livestream_settings['publish_to'], 'settings' => $livestream_settings['settings'], 'profile_details' => $profile_details);
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

    public function add_to_upload_queue($pid, $eid) {
        $vr = array();
        $vr['vrSettings'] = false;
        $has_service = $this->verify_service($pid);
        if ($has_service) {
            $config = array();
            $get_auto_upload_statuses = $this->get_auto_upload_statuses($pid);

            $youtube_to_upload_queue = $this->add_youtube_to_upload_queue($pid, $eid, $get_auto_upload_statuses);
            array_push($config, $youtube_to_upload_queue);

            $facebook_to_upload_queue = $this->add_facebook_to_upload_queue($pid, $eid, $get_auto_upload_statuses);
            array_push($config, $facebook_to_upload_queue);

            $twitch_to_upload_queue = $this->add_twitch_to_upload_queue($pid, $eid, $get_auto_upload_statuses);
            array_push($config, $twitch_to_upload_queue);

            $partnerData = $this->update_sn_partnerData($pid, $eid, $config, $vr);
            if ($partnerData['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'Could not update entry partnerData');
            }
        } else {
            $success = array('success' => false, 'message' => 'Social network service not active');
        }
        return $success;
    }

    public function add_twitch_to_upload_queue($pid, $eid, $twitch_upload_status) {
        $config = array();
        $twitch_status = (isset($twitch_upload_status['auto_upload']['twitch'])) ? true : false;
        if ($twitch_status) {
            $twitch_auto_upload_status = $twitch_upload_status['auto_upload']['twitch'];
            if ($twitch_auto_upload_status) {
                $insert_video_to_upload_queue = $this->insert_video_to_upload_queue($pid, $eid, 'rectangular', 'twitch', 'pending');
                if ($insert_video_to_upload_queue['success']) {
                    $twitch_config = $this->create_vod_sn_config('twitch', true, 'pending', 'pending');
                    array_push($config, $twitch_config['config']);
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert into upload queue');
                }
            } else {
                $twitch_config = $this->create_vod_sn_config('twitch', false, null, null);
                array_push($config, $twitch_config['config']);
            }
        } else {
            $twitch_config = $this->create_vod_sn_config('twitch', false, null, null);
            array_push($config, $twitch_config['config']);
        }
        return $config[0];
    }

    public function add_youtube_to_upload_queue($pid, $eid, $youtube_upload_status) {
        $config = array();
        $youtube_status = (isset($youtube_upload_status['auto_upload']['youtube'])) ? true : false;
        if ($youtube_status) {
            $youtube_auto_upload_status = $youtube_upload_status['auto_upload']['youtube'];
            if ($youtube_auto_upload_status) {
                $insert_video_to_upload_queue = $this->insert_video_to_upload_queue($pid, $eid, $youtube_upload_status['auto_upload']['youtube_projection'], 'youtube', 'pending');
                if ($insert_video_to_upload_queue['success']) {
                    $youtube_config = $this->create_vod_sn_config('youtube', true, 'pending', 'pending');
                    array_push($config, $youtube_config['config']);
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert into upload queue');
                }
            } else {
                $youtube_config = $this->create_vod_sn_config('youtube', false, null, null);
                array_push($config, $youtube_config['config']);
            }
        } else {
            $youtube_config = $this->create_vod_sn_config('youtube', false, null, null);
            array_push($config, $youtube_config['config']);
        }
        return $config[0];
    }

    public function add_facebook_to_upload_queue($pid, $eid, $facebook_upload_status) {
        $config = array();
        $facebook_status = (isset($facebook_upload_status['auto_upload']['facebook'])) ? true : false;
        if ($facebook_status) {
            $facebook_auto_upload_status = $facebook_upload_status['auto_upload']['facebook'];
            if ($facebook_auto_upload_status) {
                $insert_video_to_upload_queue = $this->insert_video_to_upload_queue($pid, $eid, $facebook_upload_status['auto_upload']['facebook_projection'], 'facebook', 'pending');
                if ($insert_video_to_upload_queue['success']) {
                    $facebook_config = $this->create_vod_sn_config('facebook', true, 'pending', 'pending');
                    array_push($config, $facebook_config['config']);
                } else {
                    $success = array('success' => false, 'message' => 'Could not insert into upload queue');
                }
            } else {
                $facebook_config = $this->create_vod_sn_config('facebook', false, null, null);
                array_push($config, $facebook_config['config']);
            }
        } else {
            $facebook_config = $this->create_vod_sn_config('facebook', false, null, null);
            array_push($config, $facebook_config['config']);
        }
        return $config[0];
    }

    public function get_auto_upload_statuses($pid) {
        $success = array('success' => false);
        $statuses = array();
        $youtube_status = $this->get_youtube_status($pid);
        $facebook_status = $this->get_facebook_status($pid);
        $twitch_status = $this->get_twitch_status($pid);
        if ($youtube_status['status']) {
            $youtube = $this->get_yt_settings($pid);
            if ($youtube['success']) {
                $statuses['youtube'] = $youtube['auto_upload'];
                $statuses['youtube_projection'] = $youtube['projection'];
            }
        }
        if ($facebook_status['status']) {
            $facebook = $this->get_facebook_publish_settings($pid);
            if ($facebook['success']) {
                $statuses['facebook'] = $facebook['userSettings'][0]['auto_upload'];
                $statuses['facebook_projection'] = $facebook['userSettings'][0]['projection'];
            }
        }
        if ($twitch_status['status']) {
            $twitch = $this->get_twch_settings($pid);
            if ($twitch['success']) {
                $statuses['twitch'] = $twitch['settings']['auto_upload'];
            }
        }
        $success = array('success' => true, 'auto_upload' => $statuses);

        return $success;
    }

    public function insert_video_to_upload_queue($pid, $eid, $projection, $platform, $status) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'entryId' => $eid,
            'projection' => $projection,
            'platform' => $platform,
            'status' => $status,
            'created_at' => date("Y-m-d H:i:s")
        );

        $this->config->insert('upload_queue', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_if_upload_queue_exists($pid, $eid, $platform) {
        $success = false;
        $this->config->select('*')
                ->from('upload_queue')
                ->where('partner_id', $pid)
                ->where('entryId', $eid)
                ->where('platform', $platform);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_if_platform_uploading($eid, $platform) {
        $success = false;
        $this->config->select('*')
                ->from('upload_queue')
                ->where('status', 'uploading')
                ->where('entryId', $eid)
                ->where('platform', $platform);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_if_uploading() {
        $success = false;
        $this->config->select('*')
                ->from('upload_queue')
                ->where('status', 'uploading');
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_if_youtube_vod_exists($pid, $eid) {
        $success = false;
        $this->config->select('*')
                ->from('youtube_vod_entries')
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

    public function get_youtube_vod_id($pid, $eid) {
        $success = array('success' => false);
        $videoId = '';
        $this->config->select('*')
                ->from('youtube_vod_entries')
                ->where('partner_id', $pid)
                ->where('entryId', $eid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $videoId = $res['videoId'];
            }
            $success = array('success' => true, 'videoId' => $videoId);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_if_facebook_vod_exists($pid, $eid) {
        $success = false;
        $this->config->select('*')
                ->from('facebook_vod_entries')
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

    public function get_facebook_vod_id($pid, $eid) {
        $success = array('success' => false);
        $videoId = '';
        $this->config->select('*')
                ->from('facebook_vod_entries')
                ->where('partner_id', $pid)
                ->where('entryId', $eid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $videoId = $res['videoId'];
            }
            $success = array('success' => true, 'videoId' => $videoId);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_if_twitch_vod_exists($pid, $eid) {
        $success = false;
        $this->config->select('*')
                ->from('twitch_vod_entries')
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

    public function get_twitch_vod_id($pid, $eid) {
        $success = array('success' => false);
        $videoId = '';
        $this->config->select('*')
                ->from('twitch_vod_entries')
                ->where('partner_id', $pid)
                ->where('entryId', $eid);

        $query = $this->config->get();
        $result = $query->result_array();
        if ($query->num_rows() > 0) {
            foreach ($result as $res) {
                $videoId = $res['videoId'];
            }
            $success = array('success' => true, 'videoId' => $videoId);
        } else {
            $success = array('success' => false);
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
        $this->_ci->curl->option('SSL_VERIFYPEER', false);
        $this->_ci->curl->option('SSL_VERIFYHOST', false);
        $response = json_decode($this->_ci->curl->execute());
        if ($response->social_network) {
            $has_service = true;
        }

        return $has_service;
    }

}
