<?php

class CRM_Mailgun_Page_HandleDropWebhook extends CRM_Core_Page
{
  function run()
  {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('HandleDropWebhook'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    $store = null;

    $timestamp = CRM_Utils_Request::retrieve('timestamp', 'String', $store, false, null, 'POST');
    $token = CRM_Utils_Request::retrieve('token', 'String', $store, false, null, 'POST');
    $signature = CRM_Utils_Request::retrieve('signature', 'String', $store, false, null, 'POST');

    CRM_Mailgun_Utils::checkSignature($timestamp, $token, $signature);

    $recipient = CRM_Utils_Request::retrieve('recipient', 'String', $store, false, null, 'POST');
    $description = CRM_Utils_Request::retrieve('description', 'String', $store, false, null, 'POST');
    $reason = CRM_Utils_Request::retrieve('reason', 'String', $store, false, null, 'POST');

    $message_headers_raw = CRM_Utils_Request::retrieve('message-headers', 'String', $store, false, null, 'POST');
    $message_headers_array = json_decode(json_decode($message_headers_raw, null, 512, JSON_THROW_ON_ERROR), null, 512, JSON_THROW_ON_ERROR);
    $message_headers =[];

    //~ JLog::addLogger(array('text_file'=>'civicrm.php'), JLog::ALL, array('civicrm'));
    //~ JLog::add(print_r($message_headers_array,true), JLog::INFO, 'civicrm');
    if (!empty($message_headers_array)) {
      foreach ($message_headers_array as $header) {
        if (empty($header[0]) || empty($header[1]))
          continue; // skip non-conforming headers
        $message_headers[trim($header[0])] = $header[1];
      }
    }

    // Build simplest email for Civi to parse data out of

    $email = '';
    $email .= "From: <postmaster@local>\r\n";
    $email .= "Return-Path: <>\r\n";

    $x_civimail_bounce = '';
    if (isset($message_headers['X-Civimail-Bounce'])) {
      $x_civimail_bounce = $message_headers['X-Civimail-Bounce'];
      $email .= "X-Civimail-Bounce: " . $x_civimail_bounce . "\r\n";
      $email .= "Delivered-To: " . $x_civimail_bounce  . "\r\n";
      $email .= "To: <" . $x_civimail_bounce . ">\r\n";
    }

    if (isset($message_headers['Received'])) {
      $email .= "Received: " . $message_headers['Received'] . "\r\n";
    }

    if (isset($message_headers['Date'])) {
      $email .= "Date: " . $message_headers['Date'] . "\r\n";
    }

    if (isset($message_headers['Subject'])) {
      $email .= "Subject: " . $message_headers['Subject'] . "\r\n\r\n";
    }

    $email .= $description . "\r\n";
    if ($description === 'Not delivering to previously bounced address') {
      // Add it a bit to matching a bounce patter and get it matched
      $email .= "RecipNotFound\r\n";
    }

    $query_params = array(
      1 => [$recipient, 'String'],
      2 => [$email, 'String'],
      3 => [json_encode($_POST), 'String'],
      4 => [$reason, 'String'],
    );

    CRM_Core_DAO::executeQuery("
      INSERT INTO mailgun_events
        (recipient, email, post_data, reason)
      VALUES
        (%1, %2, %3, %4)
    ", $query_params);

    echo json_encode([
      'type' => 'drop',
      'msg' => 'Post received',
    ]);


    CRM_Utils_System::civiExit();
    //~ parent::run();
  }
}
