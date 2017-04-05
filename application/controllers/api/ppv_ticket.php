<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Ppv_ticket extends REST_Controller {

    public function __construct() {

        parent::__construct();
        $this->load->model('ppv_ticket_model');
    }

    public function list_tickets_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');
        $currency = $this->get('currency');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $ticketList = $this->ppv_ticket_model->get_ticket_list($pid, $ks, $start, $length, $search, $draw, $currency);

        if (!$ticketList) {

            $this->response($ticketList, 200);
        }

        $this->response($ticketList, 200); // 200 being the HTTP response code
    }

    public function list_entry_tickets_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');
        $currency = $this->get('currency');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $ticketNames = $this->ppv_ticket_model->list_entry_tickets($pid, $ks, $start, $length, $search, $draw, $currency);

        if (!$ticketNames) {

            $this->response($ticketNames, 200);
        }

        $this->response($ticketNames, 200); // 200 being the HTTP response code
    }

    public function get_ticket_name_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $ids = $this->get('ids');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($ids) || $ids == null) {

            $this->response(array('error' => 'Missing ids'), 200);
        }

        $ticketName = $this->ppv_ticket_model->get_ticket_name($pid, $ks, $ids);

        if (!$ticketName) {

            $this->response($ticketName, 200);
        }

        $this->response($ticketName, 200); // 200 being the HTTP response code        
    }

    public function get_tickets_get() {
        $sm_ak = $this->get('sm_ak');
        $kentry = $this->get('kentry');
        $type = $this->get('type');
        $protocol = $this->get('protocol');
        $logged_in = $this->get('logged_in');
        $has_start = $this->get('has_start');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($kentry) || $kentry == null) {

            $this->response(array('error' => 'Missing entry id'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($protocol) || $protocol == null) {

            $this->response(array('error' => 'Missing protocol'), 200);
        }

        $tickets = $this->ppv_ticket_model->get_tickets($sm_ak, $kentry, $type, $protocol, $logged_in, $has_start);

        if (!$tickets) {

            $this->response($tickets, 200);
        }

        $this->response($tickets, 200); // 200 being the HTTP response code  
    }

    public function add_ticket_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $ticket_name = $this->get('ticket_name');
        $ticket_desc = $this->get('ticket_desc');
        $ticket_price = $this->get('ticket_price');
        $ticket_type = $this->get('ticket_type');
        $expires = $this->get('expires');
        $expiry_config = $this->get('expiry_config');
        $max_views = $this->get('max_views');
        $status = $this->get('status');
        $tz = $this->get('tz');
        $currency = $this->get('currency');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($ticket_name) || $ticket_name == null) {

            $this->response(array('error' => 'Missing ticket name'), 200);
        }

        if (!isset($ticket_price) || $ticket_price == null) {

            $this->response(array('error' => 'Missing ticket price'), 200);
        }

        if (!isset($ticket_type) || $ticket_type == null) {

            $this->response(array('error' => 'Missing ticket type'), 200);
        }

        if (!isset($expires) || $expires == null) {

            $this->response(array('error' => 'Missing expiration'), 200);
        }

        if (!isset($expiry_config) || $expiry_config == null) {

            $this->response(array('error' => 'Missing expiration configuration'), 200);
        }

        if (!isset($max_views) || $max_views == null) {

            $this->response(array('error' => 'Missing max views'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $addTicket = $this->ppv_ticket_model->add_ticket($pid, $ks, $ticket_name, $ticket_desc, $ticket_price, $expires, $expiry_config, $max_views, $status, $tz, $currency, $ticket_type);

        if (!$addTicket) {

            $this->response($addTicket, 200);
        }

        $this->response($addTicket, 200); // 200 being the HTTP response code
    }

    public function update_ticket_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $ticket_id = $this->get('ticket_id');
        $ticket_name = $this->get('ticket_name');
        $ticket_desc = $this->get('ticket_desc');
        $ticket_price = $this->get('ticket_price');
        $ticket_type = $this->get('ticket_type');
        $expires = $this->get('expires');
        $expiry_config = $this->get('expiry_config');
        $max_views = $this->get('max_views');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket id'), 200);
        }

        if (!isset($ticket_name) || $ticket_name == null) {

            $this->response(array('error' => 'Missing ticket name'), 200);
        }

        if (!isset($ticket_price) || $ticket_price == null) {

            $this->response(array('error' => 'Missing ticket price'), 200);
        }

        if (!isset($ticket_type) || $ticket_type == null) {

            $this->response(array('error' => 'Missing ticket type'), 200);
        }

        if (!isset($expires) || $expires == null) {

            $this->response(array('error' => 'Missing expiration'), 200);
        }

        if (!isset($expiry_config) || $expiry_config == null) {

            $this->response(array('error' => 'Missing expiration configuration'), 200);
        }

        if (!isset($max_views) || $max_views == null) {

            $this->response(array('error' => 'Missing max views'), 200);
        }

        $updateTicket = $this->ppv_ticket_model->update_ticket($pid, $ks, $ticket_id, $ticket_name, $ticket_desc, $ticket_price, $expires, $expiry_config, $max_views, $tz, $ticket_type);

        if (!$updateTicket) {

            $this->response($updateTicket, 200);
        }

        $this->response($updateTicket, 200); // 200 being the HTTP response code
    }

    public function delete_ticket_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $ticket_id = $this->get('ticket_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket id'), 200);
        }

        $deleteTicket = $this->ppv_ticket_model->delete_ticket($pid, $ks, $ticket_id);

        if (!$deleteTicket) {

            $this->response($deleteTicket, 200);
        }

        $this->response($deleteTicket, 200); // 200 being the HTTP response code
    }

    public function update_ticket_status_get() {

        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $ticket_id = $this->get('ticket_id');
        $status = $this->get('status');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket id'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateTicketStatus = $this->ppv_ticket_model->update_status($pid, $ks, $ticket_id, $status);

        if (!$updateTicketStatus) {

            $this->response($updateTicketStatus, 200);
        }

        $this->response($updateTicketStatus, 200); // 200 being the HTTP response code
    }

    public function get_ticket_price_get() {

        $sm_ak = $this->get('sm_ak');
        $ticket_id = $this->get('ticket_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket id'), 200);
        }

        $ticketPrice = $this->ppv_ticket_model->get_ticket_price($sm_ak, $ticket_id);

        if (!$ticketPrice) {

            $this->response($ticketPrice, 200);
        }

        $this->response($ticketPrice, 200); // 200 being the HTTP response code
    }

}