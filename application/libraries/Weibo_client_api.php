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
        $auth = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
        $authUrl = $auth->getAuthorizeURL(WB_CALLBACK_URL, 'code', $state, NULL);
        return $authUrl;
    }

    public function getTokens($code) {
        try {
            $auth = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
            $keys = array('code' => $code, 'redirect_uri' => WB_CALLBACK_URL);
            $token = $auth->getAccessToken('code', $keys);
            return $token;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function get_account_details($access_token) {
        $success = array('success' => false);
        try {
            $client = new SaeTClientV2(WB_AKEY, WB_SKEY, $access_token);
            $uid = $client->get_uid();
            $userResponse = $client->show_user_by_id($uid['uid']);
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

    public function checkAuthToken($access_token) {
        $success = array('success' => false);
        try {
            $access_token_expiry = $this->get_token_info($access_token);
            syslog(LOG_NOTICE, "SMH DEBUG : checkAuthToken: access_token_expiry: " . print_r($access_token_expiry, true));
            
//            $removeAuth = $this->removeAuth($access_token);
//            syslog(LOG_NOTICE, "SMH DEBUG : checkAuthToken: removeAuth: " . print_r($removeAuth, true));
//
//            $access_token_expiry = $this->get_token_info($access_token);
//            syslog(LOG_NOTICE, "SMH DEBUG : checkAuthToken: access_token_expiry: " . print_r($access_token_expiry, true));

            if ($access_token_expiry['token_info']['expire_in']) {
                syslog(LOG_NOTICE, "SMH DEBUG : checkAuthToken: VALID ");
                $success = array('success' => true, 'message' => 'valid_access_token', 'access_token' => $access_token);
            } else {
                syslog(LOG_NOTICE, "SMH DEBUG : checkAuthToken: NOT VALID ");
                $success = array('success' => false, 'message' => 'Weibo: Access token not valid');
            }
            return $success;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function get_token_info($access_token) {
        $success = array('success' => false);
        try {
            $auth = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
            $token_info = $auth->getTokenInfo($access_token);
            $success = array('success' => true, 'token_info' => $token_info);
            return $success;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function removeAuth($access_token) {
        $success = array('success' => false);
        try {
            $auth = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
            $removeApp = $auth->revokeAuth($access_token);
            if ($removeApp['result']) {
                $success = array('success' => true);
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
