<?php defined("BASEPATH") OR exit("No direct script access allowed");

/**
 *	SMH Custom Library
 *	
 *	Wowza Origin Streammanager class
 */
 
class SMOrigin {

	protected $_ci;
	protected $origin_usr = 'smhadmin';
	protected $origin_pass = 'base30config';
	protected $origin_base = 'http://lvip.smhcdn.com:8086/streammanager/streamAction';
	
	function __construct()
	{
		$this->_ci = & get_instance();
		$this->_ci->load->library("curl");
		#$this->_ci->load->model('portal_model');
		#$this->get_sess();
	}
	
	# This function handles sending POST requests to the Origin
	# Server to manually start/stop RTSP & Shoutcast streams.
	function callOrigin($action,$account,$sname,$type)
	{
		#syslog(LOG_NOTICE,"smOrigin-callOrigin pid: ".$account." Action: ".$action." streamName: ".$sname." Type: ".$type);
	
		if ($action && $account && $sname && $type)
		{
			$origin_pdata = array(
						'action' 			=> $action,
						'vhostName' 		=> 'undefined',
						'appName' 			=> $account.'/_definst_',
						'streamName' 		=> $sname,
						'groupId' 			=> '',
						'mediaCasterType' 	=> $type
						);
		
			// setup Curl call
			$this->_ci->curl->create($this->origin_base);
			// setup Origin Server Auth
			$this->_ci->curl->http_login($this->origin_usr,$this->origin_pass);
			// setup options
			$this->_ci->curl->options(array(CURLOPT_RETURNTRANSFER => 1));
			// setup Post data
			$this->_ci->curl->post($origin_pdata);
			// Make the call
			$resp = $this->_ci->curl->execute();
			
			#syslog(LOG_NOTICE,"smOrigin -- callOrigin resp: ".$resp);
			
			if ($resp)
			{
				return true;
			}
			#syslog(LOG_NOTICE,"smOrigin -- callOrigin: Error: response failed!");
			return false;
		}
		#syslog(LOG_NOTICE,"smOrigin -- callOrigin: Error: all params required!");
		return false;
	}
}

