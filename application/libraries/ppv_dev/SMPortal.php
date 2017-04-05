<?php

defined("BASEPATH") OR exit("No direct script access allowed");
require_once('../kaltura/KalturaClient.php');

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
        $this->_ci->load->model('portal_model');
        #$this->get_sess();
    }

    function start_sess() {
        $this->_ci->curl->create($this->mp_url . "service=session&action=start");
        $data = array(
            "secret" => "33acf60504d41eece7352b63df19c143",
            "type" => "2",
            "partnerId" => "-2",
            "expiry" => "60"
        );

        $this->_ci->curl->post($data);
        $response = $this->_ci->curl->execute();
        $xml = new SimpleXmlElement($response);
        $smh_ks = (string) $xml->result[0];
        return $smh_ks;
    }

    function impersonate($pid) {
        $this->_ci->curl->create($this->mp_url . "service=session&action=impersonate");
        $data = array(
            "secret" => "33acf60504d41eece7352b63df19c143",
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

    public function get_entry_details($pid, $entryId) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $version = null;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $results = $client->baseEntry->get($entryId, $version);

        $entry_info = array();
        $entry_info['name'] = $results->name;
        $entry_info['desc'] = $results->description;
        $entry_info['duration'] = $results->duration;

        return $entry_info;
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
        return $content[0];
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

                $row[] = '<a href="#" rel="tooltip" data-original-title="Edit" data-placement="top" onclick="ac_edit_dialog(\'' . $ac_data . '\');"><img width="15px" src="http://mediaplatform.streamingmediahosting.com/img/ppv_edit.png"></a> &nbsp;&nbsp;<a href="#" rel="tooltip" data-original-title="Delete" data-placement="top" onclick="ac_delete_dialog(\'' . $delete_data . '\');"><img width="15px" src="http://mediaplatform.streamingmediahosting.com/img/ppv-remove.png"></a>';
                $row[] = $ac->id;
                $output['aaData'][] = $row;
            }
        }

        return $output;
    }

    function list_ac_type($pid, $iDisplayStart, $iDisplayLength, $iSortCol_0, $sSortDir_0, $sEcho) {
        $sess = $this->impersonate($pid);
        $partnerId = $pid;
        $config = new KalturaConfiguration($partnerId);
        $config->serviceUrl = 'http://mediaplatform.streamingmediahosting.com/';
        $client = new KalturaClient($config);
        $client->setKs($sess);
        $filter = null;
        $pager = null;

        $filter = new KalturaAccessControlProfileFilter();

        // PAGING
        if (isset($iDisplayStart) && $iDisplayLength != '-1') {
            $pager = new KalturaFilterPager();
            $pager->pageSize = intval($iDisplayLength);
            $pager->pageIndex = floor(intval($iDisplayStart) / $pager->pageSize) + 1;
        }

        // ORDERING
        $aColumns = array("id", "description", "rule");
        if (isset($iSortCol_0)) {
            $filter->orderBy = ($sSortDir_0 == 'asc' ? '+' : '-') . $aColumns[intval($iSortCol_0)];
            //break; //Kaltura can do only order by single field currently
        }

        $results = $client->accessControlProfile->listAction($filter, $pager);

        $output = array(
            "orderBy" => $filter->orderBy,
            "iTotalRecords" => intval($results->totalCount),
            "iTotalDisplayRecords" => intval($results->totalCount),
            "aaData" => array()
        );

        if (isset($sEcho)) {
            $output["sEcho"] = intval($sEcho);
        }

        foreach ($results->objects as $ac) {
            if ($ac->isDefault != '1') {
                $row = array();
                $row[] = "<div id='data-name'>" . $ac->id . "</div>";
                $row[] = "<div id='data-name'>" . $ac->name . "</div>";

                $preview = '';

                if ($ac->rules) {
                    foreach ($ac->rules as $item) {
                        foreach ($item->actions as $rule) {
                            if ($rule->type == 1) {
                                $row[] = "<div id='data-name'>" . 'Completely Block' . "</div>";
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

                                $row[] = "<div id='data-name'>" . 'Block with a ' . $preview . ' preview' . "</div>";
                                $ac_data .= ",true," . $rule->limit;
                            }
                        }
                    }
                }

                $output['aaData'][] = $row;
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
        $results = $client->partner->listpartnersforuser($partnerFilter, $pager);

        $partner_id = '';
        foreach ($results->objects as $partnerInfo) {
            $partner_id = $partnerInfo->id;
        }

        if (isset($partner_id) && $partner_id == $pid) {
            $success = array('success' => true, 'pid' => $partner_id);
        } else {
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

    function add_Customer($partnerName, $partnerDescription, $partnerAdminName, $partnerAdminEmail) {
        // get session
        $sess = $this->start_sess();
        // Create user profile in portal, get profile id
        $this->_ci->curl->create($this->mp_url . "service=partner&action=register");
        $provis_data = array(
            "ks" => $sess,
            "partner:adminEmail" => $partnerAdminEmail,
            "partner:adminName" => $partnerAdminName,
            "partner:commercialUse" => 1,
            "partner:description" => $partnerDescription,
            "partner:name" => $partnerName,
            "partner:objectType" => "KalturaPartner",
            "partner:type" => 1
        );
        $this->_ci->curl->post($provis_data);
        $provis_response = $this->_ci->curl->execute();


        if ($provis_response !== null) {
            // get params & continue
            $provis_xml = new SimpleXmlElement($provis_response);

            #syslog(LOG_NOTICE,"SMPortal : createResp: ".print_r($provis_xml,true));

            $partnerId = (string) $provis_xml->result->id;
            $partnerPass = (string) $provis_xml->result->cmsPassword;
            $partnerName = (string) $provis_xml->result->name;
            $partnerMail = (string) $provis_xml->result->adminEmail;
        }

        // execute cli commands to prep account backend services
        // TODO: Switch to private function


        if ($partnerId !== null) {
            $syncdir = "/oc1_fc/store001/smh/sync";
            $rootdir = "/oc1_fc/store001";
            $homedir = $rootdir . "/home_fc/" . $partnerId;
            $flashdir = $rootdir . "/flash/" . $partnerId;
            $cdndir = $rootdir . "/cdn/" . $partnerId;
            $flashlivedir = $rootdir . "/flash/" . $partnerId . "-live";
            $cdnlivedir = $rootdir . "/cdn/" . $partnerId . "-live";
            #$mobiledir 		= $rootdir."/mobile/".$partnerId;
            #$mobilelivedir	= $rootdir."/mobile/".$partnerId."-live";
            #$wmdir 			= $rootdir."/wm/".$partnerId;
            #$qtdir			= $rootdir."/qt/".$partnerId;
            $dldir = $rootdir . "/http/" . $partnerId;
            $sampledir = $rootdir . "/cdn/10000/content";
            $portaldir = $rootdir . "/kaltura/web/content/entry/data/" . $partnerId;
            $portal_updir = $rootdir . "/cdn_portal/liveupload/" . $partnerId . "-live";

            // setup base dir structure
            mkdir($homedir, 0755, true);
            mkdir($flashdir . "/streams/_definst_", 0755, true);
            mkdir($flashlivedir, 0755, true);
            mkdir($cdndir, 0777, true);
            mkdir($cdndir . "/conf", 0777, true);
            mkdir($cdndir . "/upload", 0777, true);
            mkdir($cdnlivedir, 0777, true);
            mkdir($dldir, 0755, true);
            mkdir($portal_updir, 0777, true);

            //test
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$homedir);
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$flashdir."/streams/_definst_");
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$flashlivedir);
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$cdndir."/conf");
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$cdndir."/upload");
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$cdnlivedir);
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$dldir);
            #syslog(LOG_NOTICE,'Portal: mkdir -- '.$portal_updir);
            #syslog(LOG_NOTICE,'Portal: symlink -- '.$dldir.' --> '.$homedir."/http");
            #syslog(LOG_NOTICE,'Portal: symlink -- '.$cdndir."/content".' --> '.$dldir."/mp");
            #syslog(LOG_NOTICE,'Portal: symlink -- '.$sampledir.' --> '.$dldir."/.samples");
            #syslog(LOG_NOTICE,'Portal: symlink -- '.$cdndir."/content".' --> '.$portaldir);
            #syslog(LOG_NOTICE,'Portal: symlink -- '.$flashdir."/streams/_definst_".' --> '.$homedir."/flash");
            #syslog(LOG_NOTICE,'Portal: symlink -- '.$flashdir."/streams/_definst_".' --> '.$cdndir."/content");


            symlink($dldir, $homedir . "/http");
            symlink($cdndir . "/content", $dldir . "/mp");
            symlink($sampledir, $dldir . "/.samples");
            symlink($cdndir . "/content", $portaldir);
            symlink($flashdir . "/streams/_definst_", $homedir . "/flash");
            symlink($flashdir . "/streams/_definst_", $cdndir . "/content");

            // Adjust permissions
            chown($flashdir, 30200);
            chown($cdndir, 30200);
            chown($dldir, 30200);
            chown($flashlivedir, 30200);
            chown($cdnlivedir, 30200);
            chown($portal_updir, 30200);
            chown($portaldir, 30200);

            chgrp($flashdir, 30200);
            chgrp($cdndir, 30200);
            chgrp($dldir, 30200);
            chgrp($flashlivedir, 30200);
            chgrp($cdnlivedir, 30200);
            chgrp($portal_updir, 30200);
            chgrp($portaldir, 30200);

            // finalize
            touch($homedir . "/.banner");
            chown($homedir . "/.banner", 30200);
            chgrp($homedir . "/.banner", 30200);


            // finish portal provisioning, create drop folders, etc.	
            // TODO: Switch to private function

            $this->_ci->curl->create($this->mp_url . "service=multirequest&action=null");
            $config_data = array(
                // Create FTP Drop Folder
                "1:service" => "dropfolder_dropfolder",
                "1:action" => "add",
                "1:dropFolder:type" => "1",
                "1:dropFolder:status" => "1",
                "1:dropFolder:path" => "/oc1_fc/store001/cdn/" . $partnerId . "/upload",
                "1:dropFolder:partnerId" => $partnerId,
                "1:dropFolder:objectType" => "KalturaDropFolder",
                "1:dropFolder:ignoreFileNamePatterns" => ".pureftpd-upload*",
                "1:dropFolder:fileSizeCheckInterval" => "30",
                "1:dropFolder:fileNamePatterns" => "*",
                "1:dropFolder:name" => "FTP Upload",
                "1:dropFolder:fileHandlerType" => "1",
                "1:dropFolder:fileHandlerConfig:objectType" => "KalturaDropFolderContentFileHandlerConfig",
                "1:dropFolder:fileHandlerConfig:contentMatchPolicy" => "1",
                "1:dropFolder:fileHandlerConfig:slugRegex" => "/(?P<referenceId>.+)[.]\w{3,}/",
                "1:dropFolder:fileDeletePolicy" => "2",
                "1:dropFolder:description" => "FTP Uploading",
                "1:dropFolder:dc" => "0",
                "1:dropFolder:autoFileDeleteDays" => "1",
                // Create Live Recording Drop Folder
                "2:service" => "dropfolder_dropfolder",
                "2:action" => "add",
                "2:dropFolder:type" => "1",
                "2:dropFolder:status" => "1",
                "2:dropFolder:path" => "/oc1_fc/store001/cdn_portal/liveupload/" . $partnerId . "-live",
                "2:dropFolder:partnerId" => $partnerId,
                "2:dropFolder:objectType" => "KalturaDropFolder",
                "2:dropFolder:fileSizeCheckInterval" => "30",
                "2:dropFolder:fileNamePatterns" => "*",
                "2:dropFolder:name" => "Live Recordings",
                "2:dropFolder:fileHandlerType" => "1",
                "2:dropFolder:fileHandlerConfig:objectType" => "KalturaDropFolderContentFileHandlerConfig",
                "2:dropFolder:fileHandlerConfig:contentMatchPolicy" => "1",
                "2:dropFolder:fileHandlerConfig:slugRegex" => "/(?P<referenceId>.+)[.]\w{3,}/",
                "2:dropFolder:fileDeletePolicy" => "2",
                "2:dropFolder:description" => "Live Stream Archive",
                "2:dropFolder:dc" => "0",
                "2:dropFolder:autoFileDeleteDays" => "1",
                // Close Portal Session
                "3:service" => "session",
                "3:action" => "end",
                "ks" => $sess
            );

            $this->_ci->curl->post($config_data);
            $config_response = $this->_ci->curl->execute();


            // TODO: Sync new account to edge clusters if needed


            return array($partnerId, $partnerPass, $partnerName, $partnerMail);
        }
    }

    function add_Customer2($partnerName, $partnerDescription, $partnerAdminName, $partnerAdminEmail, $partnerParent) {
        // get session
        $sess = $this->start_sess();
        // Create user profile in portal, get profile id
        $this->_ci->curl->create($this->mp_url . "service=partner&action=register");
        $provis_data = array(
            "ks" => $sess,
            "partner:adminEmail" => $partnerAdminEmail,
            "partner:adminName" => $partnerAdminName,
            "partner:commercialUse" => 1,
            "partner:description" => $partnerDescription,
            "partner:name" => $partnerName,
            "partner:objectType" => "KalturaPartner",
            "partner:type" => 1
        );
        $this->_ci->curl->post($provis_data);
        $provis_response = $this->_ci->curl->execute();


        if ($provis_response !== null) {
            // get params & continue
            $provis_xml = new SimpleXmlElement($provis_response);
            $partnerId = (string) $provis_xml->result->id;
            $partnerPass = (string) $provis_xml->result->cmsPassword;
        }

        // execute cli commands to prep account backend services
        // TODO: Switch to private function
        if ($partnerId) {
            if (addCustomer_backend($partnerId)) {
                return addCustomer_portalFinalize($partnerId, $sess);
            }
        }
    }

    private function addCustomer_backend($partnerId) {
        // execute cli commands to prep account backend services
        if ($partnerId !== null) {
            $syncdir = "/oc1_fc/store001/smh/sync";
            $rootdir = "/oc1_fc/store001";
            $homedir = $rootdir . "/home_fc/" . $partnerId;
            $flashdir = $rootdir . "/flash/" . $partnerId;
            $cdndir = $rootdir . "/cdn/" . $partnerId;
            $flashlivedir = $rootdir . "/flash/" . $partnerId . "-live";
            $cdnlivedir = $rootdir . "/cdn/" . $partnerId . "-live";
            $dldir = $rootdir . "/http/" . $partnerId;
            $sampledir = $rootdir . "/cdn/10000/content";
            $portaldir = $rootdir . "/kaltura/web/content/entry/data/" . $partnerId;
            $portal_updir = $rootdir . "/cdn_portal/liveupload/" . $partnerId . "-live";

            // setup base dir structure
            mkdir($homedir, 0755);
            mkdir($flashdir . "/streams/_definst_", 0755);
            mkdir($flashlivedir, 0755);
            mkdir($cdndir . "/conf", 0777);
            mkdir($cdndir . "/upload", 0777);
            mkdir($cdnlivedir, 0777);
            mkdir($dldir, 0755);
            mkdir($portal_updir, 0777);
            symlink($dldir, $homedir . "/http");
            symlink($cdndir . "/content", $dldir . "/mp");
            symlink($sampledir, $dldir . "/.samples");
            symlink($cdndir . "/content", $portaldir);
            symlink($flashdir . "/streams/_definst_", $homedir . "/flash");
            symlink($flashdir . "/streams/_definst_", $cdndir . "/content");

            // Adjust permissions
            chown($flashdir, 30200);
            chown($cdndir, 30200);
            chown($dldir, 30200);
            chown($flashlivedir, 30200);
            chown($cdnlivedir, 30200);
            chown($portal_updir, 30200);
            chown($portaldir, 30200);

            chgrp($flashdir, 30200);
            chgrp($cdndir, 30200);
            chgrp($dldir, 30200);
            chgrp($flashlivedir, 30200);
            chgrp($cdnlivedir, 30200);
            chgrp($portal_updir, 30200);
            chgrp($portaldir, 30200);

            // finalize
            touch($homedir . "/.banner");
            chown($homedir . "/.banner", 30200);
            chgrp($homedir . "/.banner", 30200);

            return true;
        }
    }

    public function addCustomer_portalFinalize($partnerId, $sess) {
        // finish portal provisioning, create drop folders, etc.		
        $this->_ci->curl->create($this->mp_url . "service=multirequest&action=null");
        $config_data = array(
            // Create FTP Drop Folder
            "1:service" => "dropfolder_dropfolder",
            "1:action" => "add",
            "1:dropFolder:type" => "1",
            "1:dropFolder:status" => "1",
            "1:dropFolder:path" => "/oc1_fc/store001/cdn/" . $partnerId . "/upload",
            "1:dropFolder:partnerId" => $partnerId,
            "1:dropFolder:objectType" => "KalturaDropFolder",
            "1:dropFolder:ignoreFileNamePatterns" => ".pureftpd-upload*",
            "1:dropFolder:fileSizeCheckInterval" => "30",
            "1:dropFolder:fileNamePatterns" => "*",
            "1:dropFolder:name" => "FTP Upload",
            "1:dropFolder:fileHandlerType" => "1",
            "1:dropFolder:fileHandlerConfig:objectType" => "KalturaDropFolderContentFileHandlerConfig",
            "1:dropFolder:fileHandlerConfig:contentMatchPolicy" => "1",
            "1:dropFolder:fileHandlerConfig:slugRegex" => "/(?P<referenceId>.+)[.]\w{3,}/",
            "1:dropFolder:fileDeletePolicy" => "2",
            "1:dropFolder:description" => "FTP Uploading",
            "1:dropFolder:dc" => "0",
            "1:dropFolder:autoFileDeleteDays" => "1",
            // Create Live Recording Drop Folder
            "2:service" => "dropfolder_dropfolder",
            "2:action" => "add",
            "2:dropFolder:type" => "1",
            "2:dropFolder:status" => "1",
            "2:dropFolder:path" => "/oc1_fc/store001/cdn_portal/liveupload/" . $partnerId . "-live",
            "2:dropFolder:partnerId" => $partnerId,
            "2:dropFolder:objectType" => "KalturaDropFolder",
            "2:dropFolder:fileSizeCheckInterval" => "30",
            "2:dropFolder:fileNamePatterns" => "*",
            "2:dropFolder:name" => "Live Recordings",
            "2:dropFolder:fileHandlerType" => "1",
            "2:dropFolder:fileHandlerConfig:objectType" => "KalturaDropFolderContentFileHandlerConfig",
            "2:dropFolder:fileHandlerConfig:contentMatchPolicy" => "1",
            "2:dropFolder:fileHandlerConfig:slugRegex" => "/(?P<referenceId>.+)[.]\w{3,}/",
            "2:dropFolder:fileDeletePolicy" => "2",
            "2:dropFolder:description" => "Live Stream Archive",
            "2:dropFolder:dc" => "0",
            "2:dropFolder:autoFileDeleteDays" => "1",
            // Close Portal Session
            "3:service" => "session",
            "3:action" => "end",
            "ks" => $sess
        );

        $this->_ci->curl->post($config_data);
        $config_response = $this->_ci->curl->execute();

        return $partnerId;
    }

    public function statusCustomer($partnerId, $status) {
        // Now a systemparter API call
        // Portal Status Numbers:	3: Removed (Deleted), 2: Blocked (Suspended), 1: Active (Unblocked)
        // MGMT Status Numbers:	0: Blocked/Deleted, 1: Active
        // curl: http://mediaportal.streamingmediahosting.com/api_v3/index.php?service=systempartner_systempartner&action=updateStatus&apiVersion=3.1.4&format=2&clientTag=php5zend&partnerId=10115&status=2&ks=ZGVlY2NhZjk3MmE3OTNjNGYzYjY4NTM1MzE0NWY5MDgzNDMzYmM4MnwtMjstMjsxMzYwMTk2NjM3OzI7MTM2MDExMDIzNy4zMDAxO3N1cHBvcnRAc3RyZWFtaW5nbWVkaWFob3N0aW5nLmNvbTsqOzs%3D&kalsig=f88a53d36c29d06670467e2b26280ea7


        if ($partnerId && $status) {
            // get API Session
            $sess = $this->start_sess();

            if ($sess) {
                // Setup Call
                $this->_ci->curl->create($this->mp_url . "service=systempartner_systempartner&action=updateStatus&apiVersion=3.1.4&format=2&clientTag=php5zend&partnerId=" . $partnerId . "&status=" . $status . "&ks=" . $sess);

                // Execute
                $resp = $this->_ci->curl->execute();

                if ($resp) {
                    //syslog(LOG_NOTICE, "updateStatus: APIresp: ".print_r($resp,true));
                    return true;
                }
            }
            // Error, failed to get session
        }
        // Error, no partner or status provided
    }

    public function deleteCustomer($partnerId) {
        if ($partnerId) {
            // remove the portal account
            $status = $this->statusCustomer($partnerId, 3);

            if ($status) {
                // remove ACNT SAN Dirs
                $rootdir = "/oc1_fc/store001";

                $san_dirs = array(
                    "homedir" => $rootdir . "/home_fc/" . $partnerId,
                    "flashdir" => $rootdir . "/flash/" . $partnerId,
                    "cdndir" => $rootdir . "/cdn/" . $partnerId,
                    "flashlivedir" => $rootdir . "/flash/" . $partnerId . "-live",
                    "cdnlivedir" => $rootdir . "/cdn/" . $partnerId . "-live",
                    "dldir" => $rootdir . "/http/" . $partnerId,
                    "portaldir" => $rootdir . "/kaltura/web/content/entry/data/" . $partnerId,
                    "portal_updir" => $rootdir . "/cdn_portal/liveupload/" . $partnerId . "-live"
                );

                // Loop through SAN dir array, remove any existing directories & all contents
                foreach ($san_dirs as $dir) {
                    if (file_exists($dir)) {
                        // delete existing dirs
                        passthru("rm -rfv " . $dir . "/ >> /oc1/store001/smh/acnt_del.log");
                    }
                }
                return true;
            }
        } else {
            // Error, no parter ID provided
        }
    }

    public function updateCustomer($id, $data) {
        //syslog(LOG_NOTICE, "SMPortal:updateCustomer -- Started!");
        // set sess
        $sess = $this->start_sess();

        syslog(LOG_NOTICE, "SMPortal:updateCustomer -- Sess: " . $sess);

        // Vars
        $updated_keys = array();
        $excluded = array('adminName', 'adminEmail', 'partnerId', 'kmc_version');

        // verify params
        if ($id && is_array($data)) {
            syslog(LOG_NOTICE, "SMPortal:updateCustomer -- id: " . $id . ", data: " . print_r($data, true));

            // setup base required vars
            $partnerId = $id;
            $update = $data;

            // Get child account data
            $partner_data = $this->_ci->portal_model->getAccount($partnerId);

            // build post string/array
            $update_template = "apiVersion=3.1.4&format=2&clientTag=php5zend&partnerId=" . $partner_data['id'] . "&configuration%3AobjectType=KalturaSystemPartnerConfiguration&configuration%3Aid=" . $partner_data['id'] . "&configuration%3ApartnerName=" . $partner_data['partner_name'] . "&configuration%3Adescription=" . $partner_data['description'] . "&configuration%3AadminName=" . $partner_data['admin_name'] . "&configuration%3AadminEmail=" . $partner_data['admin_email'] . "&configuration%3Ahost=0&configuration%3AcdnHost=0&configuration%3ApartnerPackage=" . $partner_data['partner_package'] . "&configuration%3AmoderateContent=0&configuration%3ArtmpUrl=0&configuration%3AstorageDeleteFromKaltura=0&configuration%3AstorageServePriority=1&configuration%3AkmcVersion=" . $partner_data['kmc_version'] . "&configuration%3ArestrictThumbnailByKs=0&configuration%3AdefThumbOffset=3&configuration%3AdefThumbDensity=0&configuration%3AimportRemoteSourceForConvert=0&configuration%3Apermissions%3A0%3AobjectType=KalturaPermission&configuration%3Apermissions%3A0%3Atype=2&configuration%3Apermissions%3A0%3Aname=FEATURE_ENTRY_REPLACEMENT_APPROVAL&configuration%3Apermissions%3A0%3Astatus=1&configuration%3Apermissions%3A1%3AobjectType=KalturaPermission&configuration%3Apermissions%3A1%3Atype=2&configuration%3Apermissions%3A1%3Aname=FEATURE_ANALYTICS_TAB&configuration%3Apermissions%3A1%3Astatus=1&configuration%3Apermissions%3A2%3AobjectType=KalturaPermission&configuration%3Apermissions%3A2%3Atype=2&configuration%3Apermissions%3A2%3Aname=FEATURE_LIVE_STREAM&configuration%3Apermissions%3A2%3Astatus=1&configuration%3Apermissions%3A3%3AobjectType=KalturaPermission&configuration%3Apermissions%3A3%3Atype=2&configuration%3Apermissions%3A3%3Aname=FEATURE_VAST&configuration%3Apermissions%3A3%3Astatus=1&configuration%3Apermissions%3A4%3AobjectType=KalturaPermission&configuration%3Apermissions%3A4%3Atype=3&configuration%3Apermissions%3A4%3Aname=METADATA_PLUGIN_PERMISSION&configuration%3Apermissions%3A4%3Astatus=1&configuration%3Apermissions%3A5%3AobjectType=KalturaPermission&configuration%3Apermissions%3A5%3Atype=3&configuration%3Apermissions%3A5%3Aname=CONTENTDISTRIBUTION_PLUGIN_PERMISSION&configuration%3Apermissions%3A5%3Astatus=1&configuration%3Apermissions%3A6%3AobjectType=KalturaPermission&configuration%3Apermissions%3A6%3Atype=2&configuration%3Apermissions%3A6%3Aname=FEATURE_REMOTE_STORAGE&configuration%3Apermissions%3A6%3Astatus=2&configuration%3Apermissions%3A7%3AobjectType=KalturaPermission&configuration%3Apermissions%3A7%3Atype=2&configuration%3Apermissions%3A7%3Aname=FEATURE_MOBILE_FLAVORS&configuration%3Apermissions%3A7%3Astatus=1&configuration%3Apermissions%3A8%3AobjectType=KalturaPermission&configuration%3Apermissions%3A8%3Atype=2&configuration%3Apermissions%3A8%3Aname=FEATURE_508_PLAYERS&configuration%3Apermissions%3A8%3Astatus=1&configuration%3Apermissions%3A9%3AobjectType=KalturaPermission&configuration%3Apermissions%3A9%3Atype=2&configuration%3Apermissions%3A9%3Aname=FEATURE_MULTI_FLAVOR_INGESTION&configuration%3Apermissions%3A9%3Astatus=1&configuration%3Apermissions%3A10%3AobjectType=KalturaPermission&configuration%3Apermissions%3A10%3Atype=2&configuration%3Apermissions%3A10%3Aname=FEATURE_REMOTE_STORAGE_INGEST&configuration%3Apermissions%3A10%3Astatus=2&configuration%3Apermissions%3A11%3AobjectType=KalturaPermission&configuration%3Apermissions%3A11%3Atype=3&configuration%3Apermissions%3A11%3Aname=DROPFOLDER_PLUGIN_PERMISSION&configuration%3Apermissions%3A11%3Astatus=1&configuration%3Apermissions%3A12%3AobjectType=KalturaPermission&configuration%3Apermissions%3A12%3Atype=2&configuration%3Apermissions%3A12%3Aname=CONTENT_INGEST_DROP_FOLDER_MATCH&configuration%3Apermissions%3A12%3Astatus=2&configuration%3Apermissions%3A13%3AobjectType=KalturaPermission&configuration%3Apermissions%3A13%3Atype=2&configuration%3Apermissions%3A13%3Aname=FEATURE_ENTRY_REPLACEMENT&configuration%3Apermissions%3A13%3Astatus=2&configuration%3Apermissions%3A14%3AobjectType=KalturaPermission&configuration%3Apermissions%3A14%3Atype=2&configuration%3Apermissions%3A14%3Aname=FEATURE_CLIP_MEDIA&configuration%3Apermissions%3A14%3Astatus=1&configuration%3Apermissions%3A15%3AobjectType=KalturaPermission&configuration%3Apermissions%3A15%3Atype=2&configuration%3Apermissions%3A15%3Aname=FEATURE_EMAIL_INGEST&configuration%3Apermissions%3A15%3Astatus=2&configuration%3Apermissions%3A16%3AobjectType=KalturaPermission&configuration%3Apermissions%3A16%3Atype=3&configuration%3Apermissions%3A16%3Aname=CUEPOINT_PLUGIN_PERMISSION&configuration%3Apermissions%3A16%3Astatus=1&configuration%3Apermissions%3A17%3AobjectType=KalturaPermission&configuration%3Apermissions%3A17%3Atype=3&configuration%3Apermissions%3A17%3Aname=ADCUEPOINT_PLUGIN_PERMISSION&configuration%3Apermissions%3A17%3Astatus=1&configuration%3Apermissions%3A18%3AobjectType=KalturaPermission&configuration%3Apermissions%3A18%3Atype=3&configuration%3Apermissions%3A18%3Aname=CODECUEPOINT_PLUGIN_PERMISSION&configuration%3Apermissions%3A18%3Astatus=1&configuration%3Apermissions%3A19%3AobjectType=KalturaPermission&configuration%3Apermissions%3A19%3Atype=3&configuration%3Apermissions%3A19%3Aname=ANNOTATION_PLUGIN_PERMISSION&configuration%3Apermissions%3A19%3Astatus=1&configuration%3Apermissions%3A20%3AobjectType=KalturaPermission&configuration%3Apermissions%3A20%3Atype=3&configuration%3Apermissions%3A20%3Aname=ATTACHMENT_PLUGIN_PERMISSION&configuration%3Apermissions%3A20%3Astatus=1&configuration%3Apermissions%3A21%3AobjectType=KalturaPermission&configuration%3Apermissions%3A21%3Atype=3&configuration%3Apermissions%3A21%3Aname=CAPTION_PLUGIN_PERMISSION&configuration%3Apermissions%3A21%3Astatus=1&configuration%3Apermissions%3A22%3AobjectType=KalturaPermission&configuration%3Apermissions%3A22%3Atype=3&configuration%3Apermissions%3A22%3Aname=CAPTIONSEARCH_PLUGIN_PERMISSION&configuration%3Apermissions%3A22%3Astatus=1&configuration%3Apermissions%3A23%3AobjectType=KalturaPermission&configuration%3Apermissions%3A23%3Atype=3&configuration%3Apermissions%3A23%3Aname=VIRUSSCAN_PLUGIN_PERMISSION&configuration%3Apermissions%3A23%3Astatus=2&configuration%3Apermissions%3A24%3AobjectType=KalturaPermission&configuration%3Apermissions%3A24%3Atype=3&configuration%3Apermissions%3A24%3Aname=AUDIT_PLUGIN_PERMISSION&configuration%3Apermissions%3A24%3Astatus=2&configuration%3Apermissions%3A25%3AobjectType=KalturaPermission&configuration%3Apermissions%3A25%3Atype=2&configuration%3Apermissions%3A25%3Aname=FEATURE_PS2_PERMISSIONS_VALIDATION&configuration%3Apermissions%3A25%3Astatus=2&configuration%3Apermissions%3A26%3AobjectType=KalturaPermission&configuration%3Apermissions%3A26%3Atype=2&configuration%3Apermissions%3A26%3Aname=FEATURE_REMOTE_STORAGE_DELIVERY_PRIORITY&configuration%3Apermissions%3A26%3Astatus=2&configuration%3Apermissions%3A27%3AobjectType=KalturaPermission&configuration%3Apermissions%3A27%3Atype=2&configuration%3Apermissions%3A27%3Aname=FEATURE_DISABLE_KMC_LIST_THUMBNAILS&configuration%3Apermissions%3A27%3Astatus=2&configuration%3Apermissions%3A28%3AobjectType=KalturaPermission&configuration%3Apermissions%3A28%3Atype=2&configuration%3Apermissions%3A28%3Aname=FEATURE_DISABLE_KMC_DRILL_DOWN_THUMB_RESIZE&configuration%3Apermissions%3A28%3Astatus=2&configuration%3Apermissions%3A29%3AobjectType=KalturaPermission&configuration%3Apermissions%3A29%3Atype=2&configuration%3Apermissions%3A29%3Aname=IMPORT_REMOTE_CAPTION_FOR_INDEXING&configuration%3Apermissions%3A29%3Astatus=2&configuration%3Apermissions%3A30%3AobjectType=KalturaPermission&configuration%3Apermissions%3A30%3Atype=2&configuration%3Apermissions%3A30%3Aname=FEATURE_METADATA_NO_VALIDATION&configuration%3Apermissions%3A30%3Astatus=2&configuration%3Apermissions%3A31%3AobjectType=KalturaPermission&configuration%3Apermissions%3A31%3Atype=2&configuration%3Apermissions%3A31%3Aname=FEATURE_KMC_DRILLDOWN_TAGS_COLUMN&configuration%3Apermissions%3A31%3Astatus=2&configuration%3AallowMultiNotification=0&configuration%3AloginBlockPeriod=0&configuration%3AnumPrevPassToKeep=0&configuration%3ApassReplaceFreq=432000000&configuration%3AisFirstLogin=1&configuration%3ApartnerGroupType=" . $partner_data['partner_group_type'] . "&configuration%3ApartnerParentId=" . $partner_data['partner_parent_id'] . "&configuration%3Alimits%3A0%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A0%3Atype=USER_LOGIN_ATTEMPTS&configuration%3Alimits%3A0%3Amax=5000&configuration%3Alimits%3A1%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A1%3Atype=MONTHLY_BANDWIDTH&configuration%3Alimits%3A1%3Amax=0&configuration%3Alimits%3A1%3AoveragePrice=0&configuration%3Alimits%3A1%3AoverageUnit=0&configuration%3Alimits%3A2%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A2%3Atype=MONTHLY_STORAGE&configuration%3Alimits%3A2%3Amax=0&configuration%3Alimits%3A2%3AoveragePrice=0&configuration%3Alimits%3A2%3AoverageUnit=0&configuration%3Alimits%3A3%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A3%3Atype=MONTHLY_STORAGE_AND_BANDWIDTH&configuration%3Alimits%3A3%3Amax=0&configuration%3Alimits%3A3%3AoveragePrice=0&configuration%3Alimits%3A3%3AoverageUnit=0&configuration%3Alimits%3A4%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A4%3Atype=ADMIN_LOGIN_USERS&configuration%3Alimits%3A4%3Amax=3&configuration%3Alimits%3A4%3AoveragePrice=0&configuration%3Alimits%3A4%3AoverageUnit=0&configuration%3Alimits%3A5%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A5%3Atype=PUBLISHERS&configuration%3Alimits%3A5%3Amax=0&configuration%3Alimits%3A5%3AoveragePrice=0&configuration%3Alimits%3A5%3AoverageUnit=0&configuration%3Alimits%3A6%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A6%3Atype=MONTHLY_STREAM_ENTRIES&configuration%3Alimits%3A6%3Amax=0&configuration%3Alimits%3A6%3AoveragePrice=0&configuration%3Alimits%3A6%3AoverageUnit=0&configuration%3Alimits%3A7%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A7%3Atype=END_USERS&configuration%3Alimits%3A7%3Amax=0&configuration%3Alimits%3A7%3AoveragePrice=0&configuration%3Alimits%3A7%3AoverageUnit=0&configuration%3Alimits%3A8%3AobjectType=KalturaSystemPartnerLimit&configuration%3Alimits%3A8%3Atype=ENTRIES&configuration%3Alimits%3A8%3Amax=0&configuration%3Alimits%3A8%3AoveragePrice=0&configuration%3Alimits%3A8%3AoverageUnit=0&configuration%3AextendedFreeTrailExpiryReason=0&configuration%3AextendedFreeTrailExpiryDate=0&configuration%3AextendedFreeTrail=0&configuration%3AenableBulkUploadNotificationsEmails=0&configuration%3AdeliveryRestrictions=0&configuration%3AbulkUploadNotificationsEmail=shardypt189%40streamingmediahosting.com&configuration%3AinternalUse=0&configuration%3AuserLoginAttempts%3AUSER_LOGIN_ATTEMPTS_type=USER_LOGIN_ATTEMPTS&configuration%3AuserLoginAttempts%3AUSER_LOGIN_ATTEMPTS_max=5000&configuration%3AmonthlyBandwidth%3AMONTHLY_BANDWIDTH_type=MONTHLY_BANDWIDTH&configuration%3AmonthlyBandwidth%3AMONTHLY_BANDWIDTH_max=0&configuration%3AmonthlyBandwidth%3AMONTHLY_BANDWIDTH_overagePrice=0&configuration%3AmonthlyBandwidth%3AMONTHLY_BANDWIDTH_overageUnit=0&configuration%3AmonthlyStorage%3AMONTHLY_STORAGE_type=MONTHLY_STORAGE&configuration%3AmonthlyStorage%3AMONTHLY_STORAGE_max=0&configuration%3AmonthlyStorage%3AMONTHLY_STORAGE_overagePrice=0&configuration%3AmonthlyStorage%3AMONTHLY_STORAGE_overageUnit=0&configuration%3AmonthlyStorageAndBandwidth%3AMONTHLY_STORAGE_AND_BANDWIDTH_type=MONTHLY_STORAGE_AND_BANDWIDTH&configuration%3AmonthlyStorageAndBandwidth%3AMONTHLY_STORAGE_AND_BANDWIDTH_max=0&configuration%3AmonthlyStorageAndBandwidth%3AMONTHLY_STORAGE_AND_BANDWIDTH_overagePrice=0&configuration%3AmonthlyStorageAndBandwidth%3AMONTHLY_STORAGE_AND_BANDWIDTH_overageUnit=0&configuration%3AadminLoginUsers%3AADMIN_LOGIN_USERS_type=ADMIN_LOGIN_USERS&configuration%3AadminLoginUsers%3AADMIN_LOGIN_USERS_max=3&configuration%3AadminLoginUsers%3AADMIN_LOGIN_USERS_overagePrice=0&configuration%3AadminLoginUsers%3AADMIN_LOGIN_USERS_overageUnit=0&configuration%3Apublishers%3APUBLISHERS_type=PUBLISHERS&configuration%3Apublishers%3APUBLISHERS_max=0&configuration%3Apublishers%3APUBLISHERS_overagePrice=0&configuration%3Apublishers%3APUBLISHERS_overageUnit=0&configuration%3AmonthlyStreamEntries%3AMONTHLY_STREAM_ENTRIES_type=MONTHLY_STREAM_ENTRIES&configuration%3AmonthlyStreamEntries%3AMONTHLY_STREAM_ENTRIES_max=0&configuration%3AmonthlyStreamEntries%3AMONTHLY_STREAM_ENTRIES_overagePrice=0&configuration%3AmonthlyStreamEntries%3AMONTHLY_STREAM_ENTRIES_overageUnit=0&configuration%3AendUsers%3AEND_USERS_type=END_USERS&configuration%3AendUsers%3AEND_USERS_max=0&configuration%3AendUsers%3AEND_USERS_overagePrice=0&configuration%3AendUsers%3AEND_USERS_overageUnit=0&configuration%3Aentries%3AENTRIES_type=ENTRIES&configuration%3Aentries%3AENTRIES_max=0&configuration%3Aentries%3AENTRIES_overagePrice=0&configuration%3Aentries%3AENTRIES_overageUnit=0&configuration%3AcheckboxHost=0&configuration%3AcheckboxCdnHost=0&configuration%3AcheckboxRtmpUrl=0&configuration%3AcheckboxDeliveryRestrictions=0&configuration%3AfeatureDisableKmcDrillDownThumbResize=0&configuration%3AfeatureDisableKmcListThumbnails=0&configuration%3AfeatureRemoteStorageDeliveryPriority=0&configuration%3AfeatureEntryReplacementApproval=1&configuration%3AcontentIngestDropFolderMatch=0&configuration%3Afeature508Players=1&configuration%3AfeatureVast=1&configuration%3AauditPluginPermission=0&configuration%3AcaptionPluginPermission=1&configuration%3AcaptionsearchPluginPermission=1&configuration%3AimportRemoteCaptionForIndexing=0&configuration%3AfeatureClipMedia=1&configuration%3AcontentdistributionPluginPermission=1&configuration%3AdropfolderPluginPermission=1&configuration%3AfeatureEmailIngest=0&configuration%3AfeatureRemoteStorageIngest=0&configuration%3AfeatureEntryReplacement=0&configuration%3AfeatureMultiFlavorIngestion=1&configuration%3AmetadataPluginPermission=1&configuration%3AfeatureMetadataNoValidation=0&configuration%3AattachmentPluginPermission=1&configuration%3AfeaturePs2PermissionsValidation=0&configuration%3AfeatureLiveStream=1&configuration%3AfeatureMobileFlavors=1&configuration%3AfeatureRemoteStorage=0&configuration%3AfeatureAnalyticsTab=1&configuration%3AadcuepointPluginPermission=1&configuration%3AannotationPluginPermission=1&configuration%3AcodecuepointPluginPermission=1&configuration%3AcuepointPluginPermission=1&configuration%3AfeatureKmcDrilldownTagsColumn=0&configuration%3AvirusscanPluginPermission=0&ks=" . $sess;

            parse_str($update_template, $update_arr);

            syslog(LOG_NOTICE, "SMPortal:updateCustomer -- parse_str: " . print_r($update_arr, true));

            // Find updated params, and add new values to array
            foreach ($update as $key => $value) {
                syslog(LOG_NOTICE, "SMPortal:updateCustomer -- checking key: " . $key);
                if (array_key_exists($key, $update_arr)) {
                    syslog(LOG_NOTICE, "SMPortal:addCustomer -- key " . $key . " exists in update_arr!");
                    if ($update_arr[$key] !== $value) {
                        if (!in_array($key, $excluded)) {
                            $update_arr[$key] = $value;
                            $updated_keys[$key] = $value;
                            syslog(LOG_NOTICE, "SMPortal:updateCustomer -- added key: " . $key);
                        }
                    }
                }
            }

            syslog(LOG_NOTICE, "SMPortal:updateCustomer -- Updates: " . print_r($updated_keys, true));

            if (!empty($updated_keys)) {
                syslog(LOG_NOTICE, "SMPortal -- update: Starting update!");

                // Setup AC Call
                //$this->_ci->curl->create($this->ac_hst); 
                // Set advanced options
                //$this->_ci->curl->options(array(CURLOPT_RETURNTRANSFER => 0, CURLOPT_HEADER => 0, CURLOPT_COOKIESESSION => 1, CURLOPT_FOLLOWLOCATION => 1));

                $this->_ci->curl->create($this->mp_url . "service=systempartner_systempartner&action=updateConfiguration");

                // setup query
                $post_data = http_build_query($update_arr);
                syslog(LOG_NOTICE, "SMPortal -- query_str " . print_r($post_data, true));

                // setup post data
                $this->_ci->curl->post($post_data);

                //	syslog(LOG_NOTICE, "AC_STR: ".print_r($this->ac_str,true));
                // execute the call
                $update_response = $this->_ci->curl->execute();

                syslog(LOG_NOTICE, "SMPortal -- update: " . print_r($update_response, true));

                //passthru("touch /oc1/store001/smh/update_resp.log");
                //passthru("/bin/echo '".$update_response."' > /oc1/store001/smh/update_resp.log");
                // verify API response
                if ($update_response !== null) {
                    return $update_response;
                }
            } else {
                syslog(LOG_NOTICE, "SMPortal:updateCustomer -- Updates: No updates required!");
                return true;
            }
            // Error, nothing to do!
        }
        return false;
    }

    public function updateCustomer_old($id, $data) {
        // set sess
        $sess = $this->start_sess();

        // Vars
        $updated_keys = array();

        // verify params
        if ($id && is_array($data)) {
            // setup base required vars
            $partnerId = $id;
            $update = $data;

            // Get child account data
            $partner_data = $this->_ci->portal_model->getAccount($partnerId);

            // Create post data array
            $update_template = array
                (
                // Admin Console Post Data
                //	'USER_LOGIN_ATTEMPTS[USER_LOGIN_ATTEMPTS_type]' => 'USER_LOGIN_ATTEMPTS',
                //	'MONTHLY_BANDWIDTH[MONTHLY_BANDWIDTH_type]' => 'MONTHLY_BANDWIDTH',
                //	'MONTHLY_STORAGE[MONTHLY_STORAGE_type]' => 'MONTHLY_STORAGE',
                //	'MONTHLY_STORAGE_AND_BANDWIDTH[MONTHLY_STORAGE_AND_BANDWIDTH_type]' => 'MONTHLY_STORAGE_AND_BANDWIDTH',
                //	'ADMIN_LOGIN_USERS[ADMIN_LOGIN_USERS_type]' => 'ADMIN_LOGIN_USERS',
                //	'PUBLISHERS[PUBLISHERS_type]' => 'PUBLISHERS',
                //	'MONTHLY_STREAM_ENTRIES[MONTHLY_STREAM_ENTRIES_type]' => 'MONTHLY_STREAM_ENTRIES',
                //	'END_USERS[END_USERS_type]' => 'END_USERS',
                //	'ENTRIES[ENTRIES_type]' => 'ENTRIES',
                'partner_name' => $partner_data['partner_name'],
                'description' => $partner_data['description'],
                'admin_name' => $partner_data['admin_name'],
                'admin_email' => $partner_data['admin_email'],
                'id' => $partner_data['id'],
                'kmc_version' => $partner_data['kmc_version'],
                //	'crossLine' => '',
                'partner_group_type' => $partner_data['partner_group_type'],
                'partner_parent_id' => $partner_data['partner_parent_id'],
                //	'crossLine' => '',
                //	'checkbox_host' => 0,
                //	'host' => 0,
                //	'checkbox_cdn_host' => 0,
                //	'cdn_host' => 0,
                //	'checkbox_rtmp_url' => 0,
                //	'rtmp_url' => 0,
                //	'checkbox_delivery_restrictions' => 0,
                //	'delivery_restrictions' => 0,
                //	'restrict_thumbnail_by_ks' => 0,
                //	'crossLine' => '',
                //	'storage_serve_priority' => 1,
                //	'storage_delete_from_kaltura' => 0,
                //	'import_remote_source_for_convert' => 0,
                //	'FEATURE_DISABLE_KMC_DRILL_DOWN_THUMB_RESIZE' => 0,
                //	'FEATURE_DISABLE_KMC_LIST_THUMBNAILS' => 0,
                //	'FEATURE_REMOTE_STORAGE_DELIVERY_PRIORITY' => 0,
                //	'crossLine' => '',
                //	'notifications_config' => '',
                //	'allow_multi_notification' => 0,
                //	'crossLine' => '',
                //	'def_thumb_offset' => 3,
                //	'def_thumb_density' => 0,
                //	'moderate_content' => 0,
                //	'FEATURE_ENTRY_REPLACEMENT_APPROVAL' => 0,
                //	'FEATURE_ENTRY_REPLACEMENT_APPROVAL' => 1,
                //	'CONTENT_INGEST_DROP_FOLDER_MATCH' => 0,
                //	'enable_bulk_upload_notifications_emails' => 0,
                //	'bulk_upload_notifications_email' => $partner_data['admin_email'],
                //	'crossLine' => '',
                //	'USER_LOGIN_ATTEMPTS[USER_LOGIN_ATTEMPTS_max]' => 5000,
                //	'login_block_period' => 0,
                //	'num_prev_pass_to_keep' => 0,
                //	'pass_replace_freq' => 432000000,
                'partner_package' => $partner_data['partner_package'],
                //	'partner_package_class_of_service' => '',
                //	'vertical_clasiffication' => '',
                //	'crm_id' => '',
                //	'crm_link' => '',
                //	'internal_use' => 0,
                //	'crossLine' => '',
                //	'extended_free_trail' => 0,
                //	'extended_free_trail_expiry_reason' => 0,
                //	'is_first_login' => 0,
                //	'is_first_login' => 1,
                //	'crossLine' => '',
                //	'includedUsageLabel' => '',
                //	'MONTHLY_STORAGE_AND_BANDWIDTH[MONTHLY_STORAGE_AND_BANDWIDTH_max]' => 0,
                //	'MONTHLY_STORAGE_AND_BANDWIDTH[MONTHLY_STORAGE_AND_BANDWIDTH_overagePrice]' => 0,
                //	'MONTHLY_STORAGE_AND_BANDWIDTH[MONTHLY_STORAGE_AND_BANDWIDTH_overageUnit]' => 0,
                //	'MONTHLY_BANDWIDTH[MONTHLY_BANDWIDTH_max]' => 0,
                //	'MONTHLY_BANDWIDTH[MONTHLY_BANDWIDTH_overagePrice]' => 0,
                //	'MONTHLY_BANDWIDTH[MONTHLY_BANDWIDTH_overageUnit]' => 0,
                //	'MONTHLY_STORAGE[MONTHLY_STORAGE_max]' => 0,
                //	'MONTHLY_STORAGE[MONTHLY_STORAGE_overagePrice]' => 0,
                //	'MONTHLY_STORAGE[MONTHLY_STORAGE_overageUnit]' => 0,
                //	'ADMIN_LOGIN_USERS[ADMIN_LOGIN_USERS_max]' => 3,
                //	'ADMIN_LOGIN_USERS[ADMIN_LOGIN_USERS_overagePrice]' => 0,
                //	'ADMIN_LOGIN_USERS[ADMIN_LOGIN_USERS_overageUnit]' => 0,
                //	'PUBLISHERS[PUBLISHERS_max]' => 0,
                //	'PUBLISHERS[PUBLISHERS_overagePrice]' => 0,
                //	'PUBLISHERS[PUBLISHERS_overageUnit]' => 0,
                //	'MONTHLY_STREAM_ENTRIES[MONTHLY_STREAM_ENTRIES_max]' => 0,
                //	'MONTHLY_STREAM_ENTRIES[MONTHLY_STREAM_ENTRIES_overagePrice]' => 0,
                //	'MONTHLY_STREAM_ENTRIES[MONTHLY_STREAM_ENTRIES_overageUnit]' => 0,
                //	'END_USERS[END_USERS_max]' => 0,
                //	'END_USERS[END_USERS_overagePrice]' => 0,
                //	'END_USERS[END_USERS_overageUnit]' => 0,
                //	'ENTRIES[ENTRIES_max]' => 0,
                //	'ENTRIES[ENTRIES_overagePrice]' => 0,
                //	'ENTRIES[ENTRIES_overageUnit]' => 0,
                //	'crossLine' => '',
                //	'FEATURE_508_PLAYERS' => 0,
                //	'FEATURE_508_PLAYERS' => 1,
                //	'FEATURE_VAST' => 0,
                //	'FEATURE_VAST' => 1,
                //	'AUDIT_PLUGIN_PERMISSION' => 0,
                //	'CAPTION_PLUGIN_PERMISSION' => 0,
                //	'CAPTION_PLUGIN_PERMISSION' => 1,
                //	'CAPTIONSEARCH_PLUGIN_PERMISSION' => 0,
                //	'CAPTIONSEARCH_PLUGIN_PERMISSION' => 1,
                //	'IMPORT_REMOTE_CAPTION_FOR_INDEXING' => 0,
                //	'FEATURE_CLIP_MEDIA' => 0,
                //	'FEATURE_CLIP_MEDIA' => 1,
                //	'CONTENTDISTRIBUTION_PLUGIN_PERMISSION' => 0,
                //	'CONTENTDISTRIBUTION_PLUGIN_PERMISSION' => 1,
                //	'DROPFOLDER_PLUGIN_PERMISSION' => 0,
                //	'DROPFOLDER_PLUGIN_PERMISSION' => 1,
                //	'FEATURE_EMAIL_INGEST' => 0,
                //	'FEATURE_REMOTE_STORAGE_INGEST' => 0,
                //	'FEATURE_ENTRY_REPLACEMENT' => 0,
                //	'FEATURE_MULTI_FLAVOR_INGESTION' => 0,
                //	'FEATURE_MULTI_FLAVOR_INGESTION' => 1,
                //	'METADATA_PLUGIN_PERMISSION' => 0,
                //	'METADATA_PLUGIN_PERMISSION' => 1,
                //	'FEATURE_METADATA_NO_VALIDATION' => 0,
                //	'ATTACHMENT_PLUGIN_PERMISSION' => 0,
                //	'ATTACHMENT_PLUGIN_PERMISSION' => 1,
                //	'FEATURE_PS2_PERMISSIONS_VALIDATION' => 0,
                //	'FEATURE_LIVE_STREAM' => 0,
                //	'FEATURE_LIVE_STREAM' => 1,
                //	'FEATURE_MOBILE_FLAVORS' => 0,
                //	'FEATURE_MOBILE_FLAVORS' => 1,
                //	'FEATURE_REMOTE_STORAGE' => 0,
                //	'FEATURE_ANALYTICS_TAB' => 0,
                //	'FEATURE_ANALYTICS_TAB' => 1,
                //	'ADCUEPOINT_PLUGIN_PERMISSION' => 0,
                //	'ADCUEPOINT_PLUGIN_PERMISSION' => 1,
                //	'ANNOTATION_PLUGIN_PERMISSION' => 0,
                //	'ANNOTATION_PLUGIN_PERMISSION' => 1,
                //	'CODECUEPOINT_PLUGIN_PERMISSION' => 0,
                //	'CODECUEPOINT_PLUGIN_PERMISSION' => 1,
                //	'CUEPOINT_PLUGIN_PERMISSION' => 0,
                //	'CUEPOINT_PLUGIN_PERMISSION' => 1,
                //	'FEATURE_KMC_DRILLDOWN_TAGS_COLUMN' => 0,
                'VIRUSSCAN_PLUGIN_PERMISSION' => 0
            );

            //$update_str = "USER_LOGIN_ATTEMPTS%5BUSER_LOGIN_ATTEMPTS_type%5D=USER_LOGIN_ATTEMPTS&MONTHLY_BANDWIDTH%5BMONTHLY_BANDWIDTH_type%5D=MONTHLY_BANDWIDTH&MONTHLY_STORAGE%5BMONTHLY_STORAGE_type%5D=MONTHLY_STORAGE&MONTHLY_STORAGE_AND_BANDWIDTH%5BMONTHLY_STORAGE_AND_BANDWIDTH_type%5D=MONTHLY_STORAGE_AND_BANDWIDTH&ADMIN_LOGIN_USERS%5BADMIN_LOGIN_USERS_type%5D=ADMIN_LOGIN_USERS&PUBLISHERS%5BPUBLISHERS_type%5D=PUBLISHERS&MONTHLY_STREAM_ENTRIES%5BMONTHLY_STREAM_ENTRIES_type%5D=MONTHLY_STREAM_ENTRIES&END_USERS%5BEND_USERS_type%5D=END_USERS&ENTRIES%5BENTRIES_type%5D=ENTRIES&partner_name=".$partner_data['partner_name']."&description=".$partner_data['description']."&admin_name=".$partner_data['admin_name']."&admin_email=".$partner_data['admin_email']."&id=".$partner_data['id']."&kmc_version=".$partner_data['kmc_version']."&crossLine=&partner_group_type=".$partner_data['partner_group_type']."&partner_parent_id=".$partner_data['partner_parent_id']."&crossLine=&checkbox_host=0&host=0&checkbox_cdn_host=0&cdn_host=0&checkbox_rtmp_url=0&rtmp_url=0&checkbox_delivery_restrictions=0&delivery_restrictions=0&restrict_thumbnail_by_ks=0&crossLine=&storage_serve_priority=1&storage_delete_from_kaltura=0&import_remote_source_for_convert=0&FEATURE_DISABLE_KMC_DRILL_DOWN_THUMB_RESIZE=0&FEATURE_DISABLE_KMC_LIST_THUMBNAILS=0&FEATURE_REMOTE_STORAGE_DELIVERY_PRIORITY=0&crossLine=&notifications_config=&allow_multi_notification=0&crossLine=&def_thumb_offset=3&def_thumb_density=0&moderate_content=0&FEATURE_ENTRY_REPLACEMENT_APPROVAL=0&FEATURE_ENTRY_REPLACEMENT_APPROVAL=1&CONTENT_INGEST_DROP_FOLDER_MATCH=0&enable_bulk_upload_notifications_emails=0&bulk_upload_notifications_email=".$partner_data['admin_email']."&crossLine=&USER_LOGIN_ATTEMPTS%5BUSER_LOGIN_ATTEMPTS_max%5D=5000&login_block_period=0&num_prev_pass_to_keep=0&pass_replace_freq=432000000&partner_package=".$partner_data['partner_package']."&partner_package_class_of_service=&vertical_clasiffication=&crm_id=&crm_link=&internal_use=0&crossLine=&extended_free_trail=0&extended_free_trail_expiry_reason=0&is_first_login=0&is_first_login=1&crossLine=&includedUsageLabel=&MONTHLY_STORAGE_AND_BANDWIDTH%5BMONTHLY_STORAGE_AND_BANDWIDTH_max%5D=0&MONTHLY_STORAGE_AND_BANDWIDTH%5BMONTHLY_STORAGE_AND_BANDWIDTH_overagePrice%5D=0&MONTHLY_STORAGE_AND_BANDWIDTH%5BMONTHLY_STORAGE_AND_BANDWIDTH_overageUnit%5D=0&MONTHLY_BANDWIDTH%5BMONTHLY_BANDWIDTH_max%5D=0&MONTHLY_BANDWIDTH%5BMONTHLY_BANDWIDTH_overagePrice%5D=0&MONTHLY_BANDWIDTH%5BMONTHLY_BANDWIDTH_overageUnit%5D=0&MONTHLY_STORAGE%5BMONTHLY_STORAGE_max%5D=0&MONTHLY_STORAGE%5BMONTHLY_STORAGE_overagePrice%5D=0&MONTHLY_STORAGE%5BMONTHLY_STORAGE_overageUnit%5D=0&ADMIN_LOGIN_USERS%5BADMIN_LOGIN_USERS_max%5D=3&ADMIN_LOGIN_USERS%5BADMIN_LOGIN_USERS_overagePrice%5D=0&ADMIN_LOGIN_USERS%5BADMIN_LOGIN_USERS_overageUnit%5D=0&PUBLISHERS%5BPUBLISHERS_max%5D=0&PUBLISHERS%5BPUBLISHERS_overagePrice%5D=0&PUBLISHERS%5BPUBLISHERS_overageUnit%5D=0&MONTHLY_STREAM_ENTRIES%5BMONTHLY_STREAM_ENTRIES_max%5D=0&MONTHLY_STREAM_ENTRIES%5BMONTHLY_STREAM_ENTRIES_overagePrice%5D=0&MONTHLY_STREAM_ENTRIES%5BMONTHLY_STREAM_ENTRIES_overageUnit%5D=0&END_USERS%5BEND_USERS_max%5D=0&END_USERS%5BEND_USERS_overagePrice%5D=0&END_USERS%5BEND_USERS_overageUnit%5D=0&ENTRIES%5BENTRIES_max%5D=0&ENTRIES%5BENTRIES_overagePrice%5D=0&ENTRIES%5BENTRIES_overageUnit%5D=0&crossLine=&FEATURE_508_PLAYERS=0&FEATURE_508_PLAYERS=1&FEATURE_VAST=0&FEATURE_VAST=1&AUDIT_PLUGIN_PERMISSION=0&CAPTION_PLUGIN_PERMISSION=0&CAPTION_PLUGIN_PERMISSION=1&CAPTIONSEARCH_PLUGIN_PERMISSION=0&CAPTIONSEARCH_PLUGIN_PERMISSION=1&IMPORT_REMOTE_CAPTION_FOR_INDEXING=0&FEATURE_CLIP_MEDIA=0&FEATURE_CLIP_MEDIA=1&CONTENTDISTRIBUTION_PLUGIN_PERMISSION=0&CONTENTDISTRIBUTION_PLUGIN_PERMISSION=1&DROPFOLDER_PLUGIN_PERMISSION=0&DROPFOLDER_PLUGIN_PERMISSION=1&FEATURE_EMAIL_INGEST=0&FEATURE_REMOTE_STORAGE_INGEST=0&FEATURE_ENTRY_REPLACEMENT=0&FEATURE_MULTI_FLAVOR_INGESTION=0&FEATURE_MULTI_FLAVOR_INGESTION=1&METADATA_PLUGIN_PERMISSION=0&METADATA_PLUGIN_PERMISSION=1&FEATURE_METADATA_NO_VALIDATION=0&ATTACHMENT_PLUGIN_PERMISSION=0&ATTACHMENT_PLUGIN_PERMISSION=1&FEATURE_PS2_PERMISSIONS_VALIDATION=0&FEATURE_LIVE_STREAM=0&FEATURE_LIVE_STREAM=1&FEATURE_MOBILE_FLAVORS=0&FEATURE_MOBILE_FLAVORS=1&FEATURE_REMOTE_STORAGE=0&FEATURE_ANALYTICS_TAB=0&FEATURE_ANALYTICS_TAB=1&ADCUEPOINT_PLUGIN_PERMISSION=0&ADCUEPOINT_PLUGIN_PERMISSION=1&ANNOTATION_PLUGIN_PERMISSION=0&ANNOTATION_PLUGIN_PERMISSION=1&CODECUEPOINT_PLUGIN_PERMISSION=0&CODECUEPOINT_PLUGIN_PERMISSION=1&CUEPOINT_PLUGIN_PERMISSION=0&CUEPOINT_PLUGIN_PERMISSION=1&FEATURE_KMC_DRILLDOWN_TAGS_COLUMN=0&VIRUSSCAN_PLUGIN_PERMISSION=0";
            //parse_str($update_str, $update_post);

            $excluded = array('admin_name', 'admin_email', 'id', 'kmc_version');

            // Find updated params, and add new values to array
            foreach ($update as $key => $value) {
                if (array_key_exists($key, $update_template)) {
                    if ($update_template[$key] !== $value) {
                        if (!in_array($key, $excluded)) {
                            $update_template[$key] = $value;
                            $updated_keys[$key] = $value;
                        }
                    }
                }
            }

            syslog(LOG_NOTICE, "SMPortal -- update: updated_keys " . print_r($updated_keys, true));

            if (!empty($updated_keys)) {
                syslog(LOG_NOTICE, "SMPortal -- update: Starting update!");

                // Setup AC Call
                $this->_ci->curl->create($this->ac_hst);
                // Set advanced options
                //$this->_ci->curl->options(array(CURLOPT_RETURNTRANSFER => 0, CURLOPT_HEADER => 0, CURLOPT_COOKIESESSION => 1, CURLOPT_FOLLOWLOCATION => 1));

                $cookies = $this->ac_cke;
                $this->_ci->curl->set_cookies($cookies);

                // setup query
                $post_data = http_build_query($update_template);
                syslog(LOG_NOTICE, "SMPortal -- query_str " . print_r($post_data, true));

                // setup post data
                $this->_ci->curl->post($post_data);

                //	syslog(LOG_NOTICE, "AC_STR: ".print_r($this->ac_str,true));
                // execute the call
                $update_response = $this->_ci->curl->execute();

                syslog(LOG_NOTICE, "SMPortal -- update: " . print_r($update_response, true));

                //passthru("touch /oc1/store001/smh/update_resp.log");
                //passthru("/bin/echo '".$update_response."' > /oc1/store001/smh/update_resp.log");
                // verify API response
                if ($update_response !== null) {
                    return $update_response;
                }
            }
        }
        // error, partner data not provided, or incorrect format (array needed)
        return false;
    }

}
