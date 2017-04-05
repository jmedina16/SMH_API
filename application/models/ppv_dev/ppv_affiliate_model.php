<?php

//error_reporting(0);

class Ppv_affiliate_model extends CI_Model {

    public function __construct() {
        // Open the correct DB connection
        $this->affiliates = $this->load->database('ppv_dev', TRUE);
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function get_affiliate_list($pid, $ks, $start, $length, $search, $draw) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('status', 'affiliate_id', 'first_name', 'last_name', 'email', 'created_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->affiliates->limit($this->affiliates->escape_str($length), $this->affiliates->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->affiliates->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
//            }

            /*
             * Searching
             */
            if ($search != "") {
                $where = '';
                for ($i = 0; $i < count($columns); $i++) {
                    if ($i < count($columns) - 1) {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%' OR ";
                    } else {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%'";
                    }
                }
                $this->affiliates->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->affiliates->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->order_by('affiliate_id', 'desc');

            $query = $this->affiliates->get('affiliate_user');
            $affiliate_res = $query->result_array();

            /* Data set length after filtering */
            $this->affiliates->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->affiliates->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->affiliates->query('SELECT count(*) AS `Count` FROM affiliate_user WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            $active_gw = $this->getActiveGateway($valid['pid']);
            if ($active_gw == 1) {
                $currency = $this->get_pp_currency($valid['pid']);
            } else if ($active_gw == 2) {
                $currency = $this->get_authnet_currency($valid['pid']);
            }
            $currency_symbol = $this->currency_symbol($currency);
            $exchangeRate = $this->currency("USD", $currency);

            foreach ($affiliate_res as $affiliate) {
                $comms = $this->affiliates->query('SELECT SUM(commission_raw) AS `Count` FROM affiliate_sales WHERE affiliate_id = "' . $affiliate['affiliate_id'] . '" AND partner_id = "' . $valid['pid'] . '"');
                $countQuery = $comms->result_array();
                $total_comms = $countQuery[0]['Count'];

                if ($currency == 'JPY') {
                    $final_comms = $currency_symbol . round($this->final_price($total_comms, $exchangeRate)) . "<sup>" . $currency . "</sup>";
                } else {
                    $final_comms = $currency_symbol . $this->final_price($total_comms, $exchangeRate) . "<sup>" . $currency . "</sup>";
                }

                $status = '';
                $status_data = $valid['pid'] . ',\'' . $affiliate['email'] . '\',\'' . $affiliate['first_name'] . ' ' . $affiliate['last_name'] . '\',' . $affiliate['status'];
                if ($affiliate['status'] == 1) {
                    $status = '<div class="alert alert-success">Active</div>';
                    $status_text = 'Block';
                } else if ($affiliate['status'] == 2) {
                    $status = '<div class="alert alert-danger">Blocked</div>';
                    $status_text = 'Unblock';
                }

                $delete_data = $valid['pid'] . ',\'' . $affiliate['email'] . '\',\'' . $affiliate['first_name'] . ' ' . $affiliate['last_name'] . '\'';
                $affiliate_data = $valid['pid'] . ',' . $affiliate['affiliate_id'] . ',\'' . $affiliate['first_name'] . '\',\'' . $affiliate['last_name'] . '\',\'' . $affiliate['email'] . '\',\'' . $affiliate['phone'] . '\',\'' . $affiliate['fax'] . '\',\'' . $affiliate['address_line_1'] . '\',\'' . $affiliate['address_line_2'] . '\',\'' . $affiliate['city'] . '\',\'' . $affiliate['state'] . '\',\'' . $affiliate['zip_code'] . '\',\'' . $affiliate['country'] . '\',\'' . $affiliate['company_name'] . '\',\'' . $affiliate['website'] . '\',\'' . $affiliate['paypal_email'] . '\'';

                $comm = $valid['pid'] . ',' . $affiliate['affiliate_id'] . ',\'' . $affiliate['first_name'] . ' ' . $affiliate['last_name'] . '\'';

                $actions = '<span class="dropdown header">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.editAff(' . $affiliate_data . ');">Affiliate</a></li> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.affComm(' . $comm . ');">View Commissions</a></li>
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.statusAff(' . $status_data . ');">' . $status_text . '</a></li>
                                            <li role="presentation" style="border-top: solid 1px #f0f0f0;"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteAff(' . $delete_data . ');">Delete</a></li>
                                        </ul>
                                    </div>
                                </span>';

                $row = array();
                $row[] = $status;
                $row[] = "<div class='data-break'>" . $affiliate['affiliate_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $affiliate['first_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $affiliate['last_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $affiliate['email'] . "</div>";
                $row[] = "<div class='data-break'>" . $final_comms . "</div>";
                $row[] = "<div class='data-break'>" . $affiliate['created_at'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function get_campaign_list($pid, $ks, $start, $length, $search, $draw, $currency) {
        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('status', 'campaign_id', 'name', 'cookie_life', 'commission', 'created_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->affiliates->limit($this->affiliates->escape_str($length), $this->affiliates->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->affiliates->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
//            }

            /*
             * Searching
             */
            if ($search != "") {
                $where = '';
                for ($i = 0; $i < count($columns); $i++) {
                    if ($i < count($columns) - 1) {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%' OR ";
                    } else {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%'";
                    }
                }
                $this->affiliates->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->affiliates->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->order_by('campaign_id', 'desc');

            $query = $this->affiliates->get('affiliate_campaign');
            $campaign_res = $query->result_array();

            /* Data set length after filtering */
            $this->affiliates->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->affiliates->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->affiliates->query('SELECT count(*) AS `Count` FROM affiliate_campaign WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            $exchangeRate = $this->currency("USD", $currency);

            foreach ($campaign_res as $campaign) {
                $status = '';
                $status_data = $valid['pid'] . ',' . $campaign['campaign_id'] . ',\'' . $campaign['name'] . '\',' . $campaign['status'];
                if ($campaign['status'] == 1) {
                    $status = '<div class="alert alert-success">Active</div>';
                    $status_text = 'Block';
                } else if ($campaign['status'] == 2) {
                    $status = '<div class="alert alert-danger">Blocked</div>';
                    $status_text = 'Unblock';
                }

                $delete_data = $valid['pid'] . ',' . $campaign['campaign_id'] . ',\'' . $campaign['name'] . '\'';
                $campaign_data = $valid['pid'] . ',' . $campaign['campaign_id'] . ',\'' . $campaign['name'] . '\',\'' . $campaign['description'] . '\',' . $campaign['cookie_life'] . ',' . $campaign['flat_rate'] . ',' . $campaign['percentage'] . ',' . $campaign['commission'];

                $comm = $valid['pid'] . ',' . $campaign['campaign_id'] . ',\'' . $campaign['name'] . '\'';

                if ($currency == 'JPY' && $campaign['flat_rate'] == 1) {
                    $comm = round($this->final_price($campaign['commission'], $exchangeRate));
                    $currency_symbol = $this->currency_symbol($currency);
                    $currency_code = '<sup>' . $currency . '</sup>';
                } else if ($campaign['flat_rate'] == 1) {
                    $comm = $this->final_price($campaign['commission'], $exchangeRate);
                    $currency_symbol = $this->currency_symbol($currency);
                    $currency_code = '<sup>' . $currency . '</sup>';
                } else if ($campaign['percentage'] == 1) {
                    $comm = $campaign['commission'] . "%";
                    $currency_symbol = '';
                    $currency_code = '';
                }

                if ($campaign['cookie_life'] == 0) {
                    $cookie = "Infinite";
                } else {
                    $cookie = $campaign['cookie_life'] . " Days";
                }

                $actions = '<span class="dropdown header">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.editCampaign(' . $campaign_data . ');">Campaign</a></li> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.statusCampaign(' . $status_data . ');">' . $status_text . '</a></li>
                                            <li role="presentation" style="border-top: solid 1px #f0f0f0;"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteCampaign(' . $delete_data . ');">Delete</a></li>
                                        </ul>
                                    </div>
                                </span>';

                $row = array();
                $row[] = $status;
                $row[] = "<div class='data-break'>" . $campaign['campaign_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $campaign['name'] . "</div>";
                $row[] = "<div class='data-break'>" . $cookie . "</div>";
                $row[] = "<div class='data-break'>" . $currency_symbol . $comm . $currency_code . "</div>";
                $row[] = "<div class='data-break'>" . $campaign['created_at'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function get_marketing_list($pid, $ks, $start, $length, $search, $draw) {
        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('status', 'marketing_id', 'name', 'url', 'affiliate_name', 'campaign_name', 'unique_hits', 'raw_hits', 'sales', 'created_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->affiliates->limit($this->affiliates->escape_str($length), $this->affiliates->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->affiliates->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
//            }

            /*
             * Searching
             */
            if ($search != "") {
                $where = '';
                for ($i = 0; $i < count($columns); $i++) {
                    if ($i < count($columns) - 1) {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%' OR ";
                    } else {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%'";
                    }
                }
                $this->affiliates->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->affiliates->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->order_by('marketing_id', 'desc');

            $query = $this->affiliates->get('affiliate_marketing');
            $marketing_res = $query->result_array();

            /* Data set length after filtering */
            $this->affiliates->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->affiliates->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->affiliates->query('SELECT count(*) AS `Count` FROM affiliate_marketing WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($marketing_res as $marketing) {
                $status = '';
                $status_data = $valid['pid'] . ',' . $marketing['marketing_id'] . ',\'' . $marketing['name'] . '\',' . $marketing['status'];
                if ($marketing['status'] == 1) {
                    $status = '<div class="alert alert-success">Active</div>';
                    $status_text = 'Block';
                    $status_img = '<a href="#" rel="tooltip" data-original-title="Block" data-placement="top" onclick="smhPPV.marketing_status_dialog(\'' . $status_data . '\');"><img width="15px" src="/img/block-small-icon.png"></a>';
                } else if ($marketing['status'] == 2) {
                    $status = '<div class="alert alert-danger">Blocked</div>';
                    $status_text = 'Unblock';
                    $status_img = '<a href="#" rel="tooltip" data-original-title="Activate" data-placement="top" onclick="smhPPV.marketing_status_dialog(\'' . $status_data . '\');"><img width="15px" src="/img/unblock-small-icon.png"></a>';
                }

                $delete_data = $valid['pid'] . ',' . $marketing['marketing_id'] . ',\'' . $marketing['name'] . '\'';
                $marketing_data = $valid['pid'] . ',' . $marketing['marketing_id'] . ',\'' . $marketing['name'] . '\',\'' . $marketing['description'] . '\',\'' . $marketing['url'] . '\',' . $marketing['affiliate_id'] . ',' . $marketing['campaign_id'];
                $link = $marketing['marketing_id'] . ',\'' . $marketing['name'] . '\'';

                $actions = '<span class="dropdown header">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.editLink(' . $marketing_data . ');">Link</a></li>
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.viewLink(' . $link . ');">View Link</a></li>    
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.statusLink(' . $status_data . ');">' . $status_text . '</a></li>
                                            <li role="presentation" style="border-top: solid 1px #f0f0f0;"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteLink(' . $delete_data . ');">Delete</a></li>
                                        </ul>
                                    </div>
                                </span>';

                $row = array();
                $row[] = $status;
                $row[] = "<div class='data-break'>" . $marketing['marketing_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['name'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['url'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['affiliate_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['campaign_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['unique_hits'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['raw_hits'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['sales'] . "</div>";
                $row[] = "<div class='data-break'>" . $marketing['created_at'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function list_commissions($pid, $ks, $start, $length, $search, $draw) {
        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('sale_id', 'status', 'customer_name', 'aff_name', 'order_id', 'order_date', 'campaign', 'commission', 'total_sale');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->affiliates->limit($this->affiliates->escape_str($length), $this->affiliates->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->affiliates->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
//            }

            /*
             * Searching
             */
            if ($search != "") {
                $where = '';
                for ($i = 0; $i < count($columns); $i++) {
                    if ($i < count($columns) - 1) {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%' OR ";
                    } else {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%'";
                    }
                }
                $this->affiliates->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->affiliates->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->order_by('sale_id', 'desc');

            $query = $this->affiliates->get('affiliate_sales');
            $commissions_res = $query->result_array();

            /* Data set length after filtering */
            $this->affiliates->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->affiliates->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->affiliates->query('SELECT count(*) AS `Count` FROM affiliate_sales WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($commissions_res as $commissions) {
                $status_data = $valid['pid'] . ',' . $commissions['sale_id'] . ',' . $commissions['status'];
                $delete_data = $valid['pid'] . ',' . $commissions['sale_id'];
                $status = '';
                if ($commissions['status'] == 1) {
                    $status = 'Unpaid';
                    $status_text = 'Change to Paid';
                    $status_img = '<a href="#" rel="tooltip" data-original-title="Change to Paid" data-placement="top" onclick="smhPPV.comms_status_dialog(\'' . $status_data . '\');"><img width="15px" src="/img/unblock-small-icon.png"></a>';
                } else if ($commissions['status'] == 2) {
                    $status = 'Paid';
                    $status_text = 'Change to Unpaid';
                    $status_img = '<a href="#" rel="tooltip" data-original-title="Change to Unpaid" data-placement="top" onclick="smhPPV.comms_status_dialog(\'' . $status_data . '\');"><img width="15px" src="/img/block-small-icon.png"></a>';
                }

                $actions = '<span class="dropdown header">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.statusCommission(' . $status_data . ');">' . $status_text . '</a></li>
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteCommission(' . $delete_data . ');">Delete</a></li>
                                        </ul>
                                    </div>
                                </span>';

                $row = array();
                $row[] = $commissions['sale_id'];
                $row[] = "<div class='data-break'>" . $status . "</div>";
                $row[] = "<div class='data-break'>" . $commissions['customer_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $commissions['aff_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $commissions['order_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $commissions['order_date'] . "</div>";
                $row[] = "<div class='data-break'>" . $commissions['campaign'] . "</div>";
                $row[] = "<div class='data-break'>" . $commissions['commission'] . "</div>";
                $row[] = "<div class='data-break'>" . $commissions['total_sale'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function get_user_comms($pid, $ks, $aid, $start, $length, $search, $draw, $tz) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);

            $columns = array('sale_id', 'status', 'customer_name', 'order_id', 'order_date', 'campaign', 'commission', 'total_sale');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->affiliates->limit($this->affiliates->escape_str($length), $this->affiliates->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->affiliates->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
//            }

            /*
             * Searching
             */
            if ($search != "") {
                $where = '';
                for ($i = 0; $i < count($columns); $i++) {
                    if ($i < count($columns) - 1) {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%' OR ";
                    } else {
                        $where .= $columns[$i] . " LIKE '%" . $search . "%'";
                    }
                }
                $this->affiliates->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')')
                        ->where('affiliate_id', $aid);
            }

            // Query the Db
            $this->affiliates->select('SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $columns)), false)
                    ->from('affiliate_sales')
                    ->where('partner_id', $valid['pid'])
                    ->where('affiliate_id', $aid);

            $query = $this->affiliates->get();
            $orders_res = $query->result_array();

            /* Data set length after filtering */
            $this->affiliates->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->affiliates->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->affiliates->query('SELECT count(*) AS `Count` FROM `affiliate_sales` WHERE partner_id = "' . $valid['pid'] . '" AND affiliate_id = "' . $aid . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($orders_res as $order) {
                $status_data = $valid['pid'] . ',' . $aid . ',' . $order['sale_id'] . ',' . $order['status'];
                $status = '';
                if ($order['status'] == 1) {
                    $status = 'Unpaid';
                    $status_text = 'Change to Paid';
                } else if ($order['status'] == 2) {
                    $status = 'Paid';
                    $status_text = 'Change to Unpaid';
                }

                $actions = '<span class="dropdown header">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.updateUserCommsStatus(' . $status_data . ');">' . $status_text . '</a></li>
                                        </ul>
                                    </div>
                                </span>';

                $row = array();
                $row[] = $order['sale_id'];
                $row[] = "<div class='data-break'>" . $status . "</div>";
                $row[] = "<div class='data-break'>" . $order['customer_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $order['order_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $order['order_date'] . "</div>";
                $row[] = "<div class='data-break'>" . $order['campaign'] . "</div>";
                $row[] = "<div class='data-break'>" . $order['commission'] . "</div>";
                $row[] = "<div class='data-break'>" . $order['total_sale'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }

        return $output;
    }

    public function update_user_comms_status($pid, $ks, $sale_id, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($status == 2) {
                $data = array(
                    'status' => 2,
                    'updated_at' => date("Y-m-d h:i:s")
                );
            } else {
                $data = array(
                    'status' => 1,
                    'updated_at' => date("Y-m-d h:i:s")
                );
            }

            $this->affiliates->where('sale_id = "' . $sale_id . '" AND partner_id = "' . $valid['pid'] . '"');
            $this->affiliates->update('affiliate_sales', $data);
            $this->affiliates->limit(1);
            if ($this->affiliates->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function add_affiliate($pid, $ks, $fname, $lname, $email, $phone, $fax, $address1, $address2, $city, $state, $zip, $country, $company, $website, $ppemail, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if (!$this->check_affiliate($valid['pid'], $email)) {

                $data = array(
                    'first_name' => $this->affiliates->escape_str(trim($fname)),
                    'last_name' => $this->affiliates->escape_str(trim($lname)),
                    'email' => $this->affiliates->escape_str(trim($email)),
                    'phone' => $this->affiliates->escape_str(trim($phone)),
                    'fax' => $this->affiliates->escape_str(trim($fax)),
                    'address_line_1' => $this->affiliates->escape_str(trim($address1)),
                    'address_line_2' => $this->affiliates->escape_str(trim($address2)),
                    'city' => $this->affiliates->escape_str(trim($city)),
                    'state' => $this->affiliates->escape_str(trim($state)),
                    'zip_code' => $this->affiliates->escape_str(trim($zip)),
                    'country' => $this->affiliates->escape_str(trim($country)),
                    'company_name' => $this->affiliates->escape_str(trim($company)),
                    'website' => $this->affiliates->escape_str(trim($website)),
                    'paypal_email' => $this->affiliates->escape_str(trim($ppemail)),
                    'status' => $status,
                    'partner_id' => $valid['pid'],
                    'created_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->insert('affiliate_user', $data);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'affiliate exists');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function add_campaign($pid, $ks, $name, $desc, $cookie, $comm, $comm_type, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            $data = array(
                'name' => $this->affiliates->escape_str(trim($name)),
                'description' => $this->affiliates->escape_str(trim($desc)),
                'cookie_life' => $this->affiliates->escape_str(trim($cookie)),
                'commission' => $this->affiliates->escape_str(trim($comm)),
                'percentage' => ($comm_type == 1 ? 1 : 0),
                'flat_rate' => ($comm_type == 2 ? 1 : 0),
                'status' => $status,
                'partner_id' => $valid['pid'],
                'created_at' => date("Y-m-d h:i:s")
            );

            $this->affiliates->insert('affiliate_campaign', $data);
            if ($this->affiliates->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function add_link($pid, $ks, $name, $desc, $url, $aid, $cid, $a_name, $c_name, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if (!$this->check_link($valid['pid'], $aid, $cid)) {
                $data = array(
                    'affiliate_name' => $a_name,
                    'campaign_name' => $c_name,
                    'affiliate_id' => $aid,
                    'campaign_id' => $cid,
                    'name' => $this->affiliates->escape_str(trim($name)),
                    'description' => $this->affiliates->escape_str(trim($desc)),
                    'url' => $this->affiliates->escape_str(trim($url)),
                    'status' => $status,
                    'partner_id' => $valid['pid'],
                    'created_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->insert('affiliate_marketing', $data);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'link exists');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_link($pid, $ks, $name, $desc, $url, $aid, $cid, $a_name, $c_name, $mid, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_link_mid($valid['pid'], $mid)) {
                $data = array(
                    'affiliate_name' => $a_name,
                    'campaign_name' => $c_name,
                    'affiliate_id' => $aid,
                    'campaign_id' => $cid,
                    'name' => $this->affiliates->escape_str(trim($name)),
                    'description' => $this->affiliates->escape_str(trim($desc)),
                    'url' => $this->affiliates->escape_str(trim($url)),
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->where('marketing_id = "' . $mid . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->affiliates->update('affiliate_marketing', $data);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'link does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_campaign($pid, $cid, $ks, $name, $desc, $cookie, $comm, $comm_type, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_campaign($pid, $cid)) {
                $data = array(
                    'name' => $this->affiliates->escape_str(trim($name)),
                    'description' => $this->affiliates->escape_str(trim($desc)),
                    'cookie_life' => $this->affiliates->escape_str(trim($cookie)),
                    'commission' => $this->affiliates->escape_str(trim($comm)),
                    'percentage' => ($comm_type == 1 ? 1 : 0),
                    'flat_rate' => ($comm_type == 2 ? 1 : 0),
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->where('campaign_id = "' . $cid . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->affiliates->update('affiliate_campaign', $data);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'campaign does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_affiliate($pid, $aid, $ks, $fname, $lname, $email, $phone, $fax, $address1, $address2, $city, $state, $zip, $country, $company, $website, $ppemail, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_affiliate($valid['pid'], $email)) {

                $data = array(
                    'first_name' => $this->affiliates->escape_str(trim($fname)),
                    'last_name' => $this->affiliates->escape_str(trim($lname)),
                    'email' => $this->affiliates->escape_str(trim($email)),
                    'phone' => $this->affiliates->escape_str(trim($phone)),
                    'fax' => $this->affiliates->escape_str(trim($fax)),
                    'address_line_1' => $this->affiliates->escape_str(trim($address1)),
                    'address_line_2' => $this->affiliates->escape_str(trim($address2)),
                    'city' => $this->affiliates->escape_str(trim($city)),
                    'state' => $this->affiliates->escape_str(trim($state)),
                    'zip_code' => $this->affiliates->escape_str(trim($zip)),
                    'country' => $this->affiliates->escape_str(trim($country)),
                    'company_name' => $this->affiliates->escape_str(trim($company)),
                    'website' => $this->affiliates->escape_str(trim($website)),
                    'paypal_email' => $this->affiliates->escape_str(trim($ppemail)),
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->where('affiliate_id = "' . $aid . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->affiliates->update('affiliate_user', $data);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'affiliate does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function delete_affiliate($pid, $ks, $email) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_affiliate($valid['pid'], $email)) {
                $aid = $this->get_affiliate_id($valid['pid'], $email);
                if ($this->delete_links_aid($valid['pid'], $aid)) {
                    $this->affiliates->where('email = "' . $email . '" AND partner_id = "' . $valid['pid'] . '"');
                    $this->affiliates->delete('affiliate_user');
                    $this->affiliates->limit(1);
                    if ($this->affiliates->affected_rows() > 0) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'affiliate does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function delete_campaign($pid, $ks, $cid) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_campaign($valid['pid'], $cid)) {
                if ($this->delete_links_cid($valid['pid'], $cid)) {
                    $this->affiliates->where('campaign_id = "' . $cid . '" AND partner_id = "' . $valid['pid'] . '"');
                    $this->affiliates->delete('affiliate_campaign');
                    $this->affiliates->limit(1);
                    if ($this->affiliates->affected_rows() > 0) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'campaign does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function delete_commission($pid, $ks, $sale_id) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $this->affiliates->where('sale_id = "' . $sale_id . '" AND partner_id = "' . $valid['pid'] . '"');
            $this->affiliates->delete('affiliate_sales');
            $this->affiliates->limit(1);
            if ($this->affiliates->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_affiliate_status($pid, $ks, $email, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_affiliate($valid['pid'], $email)) {
                $data = array(
                    'status' => $status,
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->where('email = "' . $email . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->affiliates->update('affiliate_user', $data);
                $this->affiliates->limit(1);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'affiliate does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_campaign_status($pid, $ks, $cid, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_campaign($valid['pid'], $cid)) {
                $data = array(
                    'status' => $status,
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->where('campaign_id = "' . $cid . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->affiliates->update('affiliate_campaign', $data);
                $this->affiliates->limit(1);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'campaign does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_link_status($pid, $ks, $mid, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_link_mid($valid['pid'], $mid)) {
                $data = array(
                    'status' => $status,
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->where('marketing_id = "' . $mid . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->affiliates->update('affiliate_marketing', $data);
                $this->affiliates->limit(1);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'link does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function delete_link($pid, $ks, $mid) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_link_mid($valid['pid'], $mid)) {
                $this->affiliates->where('marketing_id = "' . $mid . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->affiliates->delete('affiliate_marketing');
                $this->affiliates->limit(1);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'link does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_marketing_data($pid, $ks) {
        $success = array('success' => false);
        $users = array();
        $campaigns = array();

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $this->affiliates->select('*')
                    ->from('affiliate_user')
                    ->where('partner_id', $pid)
                    ->where('status', 1);
            $query = $this->affiliates->get();
            $result = $query->result_array();

            if ($query->num_rows() > 0) {
                foreach ($result as $r) {
                    $users[$r['affiliate_id']] = $r['first_name'] . " " . $r['last_name'];
                }
            } else {
                $users = 'no affiliates found';
            }

            $this->affiliates->select('*')
                    ->from('affiliate_campaign')
                    ->where('partner_id', $pid)
                    ->where('status', 1);
            $query = $this->affiliates->get();
            $result = $query->result_array();

            if ($query->num_rows() > 0) {
                foreach ($result as $r) {
                    $campaigns[$r['campaign_id']] = $r['name'];
                }
            } else {
                $campaigns = 'no campaigns found';
            }
            $success = array('success' => true, 'affiliates' => $users, 'campaigns' => $campaigns);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function check_affiliate($pid, $email) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_user')
                ->where('email', $this->affiliates->escape_str($email))
                ->where('partner_id', $pid);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_affiliate_id($pid, $email) {
        $this->affiliates->select('*')
                ->from('affiliate_user')
                ->where('email', $this->affiliates->escape_str($email))
                ->where('partner_id', $pid);
        $query = $this->affiliates->get();
        $user_res = $query->result_array();

        $id = '';
        foreach ($user_res as $user) {
            $id = $user['affiliate_id'];
        }

        return $id;
    }

    public function delete_links_aid($pid, $aid) {
        $success = false;
        $this->affiliates->where('partner_id', $pid);
        $this->affiliates->where('affiliate_id', $aid);
        $this->affiliates->delete('affiliate_marketing');
        if ($this->affiliates->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        $success = true;
        return $success;
    }

    public function delete_links_cid($pid, $cid) {
        $success = false;
        $this->affiliates->where('partner_id', $pid);
        $this->affiliates->where('campaign_id', $cid);
        $this->affiliates->delete('affiliate_marketing');
        if ($this->affiliates->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        $success = true;
        return $success;
    }

    public function check_campaign($pid, $cid) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_campaign')
                ->where('campaign_id', $cid)
                ->where('partner_id', $pid);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_link($pid, $aid, $cid) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_marketing')
                ->where('affiliate_id', $aid)
                ->where('campaign_id', $cid)
                ->where('partner_id', $pid);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function check_link_mid($pid, $mid) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_marketing')
                ->where('marketing_id', $mid)
                ->where('partner_id', $pid);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function currency($from_Currency, $to_Currency) {
        $success = false;
        $currency_rate = '';
        $this->affiliates->select('rate')
                ->from('exchange_rates')
                ->where('name = "' . $from_Currency . $to_Currency . '"');
        $query = $this->affiliates->get();
        $rate = $query->result_array();
        foreach ($rate as $r) {
            $currency_rate = $r['rate'];
        }
        return $currency_rate;
    }

    public function final_price($amount, $exchangeRate) {
        $results = ($exchangeRate * $amount);
        $results = number_format($results, 2, '.', '');
        return $results;
    }

    public function get_aff_link($mid, $ip) {
        $this->affiliates->select('*')
                ->from('affiliate_marketing')
                ->where('marketing_id', $mid)
                ->limit(1);
        $query = $this->affiliates->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            foreach ($result as $r) {
                $aid = $r['affiliate_id'];
                $cid = $r['campaign_id'];
                $t_url = $r['url'];
                $pid = $r['partner_id'];
            }
            $cookie_life = $this->get_cookie_days($pid, $cid);
            if ($cookie_life == 0) {
                $cookie_life = 3652;
            }
            $cookie_data = $this->smcipher->encrypt($pid . '_' . $aid . '_' . $cid . '_' . $mid . '_' . $ip);
            $url = array('pid' => $pid, 'mid' => $mid, 'aid' => $aid, 'cid' => $cid, 'url' => $t_url, 'cookie_life' => $cookie_life, 'cookie_data' => $cookie_data);
        }

        return $url;
    }

    public function get_cookie_days($pid, $cid) {
        $days = 0;
        $this->affiliates->select('*')
                ->from('affiliate_campaign')
                ->where('partner_id', $pid)
                ->where('campaign_id', $cid)
                ->limit(1);
        $query = $this->affiliates->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            foreach ($result as $r) {
                $days = $r['cookie_life'];
            }
        }
        return $days;
    }

    public function save_aff_link($pid, $mid, $aid, $cid, $ip) {
        $success = false;

        if ($this->aff_is_active($pid, $aid) && $this->camp_is_active($pid, $cid) && $this->link_is_active($pid, $mid)) {
            if ($this->update_hits($pid, $mid, $ip)) {
                $data = array(
                    'partner_id' => $pid,
                    'affiliate_id' => $aid,
                    'campaign_id' => $cid,
                    'marketing_id' => $mid,
                    'ip' => $ip,
                    'created_at' => date("Y-m-d h:i:s")
                );

                $this->affiliates->insert('affiliate_hits', $data);
                if ($this->affiliates->affected_rows() > 0) {
                    $success = true;
                } else {
                    $success = false;
                }
            } else {
                $success = false;
            }
        } else {
            $success = false;
        }

        return $success;
    }

    public function aff_is_active($pid, $aid) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_user')
                ->where('partner_id', $pid)
                ->where('affiliate_id', $aid)
                ->where('status', 1);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function camp_is_active($pid, $cid) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_campaign')
                ->where('partner_id', $pid)
                ->where('campaign_id', $cid)
                ->where('status', 1);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function link_is_active($pid, $mid) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_marketing')
                ->where('partner_id', $pid)
                ->where('marketing_id', $mid)
                ->where('status', 1);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function update_hits($pid, $mid, $ip) {
        $success = false;
        $hits = $this->get_hits($pid, $mid);

        if ($this->check_ip($pid, $ip)) {
            $data = array(
                'raw_hits' => $hits['raw_hits'] + 1,
                'updated_at' => date("Y-m-d h:i:s")
            );
        } else {
            $data = array(
                'raw_hits' => $hits['raw_hits'] + 1,
                'unique_hits' => $hits['unique_hits'] + 1,
                'updated_at' => date("Y-m-d h:i:s")
            );
        }
        $this->affiliates->where('partner_id', $pid);
        $this->affiliates->where('marketing_id', $mid);
        $this->affiliates->update('affiliate_marketing', $data);
        if ($this->affiliates->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function get_hits($pid, $mid) {
        $success = array('success' => false);
        $this->affiliates->select('*')
                ->from('affiliate_marketing')
                ->where('partner_id', $pid)
                ->where('marketing_id', $mid)
                ->where('status', 1)
                ->limit(1);
        $query = $this->affiliates->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            foreach ($result as $r) {
                $unique_hits = $r['unique_hits'];
                $raw_hits = $r['raw_hits'];
            }
            $success = array('success' => true, 'unique_hits' => $unique_hits, 'raw_hits' => $raw_hits);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function check_ip($pid, $ip) {
        $success = false;
        $this->affiliates->select('*')
                ->from('affiliate_hits')
                ->where('ip', $ip)
                ->where('partner_id', $pid);
        $query = $this->affiliates->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function get_pp_currency($pid) {
        $this->affiliates->select('currency')
                ->from('paypal')
                ->where('partner_id', $pid);

        $query = $this->affiliates->get();
        $currency = $query->result_array();
        foreach ($currency as $c) {
            $currency = $c['currency'];
        }
        return $currency;
    }

    public function get_authnet_currency($pid) {
        $this->affiliates->select('currency')
                ->from('authnet')
                ->where('partner_id', $pid);

        $query = $this->affiliates->get();
        $currency = $query->result_array();
        foreach ($currency as $c) {
            $currency = $c['currency'];
        }
        return $currency;
    }

    public function getActiveGateway($pid) {
        $this->affiliates->select('*')
                ->from('payment_gateway')
                ->where('partner_id', $pid);

        $query = $this->affiliates->get();
        $result = $query->result_array();

        $active_gw = 0;
        foreach ($result as $r) {
            if ($r['gateway_name'] == 'paypal') {
                if ($r['gateway_status'] == 1) {
                    $active_gw = 1;
                }
            }
            if ($r['gateway_name'] == 'authnet') {
                if ($r['gateway_status'] == 1) {
                    $active_gw = 2;
                }
            }
        }
        return $active_gw;
    }

    public function currency_symbol($curr) {
        $symbol = '';
        if ($curr == 'AUD')
            $symbol = '$';
        else if ($curr == 'CAD')
            $symbol = '$';
        else if ($curr == 'CZK')
            $symbol = '';
        else if ($curr == 'DKK')
            $symbol = '';
        else if ($curr == 'EUR')
            $symbol = '&euro;';
        else if ($curr == 'HKD')
            $symbol = '$';
        else if ($curr == 'HUF')
            $symbol = '';
        else if ($curr == 'JPY')
            $symbol = '&yen;';
        else if ($curr == 'NZD')
            $symbol = '$';
        else if ($curr == 'NOK')
            $symbol = '';
        else if ($curr == 'PLN')
            $symbol = '';
        else if ($curr == 'SGD')
            $symbol = '$';
        else if ($curr == 'SEK')
            $symbol = '';
        else if ($curr == 'CHF')
            $symbol = '';
        else if ($curr == 'GBP')
            $symbol = '&pound;';
        else if ($curr == 'USD')
            $symbol = '$';
        else
            $symbol = '';

        return $symbol;
    }

}
