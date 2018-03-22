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

    public function getTokens($pid, $code) {
        try {
            $auth = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
            $keys = array('code' => $code, 'redirect_uri' => WB_CALLBACK_URL);
            $token = $auth->getAccessToken('code', $keys);
            return $token;
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Weibo_client_api->getTokens ($pid)] ERROR:  Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_clean();
            error_log($date . " [Stack trace]: " . PHP_EOL . $backtrace, 3, dirname(__FILE__) . '/sn_debug.log');
        }
    }

    public function get_account_details($pid, $access_token) {
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
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Weibo_client_api->get_account_details ($pid)] ERROR:  Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_clean();
            error_log($date . " [Stack trace]: " . PHP_EOL . $backtrace, 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

    public function checkAuthToken($pid, $access_token) {
        $success = array('success' => false);
        try {
            $access_token_expiry = $this->get_token_info($pid, $access_token);
            if ($access_token_expiry['token_info']['expire_in']) {
                $success = array('success' => true, 'message' => 'valid_access_token', 'access_token' => $access_token);
            } else {
                $success = array('success' => false, 'message' => 'Weibo: Access token not valid');
            }
            return $success;
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Weibo_client_api->checkAuthToken ($pid)] ERROR:  Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_clean();
            error_log($date . " [Stack trace]: " . PHP_EOL . $backtrace, 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

    public function get_token_info($pid, $access_token) {
        $success = array('success' => false);
        try {
            $auth = new SaeTOAuthV2(WB_AKEY, WB_SKEY);
            $token_info = $auth->getTokenInfo($access_token);
            $success = array('success' => true, 'token_info' => $token_info);
            return $success;
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Weibo_client_api->get_token_info ($pid)] ERROR:  Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_clean();
            error_log($date . " [Stack trace]: " . PHP_EOL . $backtrace, 3, dirname(__FILE__) . '/sn_debug.log');
        }
    }

    public function removeAuth($pid, $access_token) {
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
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Weibo_client_api->removeAuth ($pid)] ERROR:  Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_clean();
            error_log($date . " [Stack trace]: " . PHP_EOL . $backtrace, 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

    public function createLiveStream($pid, $access_token, $title, $width, $height, $summary, $published, $image, $replay, $projection) {
        $success = array('success' => false);
        try {
            $client = new SaeTClientV2(WB_AKEY, WB_SKEY, $access_token);
            $liveStreamResponse = $client->create_live_stream($title, $width, $height, $summary, $published, $image, $replay, $projection);
            syslog(LOG_NOTICE, "SMH DEBUG : createLiveStream: " . print_r($liveStreamResponse, true));
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Weibo_client_api->createLiveStream ($pid)] ERROR:  Caught Weibo service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            ob_start();
            debug_print_backtrace();
            $backtrace = ob_get_clean();
            error_log($date . " [Stack trace]: " . PHP_EOL . $backtrace, 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

}
