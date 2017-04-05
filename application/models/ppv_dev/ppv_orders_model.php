<?php

//error_reporting(0);

class Ppv_orders_model extends CI_Model {

    public function __construct() {
        // Open the correct DB connection
        $this->orders = $this->load->database('ppv_dev', TRUE);
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function get_order_list($pid, $ks, $start, $length, $search, $draw, $tz) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            $columns = array('order_id', 'kentry_name', 'order_status', 'status', 'email', 'ticket_name', 'ticket_price', 'expires', 'max_views', 'views', 'created_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->orders->limit($this->orders->escape_str($length), $this->orders->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->orders->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
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
                $this->orders->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            $this->orders->select('SQL_CALC_FOUND_ROWS *', false)
                    ->from('order')
                    ->where('partner_id', $valid['pid'])
                    ->order_by('order_id', 'desc');

            $query = $this->orders->get();
            $orders_res = $query->result_array();

            /* Data set length after filtering */
            $this->orders->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->orders->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->orders->query('SELECT count(*) AS `Count` FROM `order` WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($orders_res as $order) {

                if ($order['ticket_type'] == 'sub') {
                    $entry = '--';
                } else {

                    $entry = $order['kentry_name'];
                }

                $status = '';
                $refund_action = '';
                if ($order['status'] == 0) {
                    $status = 'None';
                } else if ($order['status'] == 1) {
                    $status = 'Pending';
                } else if ($order['status'] == 2) {
                    $status = 'Completed';
                } else if ($order['status'] == 3) {
                    $status = 'Denied';
                } else if ($order['status'] == 4) {
                    $status = 'Failed';
                } else if ($order['status'] == 5) {
                    $status = 'In-Progress';
                } else if ($order['status'] == 6) {
                    $status = 'Refunded';
                } else if ($order['status'] == 7) {
                    $status = 'Pending Subscription';
                } else if ($order['status'] == 8) {
                    $status = 'Recurring Payment';
                }

                $order_status = '';
                if ($order['order_status'] == 1) {
                    $order_status = 'Active';
                } else if ($order['order_status'] == 2) {
                    $order_status = 'Expired';
                } else if ($order['order_status'] == 3) {
                    $order_status = 'Refunded';
                }

                if ($order['max_views'] == -1) {
                    $max_views = 'Unlimited';
                } else {
                    $max_views = $order['max_views'];
                }

                if (($order['views'] == null || $order['views'] == '') && $order['max_views'] != -1) {
                    $views = 0;
                } else if ($order['max_views'] == -1) {
                    $views = '--';
                } else {
                    $views = $order['views'];
                }

                $expires = date("Y-m-d h:i:s A", strtotime($order['expires']));

                if ($order['expiry_config'] == -1) {
                    $expires = 'Unlimited';
                }

                $delete_data = $valid['pid'] . ',' . $order['order_id'];
                $refund_data = $valid['pid'] . ',' . $order['order_id'] . ',\'' . $order['ticket_type'] . '\',' . $order['status'] . ',' . $order['order_status'];
                if (($order['status'] == 2 || $order['status'] == 8) && $order['order_status'] != 3) {
                    $refund_action = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.refundOrder(' . $refund_data . ');">Refund</a></li>';
                }

                $actions = '<span class="dropdown header">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu">
                            ' . $refund_action . '                                        
                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteOrder(' . $delete_data . ');">Delete</a></li>
                        </ul>
                    </div>
                </span>';

                $created_at = date("Y-m-d h:i:s A", strtotime($order['created_at']));

                $row = array();
                $row[] = "<div class='data-break'>" . $order['order_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $entry . "</div>";
                $row[] = "<div class='data-break'>" . $order['email'] . "</div>";
                $row[] = "<div class='data-break'>" . $status . "</div>";
                $row[] = "<div class='data-break'><a onclick='smhPPV.viewDetails(\"" . $order['ticket_name'] . "\",\"" . $order['ticket_price'] . "\",\"" . $expires . "\",\"" . $max_views . "\",\"" . $views . "\");'>View Order Details <i class='fa fa-external-link' style='width: 100%; text-align: center; display: inline; font-size: 12px;'></i></a></div>";
                $row[] = "<div class='data-break'>" . $order_status . "</div>";
                $row[] = "<div class='data-break'>" . $created_at . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function get_user_orders_list($pid, $ks, $uid, $start, $length, $search, $draw, $tz) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);

            $columns = array('order_id', 'kentry_name', 'order_status', 'status', 'ticket_name', 'ticket_price', 'billing_period', 'expires', 'max_views', 'views', 'media_type');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->orders->limit($this->orders->escape_str($length), $this->orders->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->orders->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
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
                $this->orders->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')')
                        ->where('user_id', $uid);
            }

            // Query the Db
            $this->orders->select('SQL_CALC_FOUND_ROWS *', false)
                    ->from('order')
                    ->where('partner_id', $valid['pid'])
                    ->where('user_id', $uid);

            $query = $this->orders->get();
            $orders_res = $query->result_array();

            /* Data set length after filtering */
            $this->orders->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->orders->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->orders->query('SELECT count(*) AS `Count` FROM `order` WHERE partner_id = "' . $valid['pid'] . '" AND user_id = "' . $uid . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($orders_res as $order) {

                if ($order['ticket_type'] == 'sub') {
                    $entry = '--';
                } else {
                    $entry = $order['kentry_name'];
                }

                $status = '';
                if ($order['status'] == 0) {
                    $status = 'None';
                } else if ($order['status'] == 1) {
                    $status = 'Pending';
                } else if ($order['status'] == 2) {
                    $status = 'Completed';
                } else if ($order['status'] == 3) {
                    $status = 'Denied';
                } else if ($order['status'] == 4) {
                    $status = 'Failed';
                } else if ($order['status'] == 5) {
                    $status = 'In-Progress';
                } else if ($order['status'] == 6) {
                    $status = 'Refunded';
                } else if ($order['status'] == 7) {
                    $status = 'Pending Subscription';
                } else if ($order['status'] == 8) {
                    $status = 'Recurring Payment';
                }

                $order_status = '';
                if ($order['order_status'] == 1) {
                    $order_status = 'Active';
                } else if ($order['order_status'] == 2) {
                    $order_status = 'Expired';
                } else if ($order['order_status'] == 3) {
                    $order_status = 'Refunded';
                }

                if ($order['max_views'] == -1) {
                    $max_views = 'Unlimited';
                } else {
                    $max_views = $order['max_views'];
                }

                if (($order['views'] == null || $order['views'] == '') && $order['max_views'] != -1) {
                    $views = 0;
                } else if ($order['max_views'] == -1) {
                    $views = '--';
                } else {
                    $views = $order['views'];
                }

                $expires = date("Y-m-d h:i:s A", strtotime($order['expires']));
                ;

                if ($order['expiry_config'] == -1) {
                    $expires = 'Unlimited';
                }

                $row = array();
                $row[] = $order['order_id'];
                $row[] = "<div class='data-break'>" . $entry . "</div>";
                $row[] = "<div class='data-break'>" . $order_status . "</div>";
                $row[] = "<div class='data-break'>" . $status . "</div>";
                $row[] = "<div class='data-break'>" . $order['ticket_name'] . "</div>";
                $row[] = $order['ticket_price'] . "</sup>";
                $row[] = "<div class='data-break'>" . $expires . "</div>";
                $row[] = $max_views;
                $row[] = "<div class='data-break'>" . $views . "</div>";
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }

        return $output;
    }

    public function w_get_user_orders_list($sm_ak, $auth_key, $uid, $start, $length, $draw) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $is_logged_in = $this->is_logged_in($auth_key, $sm_ak);
        if ($is_logged_in['success']) {

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->orders->limit($this->orders->escape_str($length), $this->orders->escape_str($start));
            }


            // Query the Db
            $this->orders->select('SQL_CALC_FOUND_ROWS *', false)
                    ->from('order')
                    ->where('partner_id', $pid)
                    ->where('user_id', $uid)
                    ->where('ticket_type', 'reg')
                    ->order_by('order_id', 'desc');

            $query = $this->orders->get();
            $orders_res = $query->result_array();

            /* Data set length after filtering */
            $this->orders->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->orders->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->orders->query('SELECT count(*) AS `Count` FROM `order` WHERE partner_id = "' . $pid . '" AND user_id = "' . $uid . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($orders_res as $order) {

                if ($order['ticket_type'] == 'sub') {
                    $entry = '--';
                } else {
                    $entry = $order['kentry_name'];
                }

                $status = '';
                if ($order['status'] == 0) {
                    $status = 'None';
                } else if ($order['status'] == 1) {
                    $status = 'Pending';
                } else if ($order['status'] == 2) {
                    if ($order['order_status'] == 1) {
                        $status = 'Active';
                    } else if ($order['order_status'] == 2) {
                        $status = 'Expired';
                    } else if ($order['order_status'] == 3) {
                        $status = 'Refunded';
                    }
                } else if ($order['status'] == 3) {
                    $status = 'Denied';
                } else if ($order['status'] == 4) {
                    $status = 'Failed';
                } else if ($order['status'] == 5) {
                    $status = 'In-Progress';
                } else if ($order['status'] == 6) {
                    $status = 'Refunded';
                } else if ($order['status'] == 7) {
                    $status = 'Pending Subscription';
                } else if ($order['status'] == 8) {
                    $status = 'Recurring Payment';
                }

                if ($order['max_views'] == -1) {
                    $max_views = 'Unlimited';
                } else {
                    $max_views = $order['max_views'];
                }

                if (($order['views'] == null || $order['views'] == '') && $order['max_views'] != -1) {
                    $views = 0;
                } else if ($order['max_views'] == -1) {
                    $views = 'Unlimited';
                } else {
                    if ($order['views'] == $order['max_views']) {
                        $views = 'Expired';
                    } else {
                        $views = $order['views'];
                    }
                }

                $expires_at = date_create($order['expires']);
                $exp_Mo = date_format($expires_at, "M");
                $exp_Day = date_format($expires_at, "d");
                $exp_Year = date_format($expires_at, "Y");

                $expires = $exp_Mo . " " . $exp_Day . ", " . $exp_Year;

                if ($order['expiry_config'] == -1) {
                    $expires = 'Unlimited';
                }

                $created_at = date_create($order['created_at']);
                $Mo = date_format($created_at, "M");
                $Day = date_format($created_at, "d");
                $Year = date_format($created_at, "Y");

                $date = '<div class="date">
                            <div class="month-day">
                              <div>' . $Mo . '</div>
                              <div>' . $Day . '</div>
                            </div>
                            <div class="year">' . $Year . '</div>
                            <div class="clear"></div>
                          </div>';

                $row = array();
                $row[] = $date;
                $row[] = "<span class='order-info'>" . $order['kentry_name'] . "</span>";
                $row[] = "<span class='order-info'>" . $order['ticket_price'] . "</span>";
                $row[] = "<span class='order-info'>" . $expires . "</span>";
                $row[] = "<span class='order-info'>" . $views . "</span>";
                $row[] = "<span class='order-info'>" . $status . "</span>";
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }

        return $output;
    }

    public function w_list_user_subs_list($sm_ak, $auth_key, $uid, $start, $length, $draw) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $is_logged_in = $this->is_logged_in($auth_key, $sm_ak);
        if ($is_logged_in['success']) {

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->orders->limit($this->orders->escape_str($length), $this->orders->escape_str($start));
            }


            // Query the Db
            $this->orders->select('SQL_CALC_FOUND_ROWS *', false)
                    ->from('subscription')
                    ->where('partner_id', $pid)
                    ->where('user_id', $uid)
                    ->order_by('sub_id', 'desc');

            $query = $this->orders->get();
            $subs_res = $query->result_array();

            /* Data set length after filtering */
            $this->orders->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->orders->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->orders->query('SELECT count(*) AS `Count` FROM `subscription` WHERE partner_id = "' . $pid . '" AND user_id = "' . $uid . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($subs_res as $subs) {

                $status = '';
                $actions = 'None';
                if ($subs['profile_status'] == 0) {
                    $status = 'Pending';
                    $actions = '<button type="button" onclick="ppv_obj.delete_sub(' . $subs['sub_id'] . ');" class="btn btn-default"><span class="text">Cancel</span></button>';
                } else if ($subs['profile_status'] == 1) {
                    $status = 'Active';
                    $suspend = '';
                    $actions = '<button type="button" onclick="ppv_obj.cancel_sub(' . $subs['sub_id'] . ');" class="btn btn-default"><span class="text">Cancel</span></button>';
                } else if ($subs['profile_status'] == 2) {
                    $status = 'Suspended';
                } else if ($subs['profile_status'] == 3) {
                    $status = 'Cancelled';
                }

                $created_at = date_create($subs['created_at']);
                $Mo = date_format($created_at, "M");
                $Day = date_format($created_at, "d");
                $Year = date_format($created_at, "Y");

                $date = '<div class="date">
                            <div class="month-day">
                              <div>' . $Mo . '</div>
                              <div>' . $Day . '</div>
                            </div>
                            <div class="year">' . $Year . '</div>
                            <div class="clear"></div>
                          </div>';

                $row = array();
                $row[] = $date;
                $row[] = "<span class='order-info'>" . $subs['payment_cycle'] . "</span>";
                $row[] = "<span class='order-info'>" . $subs['ticket_price'] . "</span>";
                $row[] = "<span class='order-info'>" . $status . "</span>";
                $row[] = $actions;
                $output['data'][] = $row;
            }

            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }

        return $output;
    }

    public function list_subs($pid, $ks, $start, $length, $search, $draw, $tz) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('sub_id', 'profile_status', 'payment_cycle', 'email', 'ticket_name', 'ticket_price', 'created_at', 'updated_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->orders->limit($this->orders->escape_str($length), $this->orders->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->orders->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
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
                $this->orders->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            $this->orders->select('SQL_CALC_FOUND_ROWS *', false)
                    ->from('subscription')
                    ->where('partner_id', $valid['pid'])
                    ->order_by('created_at', 'desc');

            $query = $this->orders->get();
            $subs_res = $query->result_array();

            /* Data set length after filtering */
            $this->orders->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->orders->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->orders->query('SELECT count(*) AS `Count` FROM `subscription` WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($subs_res as $subs) {

                $cancel_sub = $pid . ',' . $subs['sub_id'];
                $status_sub = $pid . ',' . $subs['sub_id'] . ',' . $subs['profile_status'];

                $gw_used = ($subs['pp_sub']) ? 1 : (($subs['authnet_sub']) ? 2 : 0);

                $status = '';
                if ($subs['profile_status'] == 0) {
                    $status = 'Pending';
                    $status_actions = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteSub(' . $cancel_sub . ');">Delete</a></li>';
                } else if ($subs['profile_status'] == 1) {
                    $status = 'Active';
                    $suspend = '';
                    if ($gw_used == 1) {
                        $suspend = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.suspendSub(' . $status_sub . ');">Suspend</a></li>';
                    }
                    $status_actions = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.cancelSub(' . $cancel_sub . ');">Cancel</a></li>' . $suspend;
                } else if ($subs['profile_status'] == 2) {
                    $status = 'Suspended';
                    $status_actions = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.suspendSub(' . $status_sub . ');">Reactivate</a></li><li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteSub(' . $cancel_sub . ');">Delete</a></li>';
                } else if ($subs['profile_status'] == 3) {
                    $status = 'Cancelled';
                    $status_actions = '<li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteSub(' . $cancel_sub . ');">Delete</a></li>';
                }

                $actions = '<span class="dropdown header">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu">
                            ' . $status_actions . '
                        </ul>
                    </div>
                </span>';

                $row = array();
                $row[] = "<div class='data-break'>" . $subs['sub_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $status . "</div>";
                $row[] = "<div class='data-break'>" . $subs['email'] . "</div>";
                $row[] = "<div class='data-break'><a onclick='smhPPV.viewSubsDetails(\"" . $subs['ticket_name'] . "\",\"" . $subs['ticket_price'] . "\",\"" . $subs['payment_cycle'] . "\");'>View Order Details <i class='fa fa-external-link' style='width: 100%; text-align: center; display: inline; font-size: 12px;'></i></a></div>";
                $row[] = "<div class='data-break'>" . $subs['created_at'] . "</div>";
                $row[] = "<div class='data-break'>" . $subs['updated_at'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function is_logged_in($auth_key, $sm_ak) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;

        if ($this->get_user_status_logged_in($pid, $auth_key) == 2) {
            $success = false;
        } else {
            $this->orders->select('*')
                    ->from('user')
                    ->where('auth_key', $auth_key)
                    ->where('partner_id', $pid);

            $query = $this->orders->get();

            if ($query->num_rows() > 0) {
                $session = $query->result_array();
                foreach ($session as $sess) {
                    $success = array('success' => true, 'user_id' => $sess['user_id']);
                }
            } else {
                $success = false;
            }
        }

        return $success;
    }

    public function get_user_status_logged_in($pid, $auth_key) {
        $status = '';

        $this->orders->select('status')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('auth_key', $auth_key)
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $status = $r['status'];
        }

        return $status;
    }

    public function generateFP($ticket_price, $api_login_id, $transaction_key, $currency, $sequence, $tstamp) {
        return ($this->hmac($transaction_key, $api_login_id . "^" . $sequence . "^" . $tstamp . "^" . $ticket_price . "^" . $currency));
    }

    public function hmac($key, $data) {
        return (bin2hex(mhash(MHASH_MD5, $data, $key)));
    }

    public function add_order($sm_ak, $entry_id, $user_id, $ticket_id, $tz, $gw_type) {
        $pid = $this->smcipher->decrypt($sm_ak);
        date_default_timezone_set($tz);
        $success = false;

        $kentry = $this->get_kentry_mt($pid, $entry_id);
        $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
        $expires = $this->getExpiry($pid, $ticket_id, $entry['startDate']);
        $ticket = $this->get_ticket_details($pid, $ticket_id, $entry['startDate']);
        $user = $this->get_email($pid, $user_id);
        $sub_id = -1;
        $options = array();

        if ($gw_type == 2) {
            $api_login_id = '';
            $transaction_key = '';
            $currency = '';
            $this->orders->select('*')
                    ->from('authnet')
                    ->where('partner_id', $pid);

            $query = $this->orders->get();
            $result = $query->result_array();
            foreach ($result as $r) {
                $api_login_id = $this->smcipher->decrypt($r['api_login_id']);
                $transaction_key = $this->smcipher->decrypt($r['transaction_key']);
                $currency = $r['currency'];
            }
            $ticket_price = $this->get_ticket_price($pid, $ticket_id);
            srand(time());
            $sequence = rand(1, 1000);
            $tstamp = time();
            $fp = $this->generateFP($ticket_price, $api_login_id, $transaction_key, $currency, $sequence, $tstamp);
            $user_details = $this->get_user_detail($pid, $user_id);
            $options = array(
                'amount' => $ticket_price,
                'currency' => $currency,
                'login' => $api_login_id,
                'fp' => $fp,
                'sequence' => $sequence,
                'tstamp' => $tstamp,
                'title' => $kentry['kentry_name'],
                'fname' => $user_details['fname'],
                'lname' => $user_details['lname'],
                'email' => $user_details['email']
            );
        }

        if ($ticket['ticket_type'] == 'sub') {
            $status = 7;
            $sub = $this->insert_subscription($pid, $user_id, $ticket['billing_period'], $ticket['ticket_name'], $ticket['ticket_price'], $user['email']);
            $sub_id = $sub['sub_id'];
        } else {
            $status = 1;
        }

        $data = array(
            'expires' => $expires['expires'],
            'expiry_config' => $expires['expiry_config'],
            'max_views' => $expires['max_views'],
            'email' => $user['email'],
            'ticket_name' => $ticket['ticket_name'],
            'ticket_price' => $ticket['ticket_price'],
            'ticket_type' => $ticket['ticket_type'],
            'billing_period' => $ticket['billing_period'],
            'kentry_id' => $kentry['kentry_id'],
            'kentry_name' => $kentry['kentry_name'],
            'media_type' => $kentry['media_type'],
            'entry_id' => $entry_id,
            'user_id' => $user_id,
            'ticket_id' => $ticket_id,
            'created_at' => date("Y-m-d H:i:s A"),
            'status' => $status,
            'order_status' => 1,
            'partner_id' => $pid
        );

        $this->orders->insert('order', $data);
        if ($this->orders->affected_rows() > 0) {
            $order_id = $this->orders->insert_id();
            $success = array('success' => true, 'order_id' => $order_id, 'sub_id' => $sub_id, 'options' => $options);
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_kentry($pid, $entry_id) {
        // Query the Db
        $this->orders->select('*')
                ->from('entry')
                ->where('partner_id', $pid)
                ->where('entry_id', $entry_id);

        $query = $this->orders->get();
        $entry_res = $query->result_array();

        $entryid = '';
        foreach ($entry_res as $entry) {
            $entryid = $entry['kentry_id'];
        }

        return $entryid;
    }

    public function get_kentry_mt($pid, $entry_id) {
        // Query the Db
        $this->orders->select('*')
                ->from('entry')
                ->where('partner_id', $pid)
                ->where('entry_id', $entry_id);

        $query = $this->orders->get();
        $entry_res = $query->result_array();

        $entry = '';
        foreach ($entry_res as $entry) {
            $entry['kentry'] = $entry['kentry_id'];
            $entry['kentry_name'] = $entry['kentry_name'];
            $entry['media_type'] = $entry['media_type'];
        }

        return $entry;
    }

    public function get_expiration($pid, $ticket_id) {
        // Query the Db
        $this->orders->select('*')
                ->from('ticket')
                ->where('partner_id', $pid)
                ->where('ticket_id', $ticket_id);

        $query = $this->orders->get();
        $ticket_res = $query->result_array();

        $expire = '';
        foreach ($ticket_res as $ticket) {
            $expire = $ticket['expires'];
        }

        return $expire;
    }

    public function update_order($pid, $entry_id, $user_id, $ticket_id, $status, $tz) {
        date_default_timezone_set($tz);
        $success = false;
        if ($this->check_order($pid, $entry_id, $user_id, $ticket_id)) {
            $data = array(
                'entry_id' => $entry_id,
                'user_id' => $user_id,
                'ticket_id' => $ticket_id,
                'updated_at' => date("Y-m-d H:i:s"),
                'status' => $status
            );

            $this->orders->where('partner_id', $pid);
            $this->orders->update('order', $data);
            $this->orders->limit(1);
            if ($this->orders->affected_rows() > 0) {
                $success = true;
            } else {
                $success = array('notice' => 'no changes were made');
            }
        } else {
            $success = array('error' => 'order does not exist');
        }
    }

    public function delete_order($pid, $ks, $order_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $this->orders->where('partner_id', $valid['pid']);
            $this->orders->where('order_id', $order_id);
            $this->orders->delete('order');
            $this->orders->limit(1);
            if ($this->orders->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_refund_status($pid, $order_id) {
        $success = false;

        $data = array(
            'order_status' => 3
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->update('order', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = array('notice' => 'no changes were made');
        }
        return $success;
    }

    public function getActiveGateway($pid) {
        $this->orders->select('*')
                ->from('payment_gateway')
                ->where('partner_id', $pid);

        $query = $this->orders->get();
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

    public function getGatewayUsed($pid, $order_id) {
        $this->orders->select('*')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id);

        $query = $this->orders->get();
        $result = $query->result_array();

        $active_gw = 0;
        foreach ($result as $r) {
            if ($r['pp']) {
                $active_gw = 1;
            }
            if ($r['authnet']) {
                $active_gw = 2;
            }
        }
        return $active_gw;
    }

    public function refund_authnet_order($invoice_num) {
        $success = array('success' => false);
        $explode = explode("X", $invoice_num);
        $pid = urlencode($explode[0]);
        $order_id = urlencode($explode[1]);
        $refund_status = $this->update_refund_status($pid, $order_id);
        if ($refund_status) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function createAuthnetSub($sm_ak, $order_id, $sub_id, $payerEmail, $firstName, $lastName, $city) {
        $success = array('success' => false);
        $pid = $this->smcipher->decrypt($sm_ak);
        $this->load->library('/authnet/autoload');
        $authnet_config = $this->get_authnet_config($pid);
        $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
        $merchantAuthentication->setName($authnet_config['api_login_id']);
        $merchantAuthentication->setTransactionKey($authnet_config['transaction_key']);
        $transaction_id = $this->get_authnet_trans_id($pid, $order_id);

        if ($transaction_id['success']) {
            $order = $this->get_ipn_order($pid, $order_id);
            $user = $this->get_email($pid, $order['user_id']);
            $customerProfile = new net\authorize\api\contract\v1\CustomerProfileBaseType();
            $customerProfile->setMerchantCustomerId($pid . "X" . $order['user_id']);
            $customerProfile->setEmail($user['email']);
            $customerProfile->setDescription("Media Platform Profile");

            $request = new net\authorize\api\contract\v1\CreateCustomerProfileFromTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setTransId($transaction_id['transaction_id']);
            $request->setCustomer($customerProfile);
            $controller = new net\authorize\api\controller\CreateCustomerProfileFromTransactionController($request);
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

            //log_message('error', print_r($response, TRUE));

            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                $subscription = new net\authorize\api\contract\v1\ARBSubscriptionType();
                $subscription->setName("Media Platform Subscription");

                if ($order['billing_period'] == 'week') {
                    $intervalLength = 7;
                    $unit = "days";
                    $recurr_date = '+1 week';
                    $payment_cycle = 'w';
                } else if ($order['billing_period'] == 'month') {
                    $intervalLength = 1;
                    $unit = "months";
                    $recurr_date = '+1 month';
                    $payment_cycle = 'm';
                } else if ($order['billing_period'] == 'year') {
                    $intervalLength = 12;
                    $unit = "months";
                    $recurr_date = '+1 year';
                    $payment_cycle = 'y';
                }

                $DaysTimestamp = strtotime($recurr_date, strtotime('now'));
                $Mo = date('m', $DaysTimestamp);
                $Day = date('d', $DaysTimestamp);
                $Year = date('Y', $DaysTimestamp);
                $StartDate = $Year . '-' . $Mo . '-' . $Day;

                $ticket_price = $this->get_ticket_price($pid, $order['ticket_id']);

                $interval = new net\authorize\api\contract\v1\PaymentScheduleType\IntervalAType();
                $interval->setLength($intervalLength);
                $interval->setUnit($unit);

                $paymentSchedule = new net\authorize\api\contract\v1\PaymentScheduleType();
                $paymentSchedule->setInterval($interval);
                $paymentSchedule->setStartDate(new DateTime($StartDate));
                $paymentSchedule->setTotalOccurrences("9999");

                $subscription->setPaymentSchedule($paymentSchedule);
                $subscription->setAmount($ticket_price);

                $customerProfileId = $response->getCustomerProfileId();
                $customerPaymentProfileId = $response->getCustomerPaymentProfileIdList();

                $profile = new net\authorize\api\contract\v1\CustomerProfileIdType();
                $profile->setCustomerProfileId($customerProfileId);
                $profile->setCustomerPaymentProfileId($customerPaymentProfileId[0]);

                $subscription->setProfile($profile);

                $request = new net\authorize\api\contract\v1\ARBCreateSubscriptionRequest();
                $request->setmerchantAuthentication($merchantAuthentication);
                //$request->setRefId($refId);
                $request->setSubscription($subscription);
                $controller = new net\authorize\api\controller\ARBCreateSubscriptionController($request);
                $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

                if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                    $subscriptionId = $response->getSubscriptionId();
                    $date_created = date("Y-m-d H:i:s");
                    $profileStatus = 1;
                    $sub_resp = $this->insert_authnet_sub_details($pid, $order_id, $sub_id, $subscriptionId, $customerProfileId, $customerPaymentProfileId[0], $payerEmail, $firstName, $lastName, $city, $profileStatus, $payment_cycle, $date_created);
                    if ($sub_resp) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => false);
            }

            //log_message('error', print_r($response, TRUE));
        }
        return $success;
    }

    public function refund_order($pid, $ks, $order_id, $ticket_type) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $gw_used = $this->getGatewayUsed($pid, $order_id);
            if ($gw_used == 1) {
                $transaction_id = $this->get_pp_trans_id($pid, $order_id);
                if ($transaction_id['success']) {
                    $output = $this->refundPurchasePayPal($pid, $transaction_id['transaction_id']);
                    //log_message('error', print_r($output, TRUE));
                    if ($output['ACK'] == 'Success') {
                        $refund_status = $this->update_refund_status($pid, $order_id);
                        if ($refund_status) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $success = array('success' => false);
                    }
                }
            } else if ($gw_used == 2) {
                $this->load->library('/authnet/autoload');
                $authnet_config = $this->get_authnet_config($pid);
                $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
                $merchantAuthentication->setName($authnet_config['api_login_id']);
                $merchantAuthentication->setTransactionKey($authnet_config['transaction_key']);

                $transaction_id = $this->get_authnet_trans_id($pid, $order_id);
                if ($transaction_id['success']) {
                    $request = new net\authorize\api\contract\v1\GetTransactionDetailsRequest();
                    $request->setMerchantAuthentication($merchantAuthentication);
                    $request->setTransId($transaction_id['transaction_id']);
                    $controller = new net\authorize\api\controller\GetTransactionDetailsController($request);
                    $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
                    $trans_status = $response->getTransaction()->getTransactionStatus();
                    if ($trans_status == 'capturedPendingSettlement' || $trans_status == 'voided') {
                        $output = $this->voidPurchaseAuthNet($transaction_id['transaction_id'], $merchantAuthentication);
                    } else if ($trans_status == 'settledSuccessfully') {
                        $cardNumber = $response->getTransaction()->getPayment()->getCreditCard()->getCardNumber();
                        $expiration = $response->getTransaction()->getPayment()->getCreditCard()->getExpirationDate();
                        $amount = $response->getTransaction()->getAuthAmount();
                        $output = $this->refundPurchaseAuthNet($transaction_id['transaction_id'], $merchantAuthentication, $cardNumber, $expiration, $amount);
                    }
                    if ($output['success']) {
                        $refund_status = $this->update_refund_status($pid, $order_id);
                        if ($refund_status) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $success = array('success' => false);
                    }
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function refundPurchaseAuthNet($transaction_id, $merchantAuthentication, $cardNumber, $expiration, $amount) {
        $success = array('success' => false);
        $creditCard = new net\authorize\api\contract\v1\CreditCardType();
        $creditCard->setCardNumber($cardNumber);
        $creditCard->setExpirationDate($expiration);
        $paymentOne = new net\authorize\api\contract\v1\PaymentType();
        $paymentOne->setCreditCard($creditCard);

        $transactionRequest = new net\authorize\api\contract\v1\TransactionRequestType();
        $transactionRequest->setTransactionType("refundTransaction");
        $transactionRequest->setAmount($amount);
        $transactionRequest->setPayment($paymentOne);
        $transactionRequest->setRefTransId($transaction_id);

        $refund_request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $refund_request->setMerchantAuthentication($merchantAuthentication);
        $refund_request->setTransactionRequest($transactionRequest);
        $controller = new net\authorize\api\controller\CreateTransactionController($refund_request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if ($response != null) {
            $tresponse = $response->getTransactionResponse();
            if (($tresponse != null) && ($tresponse->getResponseCode() == 1)) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function voidPurchaseAuthNet($transactionId, $merchantAuthentication) {
        $success = array('success' => false);
        $transactionRequestType = new net\authorize\api\contract\v1\TransactionRequestType();
        $transactionRequestType->setTransactionType("voidTransaction");
        $transactionRequestType->setRefTransId($transactionId);

        $request = new net\authorize\api\contract\v1\CreateTransactionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setTransactionRequest($transactionRequestType);
        $controller = new net\authorize\api\controller\CreateTransactionController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);
        if ($response != null) {
            $tresponse = $response->getTransactionResponse();
            if (($tresponse != null) && ($tresponse->getResponseCode() == 1)) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function refundPurchasePayPal($pid, $transactionId) {
        $pp_config = $this->get_pp_config($pid);
        $postDetails = array(
            'USER' => $pp_config['api_user_id'],
            'PWD' => $pp_config['api_password'],
            'SIGNATURE' => $pp_config['api_sig'],
            'METHOD' => "RefundTransaction",
            'VERSION' => "124.0",
            'TRANSACTIONID' => $transactionId,
            'REFUNDTYPE' => "Full"
        );
        $arrPostVals = array_map(create_function('$key, $value', 'return $key."=".$value."&";'), array_keys($postDetails), array_values($postDetails));
        $postVals = rtrim(implode($arrPostVals), "&");
        $response = $this->parseString($this->run_pp_curl(PAYPAL_DEV, $postVals));
        return $response;
    }

    public function get_authnet_config($pid) {
        $config = array();
        $this->orders->select('*')
                ->from('authnet')
                ->where('partner_id', $pid);

        $query = $this->orders->get();
        $res = $query->result_array();
        foreach ($res as $r) {
            $config['api_login_id'] = $this->smcipher->decrypt($r['api_login_id']);
            $config['transaction_key'] = $this->smcipher->decrypt($r['transaction_key']);
            $config['currency'] = $r['currency'];
        }

        return $config;
    }

    public function get_pp_config($pid) {
        $config = array();
        $this->orders->select('*')
                ->from('paypal')
                ->where('partner_id', $pid);

        $query = $this->orders->get();
        $res = $query->result_array();
        foreach ($res as $r) {
            $config['api_user_id'] = $this->smcipher->decrypt($r['api_user_id']);
            $config['api_password'] = $this->smcipher->decrypt($r['api_password']);
            $config['api_sig'] = $this->smcipher->decrypt($r['api_sig']);
            $config['currency'] = $r['currency'];
            $config['setup'] = $r['setup'];
        }

        return $config;
    }

    public function run_pp_curl($url, $postVals = null) {
        $ch = curl_init($url);
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3
        );
        if ($postVals != null) {
            $options[CURLOPT_POSTFIELDS] = $postVals;
            $options[CURLOPT_CUSTOMREQUEST] = "POST";
        }
        $header = array('X-PAYPAL-REQUEST-SOURCE' => 'HTML5 Toolkit PHP');
        $options[CURLOPT_HTTPHEADER] = $header;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_CAINFO, '/home/ubuntu/cert/api_cert_chain.crt');
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    function parseString($string = null) {
        $allRecords = '';
        $recordString = explode("&", $string);
        foreach ($recordString as $value) {
            $singleRecord = explode("=", $value);
            $allRecords[$singleRecord[0]] = $singleRecord[1];
        }
        return $allRecords;
    }

    public function get_authnet_trans_id($pid, $order_id) {
        $success = false;
        $this->orders->select('transaction_id')
                ->from('authnet_details')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $order = $query->result_array();
            foreach ($order as $o) {
                $success = array('success' => true, 'transaction_id' => $this->smcipher->decrypt($o['transaction_id']));
                //$success = array('success' => true, 'transaction_id' => $o['transaction_id']);
            }
        } else {
            $success = false;
        }
        return $success;
    }

    public function get_pp_trans_id($pid, $order_id) {
        $success = false;
        $this->orders->select('transaction_id')
                ->from('pp_details')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $order = $query->result_array();
            foreach ($order as $o) {
                $success = array('success' => true, 'transaction_id' => $this->smcipher->decrypt($o['transaction_id']));
                //$success = array('success' => true, 'transaction_id' => $o['transaction_id']);
            }
        } else {
            $success = false;
        }
        return $success;
    }

    public function w_delete_order($sm_ak, $order_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);

        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->delete('order');
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_order_status($pid, $entry_id, $user_id, $ticket_id, $order_status, $tz) {
        date_default_timezone_set($tz);
        $success = false;
        if ($this->check_order($pid, $entry_id, $user_id, $ticket_id)) {
            $data = array(
                'updated_at' => date("Y-m-d H:i:s"),
                'order_status' => $order_status
            );

            $this->orders->where('partner_id', $pid);
            $this->orders->update('order', $data);
            $this->orders->limit(1);
            if ($this->orders->affected_rows() > 0) {
                $success = true;
            } else {
                $success = array('notice' => 'no changes were made');
            }
        } else {
            $success = array('error' => 'order does not exist');
        }

        return $success;
    }

    public function check_order($pid, $entry_id, $user_id, $ticket_id) {
        $success = false;
        $this->orders->select('*')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('entry_id', $entry_id)
                ->where('user_id', $user_id)
                ->where('ticket_id', $ticket_id)
                ->where('order_status', 1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_inventory($sm_ak, $uid, $entryId, $type, $tz) {
        $pid = $this->smcipher->decrypt($sm_ak);

        if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
            $entryId = $this->smportal->get_cat_thumb($pid, $entryId);
        }

        $success = false;
        if ($this->get_user_restriction($pid, $uid) == 2 || $this->is_valid_sub($pid, $uid)) {
            if ($type == 's' || $type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                $privilege = 'sview:' . $entryId;
            } else if ($type == 'p') {
                $privilege = 'sviewplaylist:' . $entryId;
            }
            $infinte_token = $this->smportal->create_token($pid, '86400', $privilege);
            $success = $infinte_token;
        } else {
            $order = $this->get_order($pid, $uid, $entryId);

            if ($order['success']) {
                if ($order['max_views'] == -1) {
                    if ($order['expiry_config'] == -1) {
                        if ($type == 's' || $type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                            $privilege = 'sview:' . $entryId;
                        } else if ($type == 'p') {
                            $privilege = 'sviewplaylist:' . $entryId;
                        }
                        $infinte_token = $this->smportal->create_token($pid, '86400', $privilege);
                        $success = $infinte_token;
                    } else {
                        $valid = $this->expiry_check($pid, $order['order_id'], $tz);
                        if ($valid) {
                            if ($type == 's' || $type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                                $privilege = 'sview:' . $entryId;
                            } else if ($type == 'p') {
                                $privilege = 'sviewplaylist:' . $entryId;
                            }
                            $infinte_token = $this->smportal->create_token($pid, '86400', $privilege);
                            $success = $infinte_token;
                        } else {
                            $epire_order = $this->expire_order($pid, $order['entry_id'], $uid, $order['ticket_id']);
                            $success = false;
                        }
                    }
                } else {
                    if ($order['views'] >= $order['max_views']) {
                        $epire_order = $this->expire_order($pid, $order['entry_id'], $uid, $order['ticket_id']);
                        $success = false;
                    } else {
                        if ($order['expiry_config'] == -1) {
                            if ($type == 's' || $type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                                $privilege = 'sview:' . $entryId;
                            } else if ($type == 'p') {
                                $privilege = 'sviewplaylist:' . $entryId;
                            }
                            $infinte_token = $this->smportal->create_token($pid, '86400', $privilege);
                            $success = $infinte_token;
                        } else {
                            $valid = $this->expiry_check($pid, $order['order_id'], $tz);
                            if ($valid) {
                                if ($type == 's' || $type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                                    $privilege = 'sview:' . $entryId;
                                } else if ($type == 'p') {
                                    $privilege = 'sviewplaylist:' . $entryId;
                                }
                                $infinte_token = $this->smportal->create_token($pid, '86400', $privilege);
                                $success = $infinte_token;
                            } else {
                                $epire_order = $this->expire_order($pid, $order['entry_id'], $uid, $order['ticket_id']);
                                $success = false;
                            }
                        }
                    }
                }
            } else {
                $success = false;
            }
        }
        return $success;
    }

    public function check_cat_inventory($sm_ak, $entryId, $access) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;
        $access = $access == "true" ? true : false;

        if ($access) {
            $privilege = 'sview:' . $entryId;
            $infinte_token = $this->smportal->create_token($pid, '86400', $privilege);
            $success = $infinte_token;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_order($pid, $uid, $entryId) {
        $success = false;

        $this->orders->select('*')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('user_id', $uid)
                ->where('kentry_id', $entryId)
                ->where('status', 2)
                ->where('order_status', 1)
                ->where('ticket_type', 'reg')
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            foreach ($result as $r) {
                $order_id = $r['order_id'];
                $entry_id = $r['entry_id'];
                $ticket_id = $r['ticket_id'];
                $expires = $r['expires'];
                $expiry_config = $r['expiry_config'];
                $max_views = $r['max_views'];
                $views = $r['views'];
                $media_type = $r['media_type'];
                $ticket_type = $r['ticket_type'];
            }
            $success = array('success' => true, 'order_id' => $order_id, 'expires' => $expires, 'expiry_config' => $expiry_config, 'max_views' => $max_views, 'views' => $views, 'entry_id' => $entry_id, 'ticket_id' => $ticket_id, 'media_type' => $media_type, 'ticket_type' => $ticket_type);
        } else {
            $cat_id = $this->smportal->get_cat_id($pid, $entryId);
            $this->orders->select('*')
                    ->from('order')
                    ->where('partner_id', $pid)
                    ->where('user_id', $uid)
                    ->where('kentry_id', $cat_id)
                    ->where('status', 2)
                    ->where('order_status', 1)
                    ->where('ticket_type', 'reg')
                    ->limit(1);
            $query = $this->orders->get();
            $result = $query->result_array();

            if ($query->num_rows() > 0) {
                foreach ($result as $r) {
                    $order_id = $r['order_id'];
                    $entry_id = $r['entry_id'];
                    $ticket_id = $r['ticket_id'];
                    $expires = $r['expires'];
                    $expiry_config = $r['expiry_config'];
                    $max_views = $r['max_views'];
                    $views = $r['views'];
                    $media_type = $r['media_type'];
                    $ticket_type = $r['ticket_type'];
                }
                $success = array('success' => true, 'order_id' => $order_id, 'expires' => $expires, 'expiry_config' => $expiry_config, 'max_views' => $max_views, 'views' => $views, 'entry_id' => $entry_id, 'ticket_id' => $ticket_id, 'media_type' => $media_type, 'ticket_type' => $ticket_type);
            } else {
                $success = array('success' => false);
            }
        }

        return $success;
    }

    public function get_user_restriction($pid, $uid) {
        $restriction = '';

        $this->orders->select('restriction')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('user_id', $uid)
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $restriction = $r['restriction'];
        }

        return $restriction;
    }

    public function update_restriction($pid, $user_id, $restriction) {
        $success = array('success' => false);
        $data = array(
            'restriction' => $restriction,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->orders->where('user_id = "' . $user_id . '" AND partner_id = "' . $pid . '"');
        $this->orders->update('user', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function is_valid_sub($pid, $uid) {
        $success = false;
        $this->orders->select('*')
                ->from('subscription')
                ->where('partner_id', $pid)
                ->where('user_id', $uid)
                ->where('profile_status', 1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function expire_order($pid, $entry_id, $user_id, $ticket_id) {
        $success = false;
        if ($this->check_order($pid, $entry_id, $user_id, $ticket_id)) {
            $data = array(
                'order_status' => 2
            );
            $this->orders->where('partner_id', $pid);
            $this->orders->where('entry_id', $entry_id);
            $this->orders->where('user_id', $user_id);
            $this->orders->where('ticket_id', $ticket_id);
            $this->orders->where('status', 2);
            $this->orders->update('order', $data);
            $this->orders->limit(1);
            if ($this->orders->affected_rows() > 0) {
                $success = true;
            } else {
                $success = false;
            }
        } else {
            $success = array('success' => true, 'notice' => 'No changes made');
        }

        return $success;
    }

    public function update_views($sm_ak, $uid, $entry_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;
        if ($this->get_user_restriction($pid, $uid) == 2) {
            $success = $success = array('success' => true, 'notice' => 'No changes made');
        } else {
            $order = $this->get_order($pid, $uid, $entry_id);
            if ($order['success']) {
                if ($order['max_views'] == -1) {
                    $success = array('success' => true, 'notice' => 'No changes made');
                } else {
                    if ($order['views'] == '' || $order['views'] == null) {
                        $data = array(
                            'views' => 1,
                            'updated_at' => date("Y-m-d H:i:s")
                        );
                    } else {
                        if ($order['views'] >= $order['max_views']) {
                            $success = array('success' => true, 'notice' => 'No changes made');
                        } else {
                            $data = array(
                                'views' => $order['views'] + 1,
                                'updated_at' => date("Y-m-d H:i:s")
                            );
                        }
                    }
                    $this->orders->where('partner_id', $pid);
                    $this->orders->where('order_id', $order['order_id']);
                    $this->orders->update('order', $data);
                    $this->orders->limit(1);
                    if ($this->orders->affected_rows() > 0) {
                        $success = true;
                    } else {
                        $success = array('success' => true, 'notice' => 'No changes made');
                    }
                }
            } else {
                $success = false;
            }
        }
        return $success;
    }

    public function complete_order($sm_ak, $entry_id, $user_id, $ticket_id, $ticket_type, $order_id, $payment_status, $tz, $smh_aff) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        if ($this->check_order($pid, $entry_id, $user_id, $ticket_id)) {
            $order_status = $this->update_order_payment_status($pid, $order_id, $payment_status, $ticket_type, $tz);
            if ($order_status) {
                $kentry = $this->get_kentry_mt($pid, $entry_id);
                $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
                $update_expiry = $this->update_expiry($pid, $order_id, $ticket_id, $entry['startDate']);
                if ($update_expiry) {
                    $email_queued = $this->email_thank_you($pid, $entry_id, $ticket_id, $user_id, $order_id);
                    if ($email_queued) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        if ($smh_aff != null && $smh_aff != '') {
            $smh_aff_data = explode("_", $this->smcipher->decrypt($smh_aff));
            $this->save_aff_sales_link($smh_aff_data[0], $smh_aff_data[1], $smh_aff_data[2], $smh_aff_data[3], $smh_aff_data[4], $order_id);
        }

        return $success;
    }

    public function save_aff_sales_link($pid, $aid, $cid, $mid, $ip, $order_id) {
        $success = false;
        if ($this->aff_is_active($pid, $aid) && $this->camp_is_active($pid, $cid) && $this->link_is_active($pid, $mid)) {
            if ($this->update_hits($pid, $mid)) {
                $aff_order = $this->get_aff_order($pid, $order_id, $cid, $aid);
                $data = array(
                    'partner_id' => $pid,
                    'affiliate_id' => $aid,
                    'campaign_id' => $cid,
                    'marketing_id' => $mid,
                    'order_id' => $order_id,
                    'status' => 1,
                    'customer_name' => $aff_order['customer_name'],
                    'aff_name' => $aff_order['aff_name'],
                    'order_date' => $aff_order['order_date'],
                    'campaign' => $aff_order['campaign'],
                    'commission' => $aff_order['commission'],
                    'commission_raw' => $aff_order['commission_raw'],
                    'total_sale' => $aff_order['total_sale'],
                    'ip' => $ip,
                    'created_at' => date("Y-m-d H:i:s")
                );

                $this->orders->insert('affiliate_sales', $data);
                if ($this->orders->affected_rows() > 0) {
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
        $this->orders->select('*')
                ->from('affiliate_user')
                ->where('partner_id', $pid)
                ->where('affiliate_id', $aid)
                ->where('status', 1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function camp_is_active($pid, $cid) {
        $success = false;
        $this->orders->select('*')
                ->from('affiliate_campaign')
                ->where('partner_id', $pid)
                ->where('campaign_id', $cid)
                ->where('status', 1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function link_is_active($pid, $mid) {
        $success = false;
        $this->orders->select('*')
                ->from('affiliate_marketing')
                ->where('partner_id', $pid)
                ->where('marketing_id', $mid)
                ->where('status', 1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_aff_order($pid, $order_id, $cid, $aid) {
        $success = array('success' => false);

        $this->orders->select('*')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id)
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            foreach ($result as $r) {
                $order_id = $r['order_id'];
                $order_date = $r['created_at'];
                $uid = $r['user_id'];
                $total_sale = $r['ticket_price'];
                $ticket_id = $r['ticket_id'];
            }
            $customer_name = $this->get_user_name($pid, $uid);
            $aff_name = $this->get_aff_name($pid, $aid);
            $campaign = $this->get_campaign($pid, $cid, $ticket_id);
            $success = array('success' => true, 'order_id' => $order_id, 'customer_name' => $customer_name, 'aff_name' => $aff_name, 'order_date' => $order_date, 'campaign' => $campaign['name'], 'commission' => $campaign['commission'], 'commission_raw' => $campaign['commission_raw'], 'total_sale' => $total_sale);
        }

        return $success;
    }

    public function get_user_name($pid, $uid) {
        // Query the Db
        $this->orders->select('*')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('user_id', $uid);

        $query = $this->orders->get();
        $users_res = $query->result_array();

        $name = '';
        foreach ($users_res as $user) {
            $name = $user['first_name'] . " " . $user['last_name'];
        }

        return $name;
    }

    public function get_user_detail($pid, $uid) {
        // Query the Db
        $this->orders->select('*')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('user_id', $uid);

        $query = $this->orders->get();
        $users_res = $query->result_array();

        $user_details = array();
        foreach ($users_res as $user) {
            $user_details['fname'] = $user['first_name'];
            $user_details['lname'] = $user['last_name'];
            $user_details['email'] = $user['email'];
        }

        return $user_details;
    }

    public function get_aff_name($pid, $aid) {
        // Query the Db
        $this->orders->select('*')
                ->from('affiliate_user')
                ->where('partner_id', $pid)
                ->where('affiliate_id', $aid);

        $query = $this->orders->get();
        $users_res = $query->result_array();

        $name = '';
        foreach ($users_res as $user) {
            $name = $user['first_name'] . " " . $user['last_name'];
        }

        return $name;
    }

    public function get_campaign($pid, $cid, $tid) {
        $success = array('success' => false);
        // Query the Db
        $this->orders->select('*')
                ->from('affiliate_campaign')
                ->where('partner_id', $pid)
                ->where('campaign_id', $cid);

        $query = $this->orders->get();
        $camp_res = $query->result_array();

        foreach ($camp_res as $camp) {
            $name = $camp['name'];
            $rate = $camp['flat_rate'];
            $comm = $camp['commission'];
        }

        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $currency = $this->get_pp_currency($pid);
        } else if ($active_gw == 2) {
            $currency = $this->get_authnet_currency($pid);
        }
        $exchangeRate = $this->currency("USD", $currency);
        $currency_symbol = $this->currency_symbol($currency);

        if ($currency == 'JPY' || $currency == 'HUF') {
            $price = round($this->final_price($comm, $exchangeRate));
        } else {
            $price = $this->final_price($comm, $exchangeRate);
        }

        if ($rate == 1) {
            $commission = $currency_symbol . $price . "<sup>" . $currency . "</sup>";
            $commission_raw = $price;
        } else {
            $ticket_price = $this->get_ticket_price($pid, $tid);
            $commission = sprintf('%.2f', round(($comm / 100) * $ticket_price, 2));
            $commission_raw = $commission;
            $commission = $currency_symbol . $commission . "<sup>" . $currency . "</sup>";
        }

        $success = array('success' => true, 'name' => $name, 'commission' => $commission, 'commission_raw' => $commission_raw);
        return $success;
    }

    public function get_ticket_price($pid, $ticket_id) {
        $this->orders->select('ticket_price')
                ->from('ticket')
                ->where('partner_id', $pid)
                ->where('ticket_id', $ticket_id);

        $query = $this->orders->get();
        $price = $query->result_array();
        $ticket_price = 0;
        foreach ($price as $p) {
            $ticket_price = $p['ticket_price'];
        }

        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $currency = $this->get_pp_currency($pid);
        } else if ($active_gw == 2) {
            $currency = $this->get_authnet_currency($pid);
        }
        $exchangeRate = $this->currency("USD", $currency);
        if ($currency == 'JPY' || $currency == 'HUF') {
            $price = round($this->final_price($ticket_price, $exchangeRate));
        } else {
            $price = $this->final_price($ticket_price, $exchangeRate);
        }

        return $price;
    }

    public function update_hits($pid, $mid) {
        $success = false;
        $hits = $this->get_hits($pid, $mid);

        $data = array(
            'sales' => $hits['sales'] + 1,
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('marketing_id', $mid);
        $this->orders->update('affiliate_marketing', $data);
        if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function get_hits($pid, $mid) {
        $success = array('success' => false);
        $this->orders->select('*')
                ->from('affiliate_marketing')
                ->where('partner_id', $pid)
                ->where('marketing_id', $mid)
                ->where('status', 1)
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            foreach ($result as $r) {
                $sales = $r['sales'];
            }
            $success = array('success' => true, 'sales' => $sales);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function finish_order($sm_ak, $entry_id, $user_id, $ticket_id, $ticket_type, $order_id, $payment_status, $tz) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        if ($this->check_order($pid, $entry_id, $user_id, $ticket_id)) {
            $order_status = $this->update_order_payment_status($pid, $order_id, $payment_status, $ticket_type, $tz);
            if ($order_status) {
                $success = array('success' => true, 'status' => $payment_status);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_order_payment_status($pid, $order_id, $payment_status, $ticket_type, $tz) {
        date_default_timezone_set($tz);
        $success = false;

        if ($payment_status == 'Completed') {
            if ($ticket_type == 'reg') {
                $data = array(
                    'updated_at' => date("Y-m-d H:i:s"),
                    'status' => 2
                );
            } else {
                $data = array(
                    'updated_at' => date("Y-m-d H:i:s"),
                    'status' => 8
                );
            }
        } else if ($payment_status == 'Pending') {
            if ($ticket_type == 'reg') {
                $data = array(
                    'updated_at' => date("Y-m-d H:i:s"),
                    'status' => 1
                );
            } else {
                $data = array(
                    'updated_at' => date("Y-m-d H:i:s"),
                    'status' => 7
                );
            }
        } else if ($payment_status == 'None') {
            $data = array(
                'updated_at' => date("Y-m-d H:i:s"),
                'status' => 0
            );
        } else if ($payment_status == 'Failed') {
            $data = array(
                'updated_at' => date("Y-m-d H:i:s"),
                'status' => 4
            );
        } else if ($payment_status == 'In-Progress') {
            $data = array(
                'updated_at' => date("Y-m-d H:i:s"),
                'status' => 5
            );
        } else if ($payment_status == 'Denied') {
            $data = array(
                'updated_at' => date("Y-m-d H:i:s"),
                'status' => 3
            );
        }

        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->limit(1);
        $this->orders->update('order', $data);
        if ($this->orders->affected_rows() > 0) {
            $active_gw = $this->getActiveGateway($pid);
            if ($active_gw == 1) {
                $id = $this->insert_pp_id($pid, $order_id);
            } else if ($active_gw == 2) {
                $id = $this->insert_authnet_id($pid, $order_id);
            }
            if ($id['success']) {
                $success = true;
            } else {
                $success = false;
            }
        } else {
            $success = array('notice' => 'no changes were made');
        }
        return $success;
    }

    public function update_sub_profile_status($pid, $sub_id, $pp_sub_id, $profileStatus, $tz) {
        date_default_timezone_set($tz);
        $success = false;

        if ($profileStatus == 'Pending') {
            $status = 0;
        } else if ($profileStatus == 'Active') {
            $status = 1;
        } else if ($profileStatus == 'Suspended') {
            $status = 2;
        } else if ($profileStatus == 'Cancelled') {
            $status = 3;
        }

        $data = array(
            'updated_at' => date("Y-m-d H:i:s"),
            'profile_status' => $status
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('sub_id', $sub_id);
        $this->orders->update('subscription', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $pp_sub_id = $this->insert_pp_sub_id($pid, $sub_id, $pp_sub_id);
            if ($pp_sub_id['success']) {
                $success = true;
            } else {
                $success = false;
            }
        } else {
            $success = array('notice' => 'no changes were made');
        }

        return $success;
    }

    public function update_authnet_sub_profile_status($pid, $sub_id, $profileStatus, $tz) {
        date_default_timezone_set($tz);
        $success = false;

        if ($profileStatus == 0) {
            $status = 0;
        } else if ($profileStatus == 1) {
            $status = 1;
        } else if ($profileStatus == 2) {
            $status = 2;
        } else if ($profileStatus == 3) {
            $status = 3;
        }

        $data = array(
            'updated_at' => date("Y-m-d H:i:s"),
            'profile_status' => $status
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('sub_id', $sub_id);
        $this->orders->update('subscription', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $data = array(
                'profile_status' => $this->smcipher->encrypt($profileStatus)
            );
            $this->orders->where('partner_id', $pid);
            $this->orders->where('sub_id', $sub_id);
            $this->orders->update('authnet_sub_details', $data);
            if ($this->orders->affected_rows() > 0) {
                $success = true;
            } else {
                $success = false;
            }
        } else {
            $success = false;
        }

        return $success;
    }

    public function update_ipn_sub_profile_status($pid, $sub_id, $profileStatus, $tz) {
        date_default_timezone_set($tz);
        $success = false;

        if ($profileStatus == 'Pending') {
            $status = 0;
        } else if ($profileStatus == 'Active') {
            $status = 1;
        } else if ($profileStatus == 'Suspended') {
            $status = 2;
        } else if ($profileStatus == 'Cancelled') {
            $status = 3;
        }

        $data = array(
            'updated_at' => date("Y-m-d H:i:s"),
            'profile_status' => $status
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('sub_id', $sub_id);
        $this->orders->update('subscription', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $data = array(
                'profile_status' => $this->smcipher->encrypt($profileStatus)
            );
            $this->orders->where('partner_id', $pid);
            $this->orders->where('sub_id', $sub_id);
            $this->orders->update('pp_sub_details', $data);
            if ($this->orders->affected_rows() > 0) {
                $success = true;
            } else {
                $success = false;
            }
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_authnet_id($pid, $order_id) {
        $authnet_id = '';
        $success = array('success' => false);
        $this->orders->select('*')
                ->from('authnet_details')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id)
                ->limit(1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
            foreach ($result as $r) {
                $authnet_id = $r['authnet_details_id'];
            }
            $success = array('success' => true, 'authnet_id' => $authnet_id);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_pp_id($pid, $order_id) {
        $pp_id = '';
        $success = array('success' => false);
        $this->orders->select('*')
                ->from('pp_details')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id)
                ->limit(1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
            foreach ($result as $r) {
                $pp_id = $r['paypal_details_id'];
            }
            $success = array('success' => true, 'pp_id' => $pp_id);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_pp_sub_id($pid, $sub_id) {
        $success = array('success' => false);
        $this->orders->select('*')
                ->from('pp_sub_details')
                ->where('partner_id', $pid)
                ->where('sub_id', $sub_id)
                ->limit(1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
            foreach ($result as $r) {
                $pp_sub_id = $r['pp_sub_id'];
            }
            $success = array('success' => true, 'pp_sub_id' => $pp_sub_id);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_profile_id($pid, $order_id) {
        $profile_id = '';
        $success = array('success' => false);
        $this->orders->select('*')
                ->from('pp_details')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id)
                ->limit(1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
            foreach ($result as $r) {
                $profile_id = $r['sub_profile_id'];
            }
            $success = array('success' => true, 'profile_id' => $profile_id);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_sub_profile_id($pid, $sub_id) {
        $profile_id = '';
        $success = array('success' => false);
        $this->orders->select('*')
                ->from('subscription')
                ->where('partner_id', $pid)
                ->where('sub_id', $sub_id)
                ->limit(1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
            foreach ($result as $r) {
                $profile_id = $r['sub_profile_id'];
            }
            $success = array('success' => true, 'profile_id' => $profile_id);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_payment_cycle($pid, $profile_id) {
        $payment_cycle = '';
        $success = array('success' => false);
        $this->orders->select('*')
                ->from('subscription')
                ->where('partner_id', $pid)
                ->where('sub_profile_id', $profile_id)
                ->limit(1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
            foreach ($result as $r) {
                $payment_cycle = $r['payment_cycle'];
            }
            $success = array('success' => true, 'payment_cycle' => $payment_cycle);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function insert_authnet_id($pid, $order_id) {
        $success = array('success' => false);
        $authnet = $this->get_authnet_id($pid, $order_id);
        $data = array(
            'authnet' => $authnet['authnet_id']
        );
        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->update('order', $data);
        $success = array('success' => true);
        return $success;
    }

    public function insert_pp_id($pid, $order_id) {
        $success = array('success' => false);
        $pp = $this->get_pp_id($pid, $order_id);
        $data = array(
            'pp' => $pp['pp_id']
        );
        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->update('order', $data);
        $success = array('success' => true);
        return $success;
    }

    public function insert_pp_sub_id($pid, $sub_id, $pp_sub_id) {
        $success = array('success' => false);
        $data = array(
            'pp_sub' => $pp_sub_id
        );
        $this->orders->where('partner_id', $pid);
        $this->orders->where('sub_id', $sub_id);
        $this->orders->update('subscription', $data);
        $success = array('success' => true);
        return $success;
    }

    public function insert_authnet_details($sm_ak, $order_id, $firstName, $lastName, $payerEmail, $city, $paymentStatus, $avsCode, $authCode, $transactionId, $itemName, $ticket_type, $smh_aff) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $paymentStatus = ($paymentStatus == 1) ? 'Completed' : '';
        $success = array('success' => false);
        if ($this->check_authnet_order($pid, $order_id)) {
            $status = $this->get_ipn_payment_status($pid, $order_id);

            if ($status == 2 || $status == 8) {
                $data = array(
                    'order_time' => $this->smcipher->encrypt(date("Y-m-d H:i:s"))
                        //'order_time' => $orderTime
                );
                $this->orders->where('partner_id', $pid);
                $this->orders->where('order_id', $order_id);
                $this->orders->update('authnet_details', $data);
                if ($this->orders->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $o_status = $this->update_order_payment_status($pid, $order_id, $paymentStatus, $ticket_type, 'America/Los_Angeles');
                if ($o_status && $paymentStatus == 'Completed') {
                    if ($ticket_type == 'sub') {
                        $this->activate_sub($pid, $order_id);
                    }
                    $order = $this->get_ipn_order($pid, $order_id);
                    $kentry = $this->get_kentry_mt($pid, $order['entry_id']);
                    $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
                    $this->update_expiry($pid, $order_id, $order['ticket_id'], $entry['startDate']);
                    $this->email_thank_you($pid, $order['entry_id'], $order['ticket_id'], $order['user_id'], $order_id);
                }

                $data = array(
                    'payment_status' => $this->smcipher->encrypt($paymentStatus),
                    'order_time' => $this->smcipher->encrypt(date("Y-m-d H:i:s"))
//                    'payment_status' => $paymentStatus,
//                    'order_time' => $orderTime
                );
                $this->orders->where('partner_id', $pid);
                $this->orders->where('order_id', $order_id);
                $this->orders->update('authnet_details', $data);
                if ($this->orders->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $data = array(
                'partner_id' => $pid,
                'order_id' => $order_id,
                'first_name' => $this->smcipher->encrypt($firstName),
                'last_name' => $this->smcipher->encrypt($lastName),
                'payer_email' => $this->smcipher->encrypt($payerEmail),
                'city' => $this->smcipher->encrypt($city),
                'payment_status' => $this->smcipher->encrypt($paymentStatus),
                'avs_code' => $avsCode,
                'auth_code' => $this->smcipher->encrypt($authCode),
                'transaction_id' => $this->smcipher->encrypt($transactionId),
                'item_name' => $this->smcipher->encrypt($itemName),
                'order_time' => $this->smcipher->encrypt(date("Y-m-d H:i:s"))
//            'first_name' => $firstName,
//            'last_name' => $lastName,
//            'payer_email' => $payerEmail,
//            'city' => $city,
//            'payment_status' => $paymentStatus,
//            'transaction_id' => $transactionId,
//            'item_name' => $itemName,
            );
            $this->orders->insert('authnet_details', $data);
            if ($this->orders->affected_rows() > 0) {
                $o_status = $this->update_order_payment_status($pid, $order_id, $paymentStatus, $ticket_type, 'America/Los_Angeles');
                if ($o_status && $paymentStatus == 'Completed') {
                    $order = $this->get_ipn_order($pid, $order_id);
                    $kentry = $this->get_kentry_mt($pid, $order['entry_id']);
                    $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
                    $this->update_expiry($pid, $order_id, $order['ticket_id'], $entry['startDate']);
                    $this->email_thank_you($pid, $order['entry_id'], $order['ticket_id'], $order['user_id'], $order_id);
                }
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        }
        if ($smh_aff != null && $smh_aff != '') {
            $smh_aff_data = explode("_", $this->smcipher->decrypt($smh_aff));
            $this->save_aff_sales_link($smh_aff_data[0], $smh_aff_data[1], $smh_aff_data[2], $smh_aff_data[3], $smh_aff_data[4], $order_id);
        }
        return $success;
    }

    public function insert_pp_details($sm_ak, $order_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime, $itemName) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'order_id' => $order_id,
            'receiver_email' => $this->smcipher->encrypt($receiverEmail),
            'receiver_id' => $this->smcipher->encrypt($receiverId),
            'first_name' => $this->smcipher->encrypt($firstName),
            'last_name' => $this->smcipher->encrypt($lastName),
            'payer_email' => $this->smcipher->encrypt($payerEmail),
            'payer_id' => $this->smcipher->encrypt($payerId),
            'country_code' => $this->smcipher->encrypt($countryCode),
            'payment_status' => $this->smcipher->encrypt($paymentStatus),
            'transaction_id' => $this->smcipher->encrypt($transactionId),
            'payment_type' => $this->smcipher->encrypt($paymentType),
            'order_time' => $this->smcipher->encrypt($orderTime),
            'item_name' => $this->smcipher->encrypt($itemName),
//            'receiver_email' => $receiverEmail,
//            'receiver_id' => $receiverId,
//            'first_name' => $firstName,
//            'last_name' => $lastName,
//            'payer_email' => $payerEmail,
//            'payer_id' => $payerId,
//            'country_code' => $countryCode,
//            'payment_status' => $paymentStatus,
//            'transaction_id' => $transactionId,
//            'payment_type' => $paymentType,
//            'order_time' => $orderTime,
//            'item_name' => $itemName,
        );

        $this->orders->insert('pp_details', $data);
        if ($this->orders->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function insert_ipn_pp_details($sm_ak, $order_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime, $itemName, $ticket_type) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        if ($this->check_ipn_order($pid, $order_id)) {
            $status = $this->get_ipn_payment_status($pid, $order_id);

            if ($status == 2 || $status == 8) {
                $data = array(
                    'order_time' => $this->smcipher->encrypt($orderTime)
                        //'order_time' => $orderTime
                );
                $this->orders->where('partner_id', $pid);
                $this->orders->where('order_id', $order_id);
                $this->orders->update('pp_details', $data);
                if ($this->orders->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $o_status = $this->update_order_payment_status($pid, $order_id, $paymentStatus, $ticket_type, 'America/Los_Angeles');
                if ($o_status && $paymentStatus == 'Completed') {
                    if ($ticket_type == 'sub') {
                        $this->activate_sub($pid, $order_id);
                    }
                    $order = $this->get_ipn_order($pid, $order_id);
                    $kentry = $this->get_kentry_mt($pid, $order['entry_id']);
                    $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
                    $this->update_expiry($pid, $order_id, $order['ticket_id'], $entry['startDate']);
                    $this->email_thank_you($pid, $order['entry_id'], $order['ticket_id'], $order['user_id'], $order_id);
                }

                $data = array(
                    'payment_status' => $this->smcipher->encrypt($paymentStatus),
                    'order_time' => $this->smcipher->encrypt($orderTime)
//                    'payment_status' => $paymentStatus,
//                    'order_time' => $orderTime
                );
                $this->orders->where('partner_id', $pid);
                $this->orders->where('order_id', $order_id);
                $this->orders->update('pp_details', $data);
                if ($this->orders->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $data = array(
                'partner_id' => $pid,
                'order_id' => $order_id,
                'receiver_email' => $this->smcipher->encrypt($receiverEmail),
                'receiver_id' => $this->smcipher->encrypt($receiverId),
                'first_name' => $this->smcipher->encrypt($firstName),
                'last_name' => $this->smcipher->encrypt($lastName),
                'payer_email' => $this->smcipher->encrypt($payerEmail),
                'payer_id' => $this->smcipher->encrypt($payerId),
                'country_code' => $this->smcipher->encrypt($countryCode),
                'payment_status' => $this->smcipher->encrypt($paymentStatus),
                'transaction_id' => $this->smcipher->encrypt($transactionId),
                'payment_type' => $this->smcipher->encrypt($paymentType),
                'order_time' => $this->smcipher->encrypt($orderTime),
                'item_name' => $this->smcipher->encrypt($itemName),
//                'receiver_email' => $receiverEmail,
//                'receiver_id' => $receiverId,
//                'first_name' => $firstName,
//                'last_name' => $lastName,
//                'payer_email' => $payerEmail,
//                'payer_id' => $payerId,
//                'country_code' => $countryCode,
//                'payment_status' => $paymentStatus,
//                'transaction_id' => $transactionId,
//                'payment_type' => $paymentType,
//                'order_time' => $orderTime,
//                'item_name' => $itemName,
            );

            $this->orders->insert('pp_details', $data);
            if ($this->orders->affected_rows() > 0) {
                $o_status = $this->update_order_payment_status($pid, $order_id, $paymentStatus, $ticket_type, 'America/Los_Angeles');
                if ($o_status && $paymentStatus == 'Completed') {
                    $order = $this->get_ipn_order($pid, $order_id);
                    $kentry = $this->get_kentry_mt($pid, $order['entry_id']);
                    $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
                    $this->update_expiry($pid, $order_id, $order['ticket_id'], $entry['startDate']);
                    $this->email_thank_you($pid, $order['entry_id'], $order['ticket_id'], $order['user_id'], $order_id);
                }
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        }

        return $success;
    }

    public function insert_authnet_recurr_order($pid, $uid, $subscription_id, $firstName, $lastName, $payerEmail, $city, $paymentStatus, $avsCode, $authCode, $transactionId) {
        $success = false;
        $order_id = '';
        $this->orders->select_max('order_id')
                ->from('authnet_details')
                ->where('partner_id', $pid)
                ->where('sub_id', $subscription_id);
        $query = $this->orders->get();
        $result = $query->result_array();
        foreach ($result as $r) {
            $order_id = $r['order_id'];
        }
        $order = $this->get_ipn_order($pid, $order_id);
        $paymentStatus = ($paymentStatus == 1) ? 'Completed' : 'Pending';
        $profileStatus = $this->getSubStatus($pid, $subscription_id);

        if ($profileStatus == 'Active') {
            $recurr_order_id = $this->add_recurr_order($pid, $order['entry_id'], $uid, $order['ticket_id'], $order['media_type'], $profileStatus, $paymentStatus);
            if ($recurr_order_id['success']) {
                $authnet_details = $this->insert_recurr_authnet_details($pid, $recurr_order_id['order_id'], $subscription_id, $firstName, $lastName, $payerEmail, $city, $paymentStatus, $avsCode, $authCode, $order['kentry_name'], $transactionId);
                if ($authnet_details['success']) {
                    $authnet_id = $this->insert_authnet_id($pid, $recurr_order_id['order_id']);
                    if ($authnet_id['success']) {
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
        } else {
            $profileStatus = ($profileStatus == 'Expired' || $profileStatus == 'Suspended' ) ? 2 : (($profileStatus == 'Cancelled' || $profileStatus == 'Terminated') ? 3 : 0);
            $sub_id = $this->get_sub_id($pid, $subscription_id);
            $success = $this->update_authnet_sub_profile_status($pid, $sub_id, $profileStatus, 'America/Los_Angeles');
        }

        return $success;
    }

    public function getSubStatus($pid, $subscription_id) {
        $status = '';
        $this->load->library('/authnet/autoload');
        $authnet_config = $this->get_authnet_config($pid);
        $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
        $merchantAuthentication->setName($authnet_config['api_login_id']);
        $merchantAuthentication->setTransactionKey($authnet_config['transaction_key']);

        $request = new net\authorize\api\contract\v1\ARBGetSubscriptionStatusRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setSubscriptionId($subscription_id);

        $controller = new net\authorize\api\controller\ARBGetSubscriptionStatusController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
            $status = ucwords($response->getStatus());
        } else {
            $status = 'Failed';
        }
        return $status;
    }

    public function insert_ipn_recurr_order($sm_ak, $profile_id, $profileStatus, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime) {
        $success = false;
        $pid = $this->smcipher->decrypt($sm_ak);
        $order_id = '';
        $this->orders->select_max('order_id')
                ->from('pp_details')
                ->where('partner_id', $pid)
                ->where('sub_profile_id', $profile_id);
        $query = $this->orders->get();
        $result = $query->result_array();
        foreach ($result as $r) {
            $order_id = $r['order_id'];
        }
        $order = $this->get_ipn_order($pid, $order_id);
        $recurr_order_id = $this->add_recurr_order($pid, $order['entry_id'], $order['user_id'], $order['ticket_id'], $order['media_type'], $profileStatus, $paymentStatus);
        if ($recurr_order_id['success']) {
            $pp_details = $this->insert_recurr_pp_details($sm_ak, $recurr_order_id['order_id'], $profile_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime, -1);
            if ($pp_details['success']) {
                $pp_id = $this->insert_pp_id($pid, $recurr_order_id['order_id']);
                if ($pp_id['success']) {
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

    public function add_recurr_order($pid, $entry_id, $user_id, $ticket_id, $media_type, $profileStatus, $paymentStatus) {
        $success = false;
        if ($profileStatus == 'Active') {
            $kentry = $this->get_kentry_mt($pid, $entry_id);
            $user = $this->get_email($pid, $user_id);
            $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
            $expires = $this->getExpiry($pid, $ticket_id, $entry['startDate']);
            $ticket = $this->get_ticket_details($pid, $ticket_id, $entry['startDate']);

            if ($paymentStatus == 'Completed') {
                $status = 8;
            } else if ($paymentStatus == 'Pending') {
                $status = 7;
            } else if ($paymentStatus == 'Refunded') {
                $status = 6;
            }

            $data = array(
                'expires' => $expires['expires'],
                'expiry_config' => $expires['expiry_config'],
                'max_views' => $expires['max_views'],
                'email' => $user['email'],
                'ticket_name' => $ticket['ticket_name'],
                'ticket_price' => $ticket['ticket_price'],
                'ticket_type' => $ticket['ticket_type'],
                'billing_period' => $ticket['billing_period'],
                'kentry_id' => $kentry['kentry_id'],
                'kentry_name' => $kentry['kentry_name'],
                'media_type' => $kentry['media_type'],
                'entry_id' => $entry_id,
                'user_id' => $user_id,
                'ticket_id' => $ticket_id,
                'created_at' => date("Y-m-d H:i:s"),
                'status' => $status,
                'order_status' => 1,
                'partner_id' => $pid
            );

            $this->orders->insert('order', $data);
            if ($this->orders->affected_rows() > 0) {
                $order_id = $this->orders->insert_id();
                $success = array('success' => true, 'order_id' => $order_id);
            } else {
                $success = false;
            }
        }

        return $success;
    }

    public function insert_recurr_authnet_details($pid, $order_id, $subscription_id, $firstName, $lastName, $payerEmail, $city, $paymentStatus, $avsCode, $authCode, $itemName, $transactionId) {
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'order_id' => $order_id,
            'sub_id' => $subscription_id,
            'first_name' => $this->smcipher->encrypt($firstName),
            'last_name' => $this->smcipher->encrypt($lastName),
            'payer_email' => $this->smcipher->encrypt($payerEmail),
            'city' => $this->smcipher->encrypt($city),
            'payment_status' => $this->smcipher->encrypt($paymentStatus),
            'avs_code' => $avsCode,
            'auth_code' => $this->smcipher->encrypt($authCode),
            'transaction_id' => $this->smcipher->encrypt($transactionId),
            'item_name' => $this->smcipher->encrypt($itemName),
            'order_time' => $this->smcipher->encrypt(date("Y-m-d H:i:s"))
//            'first_name' => $firstName,
//            'last_name' => $lastName,
//            'payer_email' => $payerEmail,
//            'city' => $city,
//            'payment_status' => $paymentStatus,
//            'transaction_id' => $transactionId,
//            'item_name' => $itemName,
        );
        $this->orders->insert('authnet_details', $data);
        if ($this->orders->affected_rows() > 0) {
            $id = $this->orders->insert_id();
            $success = array('success' => true, 'id' => $id);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function insert_recurr_pp_details($sm_ak, $order_id, $profile_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime, $itemName) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        $data = array(
            'partner_id' => $pid,
            'order_id' => $order_id,
            'sub_profile_id' => $profile_id,
            'receiver_email' => $this->smcipher->encrypt($receiverEmail),
            'receiver_id' => $this->smcipher->encrypt($receiverId),
            'first_name' => $this->smcipher->encrypt($firstName),
            'last_name' => $this->smcipher->encrypt($lastName),
            'payer_email' => $this->smcipher->encrypt($payerEmail),
            'payer_id' => $this->smcipher->encrypt($payerId),
            'country_code' => $this->smcipher->encrypt($countryCode),
            'payment_status' => $this->smcipher->encrypt($paymentStatus),
            'transaction_id' => $this->smcipher->encrypt($transactionId),
            'payment_type' => $this->smcipher->encrypt($paymentType),
            'order_time' => $this->smcipher->encrypt($orderTime),
            'item_name' => $this->smcipher->encrypt($itemName),
//            'receiver_email' => $receiverEmail,
//            'receiver_id' => $receiverId,
//            'first_name' => $firstName,
//            'last_name' => $lastName,
//            'payer_email' => $payerEmail,
//            'payer_id' => $payerId,
//            'country_code' => $countryCode,
//            'payment_status' => $paymentStatus,
//            'transaction_id' => $transactionId,
//            'payment_type' => $paymentType,
//            'order_time' => $orderTime,
//            'item_name' => $itemName,
        );

        $this->orders->insert('pp_details', $data);
        if ($this->orders->affected_rows() > 0) {
            $id = $this->orders->insert_id();
            $success = array('success' => true, 'id' => $id);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function activate_sub($pid, $order_id) {
        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $success = $this->activate_pp_sub($pid, $order_id);
        } else if ($active_gw == 2) {
            
        }
        return $success;
    }

    public function activate_pp_sub($pid, $order_id) {
        $success = array('success' => false);
        $pp_config = $this->get_pp_config($pid);
        $sub = $this->get_profile_id($pid, $order_id);
        $postDetails = array(
            'USER' => $pp_config['api_user_id'],
            'PWD' => $pp_config['api_password'],
            'SIGNATURE' => $pp_config['api_sig'],
            'METHOD' => "ManageRecurringPaymentsProfileStatus",
            'VERSION' => "124.0",
            'PROFILEID' => $sub['profile_id'],
            'ACTION' => 'Reactivate'
        );
        $arrPostVals = array_map(create_function('$key, $value', 'return $key."=".$value."&";'), array_keys($postDetails), array_values($postDetails));
        $postVals = rtrim(implode($arrPostVals), "&");
        $response = $this->parseString($this->run_pp_curl(PAYPAL_DEV, $postVals));
        $resp = $this->update_pp_sub($pid, $pp_config['api_user_id'], $pp_config['api_password'], $pp_config['api_sig'], $sub['profile_id']);
        if ($resp['ACK'] == 'Success') {
            $sm_ak = $this->smcipher->encrypt($pid);
            $sub_id = $this->get_sub_id($pid, $sub['profile_id']);
            $status = $this->update_ipn_sub_order($sm_ak, $sub_id, 'Active');
            if ($status) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function cancel_sub($pid, $ks, $sub_id) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $active_gw = $this->getActiveGateway($pid);
            if ($active_gw == 1) {
                $success = $this->cancel_pp_sub($pid, $sub_id);
            } else if ($active_gw == 2) {
                $success = $this->cancel_authnet_sub($pid, $sub_id);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function w_cancel_sub($sm_ak, $auth_key, $sub_id) {
        $is_logged_in = $this->is_logged_in($auth_key, $sm_ak);
        if ($is_logged_in['success']) {
            $pid = $this->smcipher->decrypt($sm_ak);
            $active_gw = $this->getActiveGateway($pid);
            if ($active_gw == 1) {
                $success = $this->cancel_pp_sub($pid, $sub_id);
            } else if ($active_gw == 2) {
                $success = $this->cancel_authnet_sub($pid, $sub_id);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function cancel_authnet_sub($pid, $sub_id) {
        $success = array('success' => false);
        $this->load->library('/authnet/autoload');
        $authnet_config = $this->get_authnet_config($pid);
        $merchantAuthentication = new net\authorize\api\contract\v1\MerchantAuthenticationType();
        $merchantAuthentication->setName($authnet_config['api_login_id']);
        $merchantAuthentication->setTransactionKey($authnet_config['transaction_key']);
        $subscriptionId = $this->get_sub_profile_id($pid, $sub_id);

        $request = new net\authorize\api\contract\v1\ARBCancelSubscriptionRequest();
        $request->setMerchantAuthentication($merchantAuthentication);
        $request->setSubscriptionId($subscriptionId['profile_id']);
        $controller = new net\authorize\api\controller\ARBCancelSubscriptionController($request);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        //log_message('error', print_r($response, TRUE));

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
            $sm_ak = $this->smcipher->encrypt($pid);
            $status = $this->update_authnet_sub_order($sm_ak, $sub_id, 3);
            if ($status) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function cancel_pp_sub($pid, $sub_id) {
        $success = array('success' => false);
        $pp_config = $this->get_pp_config($pid);
        $sub = $this->get_sub_profile_id($pid, $sub_id);
        $postDetails = array(
            'USER' => $pp_config['api_user_id'],
            'PWD' => $pp_config['api_password'],
            'SIGNATURE' => $pp_config['api_sig'],
            'METHOD' => "ManageRecurringPaymentsProfileStatus",
            'VERSION' => "124.0",
            'PROFILEID' => $sub['profile_id'],
            'ACTION' => 'Cancel'
        );
        $arrPostVals = array_map(create_function('$key, $value', 'return $key."=".$value."&";'), array_keys($postDetails), array_values($postDetails));
        $postVals = rtrim(implode($arrPostVals), "&");
        $response = $this->parseString($this->run_pp_curl(PAYPAL_DEV, $postVals));
        if ($response['ACK'] == 'Success') {
            $sm_ak = $this->smcipher->encrypt($pid);
            $status = $this->update_ipn_sub_order($sm_ak, $sub_id, 'Cancelled');
            if ($status) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function delete_sub_order($pid, $sub_id) {
        $success = false;
        $this->orders->where('partner_id', $pid);
        $this->orders->where('sub_id', $sub_id);
        $this->orders->delete('subscription');
        if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function delete_sub($pid, $ks, $sub_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $user_id = $this->get_sub_user_id($valid['pid'], $sub_id);
            $this->orders->where('partner_id', $valid['pid']);
            $this->orders->where('sub_id', $sub_id);
            $this->orders->delete('subscription');
            if ($this->orders->affected_rows() > 0) {
                $this->orders->where('partner_id', $valid['pid']);
                $this->orders->where('ticket_type', 'sub');
                $this->orders->where('user_id', $user_id);
                $this->orders->where('status', 7);
                $this->orders->delete('order');
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function w_delete_sub($sm_ak, $sub_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        $user_id = $this->get_sub_user_id($pid, $sub_id);
        $this->orders->where('partner_id', $pid);
        $this->orders->where('sub_id', $sub_id);
        $this->orders->delete('subscription');
        if ($this->orders->affected_rows() > 0) {
            $this->orders->where('partner_id', $pid);
            $this->orders->where('ticket_type', 'sub');
            $this->orders->where('user_id', $user_id);
            $this->orders->where('status', 7);
            $this->orders->delete('order');
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_sub_user_id($pid, $sub_id) {
        // Query the Db
        $this->orders->select('*')
                ->from('subscription')
                ->where('partner_id', $pid)
                ->where('sub_id', $sub_id);

        $query = $this->orders->get();
        $users_res = $query->result_array();

        $id = '';
        foreach ($users_res as $user) {
            $id = $user['user_id'];
        }

        return $id;
    }

    public function update_sub_status($pid, $ks, $sub_id, $status) {
        $success = array('success' => false);
        if ($status == 1) {
            $success = $this->reactivate_sub($pid, $ks, $sub_id);
        } else {
            $success = $this->suspend_sub($pid, $ks, $sub_id);
        }
        return $success;
    }

    public function reactivate_sub($pid, $ks, $sub_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $active_gw = $this->getActiveGateway($pid);
            if ($active_gw == 1) {
                $success = $this->reactivate_pp_sub($pid, $sub_id);
            } else if ($active_gw == 2) {
                
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function reactivate_pp_sub($pid, $sub_id) {
        $pp_config = $this->get_pp_config($pid);
        $sub = $this->get_sub_profile_id($pid, $sub_id);
        $postDetails = array(
            'USER' => $pp_config['api_user_id'],
            'PWD' => $pp_config['api_password'],
            'SIGNATURE' => $pp_config['api_sig'],
            'METHOD' => "ManageRecurringPaymentsProfileStatus",
            'VERSION' => "124.0",
            'PROFILEID' => $sub['profile_id'],
            'ACTION' => 'Reactivate'
        );
        $arrPostVals = array_map(create_function('$key, $value', 'return $key."=".$value."&";'), array_keys($postDetails), array_values($postDetails));
        $postVals = rtrim(implode($arrPostVals), "&");
        $response = $this->parseString($this->run_pp_curl(PAYPAL_DEV, $postVals));
        if ($response['ACK'] == 'Success') {
            $sm_ak = $this->smcipher->encrypt($pid);
            $status = $this->update_ipn_sub_order($sm_ak, $sub_id, 'Active');
            if ($status) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function suspend_sub($pid, $ks, $sub_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $success = $this->suspend_pp_sub($pid, $sub_id);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function suspend_pp_sub($pid, $sub_id) {
        $pp_config = $this->get_pp_config($pid);
        $sub = $this->get_sub_profile_id($pid, $sub_id);
        $postDetails = array(
            'USER' => $pp_config['api_user_id'],
            'PWD' => $pp_config['api_password'],
            'SIGNATURE' => $pp_config['api_sig'],
            'METHOD' => "ManageRecurringPaymentsProfileStatus",
            'VERSION' => "124.0",
            'PROFILEID' => $sub['profile_id'],
            'ACTION' => 'Suspend'
        );
        $arrPostVals = array_map(create_function('$key, $value', 'return $key."=".$value."&";'), array_keys($postDetails), array_values($postDetails));
        $postVals = rtrim(implode($arrPostVals), "&");
        $response = $this->parseString($this->run_pp_curl(PAYPAL_DEV, $postVals));
        if ($response['ACK'] == 'Success') {
            $sm_ak = $this->smcipher->encrypt($pid);
            $status = $this->update_ipn_sub_order($sm_ak, $sub_id, 'Suspended');
            if ($status) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_pp_sub($pid, $api_user_id, $api_password, $api_sig, $profile_id) {
        $payment_cycle = $this->get_payment_cycle($pid, $profile_id);

        $recurr_date = '';
        if ($payment_cycle['payment_cycle'] == 'Weekly') {
            $recurr_date = '+1 week';
        } else if ($payment_cycle['payment_cycle'] == 'Monthly') {
            $recurr_date = '+1 month';
        } else if ($payment_cycle['payment_cycle'] == 'Yearly') {
            $recurr_date = '+1 year';
        }

        $DaysTimestamp = strtotime($recurr_date, strtotime('now'));
        $Mo = date('m', $DaysTimestamp);
        $Day = date('d', $DaysTimestamp);
        $Year = date('Y', $DaysTimestamp);
        $StartDateGMT = $Year . '-' . $Mo . '-' . $Day . 'T00:00:00\Z';

        $postDetails = array(
            'USER' => $api_user_id,
            'PWD' => $api_password,
            'SIGNATURE' => $api_sig,
            'METHOD' => "UpdateRecurringPaymentsProfile",
            'VERSION' => "124.0",
            'PROFILEID' => $profile_id,
            'PROFILESTARTDATE' => $StartDateGMT
        );
        $arrPostVals = array_map(create_function('$key, $value', 'return $key."=".$value."&";'), array_keys($postDetails), array_values($postDetails));
        $postVals = rtrim(implode($arrPostVals), "&");
        $response = $this->parseString($this->run_pp_curl(PAYPAL_DEV, $postVals));

        return $response;
    }

    public function insert_ipn_pp_sub_details($sm_ak, $subId, $profile_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $profileStatus, $payment_cycle, $orderTime) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        if ($this->check_ipn_sub_order($pid, $subId)) {
            $status = $this->get_ipn_sub_status($pid, $subId);
            if ($status == 1) {
                $data = array(
                    'date_created' => $this->smcipher->encrypt($orderTime)
                );
                $this->orders->where('partner_id', $pid);
                $this->orders->where('sub_id', $subId);
                $this->orders->update('pp_sub_details', $data);
                if ($this->orders->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $pp_sub_id = $this->get_pp_sub_id($pid, $subId);
                $o_status = $this->update_sub_profile_status($pid, $subId, $pp_sub_id, $profileStatus, 'America/Los_Angeles');
                if ($o_status) {
                    $data = array(
                        'profile_status' => $this->smcipher->encrypt($profileStatus),
                        'date_created' => $this->smcipher->encrypt($orderTime)
                    );
                    $this->orders->where('partner_id', $pid);
                    $this->orders->where('sub_id', $subId);
                    $this->orders->update('pp_sub_details', $data);
                    if ($this->orders->affected_rows() > 0) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $data = array(
                'sub_id' => $subId,
                'profile_id' => $profile_id,
                'receiver_email' => $this->smcipher->encrypt($receiverEmail),
                'receiver_id' => $this->smcipher->encrypt($receiverId),
                'first_name' => $this->smcipher->encrypt($firstName),
                'last_name' => $this->smcipher->encrypt($lastName),
                'payer_email' => $this->smcipher->encrypt($payerEmail),
                'payer_id' => $this->smcipher->encrypt($payerId),
                'country_code' => $this->smcipher->encrypt($countryCode),
                'profile_status' => $this->smcipher->encrypt($profileStatus),
                'payment_cycle' => $this->smcipher->encrypt($payment_cycle),
                'partner_id' => $pid,
                'date_created' => $this->smcipher->encrypt($orderTime)
            );

            $this->orders->insert('pp_sub_details', $data);
            if ($this->orders->affected_rows() > 0) {
                $pp_sub_id = $this->orders->insert_id();
                $o_status = $this->update_sub_profile_status($pid, $subId, $pp_sub_id, $profileStatus, 'America/Los_Angeles');
                if ($o_status) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => false);
            }
        }

        return $success;
    }

    public function update_authnet_sub_order($sm_ak, $sub_id, $profileStatus) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;
        if ($this->update_authnet_sub_profile_status($pid, $sub_id, $profileStatus, 'America/Los_Angeles')) {
            if ($profileStatus == 3) {
                $this->delete_sub_order($pid, $sub_id);
            }
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function update_ipn_sub_order($sm_ak, $sub_id, $profileStatus) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;
        if ($this->update_ipn_sub_profile_status($pid, $sub_id, $profileStatus, 'America/Los_Angeles')) {
            if ($profileStatus == 'Cancelled') {
                $this->delete_sub_order($pid, $sub_id);
            }
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_ipn_order($pid, $order_id) {
        $success = false;

        $this->orders->select('*')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id)
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        if ($query->num_rows() > 0) {
            foreach ($result as $r) {
                $entry_id = $r['entry_id'];
                $user_id = $r['user_id'];
                $ticket_id = $r['ticket_id'];
                $ticket_type = $r['ticket_type'];
                $billing_period = $r['billing_period'];
                $media_type = $r['media_type'];
                $kentry_name = $r['kentry_name'];
            }
            $success = array('success' => true, 'entry_id' => $entry_id, 'user_id' => $user_id, 'ticket_id' => $ticket_id, 'ticket_type' => $ticket_type, 'billing_period' => $billing_period, 'media_type' => $media_type, 'kentry_name' => $kentry_name);
        }

        return $success;
    }

    public function check_ipn_sub_order($pid, $subId) {
        $success = false;
        $this->orders->select('*')
                ->from('pp_sub_details')
                ->where('partner_id', $pid)
                ->where('sub_id', $subId);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_ipn_order($pid, $order_id) {
        $success = false;
        $this->orders->select('*')
                ->from('pp_details')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_authnet_order($pid, $order_id) {
        $success = false;
        $this->orders->select('*')
                ->from('authnet_details')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_ipn_payment_status($pid, $order_id) {
        $status = '';
        $this->orders->select('status')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id);
        $query = $this->orders->get();
        $result = $query->result_array();
        foreach ($result as $r) {
            $status = $r['status'];
        }
        return $status;
    }

    public function get_ipn_sub_status($pid, $sub_id) {
        $status = '';
        $this->orders->select('profile_status')
                ->from('subscription')
                ->where('partner_id', $pid)
                ->where('sub_id', $sub_id);
        $query = $this->orders->get();
        $result = $query->result_array();
        foreach ($result as $r) {
            $status = $r['profile_status'];
        }
        return $status;
    }

    public function get_sub_id($pid, $profile_id) {
        $sub_id = '';
        $this->orders->select('sub_id')
                ->from('subscription')
                ->where('partner_id', $pid)
                ->where('sub_profile_id', $profile_id);
        $query = $this->orders->get();
        $result = $query->result_array();
        foreach ($result as $r) {
            $sub_id = $r['sub_id'];
        }
        return $sub_id;
    }

    public function update_ipn_order_status($pid, $order_id, $order_status) {
        $success = false;

        $data = array(
            'order_status' => $order_status
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->update('order', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = array('notice' => 'no changes were made');
        }

        return $success;
    }

    public function insert_authnet_sub_profile_id($pid, $order_id, $sub_id) {
        $success = false;
        $data = array(
            'sub_id' => $sub_id
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->update('authnet_details', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function insert_pp_sub_profile_id($pid, $order_id, $sub_id) {
        $success = false;
        $data = array(
            'sub_profile_id' => $sub_id
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->update('pp_details', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function insert_id_subscription($pid, $sub_id, $pp_sub, $authnet_sub, $sub_profile_id, $profile_status) {
        $success = false;
        $data = array(
            'pp_sub' => $pp_sub,
            'authnet_sub' => $authnet_sub,
            'sub_profile_id' => $sub_profile_id,
            'profile_status' => $profile_status
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('sub_id', $sub_id);
        $this->orders->update('subscription', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function insert_authnet_sub_details($pid, $order_id, $sub_id, $authnet_subscr_id, $authnet_profile_id, $authnet_payment_profile_id, $payerEmail, $firstName, $lastName, $city, $profileStatus, $payment_cycle, $date_created) {
        $success = false;

        if ($profileStatus == 0) {
            $status = 0;
        } else if ($profileStatus == 1) {
            $status = 1;
        } else if ($profileStatus == 2) {
            $status = 2;
        } else if ($profileStatus == 3) {
            $status = 3;
        }

        if ($payment_cycle == 'w') {
            $cycle = 'Weekly';
        } else if ($payment_cycle == 'm') {
            $cycle = 'Monthly';
        } else if ($payment_cycle == 'y') {
            $cycle = 'Yearly';
        }

        $data = array(
            'sub_id' => $sub_id,
            'authnet_subscr_id' => $authnet_subscr_id,
            'authnet_profile_id' => $authnet_profile_id,
            'authnet_payment_profile_id' => $this->smcipher->encrypt($authnet_payment_profile_id),
            'payer_email' => $this->smcipher->encrypt($payerEmail),
            'first_name' => $this->smcipher->encrypt($firstName),
            'last_name' => $this->smcipher->encrypt($lastName),
            'city' => $this->smcipher->encrypt($city),
            'profile_status' => $this->smcipher->encrypt($profileStatus),
            'payment_cycle' => $this->smcipher->encrypt($cycle),
            'partner_id' => $pid,
            'date_created' => $this->smcipher->encrypt($date_created)
        );

        $this->orders->insert('authnet_sub_details', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $authnet_sub_id = $this->orders->insert_id();
            if ($this->insert_authnet_sub_profile_id($pid, $order_id, $authnet_subscr_id)) {
                if ($this->insert_id_subscription($pid, $sub_id, null, $authnet_sub_id, $authnet_subscr_id, $status)) {
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

    public function insert_pp_sub_details($sm_ak, $order_id, $sub_id, $profile_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $profileStatus, $payment_cycle, $date_created) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;

        if ($profileStatus == 'PendingProfile') {
            $status = 0;
        } else if ($profileStatus == 'ActiveProfile') {
            $status = 1;
        } else if ($profileStatus == 'SuspendedProfile') {
            $status = 2;
        } else if ($profileStatus == 'CancelledProfile') {
            $status = 3;
        }

        if ($payment_cycle == 'w') {
            $cycle = 'Weekly';
        } else if ($payment_cycle == 'm') {
            $cycle = 'Monthly';
        } else if ($payment_cycle == 'y') {
            $cycle = 'Yearly';
        }

        $data = array(
            'sub_id' => $sub_id,
            'profile_id' => $profile_id,
            'receiver_email' => $this->smcipher->encrypt($receiverEmail),
            'receiver_id' => $this->smcipher->encrypt($receiverId),
            'first_name' => $this->smcipher->encrypt($firstName),
            'last_name' => $this->smcipher->encrypt($lastName),
            'payer_email' => $this->smcipher->encrypt($payerEmail),
            'payer_id' => $this->smcipher->encrypt($payerId),
            'country_code' => $this->smcipher->encrypt($countryCode),
            'profile_status' => $this->smcipher->encrypt($profileStatus),
            'payment_cycle' => $this->smcipher->encrypt($cycle),
            'partner_id' => $pid,
            'date_created' => $this->smcipher->encrypt($date_created)
        );

        $this->orders->insert('pp_sub_details', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $pp_sub_id = $this->orders->insert_id();
            if ($this->insert_pp_sub_profile_id($pid, $order_id, $profile_id)) {
                if ($this->insert_id_subscription($pid, $sub_id, $pp_sub_id, null, $profile_id, $status)) {
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

    public function insert_subscription($pid, $uid, $bill_per, $ticket_name, $ticket_price, $email) {
        $success = false;

        if ($bill_per == 'week') {
            $cycle = 'Weekly';
        } else if ($bill_per == 'month') {
            $cycle = 'Monthly';
        } else if ($bill_per == 'year') {
            $cycle = 'Yearly';
        }

        $data = array(
            'user_id' => $uid,
            'profile_status' => 0,
            'payment_cycle' => $cycle,
            'email' => $email,
            'ticket_name' => $ticket_name,
            'ticket_price' => $ticket_price,
            'created_at' => date("Y-m-d H:i:s"),
            'partner_id' => $pid
        );

        $this->orders->insert('subscription', $data);
        $this->orders->limit(1);
        if ($this->orders->affected_rows() > 0) {
            $sub_id = $this->orders->insert_id();
            $success = array('success' => true, 'sub_id' => $sub_id);
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_sub_order($pid, $order_id) {
        $success = false;
        $this->orders->select('*')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('order_id', $order_id);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $order = $query->result_array();
            foreach ($order as $o) {
                $success = array('success' => true, 'email' => $o['email'], 'ticket_name' => $o['ticket_name'], 'ticket_price' => $o['ticket_price']);
            }
        } else {
            $success = false;
        }
        return $success;
    }

    public function update_expiry($pid, $order_id, $ticket_id, $startDate) {
        $success = false;
        $expires = $this->getExpiry($pid, $ticket_id, $startDate);
        $data = array(
            'expires' => $expires['expires'],
            'updated_at' => date("Y-m-d H:i:s")
        );

        $this->orders->where('partner_id', $pid);
        $this->orders->where('order_id', $order_id);
        $this->orders->update('order', $data);
        $this->orders->limit(1);

        if ($expires['expiry_config'] == -1 && $this->orders->affected_rows() == 0) {
            $success = true;
        } else if ($this->orders->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function check_email_config($pid) {
        $success = false;
        $this->orders->select('*')
                ->from('email_config')
                ->where('partner_id', $pid);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function email_thank_you($pid, $entry_id, $ticket_id, $user_id, $order_id) {
        $subject = '';
        $body = '';

        $user_email = $this->get_email($pid, $user_id);
        if ($this->check_email($pid)) {
            if ($this->check_thank_you_default($pid)) {
                if ($this->check_email_config($pid)) {
                    $this->orders->select('*')
                            ->from('email_config')
                            ->where('partner_id', $pid);

                    $query = $this->orders->get();
                    $res = $query->result_array();
                    $row = array();
                    foreach ($res as $r) {
                        $row['from_name'] = stripslashes($r['from_name']);
                        $row['from_email'] = $r['from_email'];
                    }
                    $from = $row['from_name'];
                    $from_email = $row['from_email'];
                } else {
                    $user = $this->smportal->get_User($pid);
                    $from = $user['name'];
                    $from_email = $user['email'];
                }

                $email = $this->getEmailConfig($pid);
                $kentry = $this->get_kentry_mt($pid, $entry_id);
                $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
                $ticket = $this->get_ticket_details($pid, $ticket_id, $entry['startDate']);
                $entry_name = $this->get_kentry_name($pid, $entry_id);
                $business_name = $this->smportal->get_user_details($pid);
                $order_number = $order_id;

                if ($ticket['billing_period'] == 'week') {
                    $ticket_type = ' per week.';
                    $post_ticket = 'A weekly subscription at ';
                } else if ($ticket['billing_period'] == 'month') {
                    $ticket_type = ' per month.';
                    $post_ticket = 'A monthly subscription at ';
                } else if ($ticket['billing_period'] == 'year') {
                    $ticket_type = ' per year.';
                    $post_ticket = 'A yearly subscription at ';
                } else if ($ticket['billing_period'] == -1) {
                    $post_ticket = '';
                    $ticket_type = '';
                }

                $to = $user_email['email']; //change
                $subject = $email['thankyou_subject'];
                $body = $email['thankyou_body'];
                $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
                $body = str_replace("<strong>%order_number%</strong>", $order_number, $body);
                $body = str_replace("<strong>%order_date%</strong>", date("m/d/Y"), $body);
                $body = str_replace("<strong>%entry_name%</strong>", $entry_name, $body);
                $body = str_replace("<strong>%ticket_name%</strong>", $ticket['ticket_name'], $body);
                $body = str_replace("<strong>%ticket_description%</strong>", $ticket['ticket_desc'], $body);
                $body = str_replace("<strong>%ticket_price%</strong>", $post_ticket . $ticket['ticket_price'] . $ticket_type, $body);
                $body = str_replace("<strong>%ticket_expiry%</strong>", $ticket['ticket_expiry'], $body);
                $body = str_replace("<strong>%ticket_views%</strong>", $ticket['max_views'], $body);
                $body = str_replace("<strong>%email%</strong>", $from_email, $body);
            } else {
                if ($this->check_email_config($pid)) {
                    $this->orders->select('*')
                            ->from('email_config')
                            ->where('partner_id', $pid);

                    $query = $this->orders->get();
                    $res = $query->result_array();
                    $row = array();
                    foreach ($res as $r) {
                        $row['from_name'] = stripslashes($r['from_name']);
                        $row['from_email'] = $r['from_email'];
                    }
                    $from = $row['from_name'];
                    $from_email = $row['from_email'];
                } else {
                    $user = $this->smportal->get_User($pid);
                    $from = $user['name'];
                    $from_email = $user['email'];
                }
                $email = $this->getDefaultEmailConfig();
                $kentry = $this->get_kentry_mt($pid, $entry_id);
                $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
                $ticket = $this->get_ticket_details($pid, $ticket_id, $entry['startDate']);
                $entry_name = $this->get_kentry_name($pid, $entry_id);
                $business_name = $this->smportal->get_user_details($pid);
                $order_number = 10000 + intval($order_id);

                if ($ticket['billing_period'] == 'week') {
                    $ticket_type = ' per week.';
                    $post_ticket = 'A weekly subscription at ';
                } else if ($ticket['billing_period'] == 'month') {
                    $ticket_type = ' per month.';
                    $post_ticket = 'A monthly subscription at ';
                } else if ($ticket['billing_period'] == 'year') {
                    $ticket_type = ' per year.';
                    $post_ticket = 'A yearly subscription at ';
                } else if ($ticket['billing_period'] == -1) {
                    $post_ticket = '';
                    $ticket_type = '';
                }

                $to = $user_email['email'];
                $subject = $email['thankyou_subject'];
                $body = $email['thankyou_body'];
                $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
                $body = str_replace("<strong>%order_number%</strong>", $order_number, $body);
                $body = str_replace("<strong>%order_date%</strong>", date("m/d/Y"), $body);
                $body = str_replace("<strong>%entry_name%</strong>", $entry_name, $body);
                $body = str_replace("<strong>%ticket_name%</strong>", $ticket['ticket_name'], $body);
                $body = str_replace("<strong>%ticket_description%</strong>", $ticket['ticket_desc'], $body);
                $body = str_replace("<strong>%ticket_price%</strong>", $post_ticket . $ticket['ticket_price'] . $ticket_type, $body);
                $body = str_replace("<strong>%ticket_expiry%</strong>", $ticket['ticket_expiry'], $body);
                $body = str_replace("<strong>%ticket_views%</strong>", $ticket['max_views'], $body);
                $body = str_replace("<strong>%email%</strong>", $from_email, $body);
            }
        } else {
            if ($this->check_email_config($pid)) {
                $this->orders->select('*')
                        ->from('email_config')
                        ->where('partner_id', $pid);

                $query = $this->orders->get();
                $res = $query->result_array();
                $row = array();
                foreach ($res as $r) {
                    $row['from_name'] = stripslashes($r['from_name']);
                    $row['from_email'] = $r['from_email'];
                }
                $from = $row['from_name'];
                $from_email = $row['from_email'];
            } else {
                $user = $this->smportal->get_User($pid);
                $from = $user['name'];
                $from_email = $user['email'];
            }

            $email = $this->getDefaultEmailConfig();
            $kentry = $this->get_kentry_mt($pid, $entry_id);
            $entry = $this->smportal->get_entry_details($pid, $kentry['kentry_id']);
            $ticket = $this->get_ticket_details($pid, $ticket_id, $entry['startDate']);
            $entry_name = $this->get_kentry_name($pid, $entry_id);
            $business_name = $this->smportal->get_user_details($pid);
            $order_number = 10000 + intval($order_id);

            if ($ticket['billing_period'] == 'week') {
                $ticket_type = ' per week.';
                $post_ticket = 'A weekly subscription at ';
            } else if ($ticket['billing_period'] == 'month') {
                $ticket_type = ' per month.';
                $post_ticket = 'A monthly subscription at ';
            } else if ($ticket['billing_period'] == 'year') {
                $ticket_type = ' per year.';
                $post_ticket = 'A yearly subscription at ';
            } else if ($ticket['billing_period'] == -1) {
                $post_ticket = '';
                $ticket_type = '';
            }

            $to = $user_email['email'];
            $subject = $email['thankyou_subject'];
            $body = $email['thankyou_body'];
            $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
            $body = str_replace("<strong>%order_number%</strong>", $order_number, $body);
            $body = str_replace("<strong>%order_date%</strong>", date("m/d/Y"), $body);
            $body = str_replace("<strong>%entry_name%</strong>", $entry_name, $body);
            $body = str_replace("<strong>%ticket_name%</strong>", $ticket['ticket_name'], $body);
            $body = str_replace("<strong>%ticket_description%</strong>", $ticket['ticket_desc'], $body);
            $body = str_replace("<strong>%ticket_price%</strong>", $post_ticket . $ticket['ticket_price'] . $ticket_type, $body);
            $body = str_replace("<strong>%ticket_expiry%</strong>", $ticket['ticket_expiry'], $body);
            $body = str_replace("<strong>%ticket_views%</strong>", $ticket['max_views'], $body);
            $body = str_replace("<strong>%email%</strong>", $from_email, $body);
        }

        $result = $this->queue_email
                (
                null, // foreign_id_a
                null, // foreign_id_b
                3, // priority
                true, // is_inmediate
                null, // date_queued
                false, // is_html
                $from_email, // from
                $from, // from_name
                $to, // to
                "", // replyto
                "", // replyto_name
                $subject, // subject
                $body, // content
                $body, // content_non_html
                false // list_unsubscribe_url
        );

        return $result;
    }

    public function check_email($pid) {
        $success = false;
        $this->orders->select('*')
                ->from('email')
                ->where('partner_id', $pid);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_thank_you_default($pid) {
        $success = false;
        $result = '';
        $thankyou_default = '';
        $this->orders->select('thankyou_email_default')
                ->from('email')
                ->where('partner_id', $pid);
        $query = $this->orders->get();
        $result = $query->result_array();

        foreach ($result as $d) {
            $thankyou_default = $d['thankyou_email_default'];
        }

        if ($thankyou_default == '1') {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function getEmailConfig($pid) {
        $email = array();

        $this->orders->select('*')
                ->from('email')
                ->where('partner_id', $pid)
                ->limit(1);
        $query = $this->orders->get();
        $emailConfig = $query->result_array();

        foreach ($emailConfig as $e) {
            $email['thankyou_subject'] = $e['thankyou_email_subject'];
            $email['thankyou_body'] = stripcslashes($e['thankyou_email_body']);
        }

        return $email;
    }

    public function getDefaultEmailConfig() {
        $email = array();

        $this->orders->select('*')
                ->from('email')
                ->where('partner_id', '1')
                ->limit(1);
        $query = $this->orders->get();
        $emailConfig = $query->result_array();

        foreach ($emailConfig as $e) {
            $email['thankyou_subject'] = $e['thankyou_email_subject'];
            $email['thankyou_body'] = stripcslashes($e['thankyou_email_body']);
        }

        return $email;
    }

    public function get_kentry_name($pid, $entry_id) {
        $entry_name = '';

        $this->orders->select('*')
                ->from('entry')
                ->where('entry_id', $entry_id)
                ->where('partner_id', $pid)
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $entry_name = $r['kentry_name'];
        }

        return $entry_name;
    }

    public function get_ticket_details($pid, $ticket_id, $start_date) {
        $ticket = array();
        $expiry_config = '';
        $desc = '';
        $max_views = '';

        $this->orders->select('*')
                ->from('ticket')
                ->where('ticket_id', $ticket_id)
                ->where('partner_id', $pid)
                ->limit(1);
        $query = $this->orders->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $ticket['ticket_name'] = $r['ticket_name'];
            $ticket['ticket_type'] = $r['ticket_type'];
            $ticket['billing_period'] = $r['billing_period'];
            $desc = $r['ticket_desc'];
            $price = $r['ticket_price'];
            $expiry_config = $r['expiry_config'];
            $max_views = $r['max_views'];
        }

        if ($desc == '' || $desc == null) {
            $ticket['ticket_desc'] = ' ';
        } else {
            $ticket['ticket_desc'] = $desc;
        }

        if ($max_views == -1) {
            $ticket['max_views'] = 'Unlimited';
        } else {
            $ticket['max_views'] = $max_views;
        }

        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $currency = $this->get_pp_currency($pid);
        } else if ($active_gw == 2) {
            $currency = $this->get_authnet_currency($pid);
        }

        $exchangeRate = $this->currency("USD", $currency);
        $currency_symbol = $this->currency_symbol($currency);
        if ($currency == 'JPY' || $currency == 'HUF') {
            $price = round($this->final_price($price, $exchangeRate));
        } else {
            $price = $this->final_price($price, $exchangeRate);
        }
        $ticket['ticket_price'] = $currency_symbol . $price . "<sup>" . $currency . "</sup>";

        $expiry_config_details = '';
        if ($expiry_config == -1) {
            $expiry_config_details .= 'Does not expire. ';
        } else {
            $expiry_config = json_decode($expiry_config);
            $expiry_config_details .= 'Expires in<span style = "font-weight: bold;">';
            foreach ($expiry_config->expiryConfig as $config) {
                if ($config->years > 0) {
                    if ($config->years == 1) {
                        $expiry_config_details .= ' ' . $config->years . ' year';
                    } else {
                        $expiry_config_details .= ' ' . $config->years . ' years';
                    }
                }

                if ($config->months > 0) {
                    if ($config->months == 1) {
                        $expiry_config_details .= ' ' . $config->months . ' month';
                    } else {
                        $expiry_config_details .= ' ' . $config->months . ' months';
                    }
                }

                if ($config->days > 0) {
                    if ($config->days == 1) {
                        $expiry_config_details .= ' ' . $config->days . ' day';
                    } else {
                        $expiry_config_details .= ' ' . $config->days . ' days';
                    }
                }

                if ($config->hours > 0) {
                    if ($config->hours == 1) {
                        $expiry_config_details .= ' ' . $config->hours . ' hour';
                    } else {
                        $expiry_config_details .= ' ' . $config->hours . ' hours';
                    }
                }

                if ($start_date) {
                    $expiry_config_details .= '</span> from the start date.';
                } else {
                    $expiry_config_details .= '</span> from today.';
                }
            }
        }

        $ticket['ticket_expiry'] = $expiry_config_details;

        return $ticket;
    }

    public function getExpiry($pid, $ticket_id, $startDate) {
        $success = false;
        $this->orders->select('*')
                ->from('ticket')
                ->where('partner_id', $pid)
                ->where('ticket_id', $ticket_id)
                ->where('status', 1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $views = $query->result_array();

            foreach ($views as $v) {
                $config = $v['expiry_config'];
                if ($v['expiry_config'] == -1) {
                    $expires = -1;
                } else {
                    $expiry_config = json_decode($v['expiry_config']);
                    $hours = (int) $expiry_config->expiryConfig[0]->hours;
                    $days = (int) $expiry_config->expiryConfig[0]->days;
                    $months = (int) $expiry_config->expiryConfig[0]->months;
                    $years = (int) $expiry_config->expiryConfig[0]->years;
                    if ($startDate) {
                        $start_date = date("Y-m-d H:i:s", $startDate);
                        $today_date = date("Y-m-d H:i:s");
                        $start_time = new DateTime($start_date);
                        $current_time = new DateTime($today_date);
                        if ($current_time < $start_time) {
                            $today = date("Y-m-d H:i:s", $startDate);
                        } else {
                            $today = date("Y-m-d H:i:s");
                        }
                    } else {
                        $today = date("Y-m-d H:i:s");
                    }
                    $expires = date("Y-m-d H:i:s", strtotime("$today + $hours hour $days day $months month $years year"));
                }

                $success = array('success' => true, 'max_views' => $v['max_views'], 'expires' => $expires, 'expiry_config' => $config);
            }
        } else {
            $success = false;
        }
        return $success;
    }

    public function get_entry_id($pid, $entryId) {
        $success = false;
        $this->orders->select('entry_id')
                ->from('entry')
                ->where('partner_id', $pid)
                ->where('kentry_id', $entryId);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $entry = $query->result_array();
            foreach ($entry as $e) {
                $success = array('success' => true, 'entry_id' => $e['entry_id']);
            }
        } else {
            $success = false;
        }
        return $success;
    }

    public function get_email($pid, $user_id) {
        $this->orders->select('*')
                ->from('user')
                ->where('user_id', $user_id)
                ->where('partner_id', $pid);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $email = $query->result_array();
            foreach ($email as $e) {
                $success = array('success' => true, 'email' => $e['email']);
            }
        } else {
            $success = false;
        }

        return $success;
    }

    public function expiry_check($pid, $order_id, $tz) {
        date_default_timezone_set($tz);
        $valid = false;
        $this->orders->select('*')
                ->from('order')
                ->where('status', 2)
                ->where('order_status', 1)
                ->where('order_id', $order_id)
                ->where('partner_id', $pid);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $result = $query->result_array();
            foreach ($result as $r) {
                $expiry = $r['expires'];
            }

            $today = date("Y-m-d H:i:s");
            $current_date = new DateTime($today);
            $expiry_time = new DateTime($expiry);
            if ($current_date < $expiry_time) {
                $valid = true;
            } else {
                $valid = false;
            }
        } else {
            $valid = false;
        }

        return $valid;
    }

    public function currency($from_Currency, $to_Currency) {
        $success = false;
        $currency_rate = '';
        $this->orders->select('rate')
                ->from('exchange_rates')
                ->where('name = "' . $from_Currency . $to_Currency . '"');
        $query = $this->orders->get();
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

    public function get_confirm($sm_ak, $kentry, $ticket_id, $type, $protocol, $has_start) {
        $cat = false;
        $pid = $this->smcipher->decrypt($sm_ak);

        if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
            $cat = true;
            $entry_details = $this->smportal->get_cat_details($pid, $kentry);
        } else {
            $entry_details = $this->get_entry_details($pid, $kentry);
        }

        $success = false;
        $ticket_details = '';

        $this->orders->select('*')
                ->from('ticket')
                ->where('partner_id', $pid)
                ->where('status', 1)
                ->where('ticket_id', $ticket_id);

        $query = $this->orders->get();
        $ticket = $query->result_array();
        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $currency = $this->get_pp_currency($pid);
        } else if ($active_gw == 2) {
            $currency = $this->get_authnet_currency($pid);
        }
        $exchangeRate = $this->currency("USD", $currency);
        $currency_symbol = $this->currency_symbol($currency);
        foreach ($ticket as $t) {
            if ($currency == 'JPY' || $currency == 'HUF') {
                $price = round($this->final_price($t['ticket_price'], $exchangeRate));
            } else {
                $price = $this->final_price($t['ticket_price'], $exchangeRate);
            }

            $period = '';
            $ticket_description = '';
            $t_details = '';

            if ($t['billing_period'] == 'week') {
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }
                $date = date('F j, Y', strtotime('+1 week'));
                $t_details .= 'As part of a <span style="font-weight: bold;">Weekly Subscription</span>, all titles will be available to watch as many times as you like. You will be charged immediately for the amount of ' . $currency_symbol . $price . '. You will then be charged ' . $currency_symbol . $price . '/week on a recurring basis beginning on <span style="font-weight: bold;">' . $date . '</span>, which is the start of your new billing cycle.';
                $period = '<span id="period">/WEEK</span>';
            } else if ($t['billing_period'] == 'month') {
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }
                $date = date('F j, Y', strtotime('+1 month'));
                $t_details .= 'As part of a <span style="font-weight: bold;">Monthly Subscription</span>, all titles will be available to watch as many times as you like. You will be charged immediately for the amount of ' . $currency_symbol . $price . '. You will then be charged ' . $currency_symbol . $price . '/month on a recurring basis beginning on <span style="font-weight: bold;">' . $date . '</span>, which is the start of your new billing cycle.';
                $period = '<span id="period">/MONTH</span>';
            } else if ($t['billing_period'] == 'year') {
                $ticket_description = '';
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }
                $date = date('F j, Y', strtotime('+1 year'));
                $t_details .= 'As part of a <span style="font-weight: bold;">Yearly Subscription</span>, all titles will be available to watch as many times as you like. You will be charged immediately for the amount of ' . $currency_symbol . $price . '. You will then be charged ' . $currency_symbol . $price . '/year on a recurring basis beginning on <span style="font-weight: bold;">' . $date . '</span>, which is the start of your new billing cycle.';
                $period = '<span id="period">/YEAR</span>';
            } else {
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }

                if ($t['max_views'] == -1 && $t['expiry_config'] != -1) {
                    $t_details .= 'This title ';
                } else if ($t['max_views'] == 1) {
                    $t_details .= 'This title will be available to watch a total of <span style = "font-weight: bold;">' . $t['max_views'] . ' time</span>. ';
                } else if ($t['max_views'] != -1) {
                    $t_details .= 'This title will be available to watch a total of <span style = "font-weight: bold;">' . $t['max_views'] . ' times</span>. ';
                }

                if ($t['expiry_config'] == -1) {
                    $t_details .= '';
                } else {
                    $expiry_config = json_decode($t['expiry_config']);
                    if ($t['max_views'] == -1) {
                        $t_details .= ' will expire in<span style = "font-weight: bold;">';
                    } else {
                        $t_details .= 'It will expire in<span style = "font-weight: bold;">';
                    }

                    foreach ($expiry_config->expiryConfig as $config) {
                        if ($config->years > 0) {
                            if ($config->years == 1) {
                                $t_details .= ' ' . $config->years . ' year';
                            } else {
                                $t_details .= ' ' . $config->years . ' years';
                            }
                        }

                        if ($config->months > 0) {
                            if ($config->months == 1) {
                                $t_details .= ' ' . $config->months . ' month';
                            } else {
                                $t_details .= ' ' . $config->months . ' months';
                            }
                        }

                        if ($config->days > 0) {
                            if ($config->days == 1) {
                                $t_details .= ' ' . $config->days . ' day';
                            } else {
                                $t_details .= ' ' . $config->days . ' days';
                            }
                        }

                        if ($config->hours > 0) {
                            if ($config->hours == 1) {
                                $t_details .= ' ' . $config->hours . ' hour';
                            } else {
                                $t_details .= ' ' . $config->hours . ' hours';
                            }
                        }

                        if ($has_start === 'true') {
                            $t_details .= '</span> of the start date.';
                        } else {
                            $t_details .= '</span> of your purchase.';
                        }
                    }
                }
            }

            $img = '';
            if ($type == 's') {
                $img = '<img src = "' . $protocol . '://mediaplatform.streamingmediahosting.com/p/' . $pid . '/thumbnail/entry_id/' . $kentry . '/width/169" width="169px" />';
            } else if ($type == 'p') {
                $entry_id = $this->smportal->get_thumb($pid, $kentry);
                $img = '<img src = "' . $protocol . '://mediaplatform.streamingmediahosting.com/p/' . $pid . '/thumbnail/entry_id/' . $entry_id . '/width/169" width="169px" />';
            } else if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                $entry = $this->smportal->get_cat_thumb($pid, $kentry);
                $img = '<img src = "' . $protocol . '://mediaplatform.streamingmediahosting.com/p/' . $pid . '/thumbnail/entry_id/' . $entry . '/width/169" width="169px" />';
            }

            if ($active_gw == 1) {
                if ($t['ticket_type'] == 'reg') {
                    $description = '<span style="font-size: 11px;">Click on the <span style="font-weight: bold;">Confirm and Pay</span> button to complete your payment through PayPal&trade;. If you do not have a PayPal&trade; account, simply click on the "buy as a guest" link. You will be allowed to view this content as soon as the payment is made.</span>';
                } else {
                    $description = '<span style="font-size: 11px;">Click on the <span style="font-weight: bold;">Confirm and Pay</span> button to complete your payment through PayPal&trade;. Because this is a subscription, you <span style="font-weight: bold;">must</span> have a PayPal&trade; account to complete this purchase. You will be allowed to view this content as soon as the payment is made.</span>';
                }
            } else if ($active_gw == 2) {
                if ($t['ticket_type'] == 'reg') {
                    $description = '<span style="font-size: 11px;">Click on the <span style="font-weight: bold;">Confirm and Pay</span> button to complete your payment through Authorize.net&trade;. You will be allowed to view this content as soon as the payment is made.</span>';
                } else {
                    $description = '<span style="font-size: 11px;">Click on the <span style="font-weight: bold;">Confirm and Pay</span> button to complete your payment through Authorize.net&trade;. You will be allowed to view this content as soon as the payment is made.</span>';
                }
            }

            $entry_title = stripslashes($entry_details['name']);
            if (strlen($entry_title) > 24) {
                $entry_title = substr($entry_title, 0, 24) . "...";
            }

            $ticket_details .= '<div id="ticket">
            <div id="entry" style = "padding-top: 15px;">
            <div id="thumb_price_wrapper">
                <div id="confirm-entry-thumb">' . $img . '</div>
                <div id="confirm-entry-wrapper">
                    <div id="entry-title">' . $entry_title . ' </div>
                    <div id="entry-price">' . $currency_symbol . $price . $period . '</div>
                </div>                
            </div>
            <div class="clear"></div>
            <div id="entry-ticket-detail">' . $t_details . '</div>
            <div id="confirm-entry-details">
            <div id="entry-confirm-desc">' . $ticket_description . ' </div>
            </div>
            <div class="clear"></div>
            </div>
            <div id="ppv-ticket-details">
            <center>' . $description . '</center><br>
            </div>
            </div>';
        }

        $success = array('success' => true, 'content' => $ticket_details);
        return $success;
    }

    public function get_checkout_details($sm_ak, $entryId, $kentry, $ticket_id, $type, $uid) {
        $pid = $this->smcipher->decrypt($sm_ak);
        if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
            $entry_details = $this->smportal->get_cat_details($pid, $kentry);
        } else {
            $entry_details = $this->get_entry_details($pid, $kentry);
        }

        $this->orders->select('*')
                ->from('ticket')
                ->where('partner_id', $pid)
                ->where('status', 1)
                ->where('ticket_id', $ticket_id);

        $query = $this->orders->get();
        $ticket = $query->result_array();
        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $currency = $this->get_pp_currency($pid);
        } else if ($active_gw == 2) {
            $currency = $this->get_authnet_currency($pid);
        }
        $exchangeRate = $this->currency("USD", $currency);
        $currency_symbol = $this->currency_symbol($currency);

        foreach ($ticket as $t) {
            if ($currency == 'JPY' || $currency == 'HUF') {
                $price = round($this->final_price($t['ticket_price'], $exchangeRate));
            } else {
                $price = $this->final_price($t['ticket_price'], $exchangeRate);
            }

            $period = '';
            $ticket_description = '';
            $t_details = '';

            if ($t['billing_period'] == 'week') {
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }
                $date = date('F j, Y', strtotime('+1 week'));
                $t_details .= 'As part of a <span style="font-weight: bold;">Weekly Subscription</span>, all titles will be available to watch as many times as you like. Your card will be charged immediately for the amount of ' . $currency_symbol . $price . '. You will then be charged ' . $currency_symbol . $price . '/week on a recurring basis beginning on <span style="font-weight: bold;">' . $date . '</span>, which is the start of your new billing cycle.<br><br>';
                $period = '/WEEK';
            } else if ($t['billing_period'] == 'month') {
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }
                $date = date('F j, Y', strtotime('+1 month'));
                $t_details .= 'As part of a <span style="font-weight: bold;">Monthly Subscription</span>, all titles will be available to watch as many times as you like. Your card will be charged immediately for the amount of ' . $currency_symbol . $price . '. You will then be charged ' . $currency_symbol . $price . '/month on a recurring basis beginning on <span style="font-weight: bold;">' . $date . '</span>, which is the start of your new billing cycle.<br><br>';
                $period = '/MONTH';
            } else if ($t['billing_period'] == 'year') {
                $ticket_description = '';
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }
                $date = date('F j, Y', strtotime('+1 year'));
                $t_details .= 'As part of a <span style="font-weight: bold;">Yearly Subscription</span>, all titles will be available to watch as many times as you like. Your card will be charged immediately for the amount of ' . $currency_symbol . $price . '. You will then be charged ' . $currency_symbol . $price . '/year on a recurring basis beginning on <span style="font-weight: bold;">' . $date . '</span>, which is the start of your new billing cycle.<br><br>';
                $period = '/YEAR';
            } else {
                if ($t['ticket_desc'] != null || $t['ticket_desc'] != '') {
                    $ticket_description = '<span style="font-size: 12px;">' . $t['ticket_desc'] . '</span>';
                }

                if ($t['max_views'] == -1 && $t['expiry_config'] != -1) {
                    $t_details .= 'This title ';
                } else if ($t['max_views'] == 1) {
                    $t_details .= 'This title will be available to watch a total of <span style = "font-weight: bold;">' . $t['max_views'] . ' time</span>. ';
                } else if ($t['max_views'] != -1) {
                    $t_details .= 'This title will be available to watch a total of <span style = "font-weight: bold;">' . $t['max_views'] . ' times</span>. ';
                }

                if ($t['expiry_config'] == -1) {
                    $t_details .= '';
                } else {
                    $expiry_config = json_decode($t['expiry_config']);
                    if ($t['max_views'] == -1) {
                        $t_details .= ' will expire in<span style = "font-weight: bold;">';
                    } else {
                        $t_details .= 'It will expire in<span style = "font-weight: bold;">';
                    }

                    foreach ($expiry_config->expiryConfig as $config) {
                        if ($config->years > 0) {
                            if ($config->years == 1) {
                                $t_details .= ' ' . $config->years . ' year';
                            } else {
                                $t_details .= ' ' . $config->years . ' years';
                            }
                        }

                        if ($config->months > 0) {
                            if ($config->months == 1) {
                                $t_details .= ' ' . $config->months . ' month';
                            } else {
                                $t_details .= ' ' . $config->months . ' months';
                            }
                        }

                        if ($config->days > 0) {
                            if ($config->days == 1) {
                                $t_details .= ' ' . $config->days . ' day';
                            } else {
                                $t_details .= ' ' . $config->days . ' days';
                            }
                        }

                        if ($config->hours > 0) {
                            if ($config->hours == 1) {
                                $t_details .= ' ' . $config->hours . ' hour';
                            } else {
                                $t_details .= ' ' . $config->hours . ' hours';
                            }
                        }

                        $t_details .= '</span> of purchase.';
                    }
                }
            }

            $img = '';
            if ($type == 's') {
                $img = '/p/' . $pid . '/thumbnail/entry_id/' . $kentry . '/width/300';
            } else if ($type == 'p') {
                $entry_id = $this->smportal->get_thumb($pid, $kentry);
                $img = '/p/' . $pid . '/thumbnail/entry_id/' . $entry_id . '/width/300';
            } else if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                $entry = $this->smportal->get_cat_thumb($pid, $kentry);
                $img = '/p/' . $pid . '/thumbnail/entry_id/' . $entry . '/width/300';
            }
        }

        $user_email = $this->get_email($pid, $uid);
        $user_name = $this->get_user_name($pid, $uid);
        $title = $entry_details['name'];

        $success = array('title' => $title, 'description' => $t_details, 'price' => $price, 'currency' => $currency, 'currency_symbol' => $currency_symbol, 'thumb_img' => $img, 'period' => $period, 'user_email' => $user_email['email'], 'user_name' => $user_name);
        return $success;
    }

    public function get_entry_details($pid, $entryId) {
        return $this->smportal->get_entry_details($pid, $entryId);
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function get_pp_currency($pid) {
        $this->orders->select('currency')
                ->from('paypal')
                ->where('partner_id', $pid);

        $query = $this->orders->get();
        $currency = $query->result_array();
        foreach ($currency as $c) {
            $currency = $c['currency'];
        }
        return $currency;
    }

    public function get_authnet_currency($pid) {
        $this->orders->select('currency')
                ->from('authnet')
                ->where('partner_id', $pid);

        $query = $this->orders->get();
        $currency = $query->result_array();
        foreach ($currency as $c) {
            $currency = $c['currency'];
        }
        return $currency;
    }

    public function is_order_pending($pid, $order_id, $uid) {
        $success = false;
        $this->orders->select('*')
                ->from('order')
                ->where('partner_id', $pid)
                ->where('user_id', $uid)
                ->where('order_id', $order_id)
                ->where('status', 1);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function is_sub_order_pending($pid, $sub_id, $uid) {
        $success = false;
        $this->orders->select('*')
                ->from('subscription')
                ->where('partner_id', $pid)
                ->where('user_id', $uid)
                ->where('sub_id', $sub_id)
                ->where('profile_status', 0);
        $query = $this->orders->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function cancel_order($sm_ak, $order_id, $sub_id, $uid) {
        $success = array('success' => false);
        $pid = $this->smcipher->decrypt($sm_ak);
        if ($sub_id == -1) {
            if ($this->is_order_pending($pid, $order_id, $uid)) {
                $success = $this->w_delete_order($sm_ak, $order_id);
            } else {
                $success = array('success' => true);
            }
        } else {
            if ($this->is_sub_order_pending($pid, $sub_id, $uid)) {
                $success = $this->w_delete_sub($sm_ak, $sub_id);
            } else {
                $success = array('success' => true);
            }
        }

        return $success;
    }

    public function currency_symbol($curr) {
        $symbol = '';
        if ($curr == 'AUD')
            $symbol = '&#36;';
        else if ($curr == 'CAD')
            $symbol = '&#36;';
        else if ($curr == 'CZK')
            $symbol = '';
        else if ($curr == 'DKK')
            $symbol = '';
        else if ($curr == 'EUR')
            $symbol = '&euro;';
        else if ($curr == 'HKD')
            $symbol = '&#36;';
        else if ($curr == 'HUF')
            $symbol = '';
        else if ($curr == 'JPY')
            $symbol = '&yen;';
        else if ($curr == 'NZD')
            $symbol = '&#36;';
        else if ($curr == 'NOK')
            $symbol = '';
        else if ($curr == 'PLN')
            $symbol = '';
        else if ($curr == 'SGD')
            $symbol = '&#36;';
        else if ($curr == 'SEK')
            $symbol = '';
        else if ($curr == 'CHF')
            $symbol = '';
        else if ($curr == 'GBP')
            $symbol = '&pound;';
        else if ($curr == 'USD')
            $symbol = '&#36;';
        else
            $symbol = '';

        return $symbol;
    }

    public function queue_email($foreign_id_a = null, $foreign_id_b = null, $priority = 10, $is_inmediate = true, $date_queued = null, $is_html = false, $from, $from_name = "", $to, $replyto = "", $replyto_name = "", $subject, $content, $content_nonhtml = "", $list_unsubscribe_url = "") {
        $success = false;
        $this->load->library('emailqueue/config/config');
        $this->load->library('emailqueue/lib/database/database');
        $this->load->library('emailqueue/lib/database/dbsource_mysql_inc');
        $this->load->library('emailqueue/scripts/emailqueue_inject_class');
        $params = array('db_host' => '127.0.0.1', 'db_user' => 'emailqueue', 'db_password' => '*BF7D66E2F803EA9AD3BB2BCCD93E84A26D4E2839', 'db_name' => 'emailqueue', 'avoidpersistence' => false, 'emailqueue_timezone' => false);
        $emailqueue_inject = new emailqueue_inject_class();
        $emailqueue_inject->emailqueue_inject_construct($params);
        $result = $emailqueue_inject->inject
                (
                $foreign_id_a, // foreign_id_a
                $foreign_id_b, // foreign_id_b
                $priority, // priority
                $is_inmediate, // is_inmediate
                $date_queued, // date_queued
                $is_html, // is_html
                $from, // from
                $from_name, // from_name
                $to, // to
                $replyto, // replyto
                $replyto_name, // replyto_name
                $subject, // subject
                $content, // content
                $content_nonhtml, // content_non_html
                $list_unsubscribe_url // list_unsubscribe_url
        );
        if ($result) {
            $success = true;
        } else {
            $success = false;
        }
        $emailqueue_inject->destroy();
        return $success;
    }

}
