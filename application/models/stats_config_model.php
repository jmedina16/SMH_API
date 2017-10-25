<?php

error_reporting(1);

class Stats_config_model extends CI_Model {

    protected $_ci;

    public function __construct() {
// Open the correct DB connection
        $this->config = $this->load->database('smhstats', TRUE);
        $this->_ci = & get_instance();
        $this->_ci->load->library("curl");
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function get_child_stats($pid, $ks, $cpid, $start_date, $end_date) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $locationEntries = $this->getLocations($cpid, $start_date, $end_date);
        }

        return $locationEntries;
    }

    public function getLocations($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('locations')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date)
                ->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        $locationEntries = $query->result_array();
        
        return $locationEntries;
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

}
