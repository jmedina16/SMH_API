<?php

error_reporting(0);

class Cache_config_model extends CI_Model {

    protected $_ci;

    public function __construct() {
        // Open the correct DB connection
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
        $this->load->library('SMPortal');
    }

    public function purge_cache($pid, $ks, $asset) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $asset_list_one = array('metadata', 'ac', 'thumbnail', 'caption', 'player', 'playlist', 'delete');
            if (in_array($asset, $asset_list_one)) {
                $cdn = json_decode($this->getCDN($pid), true);
                if ($cdn[0]['edgecast']) {
                    $purge_ec = json_decode($this->purge_ec($pid));
                    if (isset($purge_ec->Id)) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not purge edgecast');
                    }
                } else if ($cdn[0]['highwinds']) {
                    $purge_hw = json_decode($this->purge_hw($pid));
                    if (isset($purge_hw->id)) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false, 'message' => 'Could not purge highwinds');
                    }
                } else {
                    $success = array('success' => true, 'message' => 'No CDN to purged');
                }
            } else {
                $success = array('success' => true, 'message' => 'Nothing purged');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function purge_ec($pid) {
        $fields = array(
            'MediaPath' => 'http://ecapps.streamingmediahosting.com/p/' . $pid . '/html5/html5lib/*',
            'MediaType' => 3
        );
        $field_string = json_encode($fields);

        //open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.edgecast.com/v2/mcc/customers/19BC0/edge/purge");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: TOK:d7e4fb53-0bbf-4e6d-aa03-976ce9294a0f',
            'Content-Type: application/json',
            'Accept: application/json'
        ));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function purge_hw($pid) {
        $urls = [];
        array_push($urls, [
            "url" => '//cds.n7x4e9i6.hwcdn.net/p/' . $pid . '/html5/html5lib/',
            "recursive" => true
        ]);
        $fields = array(
            'list' => $urls
        );
        $field_string = json_encode($fields);

        //open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://striketracker.highwinds.com/api/v1/accounts/j6f8b4i9/purge");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer 3ebdad9faa4565ad0da321fbe5d480eb6670730e387e4e79d961dbdb69b31ecc',
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function getCDN($pid) {
        $url = 'http://ecapps.streamingmediahosting.com/apps/scripts/getCDN.php?action=get_cdn&pid=' . $pid;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

}
