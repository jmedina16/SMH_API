<?php

ini_set('display_errors', 'On');
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

class Twitch_client_api {

    protected $OAUTH2_CLIENT_ID;
    protected $OAUTH2_CLIENT_SECRET;
    protected $REDIRECT_URI;

    public function __construct() {
        $this->OAUTH2_CLIENT_ID = 'hachm5pc7975xa5t07y4pdmgmvhqsy';
        $this->OAUTH2_CLIENT_SECRET = '2ywmt5dyzz0u9eum9g2azf5p58sy35';
        $this->REDIRECT_URI = 'http://devplatform.streamingmediahosting.com/apps/sn/v1.0/twitch_oauth.php';
    }

    public function getRedirectURL($pid, $ks) {
        $state = $pid . "|" . $ks;
        $scope = 'channel_editor+channel_read+channel_stream+collections_edit+user_read';
        $authUrl = 'https://api.twitch.tv/kraken/oauth2/authorize?client_id=' . $this->OAUTH2_CLIENT_ID . '&state=' . $state . '&response_type=code&redirect_uri=' . $this->REDIRECT_URI . '&scope=' . $scope;
        return $authUrl;
    }

    public function getTokens($code) {
        try {
            $tokens = array();
            $url = 'https://api.twitch.tv/kraken/oauth2/token';
            $data = array('client_id' => $this->OAUTH2_CLIENT_ID, 'client_secret' => $this->OAUTH2_CLIENT_SECRET, 'code' => $code, 'grant_type' => 'authorization_code', 'redirect_uri' => $this->REDIRECT_URI);
            $token_response = $this->curlPost($url, $data);
            $tokens['access_token'] = $token_response['access_token'];
            $tokens['refresh_token'] = $token_response['refresh_token'];
            return $tokens;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function get_account_details($access_token) {
        $success = array('success' => false);
        try {
            $url = 'https://api.twitch.tv/kraken/user';
            $data = array();
            $response = $this->curlGet($url, $data, $access_token);
            $success = array('success' => true, 'channel_name' => $response['display_name'], 'channel_logo' => $response['logo'], 'channel_id' => $response['_id']);
            return $success;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function checkAuthToken($token) {
        $url = 'https://api.twitch.tv/kraken/user';
        $data = array();
        $response = $this->curlGet($url, $data, $token['access_token']);
        if (isset($response['error'])) {
            if ($response['status'] == 401) {
                $new_access_token = $this->refreshToken($token);
                $success = array('success' => true, 'message' => 'new_access_token', 'access_token' => $new_access_token['new_token']);
            }
        } else {
            $success = array('success' => true, 'message' => 'valid_access_token', 'access_token' => $token['access_token']);
        }
        return $success;
    }

    public function refreshToken($token) {
        $success = array('success' => false);
        try {
            $url = 'https://api.twitch.tv/kraken/oauth2/token';
            $scope = 'channel_editor+channel_read+channel_stream+collections_edit+user_read';
            $data = array('client_id' => $this->OAUTH2_CLIENT_ID, 'client_secret' => $this->OAUTH2_CLIENT_SECRET, 'grant_type' => 'refresh_token', 'refresh_token' => $token['refresh_token'], 'scope' => $scope);
            $response = $this->curlPost($url, $data);
            $new_token = array('access_token' => $response['access_token'], 'refresh_token' => $response['refresh_token']);
            $success = array('success' => true, 'new_token' => $new_token);
            return $success;
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function curlPost($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function curlGet($url, $data, $access_token) {
        $final_url = $url . '?' . http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $this->OAUTH2_CLIENT_ID,
            'Authorization: OAuth ' . $access_token
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

}
