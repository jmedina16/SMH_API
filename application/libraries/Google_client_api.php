<?php

ini_set('display_errors', 'On');
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

require_once 'google/vendor/autoload.php';
session_start();

class Google_client_api {

    protected $OAUTH2_CLIENT_ID;
    protected $OAUTH2_CLIENT_SECRET;

    public function __construct() {
        $this->OAUTH2_CLIENT_ID = '625514053094-0rdhl4tub0dn2kd4edk9onfcd38i1uci.apps.googleusercontent.com';
        $this->OAUTH2_CLIENT_SECRET = 'o9fEzEUdCq_mXLMGDMHboE6m';
    }

    public function getRedirectURL($pid, $ks, $projection) {
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $client->setAccessType("offline");
            $client->setApprovalPrompt('force');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setState($pid . "|" . $ks . "|" . $projection);
            $authUrl = $client->createAuthUrl();
            return $authUrl;
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function getTokens($code) {
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->authenticate($code);
            $tokens = $client->getAccessToken();
            return $tokens;
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function checkAuthToken($token) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $client->setAccessType("offline");
            $client->setApprovalPrompt('auto');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($token);

            if ($this->validateToken($token['access_token'])) {
                $success = array('success' => true, 'message' => 'valid_access_token', 'access_token' => $token);
            } else {
                $check_refresh_token = $client->refreshToken($token['refresh_token']);
                if (isset($check_refresh_token['error'])) {
                    if ($check_refresh_token['error'] == 'invalid_grant') {
                        $success = array('success' => false);
                    }
                } else {
                    $new_access_token = $client->getAccessToken();
                    $success = array('success' => true, 'message' => 'new_access_token', 'access_token' => $new_access_token);
                }
            }
            return $success;
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function validateToken($token) {
        $valid = false;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/oauth2/v3/tokeninfo?access_token=" . $token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if (isset($response->aud)) {
            $valid = true;
        } else if (isset($response->error_description)) {
            $valid = false;
        }

        return $valid;
    }

    public function removeAuth($access_token) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            if ($client->revokeToken($access_token)) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false, 'message' => 'YouTube: could not remove authorization');
            }
            return $success;
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function createLiveStream($access_token, $name, $desc, $res, $eid, $default_thumb, $projection, $embed) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $date = date('c', strtotime(date("c") . " +1 minutes"));
                    $broadcastSnippet = new Google_Service_YouTube_LiveBroadcastSnippet();
                    $broadcastSnippet->setTitle($name);
                    if (isset($desc) && $desc !== '')
                        $broadcastSnippet->setDescription($desc);
                    $broadcastSnippet->setScheduledStartTime($date);
                    $status = new Google_Service_YouTube_LiveBroadcastStatus();
                    $status->setPrivacyStatus('public');
                    $broadcastContentDetails = new Google_Service_YouTube_LiveBroadcastContentDetails();
                    $broadcastContentDetails->setProjection($projection);
                    $broadcastContentDetails->setEnableEmbed($embed);
                    $broadcastContentDetails->setEnableDvr(true);
                    $broadcastContentDetails->setRecordFromStart(true);
                    $broadcastContentDetails->setEnableContentEncryption(false);
                    $broadcastContentDetails->setStartWithSlate(false);
                    $broadcastMonitorStream = new Google_Service_YouTube_MonitorStreamInfo();
                    $broadcastMonitorStream->setEnableMonitorStream(false);
                    $broadcastMonitorStream->setBroadcastStreamDelayMs(0);
                    $broadcastContentDetails->setMonitorStream($broadcastMonitorStream);
                    $broadcastInsert = new Google_Service_YouTube_LiveBroadcast();
                    $broadcastInsert->setSnippet($broadcastSnippet);
                    $broadcastInsert->setStatus($status);
                    $broadcastInsert->setContentDetails($broadcastContentDetails);
                    $broadcastInsert->setKind('youtube#liveBroadcast');
                    $broadcastsResponse = $youtube->liveBroadcasts->insert('snippet,contentDetails,status', $broadcastInsert, array());

                    $streamSnippet = new Google_Service_YouTube_LiveStreamSnippet();
                    $streamSnippet->setTitle($eid);
                    $cdn = new Google_Service_YouTube_CdnSettings();
                    $cdn->setFrameRate("30fps");
                    $cdn->setResolution($res);
                    $cdn->setIngestionType('rtmp');
                    $streamInsert = new Google_Service_YouTube_LiveStream();
                    $streamInsert->setSnippet($streamSnippet);
                    $streamInsert->setCdn($cdn);
                    $streamInsert->setKind('youtube#liveStream');
                    $streamsResponse = $youtube->liveStreams->insert('snippet,cdn', $streamInsert, array());

                    $bindBroadcastResponse = $youtube->liveBroadcasts->bind(
                            $broadcastsResponse['id'], 'id,contentDetails', array(
                        'streamId' => $streamsResponse['id'],
                    ));

                    $videoId = $broadcastsResponse['id'];
                    $thumbnail_path = '';
                    $thumbnail_type = '';
                    if ($default_thumb['use_default']) {
                        $thumbnail_path = '/opt/kaltura/apps/sn/v1.0/live_thumb.jpg';
                        $thumbnail_type = 'image/jpeg';
                    } else {
                        $thumbnail_path = $default_thumb['path'];
                        if ($default_thumb['fileExt'] == 'jpg') {
                            $thumbnail_type = 'image/jpeg';
                        } else {
                            $thumbnail_type = 'image/' . $default_thumb['fileExt'];
                        }
                    }

                    $chunkSizeBytes = 1 * 1024 * 1024;
                    $client->setDefer(true);
                    $setRequest = $youtube->thumbnails->set($videoId);
                    $media = new Google_Http_MediaFileUpload($client, $setRequest, $thumbnail_type, null, true, $chunkSizeBytes);
                    $media->setFileSize(filesize($thumbnail_path));
                    $status = false;
                    $handle = fopen($thumbnail_path, "rb");
                    while (!$status && !feof($handle)) {
                        $chunk = fread($handle, $chunkSizeBytes);
                        $status = $media->nextChunk($chunk);
                    }
                    fclose($handle);
                    $client->setDefer(false);

                    if (count($status['items']) > 0) {
                        $success = array('success' => true, 'liveBroadcastId' => $videoId, 'liveStreamId' => $streamsResponse['id'], 'streamName' => $streamsResponse['cdn']['ingestionInfo']['streamName'], 'ingestionAddress' => $streamsResponse['cdn']['ingestionInfo']['ingestionAddress']);
                    } else {
                        $success = array('success' => false);
                    }

                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                    $error = json_decode($e->getMessage());
                    if ($error->error->errors[0]->reason === 'invalidEmbedSetting') {
                        $success = array('success' => false, 'retry' => true);
                    } else if ($error->error->errors[0]->reason === 'insufficientLivePermissions') {
                        $success = array('success' => false, 'blocked' => true);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function updateLiveStream($access_token, $res, $name, $eid, $bid, $lid) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $bindBroadcastResponse = $youtube->liveBroadcasts->bind($bid, 'id,snippet,contentDetails', array());
                    $deleteLiveStreamResponse = $youtube->liveStreams->delete($lid, array());

                    if ($deleteLiveStreamResponse->getStatusCode() == 204) {
                        $streamSnippet = new Google_Service_YouTube_LiveStreamSnippet();
                        $streamSnippet->setTitle($eid);
                        $cdn = new Google_Service_YouTube_CdnSettings();
                        $cdn->setFrameRate("30fps");
                        $cdn->setResolution($res);
                        $cdn->setIngestionType('rtmp');
                        $streamInsert = new Google_Service_YouTube_LiveStream();
                        $streamInsert->setSnippet($streamSnippet);
                        $streamInsert->setCdn($cdn);
                        $streamInsert->setKind('youtube#liveStream');
                        $streamsResponse = $youtube->liveStreams->insert('snippet,cdn', $streamInsert, array());

                        $bindBroadcastResponse = $youtube->liveBroadcasts->bind($bid, 'id,snippet,contentDetails', array(
                            'streamId' => $streamsResponse['id']
                        ));

                        if (isset($bindBroadcastResponse['id'])) {
                            $success = array('success' => true, 'liveStreamId' => $streamsResponse['id'], 'streamName' => $streamsResponse['cdn']['ingestionInfo']['streamName'], 'ingestionAddress' => $streamsResponse['cdn']['ingestionInfo']['ingestionAddress']);
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function removeLiveStream($access_token, $bid, $lid) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $bindBroadcastResponse = $youtube->liveBroadcasts->bind($bid, 'id,snippet,contentDetails', array());
                    $deleteLiveStreamResponse = $youtube->liveStreams->delete($lid, array());
                    $deleteBroadcastResponse = $youtube->liveBroadcasts->delete($bid, array());

                    if (($deleteLiveStreamResponse->getStatusCode() == 204) && ($deleteBroadcastResponse->getStatusCode() == 204)) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function removeLiveStreamObject($access_token, $bid, $lid) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $bindBroadcastResponse = $youtube->liveBroadcasts->bind($bid, 'id,snippet,contentDetails', array());
                    $deleteLiveStreamResponse = $youtube->liveStreams->delete($lid, array());

                    if ($deleteLiveStreamResponse->getStatusCode() == 204) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function updateLiveMetaData($access_token, $bid, $name, $desc) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {

                    $date = date('c', strtotime(date("c") . " +1 minutes"));
                    $broadcastSnippet = new Google_Service_YouTube_LiveBroadcastSnippet();
                    $broadcastSnippet->setTitle($name);
                    if (isset($desc) && $desc !== '')
                        $broadcastSnippet->setDescription($desc);
                    $broadcastSnippet->setScheduledStartTime($date);
                    $status = new Google_Service_YouTube_LiveBroadcastStatus();
                    $status->setPrivacyStatus('public');

                    $broadcastUpdate = new Google_Service_YouTube_LiveBroadcast();
                    $broadcastUpdate->setId($bid);
                    $broadcastUpdate->setSnippet($broadcastSnippet);
                    $broadcastUpdate->setStatus($status);
                    $broadcastsResponse = $youtube->liveBroadcasts->update('snippet,status', $broadcastUpdate, array());

                    if (isset($broadcastsResponse['id'])) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }

                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function updateVodMetaData($access_token, $vid, $name, $desc) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {

                    $listResponse = $youtube->videos->listVideos('snippet', array(
                        'id' => $vid
                    ));

                    foreach ($listResponse['items'] as $item) {
                        $categoryId = $item['snippet']['categoryId'];
                    }

                    $videoSnippet = new Google_Service_YouTube_VideoSnippet();
                    $videoSnippet->setTitle($name);
                    if (isset($desc) && $desc !== '')
                        $videoSnippet->setDescription($desc);
                    $videoSnippet->setCategoryId($categoryId);

                    $videoUpdate = new Google_Service_YouTube_Video();
                    $videoUpdate->setId($vid);
                    $videoUpdate->setSnippet($videoSnippet);
                    $videoResponse = $youtube->videos->update('snippet,status', $videoUpdate, array());

                    if (isset($videoResponse['id'])) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }

                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function updateThumbnail($access_token, $bid, $default_thumb) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $thumbnail_path = '';
                    $thumbnail_type = '';
                    if ($default_thumb['use_default']) {
                        $thumbnail_path = '/opt/kaltura/apps/sn/v1.0/live_thumb.jpg';
                        $thumbnail_type = 'image/jpeg';
                    } else {
                        $thumbnail_path = $default_thumb['path'];
                        if ($default_thumb['fileExt'] == 'jpg') {
                            $thumbnail_type = 'image/jpeg';
                        } else {
                            $thumbnail_type = 'image/' . $default_thumb['fileExt'];
                        }
                    }

                    $chunkSizeBytes = 1 * 1024 * 1024;
                    $client->setDefer(true);
                    $setRequest = $youtube->thumbnails->set($bid);
                    $media = new Google_Http_MediaFileUpload($client, $setRequest, $thumbnail_type, null, true, $chunkSizeBytes);
                    $media->setFileSize(filesize($thumbnail_path));
                    $status = false;
                    $handle = fopen($thumbnail_path, "rb");
                    while (!$status && !feof($handle)) {
                        $chunk = fread($handle, $chunkSizeBytes);
                        $status = $media->nextChunk($chunk);
                    }
                    fclose($handle);
                    $client->setDefer(false);

                    if (count($status['items']) > 0) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }

                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function getLiveStreamStatus($access_token, $bid) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $broadcastsResponse = $youtube->liveBroadcasts->listLiveBroadcasts('snippet,contentDetails,status', array(
                        'id' => $bid
                    ));

                    $status = '';
                    $id = null;
                    foreach ($broadcastsResponse['items'] as $item) {
                        $id = $item['id'];
                        $status = $item['status']['lifeCycleStatus'];
                    }
                    if (isset($id)) {
                        $success = array('success' => true, 'status' => $status);
                    } else {
                        $success = array('success' => false);
                    }

                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function transitionLiveStream($access_token, $bid, $status) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {

                    $transitionResponse = $youtube->liveBroadcasts->transition($status, $bid, 'snippet,status', array());
                    if (isset($transitionResponse['id'])) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }

                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function createNewBroadcast($access_token, $bid, $lid, $name, $desc, $default_thumb, $projection, $embed) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $bindBroadcastResponse = $youtube->liveBroadcasts->bind($bid, 'id,snippet,contentDetails', array());

                    $date = date('c', strtotime(date("c") . " +1 minutes"));
                    $broadcastSnippet = new Google_Service_YouTube_LiveBroadcastSnippet();
                    $broadcastSnippet->setTitle($name);
                    if (isset($desc) && $desc !== '')
                        $broadcastSnippet->setDescription($desc);
                    $broadcastSnippet->setScheduledStartTime($date);
                    $status = new Google_Service_YouTube_LiveBroadcastStatus();
                    $status->setPrivacyStatus('public');
                    $broadcastContentDetails = new Google_Service_YouTube_LiveBroadcastContentDetails();
                    $broadcastContentDetails->setProjection($projection);
                    $broadcastContentDetails->setEnableEmbed($embed);
                    $broadcastContentDetails->setEnableDvr(true);
                    $broadcastContentDetails->setRecordFromStart(true);
                    $broadcastContentDetails->setEnableContentEncryption(false);
                    $broadcastContentDetails->setStartWithSlate(false);
                    $broadcastMonitorStream = new Google_Service_YouTube_MonitorStreamInfo();
                    $broadcastMonitorStream->setEnableMonitorStream(false);
                    $broadcastMonitorStream->setBroadcastStreamDelayMs(0);
                    $broadcastContentDetails->setMonitorStream($broadcastMonitorStream);
                    $broadcastInsert = new Google_Service_YouTube_LiveBroadcast();
                    $broadcastInsert->setSnippet($broadcastSnippet);
                    $broadcastInsert->setStatus($status);
                    $broadcastInsert->setContentDetails($broadcastContentDetails);
                    $broadcastInsert->setKind('youtube#liveBroadcast');
                    $broadcastsResponse = $youtube->liveBroadcasts->insert('snippet,contentDetails,status', $broadcastInsert, array());

                    $bindBroadcastResponse = $youtube->liveBroadcasts->bind(
                            $broadcastsResponse['id'], 'id,contentDetails', array(
                        'streamId' => $lid,
                    ));

                    $videoId = $broadcastsResponse['id'];
                    $thumbnail_path = '';
                    $thumbnail_type = '';
                    if ($default_thumb['use_default']) {
                        $thumbnail_path = '/opt/kaltura/apps/sn/v1.0/live_thumb.jpg';
                        $thumbnail_type = 'image/jpeg';
                    } else {
                        $thumbnail_path = $default_thumb['path'];
                        if ($default_thumb['fileExt'] == 'jpg') {
                            $thumbnail_type = 'image/jpeg';
                        } else {
                            $thumbnail_type = 'image/' . $default_thumb['fileExt'];
                        }
                    }

                    $chunkSizeBytes = 1 * 1024 * 1024;
                    $client->setDefer(true);
                    $setRequest = $youtube->thumbnails->set($videoId);
                    $media = new Google_Http_MediaFileUpload($client, $setRequest, $thumbnail_type, null, true, $chunkSizeBytes);
                    $media->setFileSize(filesize($thumbnail_path));
                    $status = false;
                    $handle = fopen($thumbnail_path, "rb");
                    while (!$status && !feof($handle)) {
                        $chunk = fread($handle, $chunkSizeBytes);
                        $status = $media->nextChunk($chunk);
                    }
                    fclose($handle);
                    $client->setDefer(false);

                    if (count($status['items']) > 0) {
                        $success = array('success' => true, 'liveBroadcastId' => $videoId, 'liveStreamId' => $lid);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                    $error = json_decode($e->getMessage());
                    if ($error->error->errors[0]->reason === 'invalidEmbedSetting') {
                        $success = array('success' => false, 'retry' => true);
                    } else if ($error->error->errors[0]->reason === 'insufficientLivePermissions') {
                        $success = array('success' => false, 'blocked' => true);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function get_verification($access_token) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $channelResponse = $youtube->channels->listChannels('id,snippet,status', array(
                        'mine' => 'false'
                    ));

                    if (count($channelResponse['items']) >= 0) {
                        $is_verified = $channelResponse['items'][0]['status']['longUploadsStatus'];
                        $success = array('success' => true, 'is_verified' => $is_verified);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function get_account_details($access_token) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $channelResponse = $youtube->channels->listChannels('id,snippet,status', array(
                        'mine' => 'false'
                    ));

                    if (count($channelResponse['items']) >= 0) {
                        $title = $channelResponse['items'][0]['snippet']['title'];
                        $thumbnail = $channelResponse['items'][0]['snippet']['thumbnails']['high']['url'];
                        $channel_id = $channelResponse['items'][0]['id'];
                        $is_verified = $channelResponse['items'][0]['status']['longUploadsStatus'];
                        $success = array('success' => true, 'channel_title' => $title, 'channel_thumb' => $thumbnail, 'channel_id' => $channel_id, 'is_verified' => $is_verified);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function uploadVideo($access_token, $name, $desc, $videoPath) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $snippet = new Google_Service_YouTube_VideoSnippet();
                    $snippet->setTitle($name);
                    $snippet->setDescription($desc);

                    $status = new Google_Service_YouTube_VideoStatus();
                    $status->privacyStatus = "public";

                    $video = new Google_Service_YouTube_Video();
                    $video->setSnippet($snippet);
                    $video->setStatus($status);

                    $chunkSizeBytes = 1 * 1024 * 1024;

                    $client->setDefer(true);
                    $insertRequest = $youtube->videos->insert("id,status,snippet", $video);

                    $media = new Google_Http_MediaFileUpload(
                            $client, $insertRequest, 'video/*', null, true, $chunkSizeBytes
                    );
                    $media->setFileSize(filesize($videoPath));

                    $status = false;
                    $handle = fopen($videoPath, "rb");
                    while (!$status && !feof($handle)) {
                        $chunk = fread($handle, $chunkSizeBytes);
                        $status = $media->nextChunk($chunk);
                    }
                    fclose($handle);
                    $client->setDefer(false);
                    if ($status['status']['uploadStatus'] == 'uploaded') {
                        $success = array('success' => true, 'videoId' => $status->id);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

    public function removeVideo($access_token, $videoId) {
        $success = array('success' => false);
        try {
            $client = new Google_Client();
            $client->setClientId($this->OAUTH2_CLIENT_ID);
            $client->setClientSecret($this->OAUTH2_CLIENT_SECRET);
            $client->addScope('https://www.googleapis.com/auth/youtube');
            $redirect = filter_var('http://devplatform.streamingmediahosting.com/apps/sn/v1.0/oauth2callback.php', FILTER_SANITIZE_URL);
            $client->setRedirectUri($redirect);
            $client->setAccessToken($access_token);

            $youtube = new Google_Service_YouTube($client);
            if ($client->getAccessToken()) {
                try {
                    $deleteVideoResponse = $youtube->videos->delete($videoId);
                    if ($deleteVideoResponse->getStatusCode() == 204) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                    return $success;
                } catch (Google_Service_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : A service error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                } catch (Google_Exception $e) {
                    syslog(LOG_NOTICE, "SMH DEBUG : An client error occurred: code: " . $e->getMessage());
                    $success = array('success' => false);
                    return $success;
                }
            }
        } catch (Google_Service_Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        } catch (Exception $e) {
            syslog(LOG_NOTICE, "SMH DEBUG : Caught Google service Exception " . $e->getCode() . " message is " . $e->getMessage());
            syslog(LOG_NOTICE, "SMH DEBUG : Stack trace is " . $e->getTraceAsString());
        }
    }

}
