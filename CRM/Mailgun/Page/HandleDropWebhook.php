<?php

require_once 'CRM/Core/Page.php';

class CRM_Mailgun_Page_HandleDropWebhook extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('HandleDropWebhook'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    static $store = null;

    $recipient = CRM_Utils_Request::retrieve('recipient', 'String', $store, false, null, 'POST');
    $description = CRM_Utils_Request::retrieve('description', 'String', $store, false, null, 'POST');
    $reason = CRM_Utils_Request::retrieve('reason', 'String', $store, false, null, 'POST');

    $message_headers_raw = CRM_Utils_Request::retrieve('message-headers', 'String', $store, false, null, 'POST');

    $message_headers_array = json_decode($message_headers_raw);

    $message_headers = array();

    foreach ($message_headers_array AS $header) {
      $message_headers[trim($header[0])] = trim($header[1]);
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
      3 => array($reason, 'String'),
    );

    CRM_Core_DAO::executeQuery("INSERT INTO mailgun_events
      (recipient, email, reason) VALUES (%1, %2, %3)", $query_params);

    parent::run();
  }
}
