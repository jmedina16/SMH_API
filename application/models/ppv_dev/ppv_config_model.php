<?php

error_reporting(0);

class Ppv_config_model extends CI_Model {

    public function __construct() {
        // Open the correct DB connection
        $this->config = $this->load->database('ppv_dev', TRUE);
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function addRegEmailConfig($pid, $ks, $reg_subject, $reg_body, $reg_default) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_email($valid['pid'])) {
                if ($reg_default == 0) {
                    $data = array(
                        'register_default' => $reg_default
                    );
                } else {
                    $data = array(
                        'register_email_subject' => $this->config->escape_str(trim($reg_subject)),
                        'register_email_body' => $this->config->escape_str(trim($reg_body)),
                        'register_default' => $reg_default
                    );
                }

                $this->config->where('partner_id', $valid['pid']);
                $this->config->update('email', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'register_email_subject' => $this->config->escape_str(trim($reg_subject)),
                    'register_email_body' => $this->config->escape_str(trim($reg_body)),
                    'register_default' => $reg_default,
                    'partner_id' => $valid['pid']
                );

                $this->config->insert('email', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function addTyEmailConfig($pid, $ks, $ty_subject, $ty_body, $ty_default) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_email($valid['pid'])) {
                if ($ty_default == 0) {
                    $data = array(
                        'thankyou_email_default' => $ty_default
                    );
                } else {
                    $data = array(
                        'thankyou_email_subject' => $this->config->escape_str(trim($ty_subject)),
                        'thankyou_email_body' => $this->config->escape_str(trim($ty_body)),
                        'thankyou_email_default' => $ty_default
                    );
                }

                $this->config->where('partner_id', $valid['pid']);
                $this->config->update('email', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'thankyou_email_subject' => $this->config->escape_str(trim($ty_subject)),
                    'thankyou_email_body' => $this->config->escape_str(trim($ty_body)),
                    'thankyou_email_default' => $ty_default,
                    'partner_id' => $valid['pid']
                );

                $this->config->insert('email', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function addPswdEmailConfig($pid, $ks, $pswd_subject, $pswd_body, $pswd_default) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_email($valid['pid'])) {
                if ($pswd_default == 0) {
                    $data = array(
                        'pswd_default' => $pswd_default
                    );
                } else {
                    $data = array(
                        'pswd_email_subject' => $this->config->escape_str(trim($pswd_subject)),
                        'pswd_email_body' => $this->config->escape_str(trim($pswd_body)),
                        'pswd_default' => $pswd_default
                    );
                }

                $this->config->where('partner_id', $valid['pid']);
                $this->config->update('email', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'pswd_email_subject' => $this->config->escape_str(trim($pswd_subject)),
                    'pswd_email_body' => $this->config->escape_str(trim($pswd_body)),
                    'pswd_default' => $pswd_default,
                    'partner_id' => $valid['pid']
                );

                $this->config->insert('email', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function check_email($pid) {
        $success = false;
        $this->config->select('*')
                ->from('email')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_email_config($pid) {
        $success = false;
        $this->config->select('*')
                ->from('email_config')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function addGateway($pid, $ks, $gate_name, $gate_status) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_gateway($valid['pid'], $gate_name)) {
                $data = array(
                    'gateway_status' => $gate_status
                );

                $this->config->where('partner_id', $valid['pid']);
                $this->config->where('gateway_name', $gate_name);
                $this->config->update('payment_gateway', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'partner_id' => $valid['pid'],
                    'gateway_name' => $gate_name,
                    'gateway_status' => $gate_status
                );

                $this->config->insert('payment_gateway', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
            $this->updateGatewayStatus($pid, $gate_name);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function updateGatewayStatus($pid, $gate_name) {
        $success = array('success' => false);
        if ($gate_name == 'paypal') {
            if ($this->check_gateway($pid, 'authnet')) {
                $data = array(
                    'gateway_status' => 0
                );

                $this->config->where('partner_id', $pid);
                $this->config->where('gateway_name', 'authnet');
                $this->config->update('payment_gateway', $data);
                $this->config->limit(1);
                $this->update_setup($pid, 0, 'authnet');
                $success = array('success' => true);
            } else {
                $data = array(
                    'partner_id' => $pid,
                    'gateway_name' => 'authnet',
                    'gateway_status' => 0
                );

                $this->config->insert('payment_gateway', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            if ($this->check_gateway($pid, 'paypal')) {
                $data = array(
                    'gateway_status' => 0
                );

                $this->config->where('partner_id', $pid);
                $this->config->where('gateway_name', 'paypal');
                $this->config->update('payment_gateway', $data);
                $this->config->limit(1);
                $this->update_setup($pid, 0, 'paypal');
                $success = array('success' => true);
            } else {
                $data = array(
                    'partner_id' => $pid,
                    'gateway_name' => 'paypal',
                    'gateway_status' => 0
                );

                $this->config->insert('payment_gateway', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        }
        return $success;
    }

    public function check_init_setup($pid) {
        $success = false;
        $this->config->select('*')
                ->from('owner')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function init_setup($pid) {
        $success = false;
        $data = array(
            'partner_id' => $pid,
            'access_key' => $this->smcipher->encrypt($pid)
        );

        $this->config->insert('owner', $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function getGateways($pid, $ks) {
        $success = false;

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $this->config->select('*')
                    ->from('payment_gateway')
                    ->where('partner_id', $valid['pid']);

            $query = $this->config->get();
            $result1 = $query->result_array();

            $gateways = array();
            $row = array();
            foreach ($result1 as $r1) {
                if ($r1['gateway_name'] == "paypal") {
                    $this->config->select('*')
                            ->from('paypal')
                            ->where('partner_id', $valid['pid']);

                    $query = $this->config->get();
                    $result2 = $query->result_array();
                    $options = array();
                    foreach ($result2 as $r2) {
                        $options = array(
                            'api_user_id' => $this->smcipher->decrypt($r2['api_user_id']),
                            'api_password' => $this->smcipher->decrypt($r2['api_password']),
                            'api_sig' => $this->smcipher->decrypt($r2['api_sig']),
                            'currency' => $r2['currency'],
                            'setup' => $r2['setup']
                        );
                    }
                    array_push($gateways, array('name' => $r1['gateway_name'], 'status' => $r1['gateway_status'], 'options' => $options));
                }
                if ($r1['gateway_name'] == "authnet") {
                    $this->config->select('*')
                            ->from('authnet')
                            ->where('partner_id', $valid['pid']);

                    $query = $this->config->get();
                    $result3 = $query->result_array();
                    $options = array();
                    foreach ($result3 as $r3) {
                        $options = array(
                            'api_login_id' => $this->smcipher->decrypt($r3['api_login_id']),
                            'transaction_key' => $this->smcipher->decrypt($r3['transaction_key']),
                            'currency' => $r3['currency'],
                            'setup' => $r3['setup']
                        );
                    }
                    array_push($gateways, array('name' => $r1['gateway_name'], 'status' => $r1['gateway_status'], 'options' => $options));
                }
            }

            if (!$this->check_init_setup($pid)) {
                $this->init_setup($pid);
            }

            $success = array('success' => true, 'gateways' => $gateways);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_pp_config($sm_ak) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $config = array();
        $this->config->select('*')
                ->from('paypal')
                ->where('partner_id', $pid);

        $query = $this->config->get();
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

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function check_gateway($pid, $gate_name) {
        $success = false;
        $this->config->select('*')
                ->from('payment_gateway')
                ->where('partner_id', $pid)
                ->where('gateway_name', $gate_name);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function add_paypal_config($pid, $ks, $api_user_id, $api_pswd, $api_sig, $currency, $setup) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_paypal($valid['pid'])) {
                $data = array(
                    'api_user_id' => $this->config->escape_str(trim($this->smcipher->encrypt($api_user_id))),
                    'api_password' => $this->config->escape_str(trim($this->smcipher->encrypt($api_pswd))),
                    'api_sig' => $this->config->escape_str(trim($this->smcipher->encrypt($api_sig))),
                    'currency' => $currency,
                    'setup' => $setup
                );

                $this->config->where('partner_id', $valid['pid']);
                $this->config->update('paypal', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'partner_id' => $valid['pid'],
                    'api_user_id' => $this->config->escape_str(trim($this->smcipher->encrypt($api_user_id))),
                    'api_password' => $this->config->escape_str(trim($this->smcipher->encrypt($api_pswd))),
                    'api_sig' => $this->config->escape_str(trim($this->smcipher->encrypt($api_sig))),
                    'currency' => $currency,
                    'setup' => $setup
                );

                $this->config->insert('paypal', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function add_authnet_config($pid, $ks, $api_login_id, $transaction_key, $currency, $setup) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_authnet($valid['pid'])) {
                $data = array(
                    'api_login_id' => $this->config->escape_str(trim($this->smcipher->encrypt($api_login_id))),
                    'transaction_key' => $this->config->escape_str(trim($this->smcipher->encrypt($transaction_key))),
                    'currency' => $currency,
                    'setup' => $setup
                );

                $this->config->where('partner_id', $valid['pid']);
                $this->config->update('authnet', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'partner_id' => $valid['pid'],
                    'api_login_id' => $this->config->escape_str(trim($this->smcipher->encrypt($api_login_id))),
                    'transaction_key' => $this->config->escape_str(trim($this->smcipher->encrypt($transaction_key))),
                    'currency' => $currency,
                    'setup' => $setup
                );

                $this->config->insert('authnet', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_setup($pid, $setup, $gate_name) {
        $success = array('success' => false);

        $data = array(
            'setup' => $setup
        );

        $this->config->where('partner_id', $pid);
        $this->config->update($gate_name, $data);
        $this->config->limit(1);
        if ($this->config->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('notice' => 'no changes were made');
        }

        return $success;
    }

    public function deactivate_gateways($pid, $ks, $setup) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $data = array(
                'gateway_status' => $setup
            );

            $this->config->where('partner_id', $pid);
            $this->config->update('payment_gateway', $data);
            if ($this->config->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('notice' => 'no changes were made');
            }
        }

        return $success;
    }

    public function check_paypal($pid) {
        $success = false;
        $this->config->select('*')
                ->from('paypal')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_authnet($pid) {
        $success = false;
        $this->config->select('*')
                ->from('authnet')
                ->where('partner_id', $pid);
        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function list_ac($pid, $ks, $iDisplayStart, $iDisplayLength, $iSortCol_0, $sSortDir_0, $sEcho) {
        $result = array(
            "orderBy" => '',
            "iTotalRecords" => '',
            "iTotalDisplayRecords" => '',
            "aaData" => array()
        );

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $result = $this->smportal->list_ac($valid['pid'], $iDisplayStart, $iDisplayLength, $iSortCol_0, $sSortDir_0, $sEcho);
        }

        return $result;
    }

    public function list_ac_type($pid, $ks, $start, $length, $draw) {
        $output = array(
            "recordsTotal" => intval($total),
            "recordsFiltered" => intval($filteredTotal),
            "data" => array()
        );

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $result = $this->smportal->list_ac_type($valid['pid'], $start, $length, $draw);
        }

        return $result;
    }

    public function setup_player($sm_ak, $entry_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $t = array();
        $owner_attr = null;

        $ac_id = $this->get_ac($pid, $entry_id);
        $ac_type = $this->smportal->get_ac_type($pid, $ac_id['ac_id']);
        $gw = $this->getActiveGateway($pid);
        $gw_type = 0;
        $owner_attr = $this->getOwnerAttr($pid);
        if ($ac_id['media_type'] == 6) {
            $entry_details = $this->get_cat_details($pid, $entry_id);
            $entry_title = stripslashes($entry_details['name']);
            if (strlen($entry_title) > 35) {
                $entry_title = substr($entry_title, 0, 35) . "...";
            }
        } else if ($ac_id['media_type'] == 3) {
            $entry = $this->smportal->get_thumb($pid, $entry_id);
            $entry_details = $this->get_entry_details($pid, $entry);
            $plist_details = $this->get_entry_details($pid, $entry_id);
            $entry_details['countdown'] = $plist_details['countdown'];
            $entry_details['timezone'] = $plist_details['timezone'];
            $entry_title = stripslashes($plist_details['name']);
            if (strlen($entry_title) > 35) {
                $entry_title = substr($entry_title, 0, 35) . "...";
            }
        } else {
            $entry_details = $this->get_entry_details($pid, $entry_id);
            $entry_title = stripslashes($entry_details['name']);
            if (strlen($entry_title) > 35) {
                $entry_title = substr($entry_title, 0, 35) . "...";
            }
        }

        foreach ($gw as $g) {
            if ($g['name'] == 'paypal') {
                if ($g['status'] == '1') {
                    $gw_type = 1;
                }
            }
            if ($g['name'] == 'authnet') {
                if ($g['status'] == '1') {
                    $gw_type = 2;
                }
            }
        }
        $t['media_type'] = $ac_id['media_type'];
        $t['ac_type'] = $ac_type;
        $t['gw_type'] = $gw_type;
        $t['start_date'] = $entry_details['startDate'];
        $t['end_date'] = $entry_details['endDate'];
        $t['countdown'] = $entry_details['countdown'];
        $t['timezone'] = $entry_details['timezone'];
        $t['title'] = $entry_title;
        $t['attrs'] = json_encode($owner_attr);

        return $t;
    }

    public function get_entry_details($pid, $entryId) {
        return $this->smportal->get_entry_details($pid, $entryId);
    }

    public function get_cat_details($pid, $entryId) {
        return $this->smportal->get_cat_details($pid, $entryId);
    }

    public function getOwnerAttr($pid) {
        $this->config->select('*')
                ->from('owner_attributes')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();

        $attr = array();
        $row = array();
        foreach ($result as $r) {
            array_push($attr, array('name' => $r['attribute'], 'id' => $r['attr_id'], 'required' => $r['required']));
        }

        return $attr;
    }

    public function getActiveGateway($pid) {
        $this->config->select('*')
                ->from('payment_gateway')
                ->where('partner_id', $pid);

        $query = $this->config->get();
        $result = $query->result_array();

        $gateways = array();
        $row = array();
        foreach ($result as $r) {
            array_push($gateways, array('name' => $r['gateway_name'], 'status' => $r['gateway_status']));
        }

        return $gateways;
    }

    public function w_get_thumb($sm_ak, $entry_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        return $this->smportal->get_thumb($pid, $entry_id);
    }

    public function get_thumb($pid, $ks, $entry_id) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            return $this->smportal->get_thumb($valid['pid'], $entry_id);
        }
    }

    public function get_cat_thumb($pid, $ks, $cat_id) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            return $this->smportal->get_cat_thumb($pid, $cat_id);
        }
    }

    public function w_get_cat_thumb($sm_ak, $cat_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        return $this->smportal->get_cat_thumb($pid, $cat_id);
    }

    public function get_ac($pid, $entry_id) {
        $this->config->select('*')
                ->from('entry')
                ->where('partner_id', $pid)
                ->where('kentry_id', $entry_id);

        $query = $this->config->get();
        if ($query->num_rows() > 0) {
            $res = $query->result_array();

            $result = array();
            foreach ($res as $r) {
                $result['ac_id'] = $r['ac_id'];
                $result['media_type'] = $r['media_type'];
            }
        } else {
            $cat_id = $this->smportal->get_cat_id($pid, $entry_id);
            $this->config->select('*')
                    ->from('entry')
                    ->where('partner_id', $pid)
                    ->where('kentry_id', $cat_id);

            $query = $this->config->get();
            if ($query->num_rows() > 0) {
                $res = $query->result_array();

                $result = array();
                foreach ($res as $r) {
                    $result['ac_id'] = $r['ac_id'];
                    $result['media_type'] = $r['media_type'];
                }
            } else {
                $result = array();
                $result['ac_id'] = 0;
                $result['media_type'] = 0;
            }
        }

        return $result;
    }

    public function add_ac($pid, $ks, $name, $desc, $preview, $preview_time) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->smportal->add_ac($valid['pid'], $name, $desc, $preview, $preview_time)) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function delete_ac($pid, $ks, $id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->smportal->delete_ac($valid['pid'], $id)) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_ac($pid, $ks, $id, $name, $desc, $preview, $preview_time) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->smportal->update_ac($valid['pid'], $id, $name, $desc, $preview, $preview_time)) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_email($pid, $ks) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_email($valid['pid'])) {
                $this->config->select('*')
                        ->from('email')
                        ->where('partner_id', $valid['pid']);

                $query = $this->config->get();
                $res = $query->result_array();

                $row = array();
                foreach ($res as $r) {
                    $row['custom_template'] = 'custom';
                    $row['custom_reg_default'] = $r['register_default'];
                    $row['custom_reg_subject'] = stripslashes($r['register_email_subject']);
                    $row['custom_reg_body'] = stripslashes($r['register_email_body']);
                    $row['custom_pswd_default'] = $r['pswd_default'];
                    $row['custom_pswd_subject'] = stripslashes($r['pswd_email_subject']);
                    $row['custom_pswd_body'] = stripslashes($r['pswd_email_body']);
                    $row['custom_thankyou_default'] = $r['thankyou_email_default'];
                    $row['custom_thankyou_subject'] = stripslashes($r['thankyou_email_subject']);
                    $row['custom_thankyou_body'] = stripslashes($r['thankyou_email_body']);
                }

                $this->config->select('*')
                        ->from('email')
                        ->where('partner_id', '1');

                $query = $this->config->get();
                $email_res = $query->result_array();

                foreach ($email_res as $email) {
                    $row['default_template'] = 'default';
                    $row['default_reg_default'] = $email['register_default'];
                    $row['default_reg_subject'] = stripslashes($email['register_email_subject']);
                    $row['default_reg_body'] = stripslashes($email['register_email_body']);
                    $row['default_pswd_default'] = $email['pswd_default'];
                    $row['default_pswd_subject'] = stripslashes($email['pswd_email_subject']);
                    $row['default_pswd_body'] = stripslashes($email['pswd_email_body']);
                    $row['default_thankyou_default'] = $email['thankyou_email_default'];
                    $row['default_thankyou_subject'] = stripslashes($email['thankyou_email_subject']);
                    $row['default_thankyou_body'] = stripslashes($email['thankyou_email_body']);
                }
            } else {
                $this->config->select('*')
                        ->from('email')
                        ->where('partner_id', '1');

                $query = $this->config->get();
                $email_res = $query->result_array();

                $row = array();
                foreach ($email_res as $email) {
                    $row['default_template'] = 'default';
                    $row['default_reg_default'] = $email['register_default'];
                    $row['default_reg_subject'] = stripslashes($email['register_email_subject']);
                    $row['default_reg_body'] = stripslashes($email['register_email_body']);
                    $row['default_pswd_default'] = $email['pswd_default'];
                    $row['default_pswd_subject'] = stripslashes($email['pswd_email_subject']);
                    $row['default_pswd_body'] = stripslashes($email['pswd_email_body']);
                    $row['default_thankyou_default'] = $email['thankyou_email_default'];
                    $row['default_thankyou_subject'] = stripslashes($email['thankyou_email_subject']);
                    $row['default_thankyou_body'] = stripslashes($email['thankyou_email_body']);
                    $row['custom_template'] = 'custom';
                    $row['custom_reg_default'] = '0';
                    $row['custom_reg_subject'] = stripslashes($email['register_email_subject']);
                    $row['custom_reg_body'] = stripslashes($email['register_email_body']);
                    $row['custom_pswd_default'] = '0';
                    $row['custom_pswd_subject'] = stripslashes($email['pswd_email_subject']);
                    $row['custom_pswd_body'] = stripslashes($email['pswd_email_body']);
                    $row['custom_thankyou_default'] = '0';
                    $row['custom_thankyou_subject'] = stripslashes($email['thankyou_email_subject']);
                    $row['custom_thankyou_body'] = stripslashes($email['thankyou_email_body']);
                }
            }
        } else {
            $row['default_template'] = '';
            $row['default_reg_default'] = '';
            $row['default_reg_subject'] = '';
            $row['default_reg_body'] = '';
            $row['default_pswd_default'] = '';
            $row['default_pswd_subject'] = '';
            $row['default_pswd_body'] = '';
            $row['default_thankyou_default'] = '';
            $row['default_thankyou_subject'] = '';
            $row['default_thankyou_body'] = '';
            $row['custom_template'] = '';
            $row['custom_reg_default'] = '';
            $row['custom_reg_subject'] = '';
            $row['custom_reg_body'] = '';
            $row['custom_pswd_default'] = '';
            $row['custom_pswd_subject'] = '';
            $row['custom_pswd_body'] = '';
            $row['custom_thankyou_default'] = '';
            $row['custom_thankyou_subject'] = '';
            $row['custom_thankyou_body'] = '';
        }

        return $row;
    }

    public function get_email_config($pid, $ks) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_email_config($valid['pid'])) {
                $this->config->select('*')
                        ->from('email_config')
                        ->where('partner_id', $valid['pid']);

                $query = $this->config->get();
                $res = $query->result_array();

                $row = array();
                foreach ($res as $r) {
                    $row['from_name'] = stripslashes($r['from_name']);
                    $row['from_email'] = $r['from_email'];
                    $row['use_default'] = $r['use_default'];
                    $row['server'] = $r['server'];
                    $row['port'] = $r['port'];
                    $row['auth'] = $r['auth'];
                    $row['pass'] = $this->smcipher->decrypt($r['pass']);
                    $row['secure'] = $r['secure'];
                }
            } else {
                $user = $this->smportal->get_User($pid);
                $row['from_name'] = $user['name'];
                $row['from_email'] = $user['email'];
                $row['use_default'] = 1;
                $row['server'] = '';
                $row['port'] = '';
                $row['auth'] = 0;
                $row['pass'] = '';
                $row['secure'] = '';
            }
        }
        return $row;
    }

    public function update_email_config($pid, $ks, $from_name, $from_email, $use_default, $smtp_server, $smtp_port, $smtp_auth, $smtp_pass, $smtp_secure) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            if ($this->check_email_config($valid['pid'])) {
                $data = array(
                    'from_name' => $this->config->escape_str(trim($from_name)),
                    'from_email' => $this->config->escape_str(trim($from_email)),
                    'use_default' => $use_default,
                    'server' => $this->config->escape_str(trim($smtp_server)),
                    'port' => $smtp_port,
                    'auth' => $smtp_auth,
                    'pass' => $this->config->escape_str(trim($this->smcipher->encrypt($smtp_pass))),
                    'secure' => $smtp_secure,
                );

                $this->config->where('partner_id', $valid['pid']);
                $this->config->update('email_config', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'from_name' => $this->config->escape_str(trim($from_name)),
                    'from_email' => $this->config->escape_str(trim($from_email)),
                    'use_default' => $use_default,
                    'server' => $this->config->escape_str(trim($smtp_server)),
                    'port' => $smtp_port,
                    'auth' => $smtp_auth,
                    'pass' => $this->config->escape_str(trim($this->smcipher->encrypt($smtp_pass))),
                    'secure' => $smtp_secure,
                    'partner_id' => $valid['pid']
                );

                $this->config->insert('email_config', $data);
                $this->config->limit(1);
                if ($this->config->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function get_player_details($pid, $uiconf) {
        return $this->smportal->get_player_details($pid, $uiconf);
    }

}
