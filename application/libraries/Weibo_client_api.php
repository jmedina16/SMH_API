<?php

ini_set('display_errors', 'On');
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

session_start();

include_once( 'weibo/config.php' );
include_once( 'weibo/saetv2.ex.class.php' );

class Weibo_client_api {

    public function getRedirectURL($pid, $ks, $projection) {
        $state = $pid . "|" . $ks . "|" . $projection;
        $o = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
        $authUrl = $o->getAuthorizeURL(WB_CALLBACK_URL, 'code', $state, NULL);
        return $authUrl;
    }

    public function getTokens($code) {
        try {
            $o = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
            $keys = array('code' => $code, 'redirect_uri' => WB_CALLBACK_URL);
            $token = $o->getAccessToken('code', $keys);
            return $token;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function get_account_details($access_token) {
        $success = array('success' => false);
        try {
            syslog(LOG_NOTICE, "SMH DEBUG : WB_AKEY: " . WB_AKEY);
            syslog(LOG_NOTICE, "SMH DEBUG : WB_SKEY: " . WB_SKEY);
            syslog(LOG_NOTICE, "SMH DEBUG : access_token: " . $access_token);
            $o = new SaeTClientV2(WB_AKEY, WB_SKEY, $access_token);
            $userResponse = $o->show_user_by_id(NULL);
            syslog(LOG_NOTICE, "SMH DEBUG : get_account_details " . print_r($userResponse, true));



//            if (count($channelResponse['items']) >= 0) {
//                $title = $channelResponse['items'][0]['snippet']['title'];
//                $thumbnail = $channelResponse['items'][0]['snippet']['thumbnails']['high']['url'];
//                $channel_id = $channelResponse['items'][0]['id'];
//                $is_verified = $channelResponse['items'][0]['status']['longUploadsStatus'];
//                $success = array('success' => true, 'channel_title' => $title, 'channel_thumb' => $thumbnail, 'channel_id' => $channel_id, 'is_verified' => $is_verified);
//            } else {
//                $success = array('success' => false);
//            }
//            return $success;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

}
