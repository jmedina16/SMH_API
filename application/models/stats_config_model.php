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
            $start_date = date('d/M/Y', strtotime("-25 days"));
            $end_date = date('d/M/Y', strtotime("-25 days"));
            $this->getLocations($cpid, $start_date, $end_date);
        }

        return $success;
    }

    public function getLocations($cpid, $start_date, $end_date) {
        syslog(LOG_NOTICE, "SMH DEBUG : getLocationsX: " . print_r($cpid, true));
        $locationEntries = array();
        $this->config->select('*')
                ->from('locations')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date);
                //->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        $result = $query->result_array();
        
        //echo $this->config->last_query();
        syslog(LOG_NOTICE, "SMH DEBUG : getLocationsY: " . print_r($this->config->last_query(), true));
        
        syslog(LOG_NOTICE, "SMH DEBUG : getLocations: " . print_r($result, true));
        
//        foreach ($result as $res) {
//
//        }

        

//        if ($query->num_rows() > 0) {
//            foreach ($result as $res) {
//                $name = $res['name'];
//                $user_id = $this->smcipher->decrypt($res['user_id']);
//                $id = $res['id'];
//            }
//            $success = array('success' => true, 'user_name' => $name, 'user_id' => $user_id, 'id' => $id);
//        } else {
//            $success = array('success' => false);
//        }
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

}
