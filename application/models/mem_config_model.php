<?php

error_reporting(0);

class Mem_config_model extends CI_Model {

    public function __construct() {
        // Open the correct DB connection
        $this->config = $this->load->database('ppv', TRUE);
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
                $this->config->update('mem_email', $data);
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

                $this->config->insert('mem_email', $data);
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
                $this->config->update('mem_email', $data);
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

                $this->config->insert('mem_email', $data);
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
                ->from('mem_email')
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

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function list_ac_type($pid, $ks, $start, $length, $draw) {
        $output = array(
            "recordsTotal" => intval($total),
            "recordsFiltered" => intval($filteredTotal),
            "data" => array()
        );

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $result = $this->smportal->mem_list_ac_type($valid['pid'], $start, $length, $draw);
        }

        return $result;
    }

    public function setup_player($sm_ak, $entry_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $t = array();
        $owner_attr = null;

        $ac_id = $this->get_ac($pid, $entry_id);
        $ac_type = $this->smportal->get_ac_type($pid, $ac_id['ac_id']);
        $owner_attr = $this->getOwnerAttr($pid);
        $entry_details = $this->get_entry_details($pid, $entry_id);
        $entry_title = stripslashes($entry_details['name']);
        if (strlen($entry_title) > 35) {
            $entry_title = substr($entry_title, 0, 35) . "...";
        }
        $t['media_type'] = $ac_id['media_type'];
        $t['ac_type'] = $ac_type;
        $t['title'] = $entry_title;
        $t['attrs'] = json_encode($owner_attr);

        return $t;
    }

    public function get_entry_details($pid, $entryId) {
        return $this->smportal->get_entry_details($pid, $entryId);
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

    public function get_ac($pid, $entry_id) {
        $this->config->select('*')
                ->from('mem_entry')
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
                    ->from('mem_entry')
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

    public function get_email($pid, $ks) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_email($valid['pid'])) {
                $this->config->select('*')
                        ->from('mem_email')
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
                }

                $this->config->select('*')
                        ->from('mem_email')
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
                }
            } else {
                $this->config->select('*')
                        ->from('mem_email')
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
                    $row['custom_template'] = 'custom';
                    $row['custom_reg_default'] = '0';
                    $row['custom_reg_subject'] = stripslashes($email['register_email_subject']);
                    $row['custom_reg_body'] = stripslashes($email['register_email_body']);
                    $row['custom_pswd_default'] = '0';
                    $row['custom_pswd_subject'] = stripslashes($email['pswd_email_subject']);
                    $row['custom_pswd_body'] = stripslashes($email['pswd_email_body']);
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
            $row['custom_template'] = '';
            $row['custom_reg_default'] = '';
            $row['custom_reg_subject'] = '';
            $row['custom_reg_body'] = '';
            $row['custom_pswd_default'] = '';
            $row['custom_pswd_subject'] = '';
            $row['custom_pswd_body'] = '';
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
