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

    public function getTokens($pid, $code) {
        try {
            $tokens = array();
            $url = 'https://api.twitch.tv/kraken/oauth2/token';
            $data = array('client_id' => $this->OAUTH2_CLIENT_ID, 'client_secret' => $this->OAUTH2_CLIENT_SECRET, 'code' => $code, 'grant_type' => 'authorization_code', 'redirect_uri' => $this->REDIRECT_URI);
            $token_response = $this->curlPost($url, $data);
            $tokens['access_token'] = $token_response['access_token'];
            $tokens['refresh_token'] = $token_response['refresh_token'];
            return $tokens;
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Twitch_client_api->getTokens ($pid)] ERROR:  Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            error_log($date . " [Stack trace] " . json_encode(debug_backtrace()), 3, dirname(__FILE__) . '/sn_debug.log');
        }
    }

    public function get_account_details($pid, $access_token) {
        $success = array('success' => false);
        try {
            $url = 'https://api.twitch.tv/kraken/user';
            $data = array();
            $response = $this->curlGet($url, $data, $access_token);
            $success = array('success' => true, 'channel_name' => $response['display_name'], 'channel_logo' => $response['logo'], 'channel_id' => $response['_id']);
            return $success;
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Twitch_client_api->get_account_details ($pid)] ERROR:  Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            error_log($date . " [Stack trace] " . json_encode(debug_backtrace()), 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

    public function get_channel_details($pid, $access_token) {
        $success = array('success' => false);
        try {
            $channel_url = 'https://api.twitch.tv/kraken/channel';
            $data = array();
            $channel_response = $this->curlGet($channel_url, $data, $access_token);
            $available_ingest = $this->get_available_ingest($access_token);
            $ingestAddress = str_replace("{stream_key}", "", $available_ingest[0]['url_template']);
            $channel_stream = array('ingestId' => $available_ingest[0]['id'], 'channelName' => $channel_response['name'], 'streamName' => $channel_response['stream_key'], 'ingestAddress' => $ingestAddress);
            $success = array('success' => true, 'channel_stream' => $channel_stream);
            return $success;
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Twitch_client_api->get_channel_details ($pid)] ERROR:  Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            error_log($date . " [Stack trace] " . json_encode(debug_backtrace()), 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

    public function get_available_ingest($access_token) {
        $ingest_url = 'https://api.twitch.tv/kraken/ingests';
        $data = array();
        $found_ingest = false;
        $available_ingest = array();
        $ingest_response = $this->curlGet($ingest_url, $data, $access_token);
        $ingest_west = $this->filter_available_west_ingests($ingest_response['ingests']);
        if ($this->multi_array_search('US West: Los Angeles, CA', $ingest_west) && !$found_ingest) {
            foreach ($ingest_west as $ingest) {
                if (strpos($ingest['name'], 'US West: Los Angeles, CA') !== false) {
                    array_push($available_ingest, array('id' => $ingest['id'], 'url_template' => $ingest['url_template'], 'availability' => $ingest['availability'], 'name' => $ingest['name']));
                }
            }
            $found_ingest = true;
        }
        if ($this->multi_array_search('US West: San Jose,CA', $ingest_west) && !$found_ingest) {
            foreach ($ingest_west as $ingest) {
                if (strpos($ingest['name'], 'US West: San Jose,CA') !== false) {
                    array_push($available_ingest, array('id' => $ingest['id'], 'url_template' => $ingest['url_template'], 'availability' => $ingest['availability'], 'name' => $ingest['name']));
                }
            }
            $found_ingest = true;
        }
        if ($this->multi_array_search('US West: San Francisco, CA', $ingest_west) && !$found_ingest) {
            foreach ($ingest_west as $ingest) {
                if (strpos($ingest['name'], 'US West: San Francisco, CA') !== false) {
                    array_push($available_ingest, array('id' => $ingest['id'], 'url_template' => $ingest['url_template'], 'availability' => $ingest['availability'], 'name' => $ingest['name']));
                }
            }
            $found_ingest = true;
        }

        return $available_ingest;
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

    public function filter_available_west_ingests($ingest_locations) {
        $ingest_west = array();
        foreach ($ingest_locations as $ingest) {
            if (strpos($ingest['name'], 'US West') !== false) {
                if ($ingest['availability'] && (strpos($ingest['name'], 'Los Angeles') !== false)) {
                    array_push($ingest_west, array('id' => $ingest['_id'], 'url_template' => $ingest['url_template'], 'availability' => $ingest['availability'], 'name' => $ingest['name']));
                }
                if ($ingest['availability'] && (strpos($ingest['name'], 'San Jose') !== false)) {
                    array_push($ingest_west, array('id' => $ingest['_id'], 'url_template' => $ingest['url_template'], 'availability' => $ingest['availability'], 'name' => $ingest['name']));
                }
                if ($ingest['availability'] && (strpos($ingest['name'], 'San Francisco') !== false)) {
                    array_push($ingest_west, array('id' => $ingest['_id'], 'url_template' => $ingest['url_template'], 'availability' => $ingest['availability'], 'name' => $ingest['name']));
                }
            }
        }
        return $ingest_west;
    }

    public function checkAuthToken($pid, $token) {
        $success = array('success' => false);
        $url = 'https://api.twitch.tv/kraken';
        $data = array();
        $response = $this->curlValidateGet($url, $data, $token['access_token']);
        if (isset($response['error'])) {
            if ($response['status'] == 401) {
                $new_access_token = $this->refreshToken($pid, $token);
                $success = array('success' => true, 'message' => 'new_access_token', 'access_token' => $new_access_token['new_token']);
            }
        } else if ($response['token']['valid']) {
            $success = array('success' => true, 'message' => 'valid_access_token', 'access_token' => $token['access_token']);
        }
        return $success;
    }

    public function refreshToken($pid, $token) {
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
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Twitch_client_api->refreshToken ($pid)] ERROR:  Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            error_log($date . " [Stack trace] " . json_encode(debug_backtrace()), 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

    public function removeAuth($pid, $access_token) {
        $success = array('success' => false);
        try {
            $tokens = array();
            $url = 'https://api.twitch.tv/kraken/oauth2/revoke';
            $data = array('client_id' => $this->OAUTH2_CLIENT_ID, 'token' => $access_token);
            $response = $this->curlPost($url, $data);
            if ($response['status'] === 'ok') {
                $success = array('success' => true);
            }
            return $success;
        } catch (Exception $e) {
            $date = date('Y-m-d H:i:s');
            error_log($date . " [Twitch_client_api->removeAuth ($pid)] ERROR:  Caught Twitch service Exception " . $e->getCode() . " message is " . $e->getMessage() . PHP_EOL, 3, dirname(__FILE__) . '/sn_debug.log');
            error_log($date . " [Stack trace] " . json_encode(debug_backtrace()), 3, dirname(__FILE__) . '/sn_debug.log');
            $success = array('success' => false);
            return $success;
        }
    }

    public function uploadVideo($access_token, $channel_id, $name, $desc, $videoPath) {
        $success = array('success' => false);
        $url = 'https://api.twitch.tv/kraken/videos';
        $data = array('channel_id' => $channel_id, 'title' => $name, 'description' => $desc);
        $createVideoResponse = $this->curlPostAuth($access_token, $url, $data);
        $videoId = str_replace("v", "", $createVideoResponse['video']['_id']);
        $uploadToken = $createVideoResponse['upload']['token'];
        $uploadUrl = $createVideoResponse['upload']['url'];
        $chunkSizeBytes = 10 * 1024 * 1024;
        $handle = fopen($videoPath, "rb");
        rewind($handle);
        $index = 0;
        $chunk = false;
        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSizeBytes);
            $index++;
            $url = $uploadUrl . '?part=' . $index . '&upload_token=' . $uploadToken;
            $this->curlPut($url, $chunk);
        }
        fclose($handle);

        $data = array('upload_token' => $uploadToken);
        $url = $uploadUrl . '/complete?upload_token=' . $uploadToken;
        $completeUploadResponse = $this->curlPostTwitch($url, $data);
        if ($completeUploadResponse === 200) {
            $success = array('success' => true, 'videoId' => $videoId);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function removeVideo($access_token, $videoId) {
        $success = array('success' => false);
        $url = 'https://api.twitch.tv/kraken/videos/' . $videoId;
        $deleteResponse = $this->curlDeleteAuth($access_token, $url);
        if ($deleteResponse['ok']) {
            $success = array('success' => true);
        }
        return $success;
    }

    public function updateVodMetaData($access_token, $vid, $name, $desc) {
        $success = array('success' => false);
        $url = 'https://api.twitch.tv/kraken/videos/' . $vid . '?title=' . $name . '&description=' . $desc;
        $updateVideoResponse = $this->curlPutAuth($access_token, $url);
        if (isset($updateVideoResponse['_id'])) {
            $success = array('success' => true);
        }
        return $success;
    }

    public function updateChannel($channelId, $name, $access_token) {
        $success = array('success' => false);
        $url = 'https://api.twitch.tv/kraken/channels/' . $channelId;
        $data = array('channel[status]' => $name);
        $updateChannelResponse = $this->curlPutAuthData($access_token, $url, $data);
        if (isset($updateChannelResponse['_id'])) {
            $success = array('success' => true);
        }
        return $success;
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

    public function curlPostTwitch($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $this->OAUTH2_CLIENT_ID
        ));
        curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpcode;
    }

    public function curlPostAuth($access_token, $url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $this->OAUTH2_CLIENT_ID,
            'Authorization: OAuth ' . $access_token
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function curlDeleteAuth($access_token, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $this->OAUTH2_CLIENT_ID,
            'Authorization: OAuth ' . $access_token
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function curlPut($url, $chunk) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $chunk);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $this->OAUTH2_CLIENT_ID,
            'Content-Length: ' . strlen($chunk)
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function curlPutAuth($access_token, $url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $this->OAUTH2_CLIENT_ID,
            'Authorization: OAuth ' . $access_token
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function curlPutAuthData($access_token, $url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Client-ID: ' . $this->OAUTH2_CLIENT_ID,
            'Authorization: OAuth ' . $access_token
        ));
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

    public function curlValidateGet($url, $data, $access_token) {
        $final_url = $url . '?' . http_build_query($data);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.twitchtv.v5+json',
            'Authorization: OAuth ' . $access_token
        ));
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

}
