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
            $o = new SaeTClientV2(WB_AKEY, WB_SKEY, $access_token);
            $uid = $o->get_uid();
            $userResponse = $o->show_user_by_id($uid['uid']);
            if (count($userResponse) >= 0) {
                $name = $userResponse['screen_name'];
                $thumbnail = $userResponse['avatar_large'];
                $user_id = $userResponse['id'];
                $success = array('success' => true, 'name' => $name, 'user_thumb' => $thumbnail, 'user_id' => $user_id);
            } else {
                $success = array('success' => false);
            }
            return $success;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

}
