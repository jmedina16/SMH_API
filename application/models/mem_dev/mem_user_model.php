<?php

//error_reporting(0);

ini_set('display_errors', 'On');
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

class Mem_user_model extends CI_Model {

    public function __construct() {
        // Open the correct DB connection
        $this->accounts = $this->load->database('ppv_dev', TRUE);
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function get_user_list($pid, $ks, $start, $length, $search, $draw) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('status', 'user_id', 'first_name', 'last_name', 'email', 'created_at', 'updated_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->accounts->limit($this->accounts->escape_str($length), $this->accounts->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->accounts->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
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
                $this->accounts->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->accounts->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->order_by('user_id', 'desc');

            $query = $this->accounts->get('user');
            $users_res = $query->result_array();

            /* Data set length after filtering */
            $this->accounts->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->accounts->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->accounts->query('SELECT count(*) AS `Count` FROM user WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            foreach ($users_res as $user) {
                $status = '';
                $destroy_session = '';
                $isLoggedIn = '';
                $status_data = $valid['pid'] . ',\'' . $user['email'] . '\',\'' . $user['first_name'] . ' ' . $user['last_name'] . '\',' . $user['status'] . ',' . $user['user_id'];
                if ($user['status'] == 1) {
                    $status = '<div class="alert alert-success">Active</div>';
                    $status_text = 'Block';
                } else if ($user['status'] == 2) {
                    $status = '<div class="alert alert-danger">Blocked</div>';
                    $status_text = 'Unblock';
                } else if ($user['status'] == 3) {
                    $status = '<div class="alert alert-warning">Not Activated</div>';
                    $status_text = 'Activate';
                }

                $delete_data = $valid["pid"] . ",\"" . $user["user_id"] . "\",\"" . $user["first_name"] . " " . $user["last_name"] . "\"";
                $reset_data = $valid["pid"] . ",\"" . $user["email"] . "\",\"" . $user["first_name"] . " " . $user["last_name"] . "\"," . $user["status"];
                $user_data = $valid["pid"] . "," . $user["user_id"] . ",\"" . $user["first_name"] . "\",\"" . $user["last_name"] . "\",\"" . $user["email"] . "\"";
                $session_data = $valid["pid"] . "," . $user["user_id"] . ",\"" . $user["first_name"] . " " . $user["last_name"] . "\"";

                if ($user['logged_in']) {
                    $isLoggedIn = "<i class='fa fa-check-square-o' style='color: #676a6c; width: 100%; text-align: center;'></i>";
                    $destroy_session = "<li role='presentation'><a role='menuitem' tabindex='-1' onclick='smhMEM.destroySession(" . $session_data . ");'>Destroy Session</a></li>";
                }

                $actions = "<span class='dropdown header'>
                                    <div class='btn-group'>
                                        <button type='button' class='btn btn-default'><span class='text'>Edit</span></button>
                                        <button class='btn btn-default dropdown-toggle' type='button' id='dropdownMenu' data-toggle='dropdown' aria-expanded='true'><span class='caret'></span></button>
                                        <ul class='dropdown-menu' id='menu' role='menu' aria-labelledby='dropdownMenu'> 
                                            <li role='presentation'><a role='menuitem' tabindex='-1' onclick='smhMEM.editUser(" . $user_data . ");'>User</a></li>" .
                        "<li role='presentation'><a role='menuitem' tabindex='-1' onclick='smhMEM.editUserDetails(" . $user["user_id"] . ",\"" . $user['user_details'] . "\");'>Additional Details</a></li>" .
                        $destroy_session .
                        "<li role='presentation'><a role='menuitem' tabindex='-1' onclick='smhMEM.userPassword(" . $reset_data . ");'>Password</a></li>
                                            <li role='presentation'><a role='menuitem' tabindex='-1' onclick='smhMEM.statusUser(" . $status_data . ");'>" . $status_text . "</a></li>                                               
                                            <li role='presentation' style='border-top: solid 1px #f0f0f0;'><a role='menuitem' tabindex='-1' onclick='smhMEM.deleteUser(" . $delete_data . ");'>Delete</a></li>
                                        </ul>
                                    </div>
                                </span>";

                $row = array();
                $row[] = $status;
                $row[] = "<div class='data-break'>" . $user['user_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $user['first_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $user['last_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $user['email'] . "</div>";
                $row[] = "<div class='data-break'><a onclick='smhMEM.viewDetails(\"" . $user['user_details'] . "\");'>View Details <i class='fa fa-external-link' style='width: 100%; text-align: center; display: inline; font-size: 12px;'></i></a></div>";
                $row[] = "<div class='data-break'>" . $isLoggedIn . "</div>";
                $row[] = "<div class='data-break'>" . $user['created_at'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }
        return $output;
    }

    public function list_user_names($pid) {
        // Query the Db
        $this->accounts->select('*')
                ->from('user')
                ->where('partner_id', $pid);

        $query = $this->accounts->get();
        $users_res = $query->result_array();

        $row = array();
        foreach ($users_res as $user) {
            $row[$user['user_id']] = $user['first_name'] . " " . $user['last_name'];
        }

        return $row;
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function get_user_name($sm_ak, $uid) {
        $pid = $this->smcipher->decrypt($sm_ak);
        // Query the Db
        $this->accounts->select('*')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('user_id', $uid);

        $query = $this->accounts->get();
        $users_res = $query->result_array();

        $name = '';
        foreach ($users_res as $user) {
            $name = $user['first_name'] . " " . $user['last_name'];
        }

        return $name;
    }

    public function update_user($pid, $ks, $uid, $fname, $lname, $email, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_user($valid['pid'], $email)) {
                $data = array(
                    'first_name' => $this->accounts->escape_str(trim($fname)),
                    'last_name' => $this->accounts->escape_str(trim($lname)),
                    'email' => $this->accounts->escape_str(trim($email)),
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->accounts->where('user_id = "' . $uid . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->accounts->update('user', $data);
                $this->accounts->limit(1);
                if ($this->accounts->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'user does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_status($pid, $ks, $email, $status, $uid, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_user($valid['pid'], $email)) {
                $user_status = $this->get_user_status($valid['pid'], $email);
                if ($user_status == 3) {
                    $activate = $this->update_activate_status($valid['pid'], $uid, $email);
                    if ($activate['success']) {
                        $success = array('success' => true);
                    }
                } else {
                    $data = array(
                        'status' => $status,
                        'updated_at' => date("Y-m-d h:i:s")
                    );

                    $this->accounts->where('email = "' . $email . '" AND partner_id = "' . $valid['pid'] . '"');
                    $this->accounts->update('user', $data);
                    $this->accounts->limit(1);
                    if ($this->accounts->affected_rows() > 0) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                }
            } else {
                $success = array('error' => 'user does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function destroy_session($pid, $ks, $uid) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $data = array(
                'auth_key' => null,
                'logged_in' => 0,
                'updated_at' => date("Y-m-d H:i:s")
            );

            $this->accounts->where('user_id = "' . $uid . '" AND partner_id = "' . $valid['pid'] . '"');
            $this->accounts->update('user', $data);
            $this->accounts->limit(1);
            if ($this->accounts->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_pswd($pid, $ks, $email, $pass, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if ($this->check_user($valid['pid'], $email)) {
                $hash = $this->hashPassword($pass);
                $data = array(
                    'hash' => $hash,
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->accounts->where('email = "' . $email . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->accounts->update('user', $data);
                $this->accounts->limit(1);
                if ($this->accounts->affected_rows() > 0) {
                    $email_queued = $this->email_reset_pswd($valid['pid'], $email);
                    if ($email_queued) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'user does not exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function delete_user($pid, $ks, $uid) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $this->accounts->where('user_id = "' . $uid . '" AND partner_id = "' . $valid['pid'] . '"');
            $this->accounts->delete('user');
            $this->accounts->limit(1);
            if ($this->accounts->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_user_id($pid, $email) {
        $user_id = '';

        $this->accounts->select('user_id')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('email', $email)
                ->limit(1);
        $query = $this->accounts->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $user_id = $r['user_id'];
        }

        return $user_id;
    }

    public function get_user_status($pid, $un) {
        $status = '';
        $this->accounts->select('status')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('email', $un)
                ->limit(1);
        $query = $this->accounts->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $status = $r['status'];
        }

        return $status;
    }

    public function get_user_status_logged_in($pid, $auth_key) {
        $status = '';
        $this->accounts->select('status')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('auth_key', $auth_key)
                ->limit(1);
        $query = $this->accounts->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $status = $r['status'];
        }

        return $status;
    }

    public function login_user($un, $pswd, $sm_ak, $type, $entryId) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        if ($this->get_user_status($pid, $un) == 2) {
            $success = array('success' => false, 'au' => false);
        } else {
            $allow_concurrent = $this->get_concurrent_status($pid);
            if ($allow_concurrent['concurrent_logins']) {
                if ($this->check_entry_status($pid, $entryId)) {
                    $hash = $this->accounts->select('*')
                            ->from('user')
                            ->where('email', $this->accounts->escape_str($un))
                            ->where('partner_id', $pid)
                            ->where('activated', 2)
                            ->limit(1);
                    $query = $this->accounts->get();
                    $user = $query->result_array();
                    if (count($user) != 0) {
                        foreach ($user as $userHash) {
                            $hash = $userHash['hash'];
                            $userId = $userHash['user_id'];
                        }
                        if (crypt($this->accounts->escape_str($pswd), $hash) == $hash) {
                            $success = $this->create_auth_key($un, $userId, $sm_ak, $type, $entryId);
                        } else {
                            $success = array('success' => false, 'au' => false);
                        }
                    } else {
                        $success = array('success' => false, 'au' => false);
                    }
                } else {
                    $success = array('success' => false, 'au' => false, 'blocked' => true);
                }
            } else {
                if ($this->check_if_active($pid, $un)) {
                    $success = array('success' => false, 'au' => true);
                } else {
                    if ($this->check_entry_status($pid, $entryId)) {
                        $hash = $this->accounts->select('*')
                                ->from('user')
                                ->where('email', $this->accounts->escape_str($un))
                                ->where('partner_id', $pid)
                                ->where('activated', 2)
                                ->limit(1);
                        $query = $this->accounts->get();
                        $user = $query->result_array();
                        if (count($user) != 0) {
                            foreach ($user as $userHash) {
                                $hash = $userHash['hash'];
                                $userId = $userHash['user_id'];
                            }
                            if (crypt($this->accounts->escape_str($pswd), $hash) == $hash) {
                                $success = $this->create_auth_key($un, $userId, $sm_ak, $type, $entryId);
                            } else {
                                $success = array('success' => false, 'au' => false);
                            }
                        } else {
                            $success = array('success' => false, 'au' => false);
                        }
                    } else {
                        $success = array('success' => false, 'au' => false, 'blocked' => true);
                    }
                }
            }
        }
        return $success;
    }

    public function check_entry_status($pid, $entryId) {
        $status = false;
        $this->accounts->select('*')
                ->from('mem_entry')
                ->where('kentry_id', $entryId)
                ->where('partner_id', $pid)
                ->where('status', 1)
                ->limit(1);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $status = true;
        }
        return $status;
    }

    public function check_if_active($pid, $un) {
        $active = false;
        $logged_in = 0;
        $this->accounts->select('*')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('email', $this->accounts->escape_str($un))
                ->where('activated', 2)
                ->limit(1);
        $query = $this->accounts->get();
        $result = $query->result_array();

        foreach ($result as $r) {
            $last_active = $r['last_active'];
            $logged_in = $r['logged_in'];
        }

        if ($logged_in) {
            $today = date("Y-m-d H:i:s");
            $current_date = new DateTime($today);
            $last_active_date = new DateTime($last_active);
            $elapsed = $current_date->getTimestamp() - $last_active_date->getTimestamp();
            if ($elapsed <= 600) {
                $active = true;
            } else {
                $active = false;
            }
        } else {
            $active = false;
        }

        return $active;
    }

    public function activate_user($sm_ak, $akey, $email, $tz) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);

        if ($this->already_activated($pid, $email)) {
            $success = array('success' => false, 'message' => 'User already activated.');
        } else {
            $activate_key = $this->accounts->select('*')
                    ->from('user')
                    ->where('email', $this->accounts->escape_str($email))
                    ->where('partner_id', $pid)
                    ->where('activated', 1)
                    ->limit(1);
            $query = $this->accounts->get();
            if ($query->num_rows() > 0) {
                $user = $query->result_array();
                foreach ($user as $key) {
                    $activate_key = $key['activate_key'];
                    $userId = $key['user_id'];
                }

                if ($akey == $activate_key) {
                    $activate = $this->update_activate_status($pid, $userId, $email);
                    if ($activate['success']) {
                        $success = array('success' => true);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('success' => false, 'message' => 'User account not found.');
            }
        }
        return $success;
    }

    public function already_activated($pid, $email) {
        $activated = false;
        $this->accounts->select('*')
                ->from('user')
                ->where('email', $this->accounts->escape_str($email))
                ->where('partner_id', $pid)
                ->where('activated', 2)
                ->limit(1);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $activated = true;
        }
        return $activated;
    }

    public function update_activate_status($pid, $userId, $email) {
        $success = array('success' => false);

        $data = array(
            'status' => 1,
            'activated' => 2
        );

        $this->accounts->where('partner_id', $pid);
        $this->accounts->where('user_id', $userId);
        $this->accounts->update('user', $data);
        $this->accounts->limit(1);
        if ($this->accounts->affected_rows() > 0) {
            $email_queued = $this->email_user($pid, $email);
            if ($email_queued) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function create_auth_key($un, $uid, $sm_ak, $type, $entryId) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;
        $auth_key = $this->smcipher->encrypt($un);
        $data = array(
            'auth_key' => $auth_key
        );
        $this->accounts->where('email', $un);
        $this->accounts->where('partner_id', $pid);
        $this->accounts->update('user', $data);
        $this->accounts->limit(1);
        if ($this->accounts->affected_rows() > 0) {
            if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                $entryId = $this->smportal->get_cat_thumb($pid, $entryId);
            }
            if ($type == 's' || $type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                $privilege = 'sview:' . $entryId;
            } else if ($type == 'p') {
                $privilege = 'sviewplaylist:' . $entryId;
            }
            $token = $this->smportal->create_token($pid, '86400', $privilege);
            $success = array('success' => true, 'auth_key' => $auth_key, 'user_id' => $uid, 'token' => $token);
        } else {
            $success = false;
        }

        return $success;
    }

    public function is_logged_in($auth_key, $sm_ak, $type, $entryId) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;

        if ($this->get_user_status_logged_in($pid, $auth_key) == 2) {
            $success = false;
        } else {
            $this->accounts->select('*')
                    ->from('user')
                    ->where('auth_key', $auth_key)
                    ->where('partner_id', $pid);

            $query = $this->accounts->get();

            if ($query->num_rows() > 0) {
                if ($type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                    $entryId = $this->smportal->get_cat_thumb($pid, $entryId);
                }
                if ($type == 's' || $type == 'cl' || $type == 'cr' || $type == 'ct' || $type == 'cb') {
                    $privilege = 'sview:' . $entryId;
                } else if ($type == 'p') {
                    $privilege = 'sviewplaylist:' . $entryId;
                }
                $token = $this->smportal->create_token($pid, '86400', $privilege);
                $session = $query->result_array();
                foreach ($session as $sess) {
                    $success = array('success' => true, 'user_id' => $sess['user_id'], 'token' => $token);
                }
            } else {
                $success = false;
            }
        }

        return $success;
    }

    public function add_user($pid, $ks, $fname, $lname, $email, $pass, $status, $tz) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            date_default_timezone_set($tz);
            if (!$this->check_user($valid['pid'], $email)) {
                $hash = $this->hashPassword($pass);

                $data = array(
                    'first_name' => $this->accounts->escape_str(trim($fname)),
                    'last_name' => $this->accounts->escape_str(trim($lname)),
                    'email' => $this->accounts->escape_str(trim($email)),
                    'restriction' => 1,
                    'hash' => $hash,
                    'created_at' => date("Y-m-d h:i:s"),
                    'activated' => 2,
                    'status' => $status,
                    'partner_id' => $valid['pid']
                );

                $this->accounts->insert('user', $data);
                if ($this->accounts->affected_rows() > 0) {
                    $id = $this->accounts->insert_id();
                    $email_queued = $this->email_user($valid['pid'], $email);
                    if ($email_queued) {
                        $success = array('success' => true, 'userId' => $id);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'user exists');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function register_user($sm_ak, $fname, $lname, $email, $pass, $tz, $url, $attrs, $type, $entryId) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);
        $already_exists = false;
        $status = 3;
        $activated = 1;

        date_default_timezone_set($tz);
        if (!$this->check_activated_user($pid, $email)) {
            $skip_activation = $this->get_activation_skip_status($pid);
            if ($this->check_pending_user($pid, $email)) {
                $activation_key = $this->getActivationKey($pid, $email);
                $already_exists = true;
            } else {
                $activation_key = $this->generateStrongPassword(32, false, 'lud');
                $password = $this->hashPassword($pass);
                if ($skip_activation['skip_activation']) {
                    $status = 1;
                    $activated = 2;
                }
                $data = array(
                    'first_name' => $this->accounts->escape_str(trim($fname)),
                    'last_name' => $this->accounts->escape_str(trim($lname)),
                    'email' => $this->accounts->escape_str(trim($email)),
                    'user_details' => ($attrs) ? $this->accounts->escape_str($attrs) : null,
                    'restriction' => 1,
                    'hash' => $password,
                    'activate_key' => $activation_key,
                    'activated' => $activated,
                    'created_at' => date("Y-m-d h:i:s"),
                    'status' => $status,
                    'partner_id' => $pid
                );
                $this->accounts->insert('user', $data);
            }

            if ($this->accounts->affected_rows() > 0 || $already_exists) {
                $userId = $this->accounts->insert_id();
                if ($skip_activation['skip_activation']) {
                    $email_queued = $this->email_user($pid, $email);
                    if ($email_queued) {
                        $success = $this->create_auth_key($email, $userId, $sm_ak, $type, $entryId);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $email_queued = $this->email_activation($pid, $sm_ak, $fname, $lname, $email, $activation_key, $url);
                    if ($email_queued) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                }
            } else {
                $success = array('success' => false);
            }
        } else {
            $success = array('error' => 'user exists');
        }

        return $success;
    }

    public function getActivationKey($pid, $email) {
        $activate_key = '';
        $this->accounts->select('*')
                ->from('user')
                ->where('email', $this->accounts->escape_str($email))
                ->where('partner_id', $pid)
                ->where('activated', 1)
                ->limit(1);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $user = $query->result_array();
            foreach ($user as $key) {
                $activate_key = $key['activate_key'];
            }
        }
        return $activate_key;
    }

    public function reset_request($email, $sm_ak, $url) {
        $pid = $this->smcipher->decrypt($sm_ak);

        if ($this->check_activated_user($pid, $email)) {
            $reset_token = $this->generateStrongPassword(10, false, 'lud');
            $hash = $this->hashPassword($reset_token);

            $data = array(
                'reset_token' => $hash
            );

            $this->accounts->where('partner_id', $pid);
            $this->accounts->where('email', $email);
            $this->accounts->update('user', $data);

            if ($this->accounts->affected_rows() > 0) {
                $email_queued = $this->email_reset_pass($pid, $email, $sm_ak, $reset_token, $url);
                if ($email_queued) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true);
                }
            } else {
                $success = array('success' => true);
            }
        } else {
            $success = array('success' => true);
        }

        return $success;
    }

    public function reset_pass($email, $sm_ak, $reset_token, $pass) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);

        $reset_key = $this->accounts->select('*')
                ->from('user')
                ->where('email', $this->accounts->escape_str($email))
                ->where('partner_id', $pid)
                ->where('activated', 2)
                ->limit(1);
        $query = $this->accounts->get();
        $user = $query->result_array();

        foreach ($user as $key) {
            $reset_key = $key['reset_token'];
        }

        if (isset($reset_key) && $reset_key != '' && $reset_key != null) {
            if (crypt($this->accounts->escape_str($reset_token), $reset_key) == $reset_key) {
                $hash = $this->hashPassword($pass);

                $data = array(
                    'hash' => $hash
                );

                $this->accounts->where('email', $email);
                $this->accounts->where('partner_id', $pid);
                $this->accounts->update('user', $data);
                $this->accounts->limit(1);
                if ($this->accounts->affected_rows() > 0) {
                    $clear_reset_token = $this->remove_reset_key($pid, $email);
                    if ($clear_reset_token) {
                        $email_queued = $this->email_reset_pswd($pid, $email);
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
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function reset_email($email, $new_email, $sm_ak, $reset_token, $pass) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = array('success' => false);

        if (!$this->check_user($pid, $new_email)) {
            $this->accounts->select('*')
                    ->from('user')
                    ->where('email', $this->accounts->escape_str($email))
                    ->where('partner_id', $pid)
                    ->where('activated', 2)
                    ->limit(1);
            $query = $this->accounts->get();
            $user = $query->result_array();

            foreach ($user as $key) {
                $hash = $key['hash'];
                $reset_key = $key['reset_token'];
            }

            if (isset($reset_key) && $reset_key != '' && $reset_key != null) {
                if (crypt($this->accounts->escape_str($pass), $hash) == $hash) {
                    if (crypt($this->accounts->escape_str($reset_token), $reset_key) == $reset_key) {
                        $data = array(
                            'email' => $new_email,
                            'updated_at' => date("Y-m-d h:i:s")
                        );

                        $this->accounts->where('email', $email);
                        $this->accounts->where('partner_id', $pid);
                        $this->accounts->update('user', $data);
                        $this->accounts->limit(1);
                        if ($this->accounts->affected_rows() > 0) {
                            $clear_reset_token = $this->remove_reset_key($pid, $new_email);
                            if ($clear_reset_token) {
                                $new_email_queued = $this->email_new_email($pid, $new_email);
                                if ($new_email_queued) {
                                    $old_email_queued = $this->email_old_email($pid, $email, $new_email);
                                    if ($old_email_queued) {
                                        $success = array('success' => true);
                                    } else {
                                        $success = array('success' => false);
                                    }
                                } else {
                                    $success = array('success' => false);
                                }
                            } else {
                                $success = array('success' => false, 'message' => 'Token not cleared');
                            }
                        } else {
                            $success = array('success' => false, 'message' => 'No changes made');
                        }
                    } else {
                        $success = array('success' => false, 'message' => 'Invalid key');
                    }
                } else {
                    $success = array('success' => false, 'message' => 'Wrong password');
                }
            } else {
                $success = array('success' => false, 'message' => 'Key deactivated');
            }
        } else {
            $success = array('success' => false, 'message' => 'User already exists');
        }
        return $success;
    }

    public function email_new_email($pid, $new_email) {
        $business_name = $this->smportal->get_user_details($pid);
        $name = $this->get_full_name($pid, $new_email);
        $subject = 'Your Account Has Been Updated';
        $body = 'Hi, ' . $name . '<br><br>Your email has been successfully changed. You may now log in with the new email address: ' . $new_email . '<br><br>Best Regards,<br>' . $business_name;

        if ($this->check_email_config($pid)) {
            $this->accounts->select('*')
                    ->from('email_config')
                    ->where('partner_id', $pid);

            $query = $this->accounts->get();
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
        $to = $new_email;

        $result = $this->queue_email
                (
                null, // foreign_id_a
                null, // foreign_id_b
                1, // priority
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

    public function email_old_email($pid, $email, $new_email) {
        $business_name = $this->smportal->get_user_details($pid);
        $name = $this->get_full_name($pid, $new_email);
        $subject = 'Your Account Has Been Updated';
        $body = 'Hi, ' . $name . '<br><br>The email for your account (' . $email . ') has been successfully changed to: ' . $new_email . '.<br><br> You must now use this new email address to login to your account.<br><br>Best Regards,<br>' . $business_name;

        if ($this->check_email_config($pid)) {
            $this->accounts->select('*')
                    ->from('email_config')
                    ->where('partner_id', $pid);

            $query = $this->accounts->get();
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
        $to = $email;

        $result = $this->queue_email
                (
                null, // foreign_id_a
                null, // foreign_id_b
                1, // priority
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

    public function remove_reset_key($pid, $email) {
        $success = false;
        $data = array(
            'reset_token' => null
        );
        $this->accounts->where('email = "' . $email . '" AND partner_id = "' . $pid . '"');
        $this->accounts->update('user', $data);
        $this->accounts->limit(1);
        if ($this->accounts->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }
        return $success;
    }

    public function check_activated_user($pid, $email) {
        $success = false;
        $this->accounts->select('*')
                ->from('user')
                ->where('email', $this->accounts->escape_str($email))
                ->where('partner_id', $pid)
                ->where('activated', 2);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_pending_user($pid, $email) {
        $success = false;
        $this->accounts->select('*')
                ->from('user')
                ->where('email', $this->accounts->escape_str($email))
                ->where('partner_id', $pid)
                ->where('activated', 1);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function inet_aton($ip) {
        $ip = trim($ip);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6))
            return 0;
        return sprintf("%u", ip2long($ip));
    }

    public function inet_ntoa($num) {
        $num = trim($num);
        if ($num == "0")
            return "0.0.0.0";
        return long2ip(-(4294967295 - ($num - 1)));
    }

    public function check_user($pid, $email) {
        $success = false;
        $this->accounts->select('*')
                ->from('user')
                ->where('email = "' . $this->accounts->escape_str($email) . '" AND partner_id = "' . $pid . '"');
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_email($pid) {
        $success = false;
        $this->accounts->select('*')
                ->from('mem_email')
                ->where('partner_id', $pid);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_reg_email_default($pid) {
        $success = false;
        $result = '';
        $register_default = '';
        $this->accounts->select('register_default')
                ->from('mem_email')
                ->where('partner_id', $pid);
        $query = $this->accounts->get();
        $result = $query->result_array();

        foreach ($result as $d) {
            $register_default = $d['register_default'];
        }

        if ($register_default == '1') {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_pswd_email_default($pid) {
        $success = false;
        $result = '';
        $pswd_default = '';
        $this->accounts->select('pswd_default')
                ->from('mem_email')
                ->where('partner_id', $pid);
        $query = $this->accounts->get();
        $result = $query->result_array();

        foreach ($result as $d) {
            $pswd_default = $d['pswd_default'];
        }

        if ($pswd_default == '1') {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_email_config($pid) {
        $success = false;
        $this->accounts->select('*')
                ->from('email_config')
                ->where('partner_id', $pid);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function email_activation($pid, $sm_ak, $fname, $lname, $email, $activation_key, $url) {
        $business_name = $this->smportal->get_user_details($pid);
        $subject = 'Registration Confirmation';
        $body = 'Hi, ' . $fname . ' ' . $lname . '<br><br>Thank you for registering! To activate your account, please click on the following link: <a href="https://mediaplatform.streamingmediahosting.com/apps/mem/v1.0/verify.php?pid=' . $pid . '&sm_ak=' . $sm_ak . '&akey=' . $activation_key . '&email=' . $email . '&url=' . $url . '">Click Here to Activate</a><br><br>Best Regards,<br>' . $business_name;

        if ($this->check_email_config($pid)) {
            $this->accounts->select('*')
                    ->from('email_config')
                    ->where('partner_id', $pid);

            $query = $this->accounts->get();
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
        $to = $email;

        $result = $this->queue_email
                (
                null, // foreign_id_a
                null, // foreign_id_b
                1, // priority
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

    public function email_reset_email($pid, $email, $sm_ak, $reset_token, $url) {
        $business_name = $this->smportal->get_user_details($pid);
        $name = $this->get_full_name($pid, $email);
        $subject = 'Email Reset Request';
        $body = 'Hi, ' . $name . '<br><br>Your email reset request has been successfully submitted. To reset your email, please visit the following link: <a href="https://mediaplatform.streamingmediahosting.com/apps/mem/v1.0/resetEmail_debug.php?pid=' . $pid . '&sm_ak=' . $sm_ak . '&reset_token=' . $reset_token . '&email=' . $email . '&url=' . $url . '">Click Here to Reset Your Email</a><br><br>Best Regards,<br>' . $business_name;

        if ($this->check_email_config($pid)) {
            $this->accounts->select('*')
                    ->from('email_config')
                    ->where('partner_id', $pid);

            $query = $this->accounts->get();
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
        $to = $email;

        $result = $this->queue_email
                (
                null, // foreign_id_a
                null, // foreign_id_b
                1, // priority
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

    public function email_reset_pass($pid, $email, $sm_ak, $reset_token, $url) {
        $business_name = $this->smportal->get_user_details($pid);
        $name = $this->get_full_name($pid, $email);
        $subject = 'Password Recovery Request';
        $body = 'Hi, ' . $name . '<br><br>Your password reset request has been succesfully submitted. To reset your password, please visit the following link: <a href="https://mediaplatform.streamingmediahosting.com/apps/mem/v1.0/forgotPass_debug.php?pid=' . $pid . '&sm_ak=' . $sm_ak . '&reset_token=' . $reset_token . '&email=' . $email . '&url=' . $url . '">Click Here to Reset Your Password</a><br><br>Best Regards,<br>' . $business_name;

        if ($this->check_email_config($pid)) {
            $this->accounts->select('*')
                    ->from('email_config')
                    ->where('partner_id', $pid);

            $query = $this->accounts->get();
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
        $to = $email;

        $result = $this->queue_email
                (
                null, // foreign_id_a
                null, // foreign_id_b
                1, // priority
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

    public function get_full_name($pid, $email) {
        // Query the Db
        $this->accounts->select('*')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('email', $email);

        $query = $this->accounts->get();
        $users_res = $query->result_array();

        $name = '';
        foreach ($users_res as $user) {
            $name = $user['first_name'] . " " . $user['last_name'];
        }

        return $name;
    }

    public function email_user($pid, $userName) {
        $subject = '';
        $body = '';

        if ($this->check_email($pid)) {
            if ($this->check_reg_email_default($pid)) {

                if ($this->check_email_config($pid)) {
                    $this->accounts->select('*')
                            ->from('email_config')
                            ->where('partner_id', $pid);

                    $query = $this->accounts->get();
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
                $business_name = $this->smportal->get_user_details($pid);
                $to = $userName; //change
                $subject = $email['register_subject'];
                $body = $email['register_body'];
                $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
                $body = str_replace("<strong>%user_email%</strong>", $userName, $body);
                $body = str_replace("<strong>%email%</strong>", $from_email, $body);
            } else {
                if ($this->check_email_config($pid)) {
                    $this->accounts->select('*')
                            ->from('email_config')
                            ->where('partner_id', $pid);

                    $query = $this->accounts->get();
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
                $business_name = $this->smportal->get_user_details($pid);
                $to = $userName;
                $subject = $email['register_subject'];
                $body = $email['register_body'];
                $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
                $body = str_replace("<strong>%user_email%</strong>", $userName, $body);
                $body = str_replace("<strong>%email%</strong>", $from_email, $body);
            }
        } else {
            if ($this->check_email_config($pid)) {
                $this->accounts->select('*')
                        ->from('email_config')
                        ->where('partner_id', $pid);

                $query = $this->accounts->get();
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
            $business_name = $this->smportal->get_user_details($pid);
            $to = $userName;
            $subject = $email['register_subject'];
            $body = $email['register_body'];
            $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
            $body = str_replace("<strong>%user_email%</strong>", $userName, $body);
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

    public function email_reset_pswd($pid, $user_email) {
        $subject = '';
        $body = '';

        if ($this->check_email($pid)) {
            if ($this->check_pswd_email_default($pid)) {
                if ($this->check_email_config($pid)) {
                    $this->accounts->select('*')
                            ->from('email_config')
                            ->where('partner_id', $pid);

                    $query = $this->accounts->get();
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
                $business_name = $this->smportal->get_user_details($pid);
                $to = $user_email; //change
                $subject = $email['pswd_subject'];
                $body = $email['pswd_body'];
                $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
                $body = str_replace("<strong>%user_email%</strong>", $user_email, $body);
                $body = str_replace("<strong>%email%</strong>", $from_email, $body);
            } else {
                if ($this->check_email_config($pid)) {
                    $this->accounts->select('*')
                            ->from('email_config')
                            ->where('partner_id', $pid);

                    $query = $this->accounts->get();
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
                $business_name = $this->smportal->get_user_details($pid);
                $to = $user_email;
                $subject = $email['pswd_subject'];
                $body = $email['pswd_body'];
                $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
                $body = str_replace("<strong>%user_email%</strong>", $user_email, $body);
                $body = str_replace("<strong>%email%</strong>", $from_email, $body);
            }
        } else {
            if ($this->check_email_config($pid)) {
                $this->accounts->select('*')
                        ->from('email_config')
                        ->where('partner_id', $pid);

                $query = $this->accounts->get();
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
            $business_name = $this->smportal->get_user_details($pid);
            $to = $user_email;
            $subject = $email['pswd_subject'];
            $body = $email['pswd_body'];
            $body = str_replace("<strong>%business_name%</strong>", $business_name, $body);
            $body = str_replace("<strong>%user_email%</strong>", $user_email, $body);
            $body = str_replace("<strong>%email%</strong>", $from_email, $body);
        }

        $result = $this->queue_email
                (
                null, // foreign_id_a
                null, // foreign_id_b
                1, // priority
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

    public function getEmailConfig($pid) {
        $email = array();

        $this->accounts->select('*')
                ->from('mem_email')
                ->where('partner_id', $pid)
                ->limit(1);
        $query = $this->accounts->get();
        $emailConfig = $query->result_array();

        foreach ($emailConfig as $e) {
            $email['register_subject'] = $e['register_email_subject'];
            $email['register_body'] = stripcslashes($e['register_email_body']);
            $email['pswd_subject'] = $e['pswd_email_subject'];
            $email['pswd_body'] = stripcslashes($e['pswd_email_body']);
        }

        return $email;
    }

    public function getDefaultEmailConfig() {
        $email = array();

        $this->accounts->select('*')
                ->from('mem_email')
                ->where('partner_id', '1')
                ->limit(1);
        $query = $this->accounts->get();
        $emailConfig = $query->result_array();

        foreach ($emailConfig as $e) {
            $email['register_subject'] = $e['register_email_subject'];
            $email['register_body'] = stripcslashes($e['register_email_body']);
            $email['pswd_subject'] = $e['pswd_email_subject'];
            $email['pswd_body'] = stripcslashes($e['pswd_email_body']);
        }

        return $email;
    }

    public function hashPassword($pswd) {
        // A higher "cost" is more secure but consumes more processing power
        $cost = 10;

        // Create a random salt
        $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
        $salt = sprintf("$2a$%02d$", $cost) . $salt;

        // Hash the password with the salt
        $hash = crypt($pswd, $salt);

        return $hash;
    }

    public function generateStrongPassword($length = 9, $add_dashes = false, $available_sets = 'luds') {
        $sets = array();
        if (strpos($available_sets, 'l') !== false)
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if (strpos($available_sets, 'u') !== false)
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if (strpos($available_sets, 'd') !== false)
            $sets[] = '23456789';
        if (strpos($available_sets, 's') !== false)
            $sets[] = '!@#$*?';

        $all = '';
        $password = '';
        foreach ($sets as $set) {
            $password .= $set[array_rand(str_split($set))];
            $all .= $set;
        }

        $all = str_split($all);
        for ($i = 0; $i < $length - count($sets); $i++)
            $password .= $all[array_rand($all)];

        $password = str_shuffle($password);

        if (!$add_dashes)
            return $password;

        $dash_len = floor(sqrt($length));
        $dash_str = '';
        while (strlen($password) > $dash_len) {
            $dash_str .= substr($password, 0, $dash_len) . '-';
            $password = substr($password, $dash_len);
        }
        $dash_str .= $password;
        return $dash_str;
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

    public function is_active($sm_ak, $uid) {
        $success = array('success' => false);
        $pid = $this->smcipher->decrypt($sm_ak);

        $data = array(
            'last_active' => date("Y-m-d H:i:s"),
            'logged_in' => 1
        );

        $this->accounts->where('partner_id', $pid);
        $this->accounts->where('user_id', $uid);
        $this->accounts->update('user', $data);
        $this->accounts->limit(1);
        if ($this->accounts->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function is_not_active($sm_ak, $uid) {
        $success = array('success' => false);
        $pid = $this->smcipher->decrypt($sm_ak);

        $data = array(
            'logged_in' => 0
        );

        $this->accounts->where('partner_id', $pid);
        $this->accounts->where('user_id', $uid);
        $this->accounts->update('user', $data);
        $this->accounts->limit(1);
        if ($this->accounts->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function activate_cat_entry($sm_ak, $entryId, $is_logged_in) {
        $pid = $this->smcipher->decrypt($sm_ak);
        $success = false;
        $is_logged_in = $is_logged_in == "true" ? true : false;

        if ($is_logged_in) {
            $privilege = 'sview:' . $entryId;
            $infinte_token = $this->smportal->create_token($pid, '86400', $privilege);
            $success = $infinte_token;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_user_config($pid) {
        $success = false;
        $this->accounts->select('*')
                ->from('user_config')
                ->where('partner_id', $pid);
        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function user_concurrent_status($pid, $ks, $concurrent) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_user_config($valid['pid'])) {
                $data = array(
                    'concurrent_logins' => $concurrent
                );
                $this->accounts->where('partner_id', $valid['pid']);
                $this->accounts->update('user_config', $data);
                $this->accounts->limit(1);
                if ($this->accounts->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'concurrent_logins' => $concurrent,
                    'partner_id' => $valid['pid']
                );
                $this->accounts->insert('user_config', $data);
                $this->accounts->limit(1);
                if ($this->accounts->affected_rows() > 0) {
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

    public function get_concurrent_status($pid) {
        $this->accounts->select('*')
                ->from('user_config')
                ->where('partner_id', $pid);

        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $res = $query->result_array();

            $result = array();
            foreach ($res as $r) {
                $result['concurrent_logins'] = ($r['concurrent_logins'] == 1) ? true : false;
            }
        } else {
            $result['concurrent_logins'] = false;
        }
        return $result;
    }

    public function user_activation_skip_status($pid, $ks, $skip) {
        $success = array('success' => false);

        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_user_config($valid['pid'])) {
                $data = array(
                    'skip_activation' => $skip
                );
                $this->accounts->where('partner_id', $valid['pid']);
                $this->accounts->update('user_config', $data);
                $this->accounts->limit(1);
                if ($this->accounts->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true, 'notice' => 'no changes were made');
                }
            } else {
                $data = array(
                    'skip_activation' => $skip,
                    'partner_id' => $valid['pid']
                );
                $this->accounts->insert('user_config', $data);
                $this->accounts->limit(1);
                if ($this->accounts->affected_rows() > 0) {
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

    public function get_activation_skip_status($pid) {
        $this->accounts->select('*')
                ->from('user_config')
                ->where('partner_id', $pid);

        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $res = $query->result_array();

            $result = array();
            foreach ($res as $r) {
                $result['skip_activation'] = ($r['skip_activation'] == 1) ? true : false;
            }
        } else {
            $result['skip_activation'] = false;
        }
        return $result;
    }

    public function get_owner_attrs($pid, $ks) {
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $this->accounts->select('*')
                    ->from('owner_attributes')
                    ->where('partner_id', $pid);

            $query = $this->accounts->get();
            $result = $query->result_array();

            $attr = array();
            $row = array();
            foreach ($result as $r) {
                array_push($attr, array('name' => $r['attribute'], 'id' => $r['attr_id'], 'required' => $r['required']));
            }

            return $attr;
        }
    }

    public function get_owner_attrs_users($pid) {
        $this->accounts->select('*')
                ->from('owner_attributes')
                ->where('partner_id', $pid);

        $query = $this->accounts->get();
        $result = $query->result_array();

        $attr = array();
        $row = array();
        foreach ($result as $r) {
            array_push($attr, array('name' => $r['attribute'], 'id' => $r['attr_id'], 'required' => $r['required']));
        }

        return $attr;
    }

    public function update_reg_fields($pid, $ks, $newFields, $updateFields, $removeFields) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $this->add_reg_fields($pid, json_decode($newFields));
            $this->update_fields($pid, json_decode($updateFields));
            $this->remove_reg_fields($pid, json_decode($removeFields));
            $success = array('success' => true);
        }
        return $success;
    }

    public function add_reg_fields($pid, $newFields) {
        if ($newFields) {
            foreach ($newFields as $attr) {
                $data = array(
                    'attribute' => $attr->name,
                    'required' => $attr->required,
                    'partner_id' => $pid,
                    'created_at' => date("Y-m-d H:i:s")
                );
                $this->accounts->insert('owner_attributes', $data);
            }
        }
    }

    public function update_fields($pid, $updateFields) {
        if ($updateFields) {
            foreach ($updateFields as $attr) {
                $data = array(
                    'attribute' => $attr->name,
                    'required' => $attr->required,
                    'updated_at' => date("Y-m-d H:i:s")
                );
                $this->accounts->where('attr_id', $attr->id);
                $this->accounts->update('owner_attributes', $data);
            }
        }
    }

    public function remove_reg_fields($pid, $removeFields) {
        if ($removeFields) {
            foreach ($removeFields as $attr) {
                $this->accounts->delete('owner_attributes', array('attr_id' => $attr));
                $this->remove_user_detail($pid, $attr);
            }
        }
    }

    public function remove_user_detail($pid, $attr_id) {
        $this->accounts->select('*')
                ->from('user')
                ->where('partner_id', $pid)
                ->like('user_details', $attr_id);

        $query = $this->accounts->get();
        if ($query->num_rows() > 0) {
            $resp = $query->result_array();
            foreach ($resp as $r) {
                $ud_array = json_decode(stripslashes($r['user_details']));
                $new_arr = array();
                if ($ud_array) {
                    foreach ($ud_array as $attr) {
                        if ($attr->id != $attr_id) {
                            $data = array(
                                'field_name' => $attr->field_name,
                                'id' => $attr->id,
                                'required' => $attr->required,
                                'value' => $attr->value
                            );
                            array_push($new_arr, $data);
                        }
                    }
                    if ($new_arr) {
                        $new_json = json_encode($new_arr);
                        $data = array(
                            'user_details' => $this->accounts->escape_str($new_json)
                        );
                    } else {
                        $data = array(
                            'user_details' => null
                        );
                    }
                    $this->accounts->where('user_id', $r['user_id']);
                    $this->accounts->update('user', $data);
                }
            }
        }
    }

    public function update_user_details($pid, $uid, $ks, $updateFields) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $data = array(
                'user_details' => $this->accounts->escape_str($updateFields),
                'updated_at' => date("Y-m-d H:i:s")
            );
            $this->accounts->where('user_id', $uid);
            $this->accounts->where('partner_id', $pid);
            $this->accounts->update('user', $data);
            if ($this->accounts->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => true, 'notice' => 'no changes were made');
            }
        }
        return $success;
    }

    public function get_user_details($sm_ak, $uid) {
        $pid = $this->smcipher->decrypt($sm_ak);
        // Query the Db
        $this->accounts->select('*')
                ->from('user')
                ->where('partner_id', $pid)
                ->where('user_id', $uid);

        $query = $this->accounts->get();
        $users_res = $query->result_array();

        $user = array();
        foreach ($users_res as $u) {
            $user['fname'] = $u['first_name'];
            $user['lname'] = $u['last_name'];
            $user['email'] = $u['email'];
        }

        return $user;
    }

    public function update_fname($sm_ak, $auth_key, $fname, $type, $entryId) {
        $success = array('success' => false);
        $pid = $this->smcipher->decrypt($sm_ak);
        $is_logged_in = $this->is_logged_in($auth_key, $sm_ak, $type, $entryId);
        if ($is_logged_in['success']) {
            $user_id = $is_logged_in['user_id'];
            $data = array(
                'first_name' => $this->accounts->escape_str(trim($fname)),
                'updated_at' => date("Y-m-d h:i:s")
            );
            $this->accounts->where('user_id = "' . $user_id . '" AND partner_id = "' . $pid . '"');
            $this->accounts->update('user', $data);
            $this->accounts->limit(1);
            if ($this->accounts->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        }
        return $success;
    }

    public function update_lname($sm_ak, $auth_key, $lname, $type, $entryId) {
        $success = array('success' => false);
        $pid = $this->smcipher->decrypt($sm_ak);
        $is_logged_in = $this->is_logged_in($auth_key, $sm_ak, $type, $entryId);
        if ($is_logged_in['success']) {
            $user_id = $is_logged_in['user_id'];
            $data = array(
                'last_name' => $this->accounts->escape_str(trim($lname)),
                'updated_at' => date("Y-m-d h:i:s")
            );
            $this->accounts->where('user_id = "' . $user_id . '" AND partner_id = "' . $pid . '"');
            $this->accounts->update('user', $data);
            $this->accounts->limit(1);
            if ($this->accounts->affected_rows() > 0) {
                $success = array('success' => true);
            } else {
                $success = array('success' => false);
            }
        }
        return $success;
    }

    public function reset_psswd_request($email, $sm_ak, $url) {
        $pid = $this->smcipher->decrypt($sm_ak);

        if ($this->check_activated_user($pid, $email)) {
            $reset_token = $this->generateStrongPassword(10, false, 'lud');
            $hash = $this->hashPassword($reset_token);

            $data = array(
                'reset_token' => $hash,
                'updated_at' => date("Y-m-d h:i:s")
            );

            $this->accounts->where('partner_id', $pid);
            $this->accounts->where('email', $email);
            $this->accounts->update('user', $data);

            if ($this->accounts->affected_rows() > 0) {
                $email_queued = $this->email_reset_pass($pid, $email, $sm_ak, $reset_token, $url);
                if ($email_queued) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true);
                }
            } else {
                $success = array('success' => true);
            }
        } else {
            $success = array('success' => true);
        }

        return $success;
    }

    public function reset_email_request($email, $sm_ak, $url) {
        $pid = $this->smcipher->decrypt($sm_ak);

        if ($this->check_activated_user($pid, $email)) {
            $reset_token = $this->generateStrongPassword(10, false, 'lud');
            $hash = $this->hashPassword($reset_token);

            $data = array(
                'reset_token' => $hash,
                'updated_at' => date("Y-m-d h:i:s")
            );

            $this->accounts->where('partner_id', $pid);
            $this->accounts->where('email', $email);
            $this->accounts->update('user', $data);

            if ($this->accounts->affected_rows() > 0) {
                $email_queued = $this->email_reset_email($pid, $email, $sm_ak, $reset_token, $url);
                if ($email_queued) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => true);
                }
            } else {
                $success = array('success' => true);
            }
        } else {
            $success = array('success' => true);
        }

        return $success;
    }

}
