<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Ppv_affiliate extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('/ppv_dev/ppv_affiliate_model');
    }

    public function list_affiliate_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $affiliateList = $this->ppv_affiliate_model->get_affiliate_list($pid, $ks, $start, $length, $search, $draw);

        if (!$affiliateList) {

            $this->response($affiliateList, 200);
        }

        $this->response($affiliateList, 200); // 200 being the HTTP response code
    }

    public function add_affiliate_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $fname = $this->get('fname');
        $lname = $this->get('lname');
        $email = $this->get('email');
        $phone = $this->get('phone');
        $fax = $this->get('fax');
        $address1 = $this->get('address1');
        $address2 = $this->get('address2');
        $city = $this->get('city');
        $state = $this->get('state');
        $zip = $this->get('zip');
        $country = $this->get('country');
        $company = $this->get('company');
        $website = $this->get('website');
        $ppemail = $this->get('ppemail');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($fname) || $fname == null) {

            $this->response(array('error' => 'Missing first name'), 200);
        }

        if (!isset($lname) || $lname == null) {

            $this->response(array('error' => 'Missing last name'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $addAffiliate = $this->ppv_affiliate_model->add_affiliate($pid, $ks, $fname, $lname, $email, $phone, $fax, $address1, $address2, $city, $state, $zip, $country, $company, $website, $ppemail, $status, $tz);

        if (!$addAffiliate) {

            $this->response($addAffiliate, 200);
        }

        $this->response($addAffiliate, 200); // 200 being the HTTP response code
    }

    public function update_affiliate_get() {
        $pid = $this->get('pid');
        $aid = $this->get('aid');
        $ks = $this->get('ks');
        $fname = $this->get('fname');
        $lname = $this->get('lname');
        $email = $this->get('email');
        $phone = $this->get('phone');
        $fax = $this->get('fax');
        $address1 = $this->get('address1');
        $address2 = $this->get('address2');
        $city = $this->get('city');
        $state = $this->get('state');
        $zip = $this->get('zip');
        $country = $this->get('country');
        $company = $this->get('company');
        $website = $this->get('website');
        $ppemail = $this->get('ppemail');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($aid) || $aid == null) {

            $this->response(array('error' => 'Missing aid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($fname) || $fname == null) {

            $this->response(array('error' => 'Missing first name'), 200);
        }

        if (!isset($lname) || $lname == null) {

            $this->response(array('error' => 'Missing last name'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        $updateAffiliate = $this->ppv_affiliate_model->update_affiliate($pid, $aid, $ks, $fname, $lname, $email, $phone, $fax, $address1, $address2, $city, $state, $zip, $country, $company, $website, $ppemail, $tz);

        if (!$updateAffiliate) {

            $this->response($updateAffiliate, 200);
        }

        $this->response($updateAffiliate, 200); // 200 being the HTTP response code 
    }

    public function delete_affiliate_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $email = $this->get('email');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        $deleteAffiliate = $this->ppv_affiliate_model->delete_affiliate($pid, $ks, $email);

        if (!$deleteAffiliate) {

            $this->response($deleteAffiliate, 200);
        }

        $this->response($deleteAffiliate, 200); // 200 being the HTTP response code
    }

    public function update_affiliate_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $email = $this->get('email');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($email) || $email == null) {

            $this->response(array('error' => 'Missing email'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateAffiliateStatus = $this->ppv_affiliate_model->update_affiliate_status($pid, $ks, $email, $status, $tz);

        if (!$updateAffiliateStatus) {

            $this->response($updateAffiliateStatus, 200);
        }

        $this->response($updateAffiliateStatus, 200); // 200 being the HTTP response code
    }

    public function list_campaign_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');
        $currency = $this->get('currency');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $campaignList = $this->ppv_affiliate_model->get_campaign_list($pid, $ks, $start, $length, $search, $draw, $currency);

        if (!$campaignList) {

            $this->response($campaignList, 200);
        }

        $this->response($campaignList, 200); // 200 being the HTTP response code
    }

    public function add_campaign_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $cookie = $this->get('cookie');
        $comm = $this->get('comm');
        $comm_type = $this->get('comm_type');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($cookie) || $cookie == null) {

            $this->response(array('error' => 'Missing last cookie'), 200);
        }

        if (!isset($comm) || $comm == null) {

            $this->response(array('error' => 'Missing comm'), 200);
        }

        if (!isset($comm_type) || $comm_type == null) {

            $this->response(array('error' => 'Missing comm_type'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $addAffiliate = $this->ppv_affiliate_model->add_campaign($pid, $ks, $name, $desc, $cookie, $comm, $comm_type, $status, $tz);

        if (!$addAffiliate) {

            $this->response($addAffiliate, 200);
        }

        $this->response($addAffiliate, 200); // 200 being the HTTP response code
    }

    public function update_campaign_get() {
        $pid = $this->get('pid');
        $cid = $this->get('cid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $cookie = $this->get('cookie');
        $comm = $this->get('comm');
        $comm_type = $this->get('comm_type');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($cookie) || $cookie == null) {

            $this->response(array('error' => 'Missing last cookie'), 200);
        }

        if (!isset($comm) || $comm == null) {

            $this->response(array('error' => 'Missing comm'), 200);
        }

        if (!isset($comm_type) || $comm_type == null) {

            $this->response(array('error' => 'Missing comm_type'), 200);
        }

        $addAffiliate = $this->ppv_affiliate_model->update_campaign($pid, $cid, $ks, $name, $desc, $cookie, $comm, $comm_type, $tz);

        if (!$addAffiliate) {

            $this->response($addAffiliate, 200);
        }

        $this->response($addAffiliate, 200); // 200 being the HTTP response code
    }

    public function update_campaign_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cid = $this->get('cid');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateCampaignStatus = $this->ppv_affiliate_model->update_campaign_status($pid, $ks, $cid, $status, $tz);

        if (!$updateCampaignStatus) {

            $this->response($updateCampaignStatus, 200);
        }

        $this->response($updateCampaignStatus, 200); // 200 being the HTTP response code
    }

    public function delete_campaign_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $cid = $this->get('cid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        $deleteCampaign = $this->ppv_affiliate_model->delete_campaign($pid, $ks, $cid);

        if (!$deleteCampaign) {

            $this->response($deleteCampaign, 200);
        }

        $this->response($deleteCampaign, 200); // 200 being the HTTP response code
    }

    public function list_marketing_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $marketingList = $this->ppv_affiliate_model->get_marketing_list($pid, $ks, $start, $length, $search, $draw);

        if (!$marketingList) {

            $this->response($marketingList, 200);
        }

        $this->response($marketingList, 200); // 200 being the HTTP response code
    }

    public function get_marketing_data_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $marketingData = $this->ppv_affiliate_model->get_marketing_data($pid, $ks);

        if (!$marketingData) {

            $this->response($marketingData, 200);
        }

        $this->response($marketingData, 200); // 200 being the HTTP response code
    }

    public function add_link_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $url = $this->get('url');
        $aid = $this->get('aid');
        $a_name = $this->get('a_name');
        $cid = $this->get('cid');
        $c_name = $this->get('c_name');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($url) || $url == null) {

            $this->response(array('error' => 'Missing last url'), 200);
        }

        if (!isset($aid) || $aid == null) {

            $this->response(array('error' => 'Missing aid'), 200);
        }

        if (!isset($a_name) || $a_name == null) {

            $this->response(array('error' => 'Missing a_name'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($c_name) || $c_name == null) {

            $this->response(array('error' => 'Missing c_name'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $addLink = $this->ppv_affiliate_model->add_link($pid, $ks, $name, $desc, $url, $aid, $cid, $a_name, $c_name, $status, $tz);

        if (!$addLink) {

            $this->response($addLink, 200);
        }

        $this->response($addLink, 200); // 200 being the HTTP response code
    }

    public function update_link_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $name = $this->get('name');
        $desc = $this->get('desc');
        $url = $this->get('url');
        $aid = $this->get('aid');
        $a_name = $this->get('a_name');
        $cid = $this->get('cid');
        $c_name = $this->get('c_name');
        $mid = $this->get('mid');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($name) || $name == null) {

            $this->response(array('error' => 'Missing name'), 200);
        }

        if (!isset($url) || $url == null) {

            $this->response(array('error' => 'Missing last url'), 200);
        }

        if (!isset($aid) || $aid == null) {

            $this->response(array('error' => 'Missing aid'), 200);
        }

        if (!isset($a_name) || $a_name == null) {

            $this->response(array('error' => 'Missing a_name'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($c_name) || $c_name == null) {

            $this->response(array('error' => 'Missing c_name'), 200);
        }

        if (!isset($mid) || $mid == null) {

            $this->response(array('error' => 'Missing mid'), 200);
        }

        $updateLink = $this->ppv_affiliate_model->update_link($pid, $ks, $name, $desc, $url, $aid, $cid, $a_name, $c_name, $mid, $tz);

        if (!$updateLink) {

            $this->response($updateLink, 200);
        }

        $this->response($updateLink, 200); // 200 being the HTTP response code
    }

    public function update_link_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $mid = $this->get('mid');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($mid) || $mid == null) {

            $this->response(array('error' => 'Missing mid'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateLinkStatus = $this->ppv_affiliate_model->update_link_status($pid, $ks, $mid, $status, $tz);

        if (!$updateLinkStatus) {

            $this->response($updateLinkStatus, 200);
        }

        $this->response($updateLinkStatus, 200); // 200 being the HTTP response code
    }

    public function delete_link_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $mid = $this->get('mid');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($mid) || $mid == null) {

            $this->response(array('error' => 'Missing mid'), 200);
        }

        $deleteLink = $this->ppv_affiliate_model->delete_link($pid, $ks, $mid);

        if (!$deleteLink) {

            $this->response($deleteLink, 200);
        }

        $this->response($deleteLink, 200); // 200 being the HTTP response code
    }

    public function get_aff_link_get() {
        $mid = $this->get('mid');
        $ip = $this->get('ip');

        if (!isset($mid) || $mid == null) {

            $this->response(array('error' => 'Missing mid'), 200);
        }

        if (!isset($ip) || $ip == null) {

            $this->response(array('error' => 'Missing ip'), 200);
        }

        $link = $this->ppv_affiliate_model->get_aff_link($mid, $ip);

        if (!$link) {

            $this->response($link, 200);
        }

        $this->response($link, 200); // 200 being the HTTP response code
    }

    public function save_aff_link_get() {
        $pid = $this->get('pid');
        $mid = $this->get('mid');
        $aid = $this->get('aid');
        $cid = $this->get('cid');
        $ip = $this->get('ip');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($mid) || $mid == null) {

            $this->response(array('error' => 'Missing mid'), 200);
        }

        if (!isset($aid) || $aid == null) {

            $this->response(array('error' => 'Missing aid'), 200);
        }

        if (!isset($cid) || $cid == null) {

            $this->response(array('error' => 'Missing cid'), 200);
        }

        if (!isset($ip) || $ip == null) {

            $this->response(array('error' => 'Missing ip'), 200);
        }

        $link = $this->ppv_affiliate_model->save_aff_link($pid, $mid, $aid, $cid, $ip);

        if (!$link) {

            $this->response($link, 200);
        }

        $this->response($link, 200); // 200 being the HTTP response code
    }

    public function get_user_comms_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');
        $aid = $this->get('aid');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($aid) || $aid == null) {

            $this->response(array('error' => 'Missing aid'), 200);
        }

        $affList = $this->ppv_affiliate_model->get_user_comms($pid, $ks, $aid, $start, $length, $search, $draw, $tz);

        if (!$affList) {

            $this->response($affList, 200);
        }

        $this->response($affList, 200); // 200 being the HTTP response code
    }

    public function update_user_comms_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $sale_id = $this->get('sale_id');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($sale_id) || $sale_id == null) {

            $this->response(array('error' => 'Missing sale_id'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $statusResp = $this->ppv_affiliate_model->update_user_comms_status($pid, $ks, $sale_id, $status, $tz);

        if (!$statusResp) {

            $this->response($statusResp, 200);
        }

        $this->response($statusResp, 200); // 200 being the HTTP response code
    }

    public function list_commissions_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $commsList = $this->ppv_affiliate_model->list_commissions($pid, $ks, $start, $length, $search, $draw);

        if (!$commsList) {

            $this->response($commsList, 200);
        }

        $this->response($commsList, 200); // 200 being the HTTP response code
    }

    public function delete_commission_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $sale_id = $this->get('sale_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($sale_id) || $sale_id == null) {

            $this->response(array('error' => 'Missing sale_id'), 200);
        }

        $statusResp = $this->ppv_affiliate_model->delete_commission($pid, $ks, $sale_id);

        if (!$statusResp) {

            $this->response($statusResp, 200);
        }

        $this->response($statusResp, 200); // 200 being the HTTP response code
    }

}