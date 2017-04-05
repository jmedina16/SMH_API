<?php

error_reporting(0);

class Mem_entry_model extends CI_Model {

    public function __construct() {
        // Open the correct DB connection
        $this->entry = $this->load->database('ppv_dev', TRUE);
        $this->load->library('SMPortal');
        $this->load->library('SMCipher');
    }

    public function get_entry_list($pid, $ks, $start, $length, $search, $draw) {

        $valid = $this->verfiy_ks($pid, $ks);

        if ($valid['success']) {
            $columns = array('status', 'kentry_id', 'kentry_name', 'media_type', 'ac_id', 'created_at', 'updated_at');

            /*
             * Paging
             */
            if (isset($start) && $length != '-1') {
                $this->entry->limit($this->entry->escape_str($length), $this->entry->escape_str($start));
            }

//            /*
//             * Ordering
//             */
//            if (isset($iSortCol_0)) {
//                $this->entry->order_by($aColumns[intval($iSortCol_0)], $sSortDir_0);
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
                $this->entry->where('partner_id = "' . $valid['pid'] . '" AND (' . $where . ')');
            }

            // Query the Db
            $this->entry->select('SQL_CALC_FOUND_ROWS *', false)
                    ->where('partner_id', $valid['pid'])
                    ->order_by('entry_id', 'desc');

            $query = $this->entry->get('mem_entry');
            $entry_res = $query->result_array();

            /* Data set length after filtering */
            $this->entry->select('FOUND_ROWS() AS found_rows');
            $filteredTotal = $this->entry->get()->row()->found_rows;

            /* Total data set length */
            $count = $this->entry->query('SELECT count(*) AS `Count` FROM mem_entry WHERE partner_id = "' . $valid['pid'] . '"');
            $countQuery = $count->result_array();
            $total = $countQuery[0]['Count'];

            $output = array(
                "recordsTotal" => intval($total),
                "recordsFiltered" => intval($filteredTotal),
                "data" => array()
            );

            $sm_ak = $this->get_sm_ak($valid['pid']);
            foreach ($entry_res as $entry) {

                $status = '';
                $status_data = $valid['pid'] . ',\'' . $entry['kentry_id'] . '\',' . $entry['status'];
                if ($entry['status'] == 1) {
                    $status = '<div class="alert alert-success">Active</div>';
                    $status_text = 'Block';
                } else {
                    $status = '<div class="alert alert-danger">Blocked</div>';
                    $status_text = 'Unblock';
                }

                $content_type = '';
                if ($entry['media_type'] == '100' || $entry['media_type'] == '101' || $entry['media_type'] == '1' || $entry['media_type'] == '5') {
                    $content_type = 'Single Entry';
                    $mode = 's';
                    $player_uiconf = '6710347';
                    $player_width = '400';
                    $player_height = '333';
                    $wrap_width = '400';
                } elseif ($entry['media_type'] == '3') {
                    $content_type = 'Playlist';
                    $mode = 'p';
                    $player_uiconf = '6709427';
                    $player_width = '680';
                    $player_height = '333';
                    $wrap_width = '680';
                } elseif ($entry['media_type'] == '6') {
                    $content_type = 'Category';
                    $mode = 'cr';
                    $player_uiconf = '6710347';
                    $player_width = '400';
                    $player_height = '333';
                    $wrap_width = '800';
                }

                $delete_data = $valid['pid'] . ',\'' . $entry['kentry_id'] . '\',' . $entry['media_type'];
                $edit_data = '\'' . $entry['kentry_id'] . '\',\'' . $entry['kentry_name'] . '\',' . $entry['media_type'] . ',' . $entry['ac_id'] . ',\'' . $entry['ac_name'] . '\'';

                $actions = '<span class="dropdown header">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-default"><span class="text">Edit</span></button>
                                        <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu" data-toggle="dropdown" aria-expanded="true"><span class="caret"></span></button>
                                        <ul class="dropdown-menu" id="menu" role="menu" aria-labelledby="dropdownMenu"> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhMEM.editEntry(' . $edit_data . ');">Entry</a></li> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhMEM.previewEmbed(\'' . $sm_ak['sm_ak'] . '\',' . $player_uiconf . ',' . $player_width . ',' . $player_height . ',\'' . $entry['kentry_id'] . '\',\'' . $mode . '\',' . $wrap_width . ');">Preview & Embed</a></li> 
                                            <li role="presentation"><a role="menuitem" tabindex="-1" onclick="smhMEM.statusEntry(' . $status_data . ');">' . $status_text . '</a></li>
                                            <li role="presentation" style="border-top: solid 1px #f0f0f0;"><a role="menuitem" tabindex="-1" onclick="smhMEM.deleteEntry(' . $delete_data . ');">Delete</a></li>
                                        </ul>
                                    </div>
                                </span>';

                $row = array();
                $row[] = $status;
                $row[] = "<div class='data-break'>" . $entry['kentry_id'] . "</div>";
                $row[] = "<div class='data-break'>" . $entry['kentry_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $content_type . "</div>";
                $row[] = "<div class='data-break'>" . $entry['ac_name'] . "</div>";
                $row[] = "<div class='data-break'>" . $entry['created_at'] . "</div>";
                $row[] = "<div class='data-break'>" . $entry['updated_at'] . "</div>";
                $row[] = $actions;
                $output['data'][] = $row;
            }
            if (isset($draw)) {
                $output["draw"] = intval($draw);
            }
        }

        return $output;
    }

    public function get_sm_ak($pid) {
        $success = array('success' => false);
        $this->entry->select('*')
                ->from('owner')
                ->where('partner_id', $pid);

        $query = $this->entry->get();
        if ($query->num_rows() > 0) {
            $res = $query->result_array();
            foreach ($res as $r) {
                $sm_ak = $r['access_key'];
            }
            $success = array('success' => true, 'sm_ak' => $sm_ak);
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function get_regular_player_details($ks, $pid) {
        $url = 'http://mediaplatform.streamingmediahosting.com/index.php/kmc/getuiconfs';
        $fields = array(
            'ks' => urlencode($ks),
            'partner_id' => urlencode($pid),
            'type' => 'player'
        );

        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode($result);

        $player = array();
        foreach ($resp as $r) {
            $player['uiconf'] = $r->id;
            $player['width'] = $r->width;
            $player['height'] = $r->height;
            break;
        }

        return $player;
    }

    public function get_playlist_player_details($ks, $pid) {
        $url = 'http://mediaplatform.streamingmediahosting.com/index.php/kmc/getuiconfs';
        $fields = array(
            'ks' => urlencode($ks),
            'partner_id' => urlencode($pid),
            'type' => 'playlist'
        );

        $fields_string = '';
        foreach ($fields as $key => $value) {
            $fields_string .= $key . '=' . $value . '&';
        }
        rtrim($fields_string, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        $resp = json_decode($result);

        $player = array();
        foreach ($resp as $r) {
            $player['uiconf'] = $r->id;
            $player['width'] = $r->width;
            $player['height'] = $r->height;
            break;
        }

        return $player;
    }

    public function list_entry_names($pid) {
        // Query the Db
        $this->entry->select('*')
                ->from('mem_entry')
                ->where('partner_id', $pid);

        $query = $this->entry->get();
        $entry_res = $query->result_array();

        $row = array();
        foreach ($entry_res as $entry) {
            $row[$entry['entry_id']] = $entry['kentry_id'];
        }

        return $row;
    }

    public function check_entry_ppv($pid, $kentry_id) {
        $success = false;
        $this->entry->select('*')
                ->from('entry')
                ->where('kentry_id = "' . $this->entry->escape_str($kentry_id) . '" AND partner_id = "' . $pid . '"');
        $query = $this->entry->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function update_entry_ppv($pid, $kentry_id, $ac_id, $ac_name) {
        $success = array('success' => false);
        $data = array(
            'ac_id' => $ac_id,
            'ac_name' => $ac_name,
            'updated_at' => date("Y-m-d h:i:s")
        );

        $this->entry->where('kentry_id = "' . $kentry_id . '" AND partner_id = "' . $pid . '"');
        $this->entry->update('entry', $data);
        $this->entry->limit(1);
        if ($this->entry->affected_rows() > 0) {
            $success = array('success' => true);
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function add_entry($pid, $ks, $kentry_id, $kentry_name, $ac_id, $ac_name, $media_type, $status) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if (!$this->check_entry($valid['pid'], $kentry_id)) {
                $data = array(
                    'partner_id' => $this->entry->escape_str(trim($valid['pid'])),
                    'kentry_id' => $this->entry->escape_str(trim($kentry_id)),
                    'kentry_name' => $this->entry->escape_str(trim($kentry_name)),
                    'ac_id' => $this->entry->escape_str(trim($ac_id)),
                    'ac_name' => $this->entry->escape_str(trim($ac_name)),
                    'media_type' => $this->entry->escape_str(trim($media_type)),
                    'created_at' => date("Y-m-d h:i:s"),
                    'status' => $this->entry->escape_str(trim($status))
                );

                $this->entry->insert('mem_entry', $data);
                if ($this->entry->affected_rows() > 0) {
                    if ($this->check_entry_ppv($pid, $kentry_id)) {
                        $this->update_entry_ppv($pid, $kentry_id, $ac_id, $ac_name);
                    }
                    if ($media_type == 6) {
                        $entries_list = $this->get_platform_cat_entries($valid['pid'], $kentry_id);
                        foreach ($entries_list as $entry) {
                            $this->entry->select('*')
                                    ->from('mem_entry')
                                    ->where('kentry_id', $entry['entry_id']);
                            $query = $this->entry->get();
                            if ($query->num_rows() > 0) {
                                $this->update_ac_id($valid['pid'], $entry['entry_id'], $ac_id, $ac_name);
                            }
                            if ($this->check_entry_ppv($pid, $entry['entry_id'])) {
                                $this->update_entry_ppv($pid, $entry['entry_id'], $ac_id, $ac_name);
                            }
                        }
                        $res = $this->smportal->update_entry_ac($valid['pid'], $kentry_id, $ac_id, $media_type);
                        if ($res) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else if ($media_type == 3) {
                        $content_list = $this->smportal->get_playlist_content_entry($valid['pid'], $kentry_id);
                        $content = explode(",", $content_list);

                        foreach ($content as $c) {
                            $this->entry->select('*')
                                    ->from('mem_entry')
                                    ->where('kentry_id', $c);
                            $query = $this->entry->get();
                            if ($query->num_rows() > 0) {
                                $this->update_ac_id($valid['pid'], $c, $ac_id, $ac_name);
                            }
                            if ($this->check_entry_ppv($pid, $c)) {
                                $this->update_entry_ppv($pid, $c, $ac_id, $ac_name);
                            }
                        }
                        $res = $this->smportal->update_entry_ac($valid['pid'], $kentry_id, $ac_id, $media_type);
                        if ($res) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $res = $this->smportal->update_entry_ac($valid['pid'], $kentry_id, $ac_id, $media_type);
                        if ($res) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'entry already exist');
            }
        } else {
            $success = array('success' => false);
        }

        return $success;
    }

    public function update_entry($pid, $ks, $kentry_id, $kentry_name, $ac_id, $ac_name, $media_type) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_entry($valid['pid'], $kentry_id)) {
                $data = array(
                    'kentry_name' => $this->entry->escape_str(trim($kentry_name)),
                    'ac_id' => $this->entry->escape_str(trim($ac_id)),
                    'ac_name' => $this->entry->escape_str(trim($ac_name)),
                    'media_type' => $this->entry->escape_str(trim($media_type)),
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->entry->where('kentry_id = "' . $kentry_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->entry->update('mem_entry', $data);
                if ($this->entry->affected_rows() > 0) {
                    if ($this->check_entry_ppv($pid, $kentry_id)) {
                        $this->update_entry_ppv($pid, $kentry_id, $ac_id, $ac_name);
                    }
                    if ($media_type == 6) {
                        $entries_list = $this->get_platform_cat_entries($valid['pid'], $kentry_id);
                        foreach ($entries_list as $entry) {
                            $this->entry->select('*')
                                    ->from('mem_entry')
                                    ->where('kentry_id', $entry['entry_id']);
                            $query = $this->entry->get();
                            if ($query->num_rows() > 0) {
                                $this->update_ac_id($valid['pid'], $entry['entry_id'], $ac_id, $ac_name);
                            }
                            if ($this->check_entry_ppv($pid, $entry['entry_id'])) {
                                $this->update_entry_ppv($pid, $entry['entry_id'], $ac_id, $ac_name);
                            }
                        }
                        $res = $this->smportal->update_entry_ac($valid['pid'], $kentry_id, $ac_id, $media_type);
                        if ($res) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else if ($media_type == 3) {
                        $content_list = $this->smportal->get_playlist_content_entry($valid['pid'], $kentry_id);
                        $content = explode(",", $content_list);

                        foreach ($content as $c) {
                            $this->entry->select('*')
                                    ->from('mem_entry')
                                    ->where('kentry_id', $c);
                            $query = $this->entry->get();
                            if ($query->num_rows() > 0) {
                                $this->update_ac_id($valid['pid'], $c, $ac_id, $ac_name);
                            }
                            if ($this->check_entry_ppv($pid, $c)) {
                                $this->update_entry_ppv($pid, $c, $ac_id, $ac_name);
                            }
                        }
                        $res = $this->smportal->update_entry_ac($valid['pid'], $kentry_id, $ac_id, $media_type);
                        if ($res) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    } else {
                        $res = $this->smportal->update_entry_ac($valid['pid'], $kentry_id, $ac_id, $media_type);
                        if ($res) {
                            $success = array('success' => true);
                        } else {
                            $success = array('success' => false);
                        }
                    }
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'entry does not exist');
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function delete_entry($pid, $ks, $kentry_id, $media_type) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_entry($valid['pid'], $kentry_id)) {
                $this->entry->where('kentry_id = "' . $kentry_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->entry->delete('mem_entry');
                $this->entry->limit(1);
                if ($this->entry->affected_rows() > 0) {
                    if ($media_type == 3) {
                        $content_list = $this->smportal->get_playlist_content_entry($valid['pid'], $kentry_id);
                        $content = explode(",", $content_list);

                        foreach ($content as $c) {
                            $this->entry->select('*')
                                    ->from('mem_entry')
                                    ->where('kentry_id', $c);
                            $query = $this->entry->get();
                            if ($query->num_rows() <= 0) {
                                if (!$this->check_entry_ppv($pid, $c)) {
                                    $this->smportal->set_entry_ac_platform_default($valid['pid'], $c);
                                }
                            }
                        }
                    } else if ($media_type == 6) {
                        $entries_list = $this->get_platform_cat_entries($valid['pid'], $kentry_id);
                        foreach ($entries_list as $entry) {
                            $this->entry->select('*')
                                    ->from('mem_entry')
                                    ->where('kentry_id', $entry['entry_id']);
                            $query = $this->entry->get();
                            if ($query->num_rows() <= 0) {
                                if (!$this->check_entry_ppv($pid, $entry['entry_id'])) {
                                    $this->smportal->set_entry_ac_platform_default($valid['pid'], $entry['entry_id']);
                                }
                            }
                        }
                    } else {
                        $cat_id = $this->smportal->get_cat_id($valid['pid'], $kentry_id);
                        $this->entry->select('*')
                                ->from('mem_entry')
                                ->where('kentry_id', $cat_id);
                        $query = $this->entry->get();
                        if ($query->num_rows() <= 0) {
                            if (!$this->check_entry_ppv($pid, $kentry_id)) {
                                $this->smportal->set_entry_ac_default($valid['pid'], $kentry_id, $media_type);
                            }
                        }
                    }

                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'entry does not exist');
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function delete_platform_entry($pid, $ks, $kentry_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_entry($valid['pid'], $kentry_id)) {
                $this->entry->where('kentry_id = "' . $kentry_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->entry->delete('mem_entry');
                $this->entry->limit(1);
                if ($this->entry->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'entry does not exist');
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function delete_playlist_entry($pid, $ks, $kentry_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_entry($valid['pid'], $kentry_id)) {
                $this->entry->where('kentry_id = "' . $kentry_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->entry->delete('mem_entry');
                $this->entry->limit(1);
                if ($this->entry->affected_rows() > 0) {
                    $content_list = $this->smportal->get_playlist_content_entry($valid['pid'], $kentry_id);
                    $content = explode(",", $content_list);

                    foreach ($content as $c) {
                        $this->entry->select('*')
                                ->from('mem_entry')
                                ->where('kentry_id', $c);
                        $query = $this->entry->get();
                        if ($query->num_rows() <= 0) {
                            $this->smportal->set_entry_ac_platform_default($valid['pid'], $c);
                        }
                    }

                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'entry does not exist');
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function update_status($pid, $ks, $kentry_id, $status) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_entry($valid['pid'], $kentry_id)) {
                $data = array(
                    'status' => $status,
                    'updated_at' => date("Y-m-d h:i:s")
                );

                $this->entry->where('kentry_id = "' . $kentry_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->entry->update('mem_entry', $data);
                $this->entry->limit(1);
                if ($this->entry->affected_rows() > 0) {
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'entry does not exist');
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

    public function check_entry($pid, $kentry_id) {
        $success = false;
        $this->entry->select('*')
                ->from('mem_entry')
                ->where('kentry_id = "' . $this->entry->escape_str($kentry_id) . '" AND partner_id = "' . $pid . '"');
        $query = $this->entry->get();
        if ($query->num_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function get_entry_details($sm_ak, $entryId) {
        $pid = $this->smcipher->decrypt($sm_ak);
        return $this->smportal->get_entry_details($pid, $entryId);
    }

    public function get_cat_details($sm_ak, $kentry_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        return $this->smportal->get_cat_details($pid, $kentry_id);
    }

    public function verfiy_ks($pid, $ks) {
        return $this->smportal->verify_ks($pid, $ks);
    }

    public function update_ac_id($pid, $entry_id, $ac_id, $ac_name) {
        $success = false;
        $data = array(
            'ac_id' => $ac_id,
            'ac_name' => $ac_name,
            'updated_at' => date("Y-m-d h:i:s")
        );

        $this->entry->where('kentry_id', $entry_id);
        $this->entry->where('partner_id', $pid);
        $this->entry->update('mem_entry', $data);
        $this->entry->limit(1);
        if ($this->entry->affected_rows() > 0) {
            $success = true;
        } else {
            $success = false;
        }

        return $success;
    }

    public function check_update_ac($pid, $ks, $playlist_id, $playlist) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        $ac = '';

        if ($valid['success']) {
            $this->entry->select('*')
                    ->from('mem_entry')
                    ->where('kentry_id', $playlist_id);
            $query = $this->entry->get();
            if ($query->num_rows() > 0) {
                $result = $query->result_array();
                foreach ($result as $res) {
                    $ac = $res['ac_id'];
                }
                $ac_result = $this->smportal->update_platform_entry_ac($valid['pid'], $playlist_id, $ac);

                if ($ac_result) {
                    $content_list = $this->smportal->get_playlist_content_entry($valid['pid'], $playlist_id);
                    $content = explode(",", $content_list);
                    $playlist = explode(",", $playlist);

                    //if (count($playlist) > count($content)) {
                    $arr_diff = array_diff($playlist, $content);

                    foreach ($arr_diff as $entry) {
                        $this->entry->select('*')
                                ->from('mem_entry')
                                ->where('kentry_id', $entry);
                        $query = $this->entry->get();
                        if ($query->num_rows() <= 0) {
                            $this->smportal->set_entry_ac_platform_default($valid['pid'], $entry);
                        }
                    }
                    //}

                    foreach ($content as $c) {
                        $this->entry->select('*')
                                ->from('mem_entry')
                                ->where('kentry_id', $c);
                        $query = $this->entry->get();
                        if ($query->num_rows() > 0) {
                            $this->update_ac_id($valid['pid'], $c, $ac);
                        }
                    }

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

    public function get_cat_entries($sm_ak, $cat_id) {
        $pid = $this->smcipher->decrypt($sm_ak);
        return $this->smportal->get_cat_entries($pid, $cat_id);
    }

    public function get_platform_cat_entries($pid, $cat_id) {
        return $this->smportal->get_cat_entries($pid, $cat_id);
    }

    public function update_platform_cat($pid, $ks, $cat, $entry_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $cat_list = explode(",", $cat);
            foreach ($cat_list as $c) {
                $cat_id = $this->smportal->get_cat_id_from_name($valid['pid'], $c);
                $this->entry->select('*')
                        ->from('mem_entry')
                        ->where('kentry_id', $cat_id);
                $query = $this->entry->get();
                if ($query->num_rows() > 0) {
                    $ac_id = $this->get_ac_id($pid, $cat_id);
                    $res = $this->smportal->update_entry_ac($valid['pid'], $cat_id, $ac_id['ac_id'], $ac_id['media_type']);
                    if ($res) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                } else {
                    $res = $this->smportal->set_entry_ac_platform_default($valid['pid'], $entry_id);
                    if ($res) {
                        $success = array('success' => true);
                    } else {
                        $success = array('success' => false);
                    }
                }
            }
        }

        return $success;
    }

    public function get_ac_id($pid, $entry_id) {
        $this->entry->select('*')
                ->from('mem_entry')
                ->where('partner_id', $pid)
                ->where('kentry_id', $entry_id);

        $query = $this->entry->get();
        if ($query->num_rows() > 0) {
            $res = $query->result_array();

            $result = array();
            foreach ($res as $r) {
                $result['ac_id'] = $r['ac_id'];
                $result['media_type'] = $r['media_type'];
            }
        }

        return $result;
    }

    public function update_drag_cat($pid, $ks, $cat_id, $entry_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            $this->entry->select('*')
                    ->from('mem_entry')
                    ->where('kentry_id', $cat_id);
            $query = $this->entry->get();
            if ($query->num_rows() > 0) {
                $ac_id = $this->get_ac_id($valid['pid'], $cat_id);
                $media_type = $this->smportal->get_mediaType_cat($valid['pid'], $entry_id);
                $res = $this->smportal->update_entry_ac($valid['pid'], $entry_id, $ac_id['ac_id'], $media_type);
                if ($res) {
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

    public function delete_platform_cat($pid, $ks, $cat_id) {
        $success = array('success' => false);
        $valid = $this->verfiy_ks($pid, $ks);
        if ($valid['success']) {
            if ($this->check_entry($valid['pid'], $cat_id)) {
                $this->entry->where('kentry_id = "' . $cat_id . '" AND partner_id = "' . $valid['pid'] . '"');
                $this->entry->delete('mem_entry');
                $this->entry->limit(1);
                if ($this->entry->affected_rows() > 0) {
                    $entries_list = $this->get_platform_cat_entries($valid['pid'], $cat_id);
                    foreach ($entries_list as $entry) {
                        $this->entry->select('*')
                                ->from('mem_entry')
                                ->where('kentry_id', $entry['entry_id']);
                        $query = $this->entry->get();
                        if ($query->num_rows() <= 0) {
                            $this->smportal->set_entry_ac_platform_default($valid['pid'], $entry['entry_id']);
                        }
                    }
                    $success = array('success' => true);
                } else {
                    $success = array('success' => false);
                }
            } else {
                $success = array('error' => 'entry does not exist');
            }
        } else {
            $success = array('success' => false);
        }
        return $success;
    }

}