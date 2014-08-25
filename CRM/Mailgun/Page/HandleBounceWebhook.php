<?php

require_once 'CRM/Core/Page.php';

class CRM_Mailgun_Page_HandleBounceWebhook extends CRM_Core_Page {
  function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    CRM_Utils_System::setTitle(ts('HandleBounceWebhook'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    static $store = null;

    $event = CRM_Utils_Request::retrieve('event', 'String', $store, false, null, 'POST');

    $headers = '';

    foreach (getallheaders() as $name => $value) {
      $headers .= "$name: $value\n";
    }

    file_put_contents('/tmp/bounces', $headers, FILE_APPEND);
    file_put_contents('/tmp/bounces', print_r($_REQUEST, true), FILE_APPEND);

    parent::run();
  }
}
