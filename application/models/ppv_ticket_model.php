<?php

error_reporting(0);

class Ppv_ticket_model extends CI_Model {

    public function __construct() {
        // Open the correct DB connection
        $this->ticket = $this->load->database('ppv', TRUE);
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function get_ticket_list($pid, $ks, $start, $length, $search, $draw, $currency) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('status', 'ticket_name', 'ticket_desc', 'ticket_price', 'ticket_type', 'expires', 'max_views', 'created_at', 'updated_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->ticket->limit($this->ticket->escape_str($length), $this->ticket->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->ticket->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
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
                $this->ticket->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->ticket->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->order_by('created_at', 'desc');

            $query = $this->ticket->get('ticket');
            $ticket_res = $query->result_array();

            /* Data set length after filtering */
            $this->ticket->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->ticket->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->ticket->query('SELECT count(*) AS `Count` FROM ticket WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            $exchangeRate = $this->currency("USD", $currency);

            foreach ($ticket_res as $ticket) {
                $hours = 0;
                $days = 0;
                $months = 0;
                $years = 0;
                $expiry_config = array();
                $max_views = '';
                $expiry = '';

                if ($ticket['expiry_config'] == -1) {
                    array_push($expiry_config, "Unlimited");
                    $expiry = -1;
                } else {
                    $expiry = 1;
                    $json_ticket = json_decode($ticket['expiry_config']);
                    foreach ($json_ticket->expiryConfig as $config) {
                        if ($config->hours != 0) {
                            if ($config->hours == 1) {
                                array_push($expiry_config, $config->hours . ' Hour');
                            } else {
                                array_push($expiry_config, $config->hours . ' Hours');
                            }
                            $hours = $config->hours;
                        }
                        if ($config->days != 0) {
                            if ($config->days == 1) {
                                array_push($expiry_config, $config->days . ' Day');
                            } else {
                                array_push($expiry_config, $config->days . ' Days');
                            }
                            $days = $config->days;
                        }
                        if ($config->months != 0) {
                            if ($config->months == 1) {
                                array_push($expiry_config, $config->months . ' Month');
                            } else {
                                array_push($expiry_config, $config->months . ' Months');
                            }
                            $months = $config->months;
                        }
                        if ($config->years != 0) {
                            if ($config->years == 1) {
                                array_push($expiry_config, $config->years . ' Year');
                            } else {
                                array_push($expiry_config, $config->years . ' Years');
                            }
                            $years = $config->years;
                        }
                    }
                }

                $expiry_period = join(", ", $expiry_config);

                if ($ticket['max_views'] == -1) {
                    $max_views = 'Unlimited';
                } else {
                    $max_views = $ticket['max_views'];
                }

                $status = '';
                $ticket_status = $valid['pid'] . ',' . $ticket['ticket_id'] . ',\'' . htmlspecialchars(str_replace('"', '&quot;', $ticket['ticket_name']), ENT_QUOTES) . '\',' . $ticket['status'];
                if ($ticket['status'] == 1) {
                    $status = '<div class="alert alert-success">Active</div>';
                    $status_text = 'Block';
                } else {
                    $status = '<div class="alert alert-danger">Blocked</div>';
                    $status_text = 'Unblock';
                }

                if ($currency == 'JPY' || $currency == 'HUF') {
                    $price = round($this->final_price($ticket['ticket_price'], $exchangeRate));
                } else {
                    $price = $this->final_price($ticket['ticket_price'], $exchangeRate);
                }

                $ticket_data = $ticket['ticket_id'] . ',\'' . htmlspecialchars(str_replace('"', '&quot;', $ticket['ticket_name']), ENT_QUOTES) . '\',\'' . htmlspecialchars(str_replace('"', '&quot;', $ticket['ticket_desc']), ENT_QUOTES) . '\',' . $price . ',' . $expiry . ',' . $hours . ',' . $days . ',' . $months . ',' . $years . ',' . $ticket['max_views'] . ',\'' . $ticket['billing_period'] . '\'';
                $ticket_delete = $valid['pid'] . ',' . $ticket['ticket_id'] . ',\'' . htmlspecialchars(str_replace('"', '&quot;', $ticket['ticket_name']), ENT_QUOTES) . '\'';

                $currency_symbol = $this->currency_symbol($currency);

                if ($ticket['billing_period'] == 'week') {
                    $ticket_type = 'Weekly Subscription';
                } else if ($ticket['billing_period'] == 'month') {
                    $ticket_type = 'Monthly Subscription';
                } else if ($ticket['billing_period'] == 'year') {
                    $ticket_type = 'Yearly Subscription';
                } else if ($ticket['billing_period'] == -1) {
                    $ticket_type = 'One-off';
                }

                $actions = '<span class="dropdown header">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.editTicket(' . $ticket_data . ');">Ticket</a></li> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhPPV.statusTicket(' . $ticket_status . ');">' . $status_text . '</a></li>
                                            <li role="presentation" style="border-top: solid 1px #f0f0f0;"><a role="menuitem" tabindex="-1" onclick="smhPPV.deleteTicket(' . $ticket_delete . ');">Delete</a></li>
                                        </ul>
                                    </div>
                                </span>';

                $row = array();
                $row[] = $status;
                $row[] = "<div class='data-break'>" . htmlspecialchars(stripslashes($ticket['ticket_name']), ENT_QUOTES) . "</div>";
                $row[] = "<div class='data-break'>" . $currency_symbol . $price . "<sup>" . $currency . "</sup></div>";
                $row[] = "<div class='data-break'>" . $ticket_type . "</div>";
                $row[] = "<div class='data-break'>" . $expiry_period . "</div>";
                $row[] = "<div class='data-break'>" . $max_views . "</div>";
                $row[] = "<div class='data-break'>" . $ticket['created_at'] . "</div>";
                $row[] = "<div class='data-break'>" . $ticket['updated_at'] . "</div>";
                $row[] = $actions;
                $row[] = $ticket['ticket_id'];
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function list_entry_tickets($pid, $ks, $start, $length, $search, $draw, $currency) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('ticket_name', 'ticket_price', 'ticket_type', 'expires', 'max_views');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->ticket->limit($this->ticket->escape_str($length), $this->ticket->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->ticket->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
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
                $this->ticket->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->ticket->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->where('status', 1)
                    ->order_by('created_at', 'desc');

            $query = $this->ticket->get('ticket');
            $tickets_res = $query->result_array();

            /* Data set length after filtering */
            $this->ticket->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->ticket->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->ticket->query('SELECT count(*) AS `Count` FROM ticket WHERE status = 1 AND partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            $exchangeRate = $this->currency("USD", $currency);

            foreach ($tickets_res as $ticket) {
                $expiry_config = array();
                $max_views = '';

                if ($ticket['expiry_config'] == -1) {
                    array_push($expiry_config, "Unlimited");
                } else {
                    $json_ticket = json_decode($ticket['expiry_config']);
                    foreach ($json_ticket->expiryConfig as $config) {
                        if ($config->hours != 0) {
                            if ($config->hours == 1) {
                                array_push($expiry_config, $config->hours . ' Hour');
                            } else {
                                array_push($expiry_config, $config->hours . ' Hours');
                            }
                        }
                        if ($config->days != 0) {
                            if ($config->days == 1) {
                                array_push($expiry_config, $config->days . ' Day');
                            } else {
                                array_push($expiry_config, $config->days . ' Days');
                            }
                        }
                        if ($config->months != 0) {
                            if ($config->months == 1) {
                                array_push($expiry_config, $config->months . ' Month');
                            } else {
                                array_push($expiry_config, $config->months . ' Months');
                            }
                        }
                        if ($config->years != 0) {
                            if ($config->years == 1) {
                                array_push($expiry_config, $config->years . ' Year');
                            } else {
                                array_push($expiry_config, $config->years . ' Years');
                            }
                        }
                    }
                }
                $expiry_period = join(", ", $expiry_config);

                if ($ticket['max_views'] == -1) {
                    $max_views = 'Unlimited';
                } else {
                    $max_views = $ticket['max_views'];
                }

                if ($currency == 'JPY' || $currency == 'HUF') {
                    $price = round($this->final_price($ticket['ticket_price'], $exchangeRate));
                } else {
                    $price = $this->final_price($ticket['ticket_price'], $exchangeRate);
                }

                if ($ticket['billing_period'] == 'week') {
                    $ticket_type = 'Weekly Subscription';
                } else if ($ticket['billing_period'] == 'month') {
                    $ticket_type = 'Monthly Subscription';
                } else if ($ticket['billing_period'] == 'year') {
                    $ticket_type = 'Yearly Subscription';
                } else if ($ticket['billing_period'] == -1) {
                    $ticket_type = 'One-off';
                }

                $row = array();
                $row[] = "<input type='checkbox' class='ppv-ticket' name='ppv_ticket' style='width=33px' id='" . $ticket['ticket_id'] . "' value='" . $ticket['ticket_id'] . "' />";
                $row[] = "<div id='data-name'>" . htmlspecialchars(stripslashes($ticket['ticket_name']), ENT_QUOTES) . "</div>";
                $row[] = "<div id='data-name'>" . $price . "<sup>" . $currency . "</sup></div>";
                $row[] = "<div id='data-name'>" . $ticket_type . "</div>";
                $row[] = "<div id='data-name'>" . $expiry_period . "</div>";
                $row[] = "<div id='data-name'>" . $max_views . "</div>";
                $row[] = $ticket['ticket_id'];
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function get_ticket_name($pid, $ks, $ids) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $ticket_names = array();
            $ticket_ids = explode("_", $ids);

            foreach ($ticket_ids as $ticket_id) {
                $this->ticket->select('ticket_name')
                        ->from('ticket')
                        ->where('partner_id', $valid['pid'])
                        ->where('ticket_id', $ticket_id);

                $query = $this->ticket->get();
                $ticket_res = $query->result_array();

                foreach ($ticket_res as $ticket) {
                    $ticket_names[$ticket_id] = $ticket['ticket_name'];
                }
            }
            $success = array('success' => true, 'data' => $ticket_names);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_tickets($sm_ak, $kentry, $type, $protocol, $logged_in, $has_start) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;
        if ($this->check_gateways($pid)) {
            $ticket_details = $this->get_ticket_details($pid, $kentry, $type, $protocol, $logged_in, $has_start);
            $success = array('success' => true, 'content' => $ticket_details);
        } else {
            $ticket_details = '<div style="margin-left: auto; margin-right: auto; width: auto; text-align: center; font-weight: bold; color: rgb(220, 31, 33); height: 80px; padding-top: 43px;">This title is currently not available for purchase. Please try again later.</div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                </div>';
            $success = array('success' => false, 'content' => $ticket_details);
        }
        return $success;
    }

    public function get_ticket_details($pid, $kentry, $type, $protocol, $logged_in, $has_start) {
        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $currency = $this->get_pp_currency($pid);
        } else if ($active_gw == 2) {
            $currency = $this->get_authnet_currency($pid);
        }
        $currency_symbol = $this->currency_symbol($currency);
        $exchangeRate = $this->currency("USD", $currency);
        $cat = false;

        if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
            $cat = true;
        }
        if ($cat) {
            $entry_details = $this->smportal->get_cat_details($pid, $kentry);
        } else {
            $entry_details = $this->get_entry_details($pid, $kentry);
        }

        ($entry_details['desc'] == null || $entry_details['desc'] == '<br>') ? $entry_desc = '' : $entry_desc = $entry_details['desc'];

        $entry_id = '';
        $ticket_bool = false;
        $tickets = array();
        $ticket_main_body = '';
        $sub_tickets = '';
        $buy_tickets = '';
        $rent_tickets = '';

        $this->ticket->select('*')
                ->from('entry')
                ->where('partner_id', $pid)
                ->where('status', 1)
                ->where('kentry_id', $kentry);

        $query = $this->ticket->get();
        if ($query->num_rows() > 0) {
            $ticket_ids = $query->result_array();
            foreach ($ticket_ids as $ticket) {
                $tickets = explode(",", $ticket['ticket_ids']);
                $entry_id = $ticket['entry_id'];
            }

            if (count($tickets) == 0) {
                $details = '<div style="height: 55px; margin-top: 30px; width: 441px; margin-left: auto; margin-right: auto;">This title is currently not available for purchase. Please try again later.</div>';
                $success = array('success' => false, 'content' => $details);
            } else {
                $ticket_rent = array();
                $ticket_sub = array();
                $ticket_buy = array();
                $loggin_register = '';
                foreach ($tickets as $ticket) {
                    $this->ticket->select('*')
                            ->from('ticket')
                            ->where('partner_id', $pid)
                            ->where('status', 1)
                            ->where('ticket_id', $ticket);

                    $query = $this->ticket->get();
                    $ticket = $query->result_array();

                    foreach ($ticket as $t) {
                        $ticket_bool = true;
                        if ($currency == 'JPY' || $currency == 'HUF') {
                            $price = round($this->final_price($t['ticket_price'], $exchangeRate));
                        } else {
                            $price = $this->final_price($t['ticket_price'], $exchangeRate);
                        }

                        $expires_in = '';
                        if ($t['expiry_config'] == -1) {
                            $expires_in = -1;
                        } else {
                            $expiry_config = json_decode($t['expiry_config']);
                            foreach ($expiry_config->expiryConfig as $config) {
                                if ($config->years > 0) {
                                    if ($config->years == 1) {
                                        $expires_in .= ' ' . $config->years . ' year';
                                    } else {
                                        $expires_in .= ' ' . $config->years . ' years';
                                    }
                                }

                                if ($config->months > 0) {
                                    if ($config->months == 1) {
                                        $expires_in .= ' ' . $config->months . ' month';
                                    } else {
                                        $expires_in .= ' ' . $config->months . ' months';
                                    }
                                }

                                if ($config->days > 0) {
                                    if ($config->days == 1) {
                                        $expires_in .= ' ' . $config->days . ' day';
                                    } else {
                                        $expires_in .= ' ' . $config->days . ' days';
                                    }
                                }

                                if ($config->hours > 0) {
                                    if ($config->hours == 1) {
                                        $expires_in .= ' ' . $config->hours . ' hour';
                                    } else {
                                        $expires_in .= ' ' . $config->hours . ' hours';
                                    }
                                }
                            }
                        }

                        if ($t['billing_period'] == -1) {
                            if ($expires_in == -1 && $t['max_views'] == -1) {
                                array_push($ticket_buy, array('id' => $t['ticket_id'], 'desc' => $t['ticket_desc'], 'entry_id' => $entry_id, 'type' => $t['ticket_type'], 'price' => $price, 'max_views' => (int) $t['max_views'], 'expires' => $expires_in, 'bill_per' => -1));
                            } else {
                                array_push($ticket_rent, array('id' => $t['ticket_id'], 'desc' => $t['ticket_desc'], 'entry_id' => $entry_id, 'type' => $t['ticket_type'], 'price' => $price, 'max_views' => (int) $t['max_views'], 'expires' => $expires_in, 'bill_per' => -1));
                            }
                        } else {
                            if ($t['billing_period'] == 'week') {
                                $bill_period = 'w';
                            } else if ($t['billing_period'] == 'month') {
                                $bill_period = 'm';
                            } else if ($t['billing_period'] == 'year') {
                                $bill_period = 'y';
                            }
                            array_push($ticket_sub, array('id' => $t['ticket_id'], 'desc' => $t['ticket_desc'], 'entry_id' => $entry_id, 'type' => $t['ticket_type'], 'price' => $price, 'expires' => 'per ' . $t['billing_period'], 'bill_per' => $bill_period));
                        }

                        $tickets_header = '';
                        if (count($ticket_rent) || count($ticket_buy)) {
                            $tickets_header = '<div id="rent_wrapper"><h2>Please select a price option:</h2>';
                        }

                        if (count($ticket_rent)) {
                            $rent_tickets = '';
                            foreach ($ticket_rent as $ticket) {
                                $max_views = '';
                                $expires = '';
                                $description = '';
                                $bullet = '';
                                if ($ticket['max_views'] !== -1) {
                                    if ($ticket['max_views'] == 1) {
                                        $max_views .= 'View a total of <b>' . $ticket['max_views'] . '</b> time ';
                                    } else {
                                        $max_views .= 'View a total of <b>' . $ticket['max_views'] . '</b> times ';
                                    }
                                }
                                if ($ticket['expires'] !== -1) {
                                    if ($has_start === 'true') {
                                        $expires .= 'Expires in <b>' . $ticket['expires'] . '</b> of the start date';
                                    } else {
                                        $expires .= 'Expires in <b>' . $ticket['expires'] . '</b> of your purchase';
                                    }
                                }

                                if ($logged_in === 'true') {
                                    $loggin_register = 'ppv_obj.loggedin_confirm(' . $ticket['id'] . ',' . $ticket['entry_id'] . ',\'' . $ticket['type'] . '\',\'' . $ticket['bill_per'] . '\');';
                                } else {
                                    $loggin_register = 'ppv_obj.register_loggin(' . $ticket['id'] . ',' . $ticket['entry_id'] . ',\'' . $ticket['type'] . '\',\'' . $ticket['bill_per'] . '\',\'' . $kentry . '\');';
                                }

                                $ticket_sup = '';
                                if ($currency_symbol == null || $currency_symbol == '') {
                                    $ticket_sup = '<sup>' . $currency . '</sup>';
                                }

                                if ($ticket['desc'] != null || $ticket['desc'] != '') {
                                    $description = '<div style="font-size: 12px; margin-bottom: 5px;">' . $ticket['desc'] . '</div>';
                                }
                                if ($max_views != '' && $expires != '') {
                                    $bullet = ' &bull; ';
                                }
                                $rent_tickets .= '<div class="button_wrapper">
                                                    <div class="ticket_button"><button class="btn btn-success ticket-btn" type="button" onclick="' . $loggin_register . '">' . $currency_symbol . $ticket['price'] . $ticket_sup . '</button></div>
                                                    <div class="ticket_info">' . $description . "<i>" . $max_views . $bullet . $expires . '</i></div>
                                                    <div class="clear"></div>
                                                  </div>';
                            }
                            $rent_tickets .= '</div>';
                        }
                        if (count($ticket_sub)) {
                            $period = '';
                            $sub_tickets = '';
                            $sub_tickets .= '<div id="sub_wrapper"><h2>Subscription options:</h2>';
                            foreach ($ticket_sub as $ticket) {
                                $description = '';
                                if ($ticket['bill_per'] == 'w') {
                                    $period = '/week';
                                } else if ($ticket['bill_per'] == 'm') {
                                    $period = '/month';
                                } else if ($ticket['bill_per'] == 'y') {
                                    $period = '/year';
                                }
                                if ($logged_in === 'true') {
                                    $loggin_register = 'ppv_obj.loggedin_confirm(' . $ticket['id'] . ',' . $ticket['entry_id'] . ',\'' . $ticket['type'] . '\',\'' . $ticket['bill_per'] . '\');';
                                } else {
                                    $loggin_register = 'ppv_obj.register_loggin(' . $ticket['id'] . ',' . $ticket['entry_id'] . ',\'' . $ticket['type'] . '\',\'' . $ticket['bill_per'] . '\',\'' . $kentry . '\');';
                                }
                                $ticket_sup = '';
                                if ($currency_symbol == null || $currency_symbol == '') {
                                    $ticket_sup = '<sup>' . $currency . '</sup>';
                                }

                                if ($ticket['desc'] != null || $ticket['desc'] != '') {
                                    $description = '<div style="font-size: 12px; margin-bottom: 5px;">' . $ticket['desc'] . '</div>';
                                }
                                $sub_tickets .= '<div class="button_wrapper">
                                                    <div class="ticket_button"><button class="btn btn-success ticket-btn" type="button" onclick="' . $loggin_register . '">' . $currency_symbol . $ticket['price'] . $ticket_sup . $period . '</button></div>
                                                    <div class="ticket_info">' . $description . '</div>
                                                    <div class="clear"></div>
                                                  </div>';
                            }
                            $sub_tickets .= '</div>';
                        }
                        if (count($ticket_buy)) {
                            $buy_tickets = '';
                            foreach ($ticket_buy as $ticket) {
                                $description = '';
                                if ($logged_in === 'true') {
                                    $loggin_register = 'ppv_obj.loggedin_confirm(' . $ticket['id'] . ',' . $ticket['entry_id'] . ',\'' . $ticket['type'] . '\',\'' . $ticket['bill_per'] . '\');';
                                } else {
                                    $loggin_register = 'ppv_obj.register_loggin(' . $ticket['id'] . ',' . $ticket['entry_id'] . ',\'' . $ticket['type'] . '\',\'' . $ticket['bill_per'] . '\',\'' . $kentry . '\');';
                                }
                                $ticket_sup = '';
                                if ($currency_symbol == null || $currency_symbol == '') {
                                    $ticket_sup = '<sup>' . $currency . '</sup>';
                                }

                                if ($ticket['desc'] != null || $ticket['desc'] != '') {
                                    $description = '<div style="font-size: 12px; margin-bottom: 5px;">' . $ticket['desc'] . '</div>';
                                }
                                $buy_tickets .= '<div class="button_wrapper">
                                                    <div class="ticket_button"><button class="btn btn-success ticket-btn" type="button" onclick="' . $loggin_register . '">' . $currency_symbol . $ticket['price'] . $ticket_sup . '</button></div>
                                                    <div class="ticket_info">' . $description . '</div>
                                                    <div class="clear"></div>
                                                  </div>';
                            }
                            $buy_tickets .= '</div>';
                        }
                        $ticket_main_body = $tickets_header . $rent_tickets . $buy_tickets . $sub_tickets;
                    }
                }
            }
        }

        $success = array('success' => true, 'desc' => $entry_desc, 'tickets' => $ticket_main_body);

        if (!$ticket_bool) {
            $details = '<div style="height: 55px; margin-top: 30px; margin-left: auto; margin-right: auto; width: auto; text-align: center; font-weight: bold; color: rgb(220, 31, 33);">This title is currently not available for purchase. Please try again later.</div>';
            $success = array('success' => true, 'desc' => $entry_desc, 'tickets' => $details);
        }

        return $success;
    }

    public function get_entry_details($pid, $entryId) {
        return $this->smportal->get_entry_details($pid, $entryId);
    }

    public function get_cat_details($pid, $cat_id) {
        return $this->smportal->get_cat_details($pid, $cat_id);
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function get_ticket_price($sm_ak, $ticket_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $this->ticket->select('ticket_price')
                ->from('ticket')
                ->where('partner_id', $pid)
                ->where('ticket_id', $ticket_id);

        $query = $this->ticket->get();
        $price = $query->result_array();
        $ticket_price = 0;
        $active_gw = $this->getActiveGateway($pid);
        if ($active_gw == 1) {
            $currency = $this->get_pp_currency($pid);
        } else if ($active_gw == 2) {
            $currency = $this->get_authnet_currency($pid);
        }
        $exchangeRate = $this->currency("USD", $currency);
        foreach ($price as $p) {
            $ticket_price = $p['ticket_price'];
        }
        if ($currency == 'JPY' || $currency == 'HUF') {
            $price = round($this->final_price($ticket_price, $exchangeRate));
        } else {
            $price = $this->final_price($ticket_price, $exchangeRate);
        }

        return $price;
    }

    public function get_pp_currency($pid) {
        $this->ticket->select('currency')
                ->from('paypal')
                ->where('partner_id', $pid);

        $query = $this->ticket->get();
        $currency = $query->result_array();
        foreach ($currency as $c) {
            $currency = $c['currency'];
        }
        return $currency;
    }

    public function get_authnet_currency($pid) {
        $this->ticket->select('currency')
                ->from('authnet')
                ->where('partner_id', $pid);

        $query = $this->ticket->get();
        $currency = $query->result_array();
        foreach ($currency as $c) {
            $currency = $c['currency'];
        }
        return $currency;
    }

    public function getActiveGateway($pid) {
        $this->ticket->select('*')
                ->from('payment_gateway')
                ->where('partner_id', $pid);

        $query = $this->ticket->get();
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

    public function check_gateways($pid) {
        $success = false;
        $this->ticket->select('*')
                ->from('payment_gateway')
                ->where('partner_id', $pid);
        $query = $this->ticket->get();
        $gateway = $query->result_array();

        foreach ($gateway as $g) {
            if ($g['gateway_status'] == 1) {
                $success = true;
            }
        }
        return $success;
    }

    public function add_ticket($pid, $ks, $ticket_name, $ticket_desc, $ticket_price, $expires, $expiry_config, $max_views, $status, $tz, $currency, $ticket_type) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            date_default_timezone_set($tz);
            $exchangeRate = $this->currency("USD", $currency);

            if ($currency == 'JPY' || $currency == 'HUF') {
                $price = round($this->final_price($ticket_price, $exchangeRate));
            } else {
                $price = $this->final_price($ticket_price, $exchangeRate);
            }

            $t_type = '';
            $bill_period = '';

            if ($ticket_type == 1) {
                $t_type = 'reg';
                $bill_period = -1;
            } else if ($ticket_type == 2) {
                $t_type = 'sub';
                $bill_period = 'week';
            } else if ($ticket_type == 3) {
                $t_type = 'sub';
                $bill_period = 'month';
            } else if ($ticket_type == 4) {
                $t_type = 'sub';
                $bill_period = 'year';
            }

            $data = array(
                'partner_id' => $this->ticket->escape_str(trim($valid['pid'])),
                'ticket_name' => $this->ticket->escape_str(trim($ticket_name)),
                'ticket_desc' => $this->ticket->escape_str(trim($ticket_desc)),
                'ticket_price' => $this->ticket->escape_str(trim($price)),
                'ticket_type' => $t_type,
                'billing_period' => $bill_period,
                'expires' => $this->ticket->escape_str(trim($expires)),
                'expiry_config' => trim($expiry_config),
                'max_views' => $this->ticket->escape_str(trim($max_views)),
                'created_at' => date("Y-m-d h:i:s"),
                'status' => $this->ticket->escape_str(trim($status))
            );

            $this->ticket->insert('ticket', $data);
            if ($this->ticket->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_ticket($pid, $ks, $ticket_id, $ticket_name, $ticket_desc, $ticket_price, $expires, $expiry_config, $max_views, $tz, $ticket_type) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            date_default_timezone_set($tz);

            $t_type = '';
            $bill_period = '';

            if ($ticket_type == 1) {
                $t_type = 'reg';
                $bill_period = -1;
            } else if ($ticket_type == 2) {
                $t_type = 'sub';
                $bill_period = 'week';
            } else if ($ticket_type == 3) {
                $t_type = 'sub';
                $bill_period = 'month';
            } else if ($ticket_type == 4) {
                $t_type = 'sub';
                $bill_period = 'year';
            }

            $data = array(
                'partner_id' => $this->ticket->escape_str(trim($valid['pid'])),
                'ticket_name' => $this->ticket->escape_str(trim($ticket_name)),
                'ticket_desc' => $this->ticket->escape_str(trim($ticket_desc)),
                'ticket_price' => $this->ticket->escape_str(trim($ticket_price)),
                'ticket_type' => $t_type,
                'billing_period' => $bill_period,
                'expires' => $this->ticket->escape_str(trim($expires)),
                'expiry_config' => trim($expiry_config),
                'max_views' => $this->ticket->escape_str(trim($max_views)),
                'updated_at' => date("Y-m-d h:i:s")
            );

            $this->ticket->where('ticket_id = "' . $ticket_id . '" AND partner_id = "' . $valid['pid'] . '"');
            $this->ticket->update('ticket', $data);
            if ($this->ticket->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function delete_ticket($pid, $ks, $ticket_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_ticket($valid['pid'], $ticket_id)) {
                $this->ticket->where('ticket_id = "' . $ticket_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->ticket->delete('ticket');
                $this->ticket->limit(1);
                if ($this->ticket->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'ticket does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_status($pid, $ks, $ticket_id, $status) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_ticket($valid['pid'], $ticket_id)) {
                $data = array(
                    'status' => $status,
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->ticket->where('ticket_id = "' . $ticket_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->ticket->update('ticket', $data);
                $this->ticket->limit(1);
                if ($this->ticket->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'ticket does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function check_ticket($pid, $ticket_id) {
        $success = false;
        $this->ticket->select('*')
                ->from('ticket')
                ->where('ticket_id = "' . $this->ticket->escape_str($ticket_id) . '" AND partner_id = "' . $pid . '"');
        $query = $this->ticket->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function currency($from_Currency, $to_Currency) {
        $currency_rate = '';
        $this->ticket->select('rate')
                ->from('exchange_rates')
                ->where('name = "' . $from_Currency . $to_Currency . '"');
        $query = $this->ticket->get();
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
