<?php

error_reporting(1);

require_once APPPATH."/libraries/PHPExcel/Classes/PHPExcel.php";

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
            $vodStatsEntries = $this->getVodStats($cpid, $start_date, $end_date);
            $liveStatsEntries = $this->getLiveStats($cpid, $start_date, $end_date);
            $locationEntries = $this->getLocations($cpid, $start_date, $end_date);
            $objPHPExcel = new PHPExcel();
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

    public function getLiveStats($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('content_live_stats')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date)
                ->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        $liveStatsEntries = $query->result_array();

        return $liveStatsEntries;
    }

    public function getVodStats($cpid, $start_date, $end_date) {
        $this->config->select('*')
                ->from('content_vod_stats')
                ->where('partner_id', $cpid)
                ->where('statistics_for >=', $start_date)
                ->where('statistics_for <=', $end_date);

        $query = $this->config->get();
        $vodStatsEntries = $query->result_array();

        return $vodStatsEntries;
    }

    public function human_filesize($bytes, $decimals = 2) {
        $bytes_temp = $bytes;
        $labels = array('B', 'KB', 'MB', 'GB', 'TB');

        foreach ($labels as $label) {
            if ($bytes > 1024) {
                $bytes = $bytes / 1024;
            } else {
                break;
            }
        }

        $bytes_temp2 = number_format($bytes_temp);
        $bytes_temp3 = floatval(str_replace(",", ".", $bytes_temp2));
        return number_format($bytes_temp3, 2) . " " . $label;
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

}
