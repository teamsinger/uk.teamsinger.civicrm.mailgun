<?php

/* Check the signature passed by Mailgun against the API key we have stored.
 *  If this is incorrect, it's not a legitimate message from Mailgun for our Mailgun account.
 *  Returns TRUE if successful, fatals out if not.
 *  TODO: CRM_Core_Error::fatal is deprecated, let's avoid its use.
 */
class CRM_Mailgun_Utils {
  static function checkSignature($timestamp, $token, $signature) {
    $apiKey = '';

    $mailProtocolId = civicrm_api3('OptionValue', 'getvalue', [
      'return' => "value",
      'label' => "MailgunDB",
      'option_group_id' => "mail_protocol",
    ]);
    if ($mailProtocolId) {
      $apiKey = civicrm_api3('MailSettings', 'getvalue', [
        'return' => 'password',
        'protocol' => $mailProtocolId,
      ]);
    }
    if ($apiKey && $signature == hash_hmac("sha256", $timestamp . $token, $apiKey)) {
      return TRUE;
    }
    else {
      $msg = ts('Failed to verify signature');
      CRM_Core_Error::fatal($msg);
    } 
  }
}
