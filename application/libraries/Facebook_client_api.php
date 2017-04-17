<?php

ini_set('display_errors', 'On');
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

require_once 'facebook/vendor/autoload.php';
session_start();

class Facebook_client_api {

    protected $OAUTH2_CLIENT_ID;
    protected $OAUTH2_CLIENT_SECRET;
    protected $GRAPH_VERSION;

    public function __construct() {
        $this->OAUTH2_CLIENT_ID = '1880095552209614';
        $this->OAUTH2_CLIENT_SECRET = 'e52af82e8fe68106197f87138355436c';
        $this->GRAPH_VERSION = 'v2.8';
    }

    public function getRedirectURL($pid, $ks) {
        $state = $pid . "|" . $ks;
        $redirect_uri = 'https://mediaplatform.streamingmediahosting.com/apps/sn/v1.0/fb-callback.php';
        $scope = 'publish_actions,manage_pages,publish_pages,user_managed_groups,user_events';
        $authUrl = 'https://www.facebook.com/v2.8/dialog/oauth?client_id=' . $this->OAUTH2_CLIENT_ID . '&state=' . $state . '&response_type=code&sdk=php-sdk-5.4.4&redirect_uri=' . $redirect_uri . '&scope=' . $scope;
        return $authUrl;
    }

    public function getTokens($code) {
        $success = array('success' => false);
        $user_access_token = $this->get_user_access_token($code);
        if ($user_access_token['success']) {
            $user_details = $this->get_user_details($user_access_token['access_token']);
            $user = array('user_name' => $user_details['user_name'], 'user_id' => $user_details['user_id'], 'access_token' => $user_access_token['access_token']);
            $pages_details = $this->get_pages_details($user_access_token['access_token'], $user_details['user_id']);
            if ($pages_details['success']) {
                $success = array('success' => true, 'user' => $user, 'pages' => $pages_details['pages']);
            } else {
                $success = array('success' => true, 'user' => $user, 'pages' => array());
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_user_details($access_token) {
        try {
            $fb = new Facebook\Facebook([
                'app_id' => $this->OAUTH2_CLIENT_ID,
                'app_secret' => $this->OAUTH2_CLIENT_SECRET,
                'default_graph_version' => $this->GRAPH_VERSION,
            ]);
            $response = $fb->get('/me', $access_token);
            $user = $response->getGraphUser()->asArray();
            $user_name = $user['name'];
            $user_id = $user['id'];
            return array('user_name' => $user_name, 'user_id' => $user_id);
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Graph returned an error: " . $e->getMessage());
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Facebook SDK returned an error: " . $e->getMessage());
            exit;
        }
    }

    public function get_pages_details($access_token, $user_id) {
        $success = array('success' => false);
        try {
            $fb = new Facebook\Facebook([
                'app_id' => $this->OAUTH2_CLIENT_ID,
                'app_secret' => $this->OAUTH2_CLIENT_SECRET,
                'default_graph_version' => $this->GRAPH_VERSION,
            ]);
            $response = $fb->get('/' . $user_id . '/accounts', $access_token);
            $pageList = $response->getGraphEdge()->asArray();
            if (count($pageList) > 0) {
                $pages = array();
                foreach ($pageList as $page) {
                    array_push($pages, array('page_name' => $page['name'], 'page_id' => $page['id'], 'access_token' => $page['access_token']));
                }
                $success = array('success' => true, 'pages' => $pages);
            } else {
                $success = array('success' => false);
            }
            return $success;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Graph returned an error: " . $e->getMessage());
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Facebook SDK returned an error: " . $e->getMessage());
            exit;
        }
    }

    public function get_user_access_token($code) {
        $success = array('success' => false);
        $redirect_uri = 'https://mediaplatform.streamingmediahosting.com/apps/sn/v1.0/fb-callback.php';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/oauth/access_token?client_id=" . $this->OAUTH2_CLIENT_ID . "&client_secret=" . $this->OAUTH2_CLIENT_SECRET . "&redirect_uri=" . $redirect_uri . "&code=" . $code);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $response = json_decode($response, true);

        if (isset($response['access_token'])) {
            $success = array('success' => true, 'access_token' => $response['access_token']);
        } else {
            $success = array('success' => false);
        }
        curl_close($ch);
        return $success;
    }

    public function get_code($access_token) {
        $success = array('success' => false);
        $redirect_uri = 'https://mediaplatform.streamingmediahosting.com/apps/sn/v1.0/fb-callback.php';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/oauth/client_code?client_id=" . $this->OAUTH2_CLIENT_ID . "&client_secret=" . $this->OAUTH2_CLIENT_SECRET . "&redirect_uri=" . $redirect_uri . "&access_token=" . $access_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $response = json_decode($response, true);

        if (isset($response['code'])) {
            $success = array('success' => true, 'code' => $response['code']);
        } else {
            $success = array('success' => false);
        }
        curl_close($ch);
        return $success;
    }

    public function checkAuthToken($access_token) {
        $success = array('success' => false);
        try {
            $fb = new Facebook\Facebook([
                'app_id' => $this->OAUTH2_CLIENT_ID,
                'app_secret' => $this->OAUTH2_CLIENT_SECRET,
                'default_graph_version' => $this->GRAPH_VERSION,
            ]);
            $oAuth2Client = $fb->getOAuth2Client();
            $tokenMetadata = $oAuth2Client->debugToken($access_token);
            if ($tokenMetadata->getIsValid()) {
                $get_code = $this->get_code($access_token);
                if ($get_code['success']) {
                    $new_access_token = $this->get_user_access_token($get_code['code']);
                    if ($new_access_token['success']) {
                        $success = array('success' => true, 'message' => 'valid_access_token', 'access_token' => $new_access_token['access_token']);
                    } else {
                        $success = array('success' => false, 'message' => 'Facebook: Could not get user access token');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Facebook: Could not get code');
                }
            } else {
                $success = array('success' => false, 'message' => 'Facebook: User token not valid');
            }
            return $success;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Graph returned an error: " . $e->getMessage());
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Facebook SDK returned an error: " . $e->getMessage());
            exit;
        }
    }

    public function get_account_pic($access_token, $user_id) {
        $success = array('success' => false);
        try {
            $fb = new Facebook\Facebook([
                'app_id' => $this->OAUTH2_CLIENT_ID,
                'app_secret' => $this->OAUTH2_CLIENT_SECRET,
                'default_graph_version' => $this->GRAPH_VERSION,
            ]);
            $requestPicture = $fb->get('/' . $user_id . '/picture?redirect=false&width=240&height=240', $access_token);
            $picture = $requestPicture->getGraphUser();
            $success = array('success' => true, 'user_pic' => $picture['url']);
            return $success;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Graph returned an error: " . $e->getMessage());
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Facebook SDK returned an error: " . $e->getMessage());
            exit;
        }
    }

    public function createLiveStream($asset, $privacy, $create_vod, $cont_streaming) {
        $success = array('success' => false);
        try {
            $fb = new Facebook\Facebook([
                'app_id' => $this->OAUTH2_CLIENT_ID,
                'app_secret' => $this->OAUTH2_CLIENT_SECRET,
                'default_graph_version' => $this->GRAPH_VERSION,
            ]);

            $value = '';
            if ($privacy == 1) {
                $value = 'EVERYONE';
            } else if ($privacy == 2) {
                $value = 'ALL_FRIENDS';
            } else if ($privacy == 3) {
                $vaule = 'SELF';
            }

            $privacy_value = array(
                'value' => $value
            );

            $data = array(
                'privacy' => json_encode($privacy_value),
                'save_vod' => ($create_vod == 'true') ? true : false,
                'stream_type' => ($cont_streaming == 'true') ? 'AMBIENT' : 'REGULAR'
            );
            $createLiveVideo = $fb->post('/' . $asset['asset_id'] . '/live_videos', $data, $asset['access_token']);
            $createLiveVideo = $createLiveVideo->getGraphNode()->asArray();
            if (isset($createLiveVideo['id'])) {
                $liveVideo = $fb->get('/' . $createLiveVideo['id'], $asset['access_token']);
                $liveVideo = $liveVideo->getGraphNode()->asArray();
                $stream_url = explode("/rtmp/", $liveVideo['stream_url']);
                $address = $stream_url[0] . '/rtmp/';
                $stream_name = $stream_url[1];
                $embed_code = $liveVideo['embed_html'];
                $live_id = $liveVideo['id'];
                $success = array('success' => true, 'address' => $address, 'stream_name' => $stream_name, 'embed_code' => $embed_code, 'live_id' => $live_id);
            } else {
                $success = array('success' => false);
            }
            return $success;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Graph returned an error: " . $e->getMessage());
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Facebook SDK returned an error: " . $e->getMessage());
            exit;
        }
    }

    public function removeAuth($access_token, $user_id) {
        $success = array('success' => false);
        try {
            $fb = new Facebook\Facebook([
                'app_id' => $this->OAUTH2_CLIENT_ID,
                'app_secret' => $this->OAUTH2_CLIENT_SECRET,
                'default_graph_version' => $this->GRAPH_VERSION,
            ]);

            $data = array();
            $removeApp = $fb->delete('/' . $user_id . '/permissions', $data, $access_token);
            $removeApp = $removeApp->getGraphNode()->asArray();
            if ($removeApp['success']) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
            return $success;
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Graph returned an error: " . $e->getMessage());
            exit;
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Facebook SDK returned an error: " . $e->getMessage());
            exit;
        }
    }

    public function get_user_id($signed_request) {
        $success = array('success' => false);
        $parse_signed_request = $this->parse_signed_request($signed_request);
        if (isset($parse_signed_request['user_id'])) {
            $success = array('success' => true, 'user_id' => $parse_signed_request['user_id']);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function parse_signed_request($signed_request) {
        list($encoded_sig, $payload) = explode('.', $signed_request, 2);

        $secret = $this->OAUTH2_CLIENT_SECRET; // Use your app secret here
        // decode the data
        $sig = $this->base64_url_decode($encoded_sig);
        $data = json_decode($this->base64_url_decode($payload), true);

        // confirm the signature
        $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
        if ($sig !== $expected_sig) {
            error_log('Bad Signed JSON signature!');
            return null;
        }

        return $data;
    }

    public function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_', '+/'));
    }

}
