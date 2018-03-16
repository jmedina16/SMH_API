<?php

ini_set('display_errors', 'On');
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

session_start();

include_once( 'weibo/config.php' );
include_once( 'weibo/saetv2.ex.class.php' );

class Weibo_client_api {

    public function getRedirectURL($pid, $ks) {
        $state = $pid . "|" . $ks;
        $o = new SaeTOAuthV2( WB_AKEY , WB_SKEY );
        $authUrl = $o->getAuthorizeURL( WB_CALLBACK_URL, 'code', $state, NULL );
        return $authUrl;
    }

}
