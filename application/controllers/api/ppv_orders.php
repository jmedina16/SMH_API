<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class Ppv_orders extends REST_Controller {

    public function __construct() {

        parent::__construct();
        $this->load->model('ppv_orders_model');
    }

    public function list_order_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $orderList = $this->ppv_orders_model->get_order_list($pid, $ks, $start, $length, $search, $draw, $tz);

        if (!$orderList) {

            $this->response($orderList, 200);
        }

        $this->response($orderList, 200); // 200 being the HTTP response code
    }

    public function list_user_orders_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $draw = $this->get('draw');
        $search = $this->get('search');
        $uid = $this->get('uid');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $userOrderList = $this->ppv_orders_model->get_user_orders_list($pid, $ks, $uid, $start, $length, $search, $draw, $tz);

        if (!$userOrderList) {

            $this->response($userOrderList, 200);
        }

        $this->response($userOrderList, 200); // 200 being the HTTP response code
    }

    public function w_list_user_orders_get() {
        $sm_ak = $this->get('sm_ak');
        $auth_key = $this->get('auth_key');
        $start = $this->get('start');
        $length = $this->get('length');
        $draw = $this->get('draw');
        $uid = $this->get('uid');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($auth_key) || $auth_key == null) {

            $this->response(array('error' => 'Missing auth_key'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $userOrderList = $this->ppv_orders_model->w_get_user_orders_list($sm_ak, $auth_key, $uid, $start, $length, $draw);

        if (!$userOrderList) {

            $this->response($userOrderList, 200);
        }

        $this->response($userOrderList, 200); // 200 being the HTTP response code
    }

    public function w_list_user_subs_get() {
        $sm_ak = $this->get('sm_ak');
        $auth_key = $this->get('auth_key');
        $start = $this->get('start');
        $length = $this->get('length');
        $draw = $this->get('draw');
        $uid = $this->get('uid');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($auth_key) || $auth_key == null) {

            $this->response(array('error' => 'Missing auth_key'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $userOrderList = $this->ppv_orders_model->w_list_user_subs_list($sm_ak, $auth_key, $uid, $start, $length, $draw);

        if (!$userOrderList) {

            $this->response($userOrderList, 200);
        }

        $this->response($userOrderList, 200); // 200 being the HTTP response code
    }

    public function add_order_get() {
        $sm_ak = $this->get('sm_ak');
        $entry_id = $this->get('entry_id');
        $user_id = $this->get('user_id');
        $ticket_id = $this->get('ticket_id');
        $tz = $this->get('tz');
        $gw_type = $this->get('gw_type');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        if (!isset($user_id) || $user_id == null) {

            $this->response(array('error' => 'Missing user_id'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($gw_type) || $gw_type == null) {

            $this->response(array('error' => 'Missing gw_type'), 200);
        }

        $addOrder = $this->ppv_orders_model->add_order($sm_ak, $entry_id, $user_id, $ticket_id, $tz, $gw_type);

        if (!$addOrder) {

            $this->response($addOrder, 200);
        }

        $this->response($addOrder, 200); // 200 being the HTTP response code
    }

    public function update_order_get() {
        $pid = $this->get('pid');
        $entry_id = $this->get('entry_id');
        $user_id = $this->get('user_id');
        $ticket_id = $this->get('ticket_id');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        if (!isset($user_id) || $user_id == null) {

            $this->response(array('error' => 'Missing user_id'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateOrder = $this->ppv_orders_model->update_order($pid, $entry_id, $user_id, $ticket_id, $status, $tz);

        if (!$updateOrder) {

            $this->response($updateOrder, 200);
        }

        $this->response($updateOrder, 200); // 200 being the HTTP response code
    }

    public function delete_order_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $order_id = $this->get('order_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        $deleteOrder = $this->ppv_orders_model->delete_order($pid, $ks, $order_id);

        if (!$deleteOrder) {

            $this->response($deleteOrder, 200);
        }

        $this->response($deleteOrder, 200); // 200 being the HTTP response code
    }

    public function refund_authnet_order_get() {
        $invoice_num = $this->get('invoice_num');

        if (!isset($invoice_num) || $invoice_num == null) {

            $this->response(array('error' => 'Missing invoice_num'), 200);
        }

        $refundOrder = $this->ppv_orders_model->refund_authnet_order($invoice_num);

        if (!$refundOrder) {

            $this->response($refundOrder, 200);
        }

        $this->response($refundOrder, 200); // 200 being the HTTP response code
    }

    public function refund_order_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $order_id = $this->get('order_id');
        $ticket_type = $this->get('ticket_type');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($ticket_type) || $ticket_type == null) {

            $this->response(array('error' => 'Missing ticket_type'), 200);
        }

        $refundOrder = $this->ppv_orders_model->refund_order($pid, $ks, $order_id, $ticket_type);

        if (!$refundOrder) {

            $this->response($refundOrder, 200);
        }

        $this->response($refundOrder, 200); // 200 being the HTTP response code
    }

    public function w_delete_order_get() {
        $sm_ak = $this->get('sm_ak');
        $order_id = $this->get('order_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        $deleteOrder = $this->ppv_orders_model->w_delete_order($sm_ak, $order_id);

        if (!$deleteOrder) {

            $this->response($deleteOrder, 200);
        }

        $this->response($deleteOrder, 200); // 200 being the HTTP response code
    }

    public function update_order_payment_status_get() {
        $pid = $this->get('pid');
        $entry_id = $this->get('entry_id');
        $user_id = $this->get('user_id');
        $ticket_id = $this->get('ticket_id');
        $status = $this->get('status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        if (!isset($user_id) || $user_id == null) {

            $this->response(array('error' => 'Missing user_id'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateOrderStatus = $this->ppv_orders_model->update_order_payment_status($pid, $entry_id, $user_id, $ticket_id, $status, $tz);

        if (!$updateOrderStatus) {

            $this->response($updateOrderStatus, 200);
        }

        $this->response($updateOrderStatus, 200); // 200 being the HTTP response code
    }

    public function update_order_status_get() {
        $pid = $this->get('pid');
        $entry_id = $this->get('entry_id');
        $user_id = $this->get('user_id');
        $ticket_id = $this->get('ticket_id');
        $order_status = $this->get('order_status');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        if (!isset($user_id) || $user_id == null) {

            $this->response(array('error' => 'Missing user_id'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($order_status) || $order_status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $updateOrderStatus = $this->ppv_orders_model->update_order_status($pid, $entry_id, $user_id, $ticket_id, $order_status, $tz);

        if (!$updateOrderStatus) {

            $this->response($updateOrderStatus, 200);
        }

        $this->response($updateOrderStatus, 200); // 200 being the HTTP response code
    }

    public function complete_order_get() {
        $sm_ak = $this->get('sm_ak');
        $entry_id = $this->get('entry_id');
        $user_id = $this->get('user_id');
        $ticket_id = $this->get('ticket_id');
        $ticket_type = $this->get('ticket_type');
        $tz = $this->get('tz');
        $order_id = $this->get('order_id');
        $payment_status = $this->get('payment_status');
        $smh_aff = $this->get('smh_aff');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        if (!isset($user_id) || $user_id == null) {

            $this->response(array('error' => 'Missing user_id'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($ticket_type) || $ticket_type == null) {

            $this->response(array('error' => 'Missing ticket_type'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($payment_status) || $payment_status == null) {

            $this->response(array('error' => 'Missing payment_status'), 200);
        }

        $completeOrder = $this->ppv_orders_model->complete_order($sm_ak, $entry_id, $user_id, $ticket_id, $ticket_type, $order_id, $payment_status, $tz, $smh_aff);

        if (!$completeOrder) {

            $this->response($completeOrder, 200);
        }

        $this->response($completeOrder, 200); // 200 being the HTTP response code
    }

    public function finish_order_get() {
        $sm_ak = $this->get('sm_ak');
        $entry_id = $this->get('entry_id');
        $user_id = $this->get('user_id');
        $ticket_id = $this->get('ticket_id');
        $ticket_type = $this->get('ticket_type');
        $tz = $this->get('tz');
        $order_id = $this->get('order_id');
        $payment_status = $this->get('payment_status');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entry_id) || $entry_id == null) {

            $this->response(array('error' => 'Missing entry_id'), 200);
        }

        if (!isset($user_id) || $user_id == null) {

            $this->response(array('error' => 'Missing user_id'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($ticket_type) || $ticket_type == null) {

            $this->response(array('error' => 'Missing ticket_type'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($payment_status) || $payment_status == null) {

            $this->response(array('error' => 'Missing payment_status'), 200);
        }

        $finishOrder = $this->ppv_orders_model->finish_order($sm_ak, $entry_id, $user_id, $ticket_id, $ticket_type, $order_id, $payment_status, $tz);

        if (!$finishOrder) {

            $this->response($finishOrder, 200);
        }

        $this->response($finishOrder, 200); // 200 being the HTTP response code
    }

    public function check_inventory_get() {
        $sm_ak = $this->get('sm_ak');
        $type = $this->get('type');
        $entryId = $this->get('entryId');
        $uid = $this->get('uid');
        $tz = $this->get('tz');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $inventory = $this->ppv_orders_model->check_inventory($sm_ak, $uid, $entryId, $type, $tz);

        if (!$inventory) {

            $this->response($inventory, 200);
        }

        $this->response($inventory, 200); // 200 being the HTTP response code
    }

    public function check_cat_inventory_get() {
        $sm_ak = $this->get('sm_ak');
        $entryId = $this->get('entryId');
        $access = $this->get('access');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        if (!isset($access) || $access == null) {

            $this->response(array('error' => 'Missing access'), 200);
        }

        $inventory = $this->ppv_orders_model->check_cat_inventory($sm_ak, $entryId, $access);

        if (!$inventory) {

            $this->response($inventory, 200);
        }

        $this->response($inventory, 200); // 200 being the HTTP response code
    }

    public function update_views_get() {
        $sm_ak = $this->get('sm_ak');
        $entryId = $this->get('entryId');
        $uid = $this->get('uid');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $views = $this->ppv_orders_model->update_views($sm_ak, $uid, $entryId);

        if (!$views) {
            $this->response($views, 200);
        }

        $this->response($views, 200); // 200 being the HTTP response code 
    }

    public function get_confirm_get() {
        $sm_ak = $this->get('sm_ak');
        $entryId = $this->get('entryId');
        $ticket_id = $this->get('ticket_id');
        $type = $this->get('type');
        $protocol = $this->get('protocol');
        $has_start = $this->get('has_start');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($protocol) || $protocol == null) {

            $this->response(array('error' => 'Missing protocol'), 200);
        }

        $confirm = $this->ppv_orders_model->get_confirm($sm_ak, $entryId, $ticket_id, $type, $protocol, $has_start);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function get_checkout_details_get() {
        $sm_ak = $this->get('sm_ak');
        $entryId = $this->get('entryId');
        $kentry = $this->get('kentry');
        $ticket_id = $this->get('ticket_id');
        $type = $this->get('type');
        $uid = $this->get('uid');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($entryId) || $entryId == null) {

            $this->response(array('error' => 'Missing entryId'), 200);
        }

        if (!isset($kentry) || $kentry == null) {

            $this->response(array('error' => 'Missing kentry'), 200);
        }

        if (!isset($ticket_id) || $ticket_id == null) {

            $this->response(array('error' => 'Missing ticket_id'), 200);
        }

        if (!isset($type) || $type == null) {

            $this->response(array('error' => 'Missing type'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $confirm = $this->ppv_orders_model->get_checkout_details($sm_ak, $entryId, $kentry, $ticket_id, $type, $uid);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_authnet_details_get() {
        $sm_ak = $this->get('sm_ak');
        $order_id = $this->get('order_id');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $city = $this->get('city');
        $paymentStatus = $this->get('paymentStatus');
        $avsCode = $this->get('avsCode');
        $authCode = $this->get('authCode');
        $transactionId = $this->get('transactionId');
        $itemName = $this->get('itemName');
        $ticket_type = $this->get('ticket_type');
        $smh_aff = $this->get('smh_aff');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($city) || $city == null) {

            $this->response(array('error' => 'Missing city'), 200);
        }

        if (!isset($paymentStatus) || $paymentStatus == null) {

            $this->response(array('error' => 'Missing paymentStatus'), 200);
        }

        if (!isset($avsCode) || $avsCode == null) {

            $this->response(array('error' => 'Missing avsCode'), 200);
        }

        if (!isset($authCode) || $authCode == null) {

            $this->response(array('error' => 'Missing authCode'), 200);
        }

        if (!isset($transactionId) || $transactionId == null) {

            $this->response(array('error' => 'Missing transactionId'), 200);
        }

        if (!isset($itemName) || $itemName == null) {

            $this->response(array('error' => 'Missing itemName'), 200);
        }

        if (!isset($ticket_type) || $ticket_type == null) {

            $this->response(array('error' => 'Missing ticket_type'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_authnet_details($sm_ak, $order_id, $firstName, $lastName, $payerEmail, $city, $paymentStatus, $avsCode, $authCode, $transactionId, $itemName, $ticket_type, $smh_aff);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_authnet_sub_details_get() {
        $pid = $this->get('pid');
        $uid = $this->get('uid');
        $sub_id = $this->get('sub_id');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $city = $this->get('city');
        $paymentStatus = $this->get('paymentStatus');
        $avsCode = $this->get('avsCode');
        $authCode = $this->get('authCode');
        $transactionId = $this->get('transactionId');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($city) || $city == null) {

            $this->response(array('error' => 'Missing city'), 200);
        }

        if (!isset($paymentStatus) || $paymentStatus == null) {

            $this->response(array('error' => 'Missing paymentStatus'), 200);
        }

        if (!isset($avsCode) || $avsCode == null) {

            $this->response(array('error' => 'Missing avsCode'), 200);
        }

        if (!isset($authCode) || $authCode == null) {

            $this->response(array('error' => 'Missing authCode'), 200);
        }

        if (!isset($transactionId) || $transactionId == null) {

            $this->response(array('error' => 'Missing transactionId'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_authnet_sub_details($pid, $uid, $subscription_id, $firstName, $lastName, $payerEmail, $city, $paymentStatus, $avsCode, $authCode, $transactionId);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_pp_details_get() {
        $sm_ak = $this->get('sm_ak');
        $order_id = $this->get('order_id');
        $receiverEmail = $this->get('receiverEmail');
        $receiverId = $this->get('receiverId');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $payerId = $this->get('payerId');
        $countryCode = $this->get('currencyCode');
        $paymentStatus = $this->get('paymentStatus');
        $transactionId = $this->get('transactionId');
        $paymentType = $this->get('paymentType');
        $orderTime = $this->get('orderTime');
        $itemName = $this->get('itemName');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($receiverEmail) || $receiverEmail == null) {

            $this->response(array('error' => 'Missing receiverEmail'), 200);
        }

        if (!isset($receiverId) || $receiverId == null) {

            $this->response(array('error' => 'Missing receiverId'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($payerId) || $payerId == null) {

            $this->response(array('error' => 'Missing payerId'), 200);
        }

        if (!isset($countryCode) || $countryCode == null) {

            $this->response(array('error' => 'Missing currencyCode'), 200);
        }

        if (!isset($paymentStatus) || $paymentStatus == null) {

            $this->response(array('error' => 'Missing paymentStatus'), 200);
        }

        if (!isset($transactionId) || $transactionId == null) {

            $this->response(array('error' => 'Missing transactionId'), 200);
        }

        if (!isset($paymentType) || $paymentType == null) {

            $this->response(array('error' => 'Missing paymentType'), 200);
        }

        if (!isset($orderTime) || $orderTime == null) {

            $this->response(array('error' => 'Missing orderTime'), 200);
        }

        if (!isset($itemName) || $itemName == null) {

            $this->response(array('error' => 'Missing itemName'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_pp_details($sm_ak, $order_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime, $itemName);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_pp_sub_details_get() {
        $sm_ak = $this->get('sm_ak');
        $profile_id = $this->get('profile_id');
        $order_id = $this->get('order_id');
        $sub_id = $this->get('sub_id');
        $receiverEmail = $this->get('receiverEmail');
        $receiverId = $this->get('receiverId');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $payerId = $this->get('payerId');
        $countryCode = $this->get('countryCode');
        $profileStatus = $this->get('sub_status');
        $payment_cycle = $this->get('bill_per');
        $date_created = $this->get('date_created');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($profile_id) || $profile_id == null) {

            $this->response(array('error' => 'Missing profile_id'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($receiverEmail) || $receiverEmail == null) {

            $this->response(array('error' => 'Missing receiverEmail'), 200);
        }

        if (!isset($receiverId) || $receiverId == null) {

            $this->response(array('error' => 'Missing receiverId'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($payerId) || $payerId == null) {

            $this->response(array('error' => 'Missing payerId'), 200);
        }

        if (!isset($countryCode) || $countryCode == null) {

            $this->response(array('error' => 'Missing countryCode'), 200);
        }

        if (!isset($payment_cycle) || $payment_cycle == null) {

            $this->response(array('error' => 'Missing payment_cycle'), 200);
        }

        if (!isset($profileStatus) || $profileStatus == null) {

            $this->response(array('error' => 'Missing profileStatus'), 200);
        }

        if (!isset($date_created) || $date_created == null) {

            $this->response(array('error' => 'Missing date_created'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_pp_sub_details($sm_ak, $order_id, $sub_id, $profile_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $profileStatus, $payment_cycle, $date_created);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_ipn_pp_details_get() {
        $sm_ak = $this->get('sm_ak');
        $order_id = $this->get('order_id');
        $receiverEmail = $this->get('receiverEmail');
        $receiverId = $this->get('receiverId');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $payerId = $this->get('payerId');
        $countryCode = $this->get('countryCode');
        $paymentStatus = $this->get('paymentStatus');
        $transactionId = $this->get('transactionId');
        $paymentType = $this->get('paymentType');
        $orderTime = $this->get('orderTime');
        $itemName = $this->get('itemName');
        $ticket_type = $this->get('ticket_type');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($receiverEmail) || $receiverEmail == null) {

            $this->response(array('error' => 'Missing receiverEmail'), 200);
        }

        if (!isset($receiverId) || $receiverId == null) {

            $this->response(array('error' => 'Missing receiverId'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($payerId) || $payerId == null) {

            $this->response(array('error' => 'Missing payerId'), 200);
        }

        if (!isset($countryCode) || $countryCode == null) {

            $this->response(array('error' => 'Missing currencyCode'), 200);
        }

        if (!isset($paymentStatus) || $paymentStatus == null) {

            $this->response(array('error' => 'Missing paymentStatus'), 200);
        }

        if (!isset($transactionId) || $transactionId == null) {

            $this->response(array('error' => 'Missing transactionId'), 200);
        }

        if (!isset($paymentType) || $paymentType == null) {

            $this->response(array('error' => 'Missing paymentType'), 200);
        }

        if (!isset($orderTime) || $orderTime == null) {

            $this->response(array('error' => 'Missing orderTime'), 200);
        }

        if (!isset($itemName) || $itemName == null) {

            $this->response(array('error' => 'Missing itemName'), 200);
        }

        if (!isset($ticket_type) || $ticket_type == null) {

            $this->response(array('error' => 'Missing ticket_type'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_ipn_pp_details($sm_ak, $order_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime, $itemName, $ticket_type);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_ipn_pp_sub_details_get() {
        $sm_ak = $this->get('sm_ak');
        $profile_id = $this->get('profile_id');
        $sub_id = $this->get('sub_id');
        $receiverEmail = $this->get('receiverEmail');
        $receiverId = $this->get('receiverId');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $payerId = $this->get('payerId');
        $countryCode = $this->get('countryCode');
        $profileStatus = $this->get('profileStatus');
        $payment_cycle = $this->get('payment_cycle');
        $orderTime = $this->get('orderTime');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($profile_id) || $profile_id == null) {

            $this->response(array('error' => 'Missing profile_id'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($receiverEmail) || $receiverEmail == null) {

            $this->response(array('error' => 'Missing receiverEmail'), 200);
        }

        if (!isset($receiverId) || $receiverId == null) {

            $this->response(array('error' => 'Missing receiverId'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($payerId) || $payerId == null) {

            $this->response(array('error' => 'Missing payerId'), 200);
        }

        if (!isset($countryCode) || $countryCode == null) {

            $this->response(array('error' => 'Missing countryCode'), 200);
        }

        if (!isset($payment_cycle) || $payment_cycle == null) {

            $this->response(array('error' => 'Missing payment_cycle'), 200);
        }

        if (!isset($profileStatus) || $profileStatus == null) {

            $this->response(array('error' => 'Missing profileStatus'), 200);
        }

        if (!isset($orderTime) || $orderTime == null) {

            $this->response(array('error' => 'Missing orderTime'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_ipn_pp_sub_details($sm_ak, $sub_id, $profile_id, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $profileStatus, $payment_cycle, $orderTime);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function list_subs_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $start = $this->get('start');
        $length = $this->get('length');
        $search = $this->get('search');
        $draw = $this->get('draw');
        $tz = $this->get('tz');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        $subsList = $this->ppv_orders_model->list_subs($pid, $ks, $start, $length, $search, $draw, $tz);

        if (!$subsList) {

            $this->response($subsList, 200);
        }

        $this->response($subsList, 200); // 200 being the HTTP response code
    }

    public function update_ipn_sub_order_get() {
        $sm_ak = $this->get('sm_ak');
        $sub_id = $this->get('sub_id');
        $profileStatus = $this->get('profileStatus');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($profileStatus) || $profileStatus == null) {

            $this->response(array('error' => 'Missing profileStatus'), 200);
        }

        $confirm = $this->ppv_orders_model->update_ipn_sub_order($sm_ak, $sub_id, $profileStatus);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_ipn_recurr_order_get() {
        $sm_ak = $this->get('sm_ak');
        $profile_id = $this->get('profile_id');
        $receiverEmail = $this->get('receiverEmail');
        $receiverId = $this->get('receiverId');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $payerId = $this->get('payerId');
        $countryCode = $this->get('countryCode');
        $profileStatus = $this->get('profileStatus');
        $orderTime = $this->get('orderTime');
        $paymentStatus = $this->get('paymentStatus');
        $transactionId = $this->get('transactionId');
        $paymentType = $this->get('paymentType');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($profile_id) || $profile_id == null) {

            $this->response(array('error' => 'Missing profile_id'), 200);
        }

        if (!isset($receiverEmail) || $receiverEmail == null) {

            $this->response(array('error' => 'Missing receiverEmail'), 200);
        }

        if (!isset($receiverId) || $receiverId == null) {

            $this->response(array('error' => 'Missing receiverId'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($payerId) || $payerId == null) {

            $this->response(array('error' => 'Missing payerId'), 200);
        }

        if (!isset($countryCode) || $countryCode == null) {

            $this->response(array('error' => 'Missing countryCode'), 200);
        }

        if (!isset($profileStatus) || $profileStatus == null) {

            $this->response(array('error' => 'Missing profileStatus'), 200);
        }

        if (!isset($orderTime) || $orderTime == null) {

            $this->response(array('error' => 'Missing orderTime'), 200);
        }

        if (!isset($paymentStatus) || $paymentStatus == null) {

            $this->response(array('error' => 'Missing paymentStatus'), 200);
        }

        if (!isset($transactionId) || $transactionId == null) {

            $this->response(array('error' => 'Missing transactionId'), 200);
        }

        if (!isset($paymentType) || $paymentType == null) {

            $this->response(array('error' => 'Missing paymentType'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_ipn_recurr_order($sm_ak, $profile_id, $profileStatus, $receiverEmail, $receiverId, $firstName, $lastName, $payerEmail, $payerId, $countryCode, $paymentStatus, $transactionId, $paymentType, $orderTime);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function w_cancel_sub_get() {
        $sub_id = $this->get('sub_id');
        $sm_ak = $this->get('sm_ak');
        $auth_key = $this->get('auth_key');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($auth_key) || $auth_key == null) {

            $this->response(array('error' => 'Missing auth_key'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        $cancel = $this->ppv_orders_model->w_cancel_sub($sm_ak, $auth_key, $sub_id);

        if (!$cancel) {

            $this->response($cancel, 200);
        }

        $this->response($cancel, 200); // 200 being the HTTP response code
    }

    public function cancel_sub_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $sub_id = $this->get('sub_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        $cancel = $this->ppv_orders_model->cancel_sub($pid, $ks, $sub_id);

        if (!$cancel) {

            $this->response($cancel, 200);
        }

        $this->response($cancel, 200); // 200 being the HTTP response code
    }

    public function delete_sub_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $sub_id = $this->get('sub_id');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        $cancel = $this->ppv_orders_model->delete_sub($pid, $ks, $sub_id);

        if (!$cancel) {

            $this->response($cancel, 200);
        }

        $this->response($cancel, 200); // 200 being the HTTP response code
    }

    public function w_delete_sub_get() {
        $sm_ak = $this->get('sm_ak');
        $sub_id = $this->get('sub_id');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        $cancel = $this->ppv_orders_model->w_delete_sub($sm_ak, $sub_id);

        if (!$cancel) {

            $this->response($cancel, 200);
        }

        $this->response($cancel, 200); // 200 being the HTTP response code
    }

    public function update_sub_status_get() {
        $pid = $this->get('pid');
        $ks = $this->get('ks');
        $sub_id = $this->get('sub_id');
        $status = $this->get('status');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($ks) || $ks == null) {

            $this->response(array('error' => 'Missing ks'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($status) || $status == null) {

            $this->response(array('error' => 'Missing status'), 200);
        }

        $cancel = $this->ppv_orders_model->update_sub_status($pid, $ks, $sub_id, $status);

        if (!$cancel) {

            $this->response($cancel, 200);
        }

        $this->response($cancel, 200); // 200 being the HTTP response code
    }

    public function cancel_order_get() {
        $sm_ak = $this->get('sm_ak');
        $sub_id = $this->get('sub_id');
        $order_id = $this->get('order_id');
        $uid = $this->get('uid');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        $cancel = $this->ppv_orders_model->cancel_order($sm_ak, $order_id, $sub_id, $uid);

        if (!$cancel) {

            $this->response($cancel, 200);
        }

        $this->response($cancel, 200); // 200 being the HTTP response code
    }

    public function createAuthnetSub_get() {
        $sm_ak = $this->get('sm_ak');
        $order_id = $this->get('order_id');
        $sub_id = $this->get('sub_id');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $city = $this->get('city');

        if (!isset($sm_ak) || $sm_ak == null) {

            $this->response(array('error' => 'Missing sm_ak'), 200);
        }

        if (!isset($order_id) || $order_id == null) {

            $this->response(array('error' => 'Missing order_id'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($city) || $city == null) {

            $this->response(array('error' => 'Missing city'), 200);
        }

        $confirm = $this->ppv_orders_model->createAuthnetSub($sm_ak, $order_id, $sub_id, $payerEmail, $firstName, $lastName, $city);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

    public function insert_authnet_recurr_order_get() {
        $pid = $this->get('pid');
        $uid = $this->get('uid');
        $sub_id = $this->get('sub_id');
        $firstName = $this->get('firstName');
        $lastName = $this->get('lastName');
        $payerEmail = $this->get('payerEmail');
        $city = $this->get('city');
        $paymentStatus = $this->get('paymentStatus');
        $avsCode = $this->get('avsCode');
        $authCode = $this->get('authCode');
        $transactionId = $this->get('transactionId');

        if (!isset($pid) || $pid == null) {

            $this->response(array('error' => 'Missing pid'), 200);
        }

        if (!isset($uid) || $uid == null) {

            $this->response(array('error' => 'Missing uid'), 200);
        }

        if (!isset($sub_id) || $sub_id == null) {

            $this->response(array('error' => 'Missing sub_id'), 200);
        }

        if (!isset($firstName) || $firstName == null) {

            $this->response(array('error' => 'Missing firstName'), 200);
        }

        if (!isset($lastName) || $lastName == null) {

            $this->response(array('error' => 'Missing lastName'), 200);
        }

        if (!isset($payerEmail) || $payerEmail == null) {

            $this->response(array('error' => 'Missing payerEmail'), 200);
        }

        if (!isset($city) || $city == null) {

            $this->response(array('error' => 'Missing city'), 200);
        }

        if (!isset($paymentStatus) || $paymentStatus == null) {

            $this->response(array('error' => 'Missing paymentStatus'), 200);
        }

        if (!isset($avsCode) || $avsCode == null) {

            $this->response(array('error' => 'Missing avsCode'), 200);
        }

        if (!isset($authCode) || $authCode == null) {

            $this->response(array('error' => 'Missing authCode'), 200);
        }

        if (!isset($transactionId) || $transactionId == null) {

            $this->response(array('error' => 'Missing transactionId'), 200);
        }

        $confirm = $this->ppv_orders_model->insert_authnet_recurr_order($pid, $uid, $sub_id, $firstName, $lastName, $payerEmail, $city, $paymentStatus, $avsCode, $authCode, $transactionId);

        if (!$confirm) {
            $this->response($confirm, 200);
        }

        $this->response($confirm, 200); // 200 being the HTTP response code 
    }

}