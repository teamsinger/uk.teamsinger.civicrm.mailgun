<?php

require_once 'CRM/Core/Page.php';
require_once 'api/class.api.php';

class CRM_Mailgun_Page_HandleDropWebhook extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('HandleDropWebhook'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    static $store = null;

    $timestamp = CRM_Utils_Request::retrieve('timestamp', 'String', $store, false, null, 'POST');
    $token = CRM_Utils_Request::retrieve('token', 'String', $store, false, null, 'POST');
    $signature = CRM_Utils_Request::retrieve('signature', 'String', $store, false, null, 'POST');

    $api = new civicrm_api3();

    $params = array();
    $params['version'] = 3;
    $params['name'] = 'mail_protocol';

    $apiKey = '';

    if ($api->OptionGroup->Getsingle( $params )) {
      $optionGroupId = $api->id;

      unset($params);
      $params['version'] = 3;
      $params['option_group_id'] = $optionGroupId;
      $params['label'] = 'MailgunDB';

      if ($api->OptionValue->Getsingle( $params )) {
        $protocol = $api->weight;

        unset($params);
        $params['version'] = 3;
        $params['protocol'] = $protocol;

        if ($api->MailSettings->Getsingle( $params )) {
          $apiKey = $api->password;
        }
      }
    }

    if ( $signature !== hash_hmac("sha256", $timestamp . $token, $apiKey) ) {
      $msg = ts('Failed to verify signature');
      CRM_Core_Error::fatal($msg);
    }

    $recipient = CRM_Utils_Request::retrieve('recipient', 'String', $store, false, null, 'POST');
    $description = CRM_Utils_Request::retrieve('description', 'String', $store, false, null, 'POST');
    $reason = CRM_Utils_Request::retrieve('reason', 'String', $store, false, null, 'POST');

    $message_headers_raw = CRM_Utils_Request::retrieve('message-headers', 'String', $store, false, null, 'POST');

    $message_headers_array = json_decode($message_headers_raw);

    $message_headers = array();

	//~ JLog::addLogger(array('text_file'=>'civicrm.php'), JLog::ALL, array('civicrm'));
	//~ JLog::add(print_r($message_headers_array,true), JLog::INFO, 'civicrm');
	if (!empty($message_headers_array)) {
		foreach ($message_headers_array AS $header) {
      if (empty($header[0])||empty($header[1])) continue; // skip non-conforming headers
		  $message_headers[trim($header[0])] = $header[1];
		}
	}

    $headers = '';

    foreach (getallheaders() as $name => $value) {
      $headers .= "$name: $value\n";
    }

    // Build simplest email for Civi to parse data out of

    $email = '';

    if (isset($message_headers['X-Civimail-Bounce'])) {
      $x_civimail_bounce = $message_headers['X-Civimail-Bounce'];
      $email .= "Delivered to: " . $x_civimail_bounce  . "\n";
    } else {
	  $x_civimail_bounce = '';
      $return_path = '';
    }

    if (isset($message_headers['Received'])) {
      $email .= "Received: " . $message_headers['Received'] . "\n";
    }

    $email .= "Return-Path: <>\n";
    $email .= "X-Civimail-Bounce: " . $x_civimail_bounce . "\n";
    $email .= "To: <" . $x_civimail_bounce . ">\n";
    $email .= "From: <postmaster@local>\n";

    if (isset($message_headers['Date'])) {
      $email .= "Date: " . $message_headers['Date'] . "\n";
    }

    if (isset($message_headers['Subject'])) {
      $email .= "Subject: " . $message_headers['Subject'] . "\n\n";
    }

    $email .= $description . "\n";


    $query_params = array(
      1 => array($recipient, 'String'),
      2 => array($email, 'String'),
      3 => array(json_encode($_POST), 'String'),
      4 => array($reason, 'String'),
    );

    CRM_Core_DAO::executeQuery("INSERT INTO mailgun_events
      (recipient, email, post_data, reason) VALUES (%1, %2, %3, %4)", $query_params);

	echo json_encode(array(
		'type' => 'drop',
		'msg' => 'Post received',
	));
    //~ parent::run();
  }
}
