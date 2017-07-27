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
            $asset_list_one = array('metadata', 'ac', 'thumbnail', 'caption', 'player');
            if (in_array($asset, $asset_list_one)) {
                $purge_assets_one = $this->purge_assets_one($pid);
                if ($purge_assets_one['success']) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false, 'message' => 'Could not purge assests one');
                }
            } else {
                $success = array('success' => true, 'message' => 'Nothing purged');
            }
        } else {
            $success = array('success' => false, 'message' => 'Invalid KS: Access Denied');
        }

        return $success;
    }

    public function purge_assets_one($pid) {
        $fields = array(
            'MediaPath' => 'http:\/\/apps.streamingmediahosting.com\/p\/' . $pid . '\/html5\/html5lib\/*',
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
        syslog(LOG_NOTICE, "SMH DEBUG : purge_assets_one " . print_r($output, true));
        return $output;
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

}
