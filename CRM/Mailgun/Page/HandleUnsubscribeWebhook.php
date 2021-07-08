<?php

class CRM_Mailgun_Page_HandleUnsubscribeWebhook extends CRM_Core_Page {
  function run() {
	  
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('HandleUnsubscribeWebhook'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    static $store = null;

    $timestamp = CRM_Utils_Request::retrieve('timestamp', 'String', $store, false, null, 'POST');
    $token = CRM_Utils_Request::retrieve('token', 'String', $store, false, null, 'POST');
    $signature = CRM_Utils_Request::retrieve('signature', 'String', $store, false, null, 'POST');

		CRM_Mailgun_Utils::checkSignature($timestamp, $token, $signature);

    $recipient = CRM_Utils_Request::retrieve('recipient', 'String', $store, false, null, 'POST');
    $reason = CRM_Utils_Request::retrieve('event', 'String', $store, false, null, 'POST');

	//~ print_r($_REQUEST);
	 //~ Array ( 
		//~ [option] => com_civicrm 
		//~ [task] => civicrm/mailgun/unsubscribe 
		//~ [format] => raw 
		//~ [ip] => 50.56.129.169 
		//~ [city] => San Francisco 
		//~ [domain] => mg.lalgbtcenter.org 
		//~ [device-type] => desktop 
		//~ [my_var_1] => Mailgun Variable #1 
		//~ [country] => US 
		//~ [region] => CA 
		//~ [client-name] => Chrome 
		//~ [user-agent] => Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.31 (KHTML, like Gecko) Chrome/26.0.1410.43 Safari/537.31 
		//~ [client-os] => Linux [my-var-2] => awesome 
		//~ [client-type] => browser 
		//~ [tag] => * 
		//~ [recipient] => alice@example.com 
		//~ [event] => unsubscribed 
		//~ [timestamp] => 1486762308 
		//~ [token] => 028d532e33d2c2be8a12803219746236330e0f40a79c08fdcb 
		//~ [signature] => 9c577cedadb8aea1583c2166a5a7e7c8d569b060baf8346863878d7363856b6f 
		//~ [body-plain] => 
		//~ [Itemid] =>
		//~ [IDS_request_uri] => /index.php?option=com_civicrm&task=civicrm/mailgun/unsubscribe&format=raw 
	//~ )
	 
	//~ print_r($message_headers_array);
	//~ JLog::addLogger(array('text_file'=>'civicrm.php'), JLog::ALL, array('civicrm'));
	//~ JLog::add(print_r($message_headers_array,true), JLog::INFO, 'civicrm');
	//~ exit; 
	
	try {
			
		// get old contact -- necessary to prevent "Ambiguous match criteria" on create
		$origcontact = civicrm_api3('Contact', 'get',array(
			'email' => $recipient,
			'match' => array(
				'email',
			),
		));
		if (!empty($origcontact['is_error'])) throw new Exception($origcontact['error_message']);
		//~ print_r($origcontact); exit;
		
		// noone to unsubscribe if noone exists
		$contact_ids = array();
		$optouts = array();
		if ($origcontact['count']>0) {
			foreach ($origcontact['values'] as $val) {
				//~ print_r($val);
				$optouts[] = civicrm_api3('Contact', 'create', array(
					'contact_id' => $val['contact_id'],
					'is_opt_out' => '1',
					'match' => array(
						'contact_id',
					),
				));
			}
			//~ print_r($optouts); exit;
		}
		
	} catch(Exception $e) {
		echo json_encode(array(
			'error' => $e->getMessage(),
		));
		return;
	}
	
    $query_params = array(
      1 => array($recipient, 'String'),
      2 => array(json_encode($optouts), 'String'),
      3 => array(json_encode($_POST), 'String'),
      4 => array($reason, 'String'),
    );
    
    //~ var_dump($query_params); exit;

    CRM_Core_DAO::executeQuery("INSERT INTO mailgun_events
      (recipient, email, post_data, reason) VALUES (%1, %2, %3, %4)", $query_params);

		echo json_encode(array(
			'type' => 'unsubscribe',
			'msg' => 'Post received',
		));

		CRM_Utils_System::civiExit();
    //~ parent::run();
  }
}
