<?php

ini_set('display_errors', 'On');
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

defined("BASEPATH") OR exit("No direct script access allowed");
require_once('kaltura/KalturaClient.php');

/**
 * 	SMH Custom Class
 * 	
 * 	Media Portal Class
 */
class SMPortal {

    protected $_ci;
    protected $smp_sess = null;
    protected $mp_url = "http://mediaplatform.streamingmediahosting.com/api_v3/?";
    protected $ac_hst = "http://mediaplatform.streamingmediahosting.com/admin_console/index.php/";
    protected $ac_url = "http://mediaplatform.streamingmediahosting.com/admin_console/index.php/user/login";
    protected $ac_str = "email=support%40streamingmediahosting.com&password=smh0nly&remember_me=0&submit=&next_uri=";
    protected $ac_cke = array("PHPSESSID" => "51mc6i2a3c6hkgp86j7a3v1f02");

    function __construct() {
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
    }

    function start_sess() {
        $this->_ci->curl->create($this->mp_url . "service=session&action=start");
        $data = array(
            "secret" => "68b329da9893e34099c7d8ad5cb9c940",
            "type" => "2",
            "partnerId" => "-2",
            "expiry" => "60"
        );

        $this->_ci->curl->post($data);
        $response = $this->_ci->curl->execute();
        $xml = new SimpleXmlElement($response);
        $smh_ks = (string) $xml->result[0];
        syslog(LOG_NOTICE, "SMPortal : start_sess: " . print_r($xml, true));
        return $smh_ks;
    }

    function impersonate($pid) {
        $this->_ci->curl->create($this->mp_url . "service=session&action=impersonate");
        $data = array(
            "secret" => "68b329da9893e34099c7d8ad5cb9c940",
            "type" => "2",
            "partnerId" => "-2",
            "impersonatedPartnerId" => $pid,
            "expiry" => "60"
        );

        $this->_ci->curl->post($data);
        $response = $this->_ci->curl->execute();
        $xml = new SimpleXmlElement($response);
        $smh_ks = (string) $xml->result[0];
        return $smh_ks;
    }

    function create_token($pid, $expiry, $privilege) {
        $token_config = $this->get_token_config($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs('');
        $secret = $token_config['secret'];
        $userId = $token_config['userId'];
        $type = KalturaSessionType::USER;
        $privileges = $privilege;

        $result = $client->session->start($secret, $userId, $type, $pid, $expiry, $privileges);

        return $result;
    }

    function create_token_dev($pid, $expiry, $privilege) {
        $token_config = $this->get_token_config($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs('');
        $secret = $token_config['secret'];
        $userId = $token_config['userId'];
        $type = KalturaSessionType::USER;
        $privileges = $privilege;

        $result = $client->session->start($secret, $userId, $type, $pid, $expiry, $privileges);

        return $result;
    }

    function valid_token($pid, $token) {
        $valid = false;
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->session->get($token);

        $current_time = time();
        $expiry_time = (int) $results->expiry;
        if ($current_time < $expiry_time) {
            $valid = true;
        } else {
            $valid = false;
        }

        return $valid;
    }

    function get_token_config($pid) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->partner->get($pid);

        $partner_info = array();
        $partner_info['secret'] = $results->secret;
        $partner_info['userId'] = $results->adminUserId;

        return $partner_info;
    }

    public function add_live_channel($pid, $ks, $name, $desc) {
        $success = array('success' => false);
        try {
            $time = time();
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($ks);
            $liveChannel = new KalturaLiveChannel();
            $liveChannel->name = $name;
            $liveChannel->description = $desc;
            $liveChannel->startDate = $time;
            $liveChannel->repeat = KalturaNullableBoolean::TRUE_VALUE;
            $result = $client->liveChannel->add($liveChannel);
            if ($result) {
                $success = array('success' => true, 'id' => $result->id);
            } else {
                $success = array('success' => false);
            }
            return $success;
        } catch (Exception $ex) {
            syslog(LOG_NOTICE, "SMH DEBUG : add_live_channel: " . $ex->getCode() . " message is " . $ex->getMessage());
            return $success;
        }
    }

    public function add_live_segment($pid, $ks, $cid, $eid) {
        $success = array('success' => false);
        try {
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($ks);
            $liveChannelSegment = new KalturaLiveChannelSegment();
            $liveChannelSegment->channelId = $cid;
            $liveChannelSegment->entryId = $eid;
            $liveChannelSegment->startTime = 0;
            $liveChannelSegment->duration = -1;
            $result = $client->liveChannelSegment->add($liveChannelSegment);
            if ($result) {
                $success = array('success' => true, 'id' => $result->id);
            } else {
                $success = array('success' => false);
            }
            return $success;
        } catch (Exception $ex) {
            syslog(LOG_NOTICE, "SMH DEBUG : add_live_segment: " . $ex->getCode() . " message is " . $ex->getMessage());
            return $success;
        }
    }

    public function update_live_segment($pid, $ks, $lsid, $cid, $eid) {
        $success = array('success' => false);
        try {
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($ks);
            $liveChannelSegment = new KalturaLiveChannelSegment();
            $liveChannelSegment->channelId = $cid;
            $liveChannelSegment->entryId = $eid;
            $liveChannelSegment->startTime = 0;
            $liveChannelSegment->duration = -1;
            $result = $client->liveChannelSegment->update($lsid, $liveChannelSegment);
            if ($result) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
            return $success;
        } catch (Exception $ex) {
            syslog(LOG_NOTICE, "SMH DEBUG : delete_live_segment: " . $ex->getCode() . " message is " . $ex->getMessage());
            return $success;
        }
    }

    public function delete_live_segment($pid, $ks, $id) {
        $success = array('success' => false);
        try {
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($ks);
            $result = $client->liveChannelSegment->delete($id);
            $success = array('success' => true);
            return $success;
        } catch (Exception $ex) {
            syslog(LOG_NOTICE, "SMH DEBUG : delete_live_segment: " . $ex->getCode() . " message is " . $ex->getMessage());
            return $success;
        }
    }

    public function delete_live_channel($pid, $ks, $id) {
        $success = array('success' => false);
        try {
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($ks);
            $result = $client->liveChannel->delete($id);
            $success = array('success' => true);
            return $success;
        } catch (Exception $ex) {
            syslog(LOG_NOTICE, "SMH DEBUG : delete_live_channel: " . $ex->getCode() . " message is " . $ex->getMessage());
            return $success;
        }
    }

    public function update_channel_status($pid, $ks, $cid, $status) {
        $success = array('success' => false);
        try {
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($ks);
            $liveChannel = new KalturaLiveChannel();
            $liveChannel->pushPublishEnabled = ($status === 'true') ? true : false;
            $result = $client->liveChannel->update($cid, $liveChannel);
            $success = array('success' => true);
            return $success;
        } catch (Exception $ex) {
            syslog(LOG_NOTICE, "SMH DEBUG : update_channel_status: " . $ex->getCode() . " message is " . $ex->getMessage());
            return $success;
        }
    }

    public function get_channel_ids($pid) {
        $ks = $this->impersonate($pid);
        $channel_ids = array();
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaLiveChannelFilter();
        $filter->orderBy = '-createdAt';
        $filter->statusEqual = KalturaEntryStatus::READY;
        $pager = null;
        $results = $client->liveChannel->listAction($filter, $pager);
        foreach ($results->objects as $r) {
            array_push($channel_ids, $r->id);
        }

        return $channel_ids;
    }

    public function get_account_channel_ids($pid, $ks) {
        $channel_ids = array();
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaLiveChannelFilter();
        $filter->orderBy = '-createdAt';
        $filter->statusEqual = KalturaEntryStatus::READY;
        $pager = null;
        $results = $client->liveChannel->listAction($filter, $pager);
        foreach ($results->objects as $r) {
            array_push($channel_ids, $r->id);
        }

        return $channel_ids;
    }

    public function get_int_timezone($pid, $ks) {
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaUserFilter();
        $filter->isAdminEqual = KalturaNullableBoolean::TRUE_VALUE;
        $pager = null;
        $results = $client->user->listAction($filter, $pager);
        $partnerData = null;
        foreach ($results->objects as $r) {
            if ($r->isAccountOwner) {
                $partnerData = json_decode($r->partnerData);
            }
        }
        $timezone = ($partnerData) ? ((isset($partnerData->cmConfig)) ? $partnerData->cmConfig[0]->timezone : null) : null;
        return $timezone;
    }

    public function get_timezone($pid, $ks, $tz) {
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaUserFilter();
        $filter->isAdminEqual = KalturaNullableBoolean::TRUE_VALUE;
        $pager = null;
        $results = $client->user->listAction($filter, $pager);
        $partnerData = null;
        $userId = null;
        foreach ($results->objects as $r) {
            if ($r->isAccountOwner) {
                $userId = $r->id;
                $partnerData = json_decode($r->partnerData);
            }
        }
        $timezone = ($partnerData) ? ((isset($partnerData->cmConfig)) ? $partnerData->cmConfig[0]->timezone : null) : null;
        if (!$timezone) {
            $timezone = $this->update_timezone($pid, $ks, $userId, $tz);
        }
        return $timezone;
    }

    public function set_new_timezone($pid, $ks, $tz) {
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaUserFilter();
        $filter->isAdminEqual = KalturaNullableBoolean::TRUE_VALUE;
        $pager = null;
        $results = $client->user->listAction($filter, $pager);
        $userId = null;
        foreach ($results->objects as $r) {
            if ($r->isAccountOwner) {
                $userId = $r->id;
            }
        }
        $timezone = $this->update_timezone($pid, $ks, $userId, $tz);
        return $timezone;
    }

    public function update_timezone($pid, $ks, $userId, $tz) {
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $user = new KalturaUser();
        $cmConfig = array();
        $config = array();
        array_push($config, array('timezone' => $tz));
        $cmConfig['cmConfig'] = $config;
        $user->partnerData = json_encode($cmConfig);
        $result = $client->user->update($userId, $user);
        $partnerData = json_decode($result->partnerData);
        $timezone = ($partnerData) ? ((isset($partnerData->cmConfig)) ? $partnerData->cmConfig[0]->timezone : null) : null;
        return $timezone;
    }

    public function get_public_channels($pid, $ks) {
        $channels = array();
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaLiveChannelFilter();
        $filter->orderBy = '+createdAt';
        $filter->statusIn = '2';
        $pager = null;

        $results = $client->liveChannel->listAction($filter, $pager);

        $output = array(
            "orderBy" => $filter->orderBy,
            "recordsTotal" => intval($results->totalCount),
            "recordsFiltered" => intval($results->totalCount),
            "data" => array(),
        );

        if (isset($draw)) {
            $output["draw"] = intval($draw);
        }

        $sort = 1;
        foreach ($results->objects as $r) {
            if ($r->pushPublishEnabled) {
                $channels[$sort] = array('id' => $r->id, 'name' => $r->name, 'description' => $r->description, 'tags' => $r->tags, 'referenceId' => $r->referenceId, 'categories' => $r->categories, 'status' => $r->status, 'pushPublishEnabled' => $r->pushPublishEnabled, 'thumbnailUrl' => $r->thumbnailUrl, 'accessControlId' => $r->accessControlId, 'partnerSortValue' => $r->partnerSortValue, 'createdAt' => $r->createdAt);
                $sort++;
            }
        }

        ksort($channels);
        $output["data"] = $channels;

        return $output;
    }

    public function get_channels($pid, $ks, $start, $length, $draw, $search, $category, $ac) {
        $channels = array();
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaLiveChannelFilter();
        $filter->orderBy = '+createdAt';
        $filter->statusIn = '2,6,7';
        $pager = new KalturaFilterPager();

        // PAGING
        if (isset($start) && $length != '-1') {
            $pager->pageSize = intval($length);
            $pager->pageIndex = floor(intval($start) / $pager->pageSize) + 1;
        }

        if (isset($search) && $search != "") {
            $filter->freeText = $search;
        }

        //access control profiles
        if (isset($ac) && $ac != "") {
            $filter->accessControlIdIn = $ac;
        }

        if (isset($category) && $category != "" && $category != undefined) {
            $filter->categoriesIdsMatchOr = $category;
        }

        $results = $client->liveChannel->listAction($filter, $pager);

        $output = array(
            "orderBy" => $filter->orderBy,
            "recordsTotal" => intval($results->totalCount),
            "recordsFiltered" => intval($results->totalCount),
            "data" => array(),
        );

        if (isset($draw)) {
            $output["draw"] = intval($draw);
        }

        $sort = 1;
        foreach ($results->objects as $r) {
            //$channels[$r->partnerSortValue] = array('id' => $r->id, 'name' => $r->name, 'description' => $r->description, 'tags' => $r->tags, 'referenceId' => $r->referenceId, 'categories' => $r->categories, 'status' => $r->status, 'pushPublishEnabled' => $r->pushPublishEnabled, 'thumbnailUrl' => $r->thumbnailUrl, 'accessControlId' => $r->accessControlId, 'partnerSortValue' => $r->partnerSortValue, 'createdAt' => $r->createdAt);
            $channels[$sort] = array('id' => $r->id, 'name' => $r->name, 'description' => $r->description, 'tags' => $r->tags, 'referenceId' => $r->referenceId, 'categories' => $r->categories, 'status' => $r->status, 'pushPublishEnabled' => $r->pushPublishEnabled, 'thumbnailUrl' => $r->thumbnailUrl, 'accessControlId' => $r->accessControlId, 'partnerSortValue' => $r->partnerSortValue, 'createdAt' => $r->createdAt);
            //array_push($channels, array('id' => $r->id, 'name' => $r->name, 'description' => $r->description, 'status' => $r->status, 'thumbnailUrl' => $r->thumbnailUrl, 'accessControlId' => $r->accessControlId, 'partnerSortValue' => $r->partnerSortValue, 'createdAt' => $r->createdAt));
            $sort++;
        }

        ksort($channels);
        $output["data"] = $channels;

        return $output;
    }

    public function get_player_details($pid, $uiconf) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->uiConf->get($uiconf);

        $player_info = array();
        $player_info['width'] = $results->width;
        $player_info['height'] = $results->height;

        return $player_info;
    }

    public function update_partner_notification($pid, $ks) {
        $success = array('success' => false);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $partner = new KalturaPartner();
        $partner->notificationUrl = 'http://devplatform.streamingmediahosting.com/apps/sn/v1.0/add_to_upload_queue.php';
        $partner->notify = 1;
        $partner->notificationsConfig = '*=0;1=1;2=0;3=0;4=0;21=0;6=0;7=0;26=0;5=0;';
        $allowEmpty = null;
        $result = $client->partner->update($partner, $allowEmpty);
        if ($result) {
            $success = array('success' => true);
        }
        return $success;
    }

    public function get_highest_bitrate($pid, $ks, $eid) {
        $success = array('success' => false);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $version = null;
        $result = $client->baseEntry->get($eid, $version);
        $temp_arr = array();
        $bitrates = $result->bitrates;
        foreach ($bitrates as $bitrate) {
            array_push($temp_arr, $bitrate[0]);
        }
        if (count($bitrates) > 1) {
            $isMultiBitrate = true;
            $sn = explode("?", $result->streamName);
            $streamName = $sn[0];
        } else {
            $isMultiBitrate = false;
            $sn = explode("?", $result->streamName);
            $streamName = $sn[0];
        }

        $max = max($temp_arr);
        $max_arr = array_keys(array_filter($temp_arr, function ($bitrate) use ($max) {
                    return $bitrate == $max;
                }));

        if ($isMultiBitrate) {
            $highest_bitrates = array();
            if (count($max_arr) > 1) {
                foreach ($max_arr as $key) {
                    array_push($highest_bitrates, $bitrates[$key]);
                }
                $temp_arr = array();
                foreach ($highest_bitrates as $bitrate) {
                    array_push($temp_arr, $bitrate[2]);
                }
                $max = max($temp_arr);
                $max_arr = array_keys(array_filter($temp_arr, function ($bitrate) use ($max) {
                            return $bitrate == $max;
                        }));
            }
            $sn_num = $max_arr[0] + 1;
            $streamName = $streamName . $sn_num;
            $multiBitrate = array('status' => $isMultiBitrate, 'highestBitrate' => $streamName);
            $success = array('success' => true, 'multiBitrate' => $multiBitrate);
        } else {
            $multiBitrate = array('status' => false, 'streamName' => $streamName);
            $success = array('success' => true, 'multiBitrate' => $multiBitrate);
        }
        return $success;
    }

    public function get_entry_details($pid, $entryId) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $version = null;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->baseEntry->get($entryId, $version);

        $partnerData = json_decode($results->partnerData);
        $entry_info = array();
        $entry_info['name'] = $results->name;
        $entry_info['desc'] = $results->description;
        $entry_info['duration'] = $results->duration;
        $entry_info['thumbnailUrl'] = $results->thumbnailUrl . '/quality/100/type/1/width/300/height/90';
        $entry_info['startDate'] = $results->startDate;
        $entry_info['endDate'] = $results->endDate;
        $entry_info['status'] = $results->status;
        $entry_info['type'] = $results->type;
        $entry_info['countdown'] = ($partnerData) ? ((isset($partnerData->ppvConfig)) ? $partnerData->ppvConfig[0]->countdown : null) : null;
        $entry_info['timezone'] = ($partnerData) ? ((isset($partnerData->ppvConfig)) ? $partnerData->ppvConfig[0]->timezone : null) : null;

        return $entry_info;
    }
    
    //SMH UPDATE
    public function get_ott_entry_details($pid, $entryId) {
        $success = array('success' => false);
        $sess = $this->impersonate($pid);
        try {
            $version = null;
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($sess);
            $results = $client->baseEntry->get($entryId, $version);

            $partnerData = json_decode($results->partnerData);
            $entry_info = array();
            $entry_info['name'] = $results->name;
            $entry_info['desc'] = $results->description;
            $entry_info['duration'] = $results->duration;
            $entry_info['thumbnailUrl'] = $results->thumbnailUrl . '/quality/100/type/1/width/300/height/90';
            $entry_info['startDate'] = $results->startDate;
            $entry_info['endDate'] = $results->endDate;
            $entry_info['status'] = $results->status;
            $entry_info['type'] = $results->type;
            $entry_info['countdown'] = ($partnerData) ? ((isset($partnerData->ppvConfig)) ? $partnerData->ppvConfig[0]->countdown : null) : null;
            $entry_info['timezone'] = ($partnerData) ? ((isset($partnerData->ppvConfig)) ? $partnerData->ppvConfig[0]->timezone : null) : null;
            $entry_info['ks'] = $sess;

            $success = array('success' => true, 'entry_info' => $entry_info);

            return $success;
        } catch (Exception $ex) {
            syslog(LOG_NOTICE, "SMH DEBUG : get_ott_entry_details: " . $ex->getCode() . " message is " . $ex->getMessage());
            $success = array('success' => false, 'ks' => $sess);
            return $success;
        }
    }

    public function get_entry_path($pid, $entryId) {
        $id = '';
        $version = '';
        $ext = '';
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->flavorAsset->getflavorassetswithparams($entryId);

        foreach ($results as $flavor) {
            if ($flavor->flavorAsset->isOriginal) {
                $id = $flavor->flavorAsset->id;
                $version = $flavor->flavorAsset->version;
                $ext = $flavor->flavorAsset->fileExt;
            }
        }

        $original_path = '/opt/kaltura/web/content/entry/data/' . $pid . '/' . $entryId . '_' . $id . '_' . $version . '.' . $ext;
        $threesixty_tmp_path = '/opt/kaltura/web/content/entry/data/' . $pid . '/' . $entryId . '_' . $id . '_' . $version . '_tmp.' . $ext;

        $success = array('success' => true, 'original_path' => $original_path, 'threesixty_tmp_path' => $threesixty_tmp_path);

        return $success;
    }

    public function ott_get_flavor($pid, $entryId) {
        $source = array();
        $flavors = array();
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = new KalturaAssetFilter();
        $filter->entryIdEqual = $entryId;
        $pager = null;
        $results = $client->flavorAsset->listAction($filter, $pager);

        $media_type = $this->get_mediaType($entryId, $pid, $sess);

        foreach ($results->objects as $flavor) {
            $flavors_tags = explode(',', $flavor->tags);
            if ($media_type === 1) {
                if ($this->ott_valid_playback_file($flavor->fileExt) && $flavor->isWeb && !in_array('audio', $flavors_tags)) {
                    if ($flavor->isOriginal) {
                        array_push($source, array('id' => $flavor->id, 'version' => $flavor->version, 'fileExt' => $flavor->fileExt, 'height' => $flavor->height, 'flavorParamsId' => $flavor->flavorParamsId));
                    } else {
                        array_push($flavors, array('id' => $flavor->id, 'version' => $flavor->version, 'fileExt' => $flavor->fileExt, 'height' => $flavor->height, 'flavorParamsId' => $flavor->flavorParamsId));
                    }
                }
            } else if ($media_type === 5) {
                if ($this->ott_valid_playback_file($flavor->fileExt) && $flavor->isWeb) {
                    if ($flavor->isOriginal) {
                        array_push($source, array('id' => $flavor->id, 'version' => $flavor->version, 'fileExt' => $flavor->fileExt, 'height' => $flavor->height, 'flavorParamsId' => $flavor->flavorParamsId));
                    } else {
                        array_push($flavors, array('id' => $flavor->id, 'version' => $flavor->version, 'fileExt' => $flavor->fileExt, 'height' => $flavor->height, 'flavorParamsId' => $flavor->flavorParamsId));
                    }
                }
            }
        }

        if (count($flavors) > 0) {
            usort($flavors, function($a, $b) {
                return $b['flavorParamsId'] - $a['flavorParamsId'];
            });

            usort($flavors, function($a, $b) {
                return $b['height'] - $a['height'];
            });
            syslog(LOG_NOTICE, "SMH DEBUG : ott_get_flavor: " . print_r($flavors, true));

            $success = array('success' => true, 'fileExt' => $flavors[0]['fileExt'], 'flavorId' => $flavors[0]['id'], 'flavorVersion' => $flavors[0]['version']);
        } else {
            $success = array('success' => true, 'fileExt' => $source[0]['fileExt'], 'flavorId' => $source[0]['id'], 'flavorVersion' => $source[0]['version']);
            syslog(LOG_NOTICE, "SMH DEBUG : ott_get_flavor: " . print_r($source, true));
        }

        return $success;
    }

    public function ott_valid_playback_file($fileExt) {
        $valid = false;
        switch ($fileExt) {
            case 'mp4':
                $valid = true;
                break;
            case 'flv':
                $valid = true;
                break;
            case 'f4v':
                $valid = true;
                break;
            case 'm4v':
                $valid = true;
                break;
            case 'asf':
                $valid = true;
                break;
            case 'mov':
                $valid = true;
                break;
            case 'avi':
                $valid = true;
                break;
            case '3gp':
                $valid = true;
                break;
            case 'ogg':
                $valid = true;
                break;
            case 'mkv':
                $valid = true;
                break;
            case 'wmv':
                $valid = true;
                break;
            case 'wma':
                $valid = true;
                break;
            case 'webm':
                $valid = true;
                break;
            case 'mpeg':
                $valid = true;
                break;
            case 'mpg':
                $valid = true;
                break;
            case 'm1v':
                $valid = true;
                break;
            case 'm2v':
                $valid = true;
                break;
            case 'wav':
                $valid = true;
                break;
            case 'mp3':
                $valid = true;
                break;
            case 'aac':
                $valid = true;
                break;
            case 'flac':
                $valid = true;
                break;
            case 'ac3':
                $valid = true;
                break;
            default:
                $valid = false;
        }

        return $valid;
    }

    public function get_stream_name($pid, $entryId) {
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $version = null;
        $result = $client->liveStream->get($entryId, $version);
        $explode = explode("?", $result->streamName);
        $streamName = $explode[0];
        return $streamName;
    }

    public function get_partner_child_acnts($pid, $ks) {
        try {
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($ks);

            $filter = new KalturaPartnerFilter();
            $filter->orderBy = '-createdAt';
            $filter->statusIn = '1,2';
            $filter->idNotIn = $pid;
            $pager = new KalturaFilterPager();
            $pager->pageSize = 200;
            $pager->pageIndex = 0;
            $result = $client->partner->listAction($filter, $pager);

            $child_ids = array();
            foreach ($result->objects as $partner) {
                array_push($child_ids, $partner->id);
            }

            $success = array('success' => true, 'childIds' => $child_ids);
        } catch (Exception $ex) {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_entry_partnerData($pid, $entryId) {
        try {
            $sess = $this->impersonate($pid);
            $version = null;
            $config = new KalturaConfiguration($pid);
            $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
            $client = new KalturaClient($config);
            $client->setKs($sess);
            $results = $client->baseEntry->get($entryId, $version);
            $success = array('success' => true, 'partnerData' => $results->partnerData);
        } catch (Exception $ex) {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_entry_partnerData($pid, $entryId, $partnerData) {
        $success = array('success' => false);
        $sess = $this->impersonate($pid);
        $version = null;
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $baseEntry = new KalturaBaseEntry();
        $baseEntry->partnerData = json_encode($partnerData);
        $result = $client->baseEntry->update($entryId, $baseEntry);
        if ($result) {
            $success = array('success' => true, 'partnerData' => $result->partnerData);
            return $success;
        } else {
            return $success;
        }
    }

    public function get_cat_details($pid, $cat_id) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->category->get($cat_id);

        $cat_info = array();
        $cat_info['name'] = $results->name;
        $cat_info['desc'] = $results->description;

        return $cat_info;
    }

    function get_User($pid) {

        $sess = $this->impersonate($pid);
        if ($pid) {
            // Build call
            $this->_ci->curl->create($this->mp_url . "service=user&action=list");
            $post_data = array(
                'format' => 1,
                'ks' => $sess
            );
            $this->_ci->curl->post($post_data);
            $resp = $this->_ci->curl->execute();

            $user = json_decode($resp);
            // syslog(LOG_NOTICE,"getUsers: ".print_r($resp,true));

            $userInfo = array();
            foreach ($user->objects as $u) {
                if ((string) $u->isAccountOwner == '1') {
                    $userInfo['name'] = $u->fullName;
                    $userInfo['email'] = $u->email;
                }
            }
        }
        return $userInfo;
    }

    function get_user_details($pid) {

        $sess = $this->impersonate($pid);
        if ($pid) {
            // Build call
            $this->_ci->curl->create($this->mp_url . "service=partner&action=getInfo");
            $post_data = array(
                'ks' => $sess
            );
            $this->_ci->curl->post($post_data);
            $resp = $this->_ci->curl->execute();

            $resp = new SimpleXmlElement($resp);
            $business_name = (string) $resp->result->name;
        }
        return $business_name;
    }

    function update_entry_ac($pid, $entry, $ac, $media_type) {
        $sess = $this->impersonate($pid);
        if ($pid) {
            if ($media_type == '1' || $media_type == '5') {
                $this->_ci->curl->create($this->mp_url . "service=media&action=update");
                $post_data = array(
                    'entryId' => $entry,
                    'ks' => $sess,
                    'mediaEntry:accessControlId' => $ac,
                    'mediaEntry:objectType' => 'KalturaMediaEntry'
                );
                $this->_ci->curl->post($post_data);
                $resp = $this->_ci->curl->execute();
            } else if ($media_type == '100' || $media_type == '101') {
                $this->_ci->curl->create($this->mp_url . "service=liveStream&action=update");
                $post_data = array(
                    'entryId' => $entry,
                    'ks' => $sess,
                    'liveStreamEntry:accessControlId' => $ac,
                    'liveStreamEntry:objectType' => 'KalturaLiveStreamAdminEntry'
                );
                $this->_ci->curl->post($post_data);
                $resp = $this->_ci->curl->execute();
            } else if ($media_type == '3') {
                $content_list = $this->get_playlist_content($sess, $pid, $entry);
                $content = explode(",", $content_list);
                $resp = $this->update_playlist_ac($sess, $pid, $content, $ac);
            } else if ($media_type == '6') {
                $resp = $this->update_cat_ac($sess, $pid, $entry, $ac);
            }
        }
        return $resp;
    }

    function update_platform_entry_ac($pid, $playlist_id, $ac) {
        $sess = $this->impersonate($pid);
        $content_list = $this->get_playlist_content($sess, $pid, $playlist_id);
        $content = explode(",", $content_list);
        $resp = $this->update_playlist_ac($sess, $pid, $content, $ac);
        return $resp;
    }

    function get_thumb($pid, $entry_id) {
        $sess = $this->impersonate($pid);
        $content_list = $this->get_playlist_content($sess, $pid, $entry_id);
        $content = explode(",", $content_list);
        $img = str_replace("mediaplatform.streamingmediahosting.com", "images.mediaplatform.streamingmediahosting.com", $content[0]);
        return $img;
    }

    function get_default_thumb($pid, $entry_id, $ks) {
        $success = array('use_default' => false);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $filter = new KalturaAssetFilter();
        $filter->entryIdEqual = $entry_id;
        $pager = null;
        $result = $client->thumbAsset->listAction($filter, $pager);

        if ($result->totalCount) {
            foreach ($result->objects as $r) {
                if ($r->tags == 'default_thumb') {
                    $success = array('use_default' => false, 'path' => '/opt/kaltura/web/content/entry/data/' . $pid . '/' . $r->entryId . '_' . $r->id . '_' . $r->version . '.' . $r->fileExt, 'fileExt' => $r->fileExt);
                }
            }
        } else {
            $success = array('use_default' => true);
        }
        return $success;
    }

    function get_cat_thumb($pid, $cat_id) {
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = new KalturaCategoryEntryFilter();
        $filter->orderBy = '-createdAt';
        $filter->categoryIdEqual = $cat_id;
        $pager = new KalturaFilterPager();
        $pager->pageSize = 1;
        $results = $client->categoryEntry->listAction($filter, $pager);
        foreach ($results->objects as $r) {
            $r = $r->entryId;
        }

        return $r;
    }

    function get_playlist_content($sess, $pid, $id) {
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $version = null;
        $resp = $client->playlist->get($id, $version);
        $content = $resp->playlistContent;
        return $content;
    }

    function get_playlist_content_entry($pid, $id) {
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $version = null;
        $resp = $client->playlist->get($id, $version);
        $content = $resp->playlistContent;
        return $content;
    }

    function update_playlist_ac($sess, $pid, $content, $ac) {
        $result = false;
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        foreach ($content as $entry) {
            $type = $this->get_mediaType($entry, $pid, $sess);
            if ($type == '100' || $type == '101') {
                $liveStreamEntry = new KalturaLiveStreamAdminEntry();
                $liveStreamEntry->accessControlId = $ac;
                $result = $client->liveStream->update($entry, $liveStreamEntry);
            } else {
                $mediaEntry = new KalturaMediaEntry();
                $mediaEntry->accessControlId = $ac;
                $result = $client->media->update($entry, $mediaEntry);
            }
        }
        return $result;
    }

    function update_cat_ac($sess, $pid, $cat_id, $ac) {
        $result = false;
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = new KalturaCategoryEntryFilter();
        $filter->orderBy = '-createdAt';
        $filter->categoryIdEqual = $cat_id;
        $pager = new KalturaFilterPager();
        $pager->pageSize = 500;
        $pager->pageIndex = 1;
        $entries = $client->categoryEntry->listAction($filter, $pager)->objects;
        foreach ($entries as $entry) {
            $mediaEntry = new KalturaMediaEntry();
            $mediaEntry->accessControlId = $ac;
            $result = $client->media->update($entry->entryId, $mediaEntry);
        }
        return $result;
    }

    function get_mediaType($entryId, $pid, $sess) {
        $type = '';
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $version = null;
        $results = $client->baseEntry->get($entryId, $version);
        $type = $results->mediaType;
        return $type;
    }

    function get_mediaType_cat($pid, $entryId) {
        $sess = $this->impersonate($pid);
        $type = '';
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $version = null;
        $results = $client->baseEntry->get($entryId, $version);
        $type = $results->mediaType;
        return $type;
    }

    function get_cat_id($pid, $entry_id) {
        $cat_id = '';
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = new KalturaCategoryEntryFilter();
        $filter->entryIdEqual = $entry_id;
        $pager = null;
        $results = $client->categoryEntry->listAction($filter, $pager);
        foreach ($results->objects as $r) {
            $cat_id = $r->categoryId;
        }

        return $cat_id;
    }

    function get_cat_id_from_name($pid, $cat) {
        $cat_id = '';
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = new KalturaCategoryEntryFilter();
        $filter->fullNameEqual = $cat;
        $pager = null;
        $results = $client->category->listAction($filter, $pager);
        foreach ($results->objects as $r) {
            $cat_id = $r->id;
        }

        return $cat_id;
    }

    function get_cat_entries($pid, $cat_id) {
        $entries = array();
        $entry_details = array();
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = new KalturaCategoryEntryFilter();
        $filter->orderBy = '-createdAt';
        $filter->categoryIdEqual = $cat_id;
        $pager = null;
        $results = $client->categoryEntry->listAction($filter, $pager);
        foreach ($results->objects as $r) {
            array_push($entries, $r->entryId);
        }

        foreach ($entries as $entryId) {
            $entry = $this->get_entry_details($pid, $entryId);
            array_push($entry_details, array('entry_id' => $entryId, 'name' => $entry['name'], 'desc' => $entry['desc']));
        }

        return $entry_details;
    }

    function set_entry_ac_default($pid, $kentry_id, $media_type) {
        $sess = $this->impersonate($pid);
        if ($pid) {
            $ac_default = $this->get_default_ac($pid);
            if ($media_type != '100' && $media_type != '101') {
                $this->_ci->curl->create($this->mp_url . "service=media&action=update");
                $post_data = array(
                    'entryId' => $kentry_id,
                    'ks' => $sess,
                    'mediaEntry:accessControlId' => $ac_default,
                    'mediaEntry:objectType' => 'KalturaMediaEntry'
                );
            } else {
                $this->_ci->curl->create($this->mp_url . "service=liveStream&action=update");
                $post_data = array(
                    'entryId' => $kentry_id,
                    'ks' => $sess,
                    'liveStreamEntry:accessControlId' => $ac_default,
                    'liveStreamEntry:objectType' => 'KalturaLiveStreamAdminEntry'
                );
            }

            $this->_ci->curl->post($post_data);
            $resp = $this->_ci->curl->execute();
        }
        return $resp;
    }

    function set_entry_ac_platform_default($pid, $kentry_id) {
        $sess = $this->impersonate($pid);
        $media_type = $this->get_mediaType($kentry_id, $pid, $sess);
        if ($pid) {
            $ac_default = $this->get_default_ac($pid);
            if ($media_type != '100' && $media_type != '101') {
                $this->_ci->curl->create($this->mp_url . "service=media&action=update");
                $post_data = array(
                    'entryId' => $kentry_id,
                    'ks' => $sess,
                    'mediaEntry:accessControlId' => $ac_default,
                    'mediaEntry:objectType' => 'KalturaMediaEntry'
                );
            } else {
                $this->_ci->curl->create($this->mp_url . "service=liveStream&action=update");
                $post_data = array(
                    'entryId' => $kentry_id,
                    'ks' => $sess,
                    'liveStreamEntry:accessControlId' => $ac_default,
                    'liveStreamEntry:objectType' => 'KalturaLiveStreamAdminEntry'
                );
            }

            $this->_ci->curl->post($post_data);
            $resp = $this->_ci->curl->execute();
        }
        return $resp;
    }

    function get_default_ac($pid) {
        $sess = $this->impersonate($pid);
        if ($pid) {
            $this->_ci->curl->create($this->mp_url . "service=accessControlProfile&action=list");
            $post_data = array(
                'format' => 1,
                'ks' => $sess
            );

            $this->_ci->curl->post($post_data);
            $resp = $this->_ci->curl->execute();
            $ac = json_decode($resp);
            // syslog(LOG_NOTICE,"getUsers: ".print_r($resp,true));

            $acId = '';
            foreach ($ac->objects as $id) {
                if ((string) $id->isDefault == '1') {
                    $acId = $id->id;
                }
            }
        }
        return $acId;
    }

    function list_ac($pid, $iDisplayStart, $iDisplayLength, $iSortCol_0, $sSortDir_0, $sEcho) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = new KalturaAccessControlProfileFilter();
        $filter->orderBy = '-createdAt';
        $pager = null;

        // PAGING
        if (isset($iDisplayStart) && $iDisplayLength != '-1') {
            $pager = new KalturaFilterPager();
            $pager->pageSize = intval($iDisplayLength);
            $pager->pageIndex = floor(intval($iDisplayStart) / $pager->pageSize) + 1;
        }

        // ORDERING
//        $aColumns = array("id");
//        if (isset($iSortCol_0)) {
//            $filter = new KalturaAccessControlProfileFilter();
//            $filter->orderBy = ($sSortDir_0 == 'asc' ? '+' : '-') . $aColumns[intval($iSortCol_0)];
//        }

        $results = $client->accessControlProfile->listAction($filter, $pager);

        $output = array(
            "orderBy" => $filter->orderBy,
            "iTotalRecords" => intval($results->totalCount - 1),
            "iTotalDisplayRecords" => intval($results->totalCount - 1),
            "aaData" => array()
        );

        if (isset($sEcho)) {
            $output["sEcho"] = intval($sEcho);
        }

        foreach ($results->objects as $ac) {
            if ($ac->isDefault != '1') {
                $delete_data = $pid . "," . $ac->id . "," . $ac->name;
                $ac_data = $pid . "," . $ac->id . "," . $ac->name . "," . $ac->description;
                ;
                $row = array();
                $row[] = "<div id='data-name'>" . $ac->id . "</div>";
                $row[] = "<div id='data-name'>" . $ac->name . "</div>";
                $row[] = "<div id='data-name'>" . $ac->description . "</div>";

                $preview = '';

                if ($ac->rules) {
                    foreach ($ac->rules as $item) {
                        if (get_class($item->conditions[0]) == 'KalturaAuthenticatedCondition') {
                            foreach ($item->actions as $rule) {
                                if ($rule->type == 1) {
                                    $row[] = "<div id='data-name'>Completely Block</div>";
                                    $ac_data .= ",false,-1";
                                } else {
                                    if ($rule->limit < 60) {
                                        $preview = (int) gmdate("s", $rule->limit) . ' second';
                                    } else {
                                        $time = (explode(":", ltrim(gmdate("i:s", $rule->limit), 0)));

                                        if ($time[1] == 00) {
                                            $preview = $time[0] . ' minute';
                                        } else {
                                            $preview = $time[0] . ' minute, ' . $time[1] . ' second';
                                        }
                                    }

                                    $row[] = "<div id='data-name'>Block with a " . $preview . " preview</div>";
                                    $ac_data .= ",true," . $rule->limit;
                                }
                            }
                        }
                    }
                }

                $row[] = '<a href="#" rel="tooltip" data-original-title="Edit" data-placement="top" onclick="smhPPV.ac_edit_dialog(\'' . $ac_data . '\');"><img width="15px" src="/img/ppv_edit.png"></a> &nbsp;&nbsp;<a href="#" rel="tooltip" data-original-title="Delete" data-placement="top" onclick="smhPPV.ac_delete_dialog(\'' . $delete_data . '\');"><img width="15px" src="/img/ppv-remove.png"></a>';
                $row[] = $ac->id;
                $output['aaData'][] = $row;
            }
        }

        return $output;
    }

    function list_ac_type($pid, $start, $length, $draw) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $client->startMultiRequest();

        $filter = null;
        $pager = null;
        $client->flavorParams->listAction($filter, $pager);

        $filter = null;
        $pager = null;
        $filter = new KalturaAccessControlProfileFilter();
        $filter->orderBy = '-createdAt';

        // PAGING
        if (isset($start) && $length != '-1') {
            $pager = new KalturaFilterPager();
            $pager->pageSize = intval($length);
            $pager->pageIndex = floor(intval($start) / $pager->pageSize) + 1;
        }

//        // ORDERING
//        $aColumns = array("id", "description", "rule");
//        if (isset($iSortCol_0)) {
//            $filter->orderBy = ($sSortDir_0 == 'asc' ? '+' : '-') . $aColumns[intval($iSortCol_0)];
//            //break; //Kaltura can do only order by single field currently
//        }

        $client->accessControlProfile->listAction($filter, $pager);
        $results = $client->doMultiRequest();

        $output = array(
            "recordsTotal" => intval($results[1]->totalCount),
            "recordsFiltered" => intval($results[1]->totalCount),
            "data" => array()
        );

        if (isset($draw)) {
            $output["draw"] = intval($draw);
        }

        foreach ($results[1]->objects as $ac) {
            $unixtime_to_date = date('n/j/Y H:i', $ac->createdAt);
            $newDatetime = strtotime($unixtime_to_date);
            $newDatetime = date('m/d/Y h:i A', $newDatetime);
            $domains = 'Allow All';
            $countries_codes = 'Allow All';
            $ips = 'Allow All';
            $flavors = 'Allow All';
            $advsec = 'None';
            $auth_token = -1;
            $preview_time = -1;
            if ($ac->isDefault != '1') {
                if ($ac->rules) {
                    $preview = '';
                    $type = '';
                    $condition_type = '';
                    $limit = '';
                    foreach ($ac->rules as $item) {
                        if (count($item->conditions) == 0) {
                            foreach ($item->actions as $action) {
                                $auth_block = ($action->isBlockedList == 0) ? 'Authorized: ' : 'Blocked: ';
                                $flavors_exp = explode(",", $action->flavorParamsIds);
                                $flavors_arr = array();
                                foreach ($results[0]->objects as $flavor) {
                                    if (in_array($flavor->id, $flavors_exp)) {
                                        array_push($flavors_arr, $flavor->name);
                                    }
                                }
                                $flavors = $auth_block . implode(", ", $flavors_arr);
                            }
                        }
                        foreach ($item->actions as $rule) {
                            $type = $rule->type;
                            if ($type == 2) {
                                $limit = $rule->limit;
                            }
                        }
                        foreach ($item->conditions as $conditions) {
                            if ($conditions->type == 4) {
                                $domains_arr = array();
                                $auth_block = ($conditions->not == 1) ? 'Authorized: ' : 'Blocked: ';
                                foreach ($conditions->values as $item3) {
                                    array_push($domains_arr, $item3->value);
                                }
                                $domains = $auth_block . implode(",", $domains_arr);
                            }
                            if ($conditions->type == 2) {
                                $countries_code_arr = array();
                                $auth_block = ($conditions->not == 1) ? 'Authorized: ' : 'Blocked: ';
                                foreach ($conditions->values as $item3) {
                                    array_push($countries_code_arr, $item3->value);
                                }
                                $countries_codes = $auth_block . implode(",", $countries_code_arr);
                            }
                            if ($conditions->type == 3) {
                                $ips_arr = array();
                                $auth_block = ($conditions->not == 1) ? 'Authorized: ' : 'Blocked: ';
                                foreach ($conditions->values as $item3) {
                                    array_push($ips_arr, $item3->value);
                                }
                                $ips = $auth_block . implode(",", $ips_arr);
                            }
                            if ($conditions->type == 1) {
                                $row = array();
                                $row[] = "<input type='radio' class='ppv-ac' name='ppv_ac' style='width=33px' id='" . $ac->id . "' value='" . $ac->id . ";" . $ac->name . "' />";
                                $row[] = "<div id='data-name'>" . $ac->name . "</div>";
                                if ($type == 1) {
                                    $auth_token = 1;
                                    $advsec = 'Protected by authentication token';
                                } else if ($type == 2) {
                                    $auth_token = 1;
                                    if ($limit < 60) {
                                        $time = (int) gmdate("s", $limit);
                                        $time = ltrim($time, '0');
                                        $preview = $time . ' second';
                                    } else {
                                        $time = (explode(":", ltrim(gmdate("i:s", $limit), 0)));
                                        if ($time[1] == 00) {
                                            $time_m = ltrim($time[0], '0');
                                            $preview = $time_m . ' minute';
                                        } else {
                                            $time_m = ltrim($time[0], '0');
                                            $time_s = ltrim($time[1], '0');
                                            $preview = $time_m . ' minute, ' . $time_s . ' second';
                                        }
                                    }
                                    $advsec = 'Protected by authentication token<br> with a ' . $preview . ' free preview';
                                }
                                $row[] = "<div class='data-break'><a onclick='smhPPV.viewRules(\"" . $ac->name . "\",\"" . $domains . "\",\"" . $countries_codes . "\",\"" . $ips . "\",\"" . $flavors . "\",\"" . $advsec . "\");'>View Rules <i class='fa fa-external-link' style='width: 100%; text-align: center; display: inline; font-size: 12px;'></i></a></div>";
                                $row[] = "<div class='data-break'>" . $newDatetime . "</div>";
                                $output['data'][] = $row;
                            }
                        }
                    }
                }
            }
        }

        return $output;
    }

    function mem_list_ac_type($pid, $start, $length, $draw) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $client->startMultiRequest();

        $filter = null;
        $pager = null;
        $client->flavorParams->listAction($filter, $pager);

        $filter = null;
        $pager = null;
        $filter = new KalturaAccessControlProfileFilter();
        $filter->orderBy = '-createdAt';

        // PAGING
        if (isset($start) && $length != '-1') {
            $pager = new KalturaFilterPager();
            $pager->pageSize = intval($length);
            $pager->pageIndex = floor(intval($start) / $pager->pageSize) + 1;
        }

//        // ORDERING
//        $aColumns = array("id", "description", "rule");
//        if (isset($iSortCol_0)) {
//            $filter->orderBy = ($sSortDir_0 == 'asc' ? '+' : '-') . $aColumns[intval($iSortCol_0)];
//            //break; //Kaltura can do only order by single field currently
//        }

        $client->accessControlProfile->listAction($filter, $pager);
        $results = $client->doMultiRequest();

        $output = array(
            "recordsTotal" => intval($results[1]->totalCount),
            "recordsFiltered" => intval($results[1]->totalCount),
            "data" => array()
        );

        if (isset($draw)) {
            $output["draw"] = intval($draw);
        }

        foreach ($results[1]->objects as $ac) {
            $unixtime_to_date = date('n/j/Y H:i', $ac->createdAt);
            $newDatetime = strtotime($unixtime_to_date);
            $newDatetime = date('m/d/Y h:i A', $newDatetime);
            $domains = 'Allow All';
            $countries_codes = 'Allow All';
            $ips = 'Allow All';
            $flavors = 'Allow All';
            $advsec = 'None';
            $auth_token = -1;
            $preview_time = -1;
            if ($ac->isDefault != '1') {
                if ($ac->rules) {
                    $preview = '';
                    $type = '';
                    $condition_type = '';
                    $limit = '';
                    foreach ($ac->rules as $item) {
                        if (count($item->conditions) == 0) {
                            foreach ($item->actions as $action) {
                                $auth_block = ($action->isBlockedList == 0) ? 'Authorized: ' : 'Blocked: ';
                                $flavors_exp = explode(",", $action->flavorParamsIds);
                                $flavors_arr = array();
                                foreach ($results[0]->objects as $flavor) {
                                    if (in_array($flavor->id, $flavors_exp)) {
                                        array_push($flavors_arr, $flavor->name);
                                    }
                                }
                                $flavors = $auth_block . implode(", ", $flavors_arr);
                            }
                        }
                        foreach ($item->actions as $rule) {
                            $type = $rule->type;
                            if ($type == 2) {
                                $limit = $rule->limit;
                            }
                        }
                        foreach ($item->conditions as $conditions) {
                            if ($conditions->type == 4) {
                                $domains_arr = array();
                                $auth_block = ($conditions->not == 1) ? 'Authorized: ' : 'Blocked: ';
                                foreach ($conditions->values as $item3) {
                                    array_push($domains_arr, $item3->value);
                                }
                                $domains = $auth_block . implode(",", $domains_arr);
                            }
                            if ($conditions->type == 2) {
                                $countries_code_arr = array();
                                $auth_block = ($conditions->not == 1) ? 'Authorized: ' : 'Blocked: ';
                                foreach ($conditions->values as $item3) {
                                    array_push($countries_code_arr, $item3->value);
                                }
                                $countries_codes = $auth_block . implode(",", $countries_code_arr);
                            }
                            if ($conditions->type == 3) {
                                $ips_arr = array();
                                $auth_block = ($conditions->not == 1) ? 'Authorized: ' : 'Blocked: ';
                                foreach ($conditions->values as $item3) {
                                    array_push($ips_arr, $item3->value);
                                }
                                $ips = $auth_block . implode(",", $ips_arr);
                            }
                            if ($conditions->type == 1) {
                                $row = array();
                                $row[] = "<input type='radio' class='mem-ac' name='mem_ac' style='width=33px' id='" . $ac->id . "' value='" . $ac->id . ";" . $ac->name . "' />";
                                $row[] = "<div id='data-name'>" . $ac->name . "</div>";
                                if ($type == 1) {
                                    $auth_token = 1;
                                    $advsec = 'Protected by authentication token';
                                } else if ($type == 2) {
                                    $auth_token = 1;
                                    if ($limit < 60) {
                                        $time = (int) gmdate("s", $limit);
                                        $time = ltrim($time, '0');
                                        $preview = $time . ' second';
                                    } else {
                                        $time = (explode(":", ltrim(gmdate("i:s", $limit), 0)));
                                        if ($time[1] == 00) {
                                            $time_m = ltrim($time[0], '0');
                                            $preview = $time_m . ' minute';
                                        } else {
                                            $time_m = ltrim($time[0], '0');
                                            $time_s = ltrim($time[1], '0');
                                            $preview = $time_m . ' minute, ' . $time_s . ' second';
                                        }
                                    }
                                    $advsec = 'Protected by authentication token<br> with a ' . $preview . ' free preview';
                                }
                                $row[] = "<div class='data-break'><a onclick='smhMEM.viewRules(\"" . $ac->name . "\",\"" . $domains . "\",\"" . $countries_codes . "\",\"" . $ips . "\",\"" . $flavors . "\",\"" . $advsec . "\");'>View Rules <i class='fa fa-external-link' style='width: 100%; text-align: center; display: inline; font-size: 12px;'></i></a></div>";
                                $row[] = "<div class='data-break'>" . $newDatetime . "</div>";
                                $output['data'][] = $row;
                            }
                        }
                    }
                }
            }
        }

        return $output;
    }

    function add_ac($pid, $name, $desc, $preview, $preview_time) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);

        $accessControl = new KalturaAccessControl();
        $accessControl->name = $name;

        $accessControlRestrictions = array();
        $accessControlRestrictions0 = new KalturaSessionRestriction();
        $accessControlRestrictions[0] = $accessControlRestrictions0;
        $accessControl->description = $desc;
        //If the preview options was selected, apply the preview time to the profile
        if ($preview == 'true') {
            $accessControlRestrictions1 = new KalturaPreviewRestriction();
            $accessControlRestrictions1->previewLength = $preview_time;
            $accessControlRestrictions[1] = $accessControlRestrictions1;
        }

        $accessControl->restrictions = $accessControlRestrictions;
        $results = $client->accessControl->add($accessControl);

        if ($results) {
            return true;
        } else {
            return false;
        }
    }

    function delete_ac($pid, $id) {
        $sess = $this->impersonate($pid);
        if ($pid) {
            $this->_ci->curl->create($this->mp_url . "service=accessControlProfile&action=delete");
            $post_data = array(
                'format' => 1,
                'ks' => $sess,
                'id' => $id
            );

            $this->_ci->curl->post($post_data);
            $resp = $this->_ci->curl->execute();

            if ($resp) {
                return true;
            } else {
                return false;
            }
        }
    }

    function update_ac($pid, $id, $name, $desc, $preview, $preview_time) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);

        $accessControl = new KalturaAccessControl();
        $accessControl->name = $name;

        $accessControlRestrictions = array();
        $accessControlRestrictions0 = new KalturaSessionRestriction();
        $accessControlRestrictions[0] = $accessControlRestrictions0;
        $accessControl->description = $desc;
        //If the preview options was selected, apply the preview time to the profile
        if ($preview == 'true') {
            $accessControlRestrictions1 = new KalturaPreviewRestriction();
            $accessControlRestrictions1->previewLength = $preview_time;
            $accessControlRestrictions[1] = $accessControlRestrictions1;
        }
        $accessControl->restrictions = $accessControlRestrictions;
        $results = $client->accessControl->update($id, $accessControl);

        if ($results) {
            return true;
        } else {
            return false;
        }
    }

    function end_sess($x) {
        $this->_ci->curl->create($this->mp_url . "service=session&action=end");
        $sess = $this->smp_sess;
        $data = array(
            "ks" => $x
                #"ks" => ""
        );

        $this->_ci->curl->post($data);
        $response = $this->_ci->curl->execute();
        $xml = new SimpleXmlElement($response);
        return $response;
    }

    function end_order_token($pid, $ks) {
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($ks);
        $client->session->end();
    }

    function get_ac_type($pid, $ac_id) {

        $t = '';
        $result = $this->get_ac_details($pid, $ac_id);
        foreach ($result->rules as $rules) {
            foreach ($rules->actions as $tp) {
                $t = $tp->type;
            }
        }

        return $t;
    }

    function get_ac_details($pid, $ac_id) {
        $sess = $this->impersonate($pid);
        $config = new KalturaConfiguration($pid);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->accessControlProfile->get($ac_id);

        return $results;
    }

    function get_ac($pid, $entry_id) {
        $sess = $this->impersonate($pid);
        $this->_ci->curl->create($this->mp_url . "service=baseEntry&action=get");
        $post_data = array(
            'format' => 1,
            'ks' => $sess,
            'entryId' => $entry_id
        );

        $this->_ci->curl->post($post_data);
        $resp = $this->_ci->curl->execute();
        $resp = json_decode($resp);

        $ac_id = array('ac_id' => $resp->accessControlId, 'media_type' => $resp->mediaType);

        return $ac_id;
    }

    function verify_ks($pid, $ks) {
        $success = false;
        $config = new KalturaConfiguration(0);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $partnerFilter = null;
        $pager = null;
        $client->setKs($ks);

        try {
            $result = $client->partner->get($pid);
            $partner_id = $result->id;

            if (isset($partner_id) && $partner_id == $pid) {
                $success = array('success' => true, 'pid' => $partner_id);
            } else {
                $success = array('success' => false);
            }
        } catch (Exception $ex) {
            $success = array('success' => false);
        }
        return $success;
    }

    function get_acsess($uri) {
        // Setup AC Call
        $this->_ci->curl->create($this->ac_url); // ."partner/update-status/partner_id/".$partnerId."/status/".$status  "next_uri=%2Fpartner%2Fupdate-status%2Fpartner_id%2F".$partnerId."%2Fstatus%2F".$status
        // Set advanced options
        $this->_ci->curl->options(array(CURLOPT_RETURNTRANSFER => 1, CURLOPT_HEADER => 1, CURLOPT_COOKIESESSION => 1, CURLOPT_FOLLOWLOCATION => 1));

        $this->_ci->curl->post($this->ac_str . $uri);

        //	syslog(LOG_NOTICE, "AC_STR: ".print_r($this->ac_str,true));
        // Make KMC Call
        $resp = $this->_ci->curl->execute();
        //syslog(LOG_NOTICE,"SMPortal -- get_acsess resp: ".print_r($resp,true));

        preg_match('/^Set-Cookie:\s*([^;]*)/mi', $resp, $m);
        //syslog(LOG_NOTICE,"SMPortal -- get_ACSess: Cookie: ".print_r($m[0],true));
        $n = explode('=', $m[0]);
        $myCookie = $n[1];
        syslog(LOG_NOTICE, "SMPortal -- get_ACSess: Cookie: " . $myCookie);

        $this->ac_cke['PHPSESSID'] = $myCookie;

        // verify result
        if ($resp) {
            return true;
        } else {
            return false;
        }
    }

    function get_sess() {
        return $this->smp_sess;
    }

    function get_Users($pid) {

        $sess = $this->start_sess();
        if ($pid) {
            // Build call
            $this->_ci->curl->create($this->mp_url . "service=user&action=list");
            $post_data = array(
                'apiVersion' => '3.1.4',
                'format' => 1,
                'clientTag' => 'php5zend',
                'filter%3AobjectType' => 'KalturaUserFilter',
                'filter%3ApartnerIdEqual' => $pid,
                'partnerId' => $pid,
                'filter%3AstatusEqual' => 1,
                'filter%3AisAdminEqual' => 1,
                'ks' => $sess
            );
            $this->_ci->curl->post($post_data);
            $resp = $this->_ci->curl->execute();

            // syslog(LOG_NOTICE,"getUsers: ".print_r($resp,true));

            return $resp;
        }
    }

}
