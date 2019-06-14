<?php

require_once 'mailgun.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function mailgun_civicrm_config(&$config) {
  _mailgun_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function mailgun_civicrm_xmlMenu(&$files) {
  _mailgun_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function mailgun_civicrm_install() {
  require_once "CRM/Core/DAO.php";

  CRM_Core_DAO::executeQuery("
    CREATE TABLE IF NOT EXISTS `mailgun_events` (
      `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `processed` INT(1) NOT NULL DEFAULT 0,
      `ignored` INT(1) NOT NULL DEFAULT 0,
      `recipient` VARCHAR(254) COLLATE utf8_unicode_ci DEFAULT NULL,
      `email` MEDIUMTEXT COLLATE utf8_unicode_ci DEFAULT NULL,
      `post_data` MEDIUMTEXT COLLATE utf8_unicode_ci DEFAULT NULL,
      `reason` VARCHAR(64) COLLATE utf8_unicode_ci DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
  ");

  return _mailgun_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function mailgun_civicrm_uninstall() {
  require_once "CRM/Core/DAO.php";

  CRM_Core_DAO::executeQuery("DROP TABLE mailgun_events");

  return _mailgun_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function mailgun_civicrm_enable() {
  // @todo Add Mail Protocol - Option Value

  return _mailgun_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function mailgun_civicrm_disable() {
  // @todo Remove Mail Protocol - Option Value

  return _mailgun_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function mailgun_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _mailgun_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function mailgun_civicrm_managed(&$entities) {
  _mailgun_civix_civicrm_managed($entities);
  $entities[] = [
    'module' => 'uk.teamsinger.civicrm.mailgun',
    'name' => 'MailgunDBMailProtocol',
    'entity' => 'OptionValue',
    'params' => [
      'label' => ts('MailgunDB'),
      'name' => 'MailgunDB',
      'value' => 'MailgunDB',
      'option_group_id' => 'mail_protocol',
      'is_active' => TRUE,
      'version' => 3,
    ],
  ];

}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function mailgun_civicrm_caseTypes(&$caseTypes) {
  _mailgun_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function mailgun_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _mailgun_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// https://docs.civicrm.org/dev/en/hooks-op-4/hooks/hook_civicrm_idsException/
function mailgun_civicrm_idsException(&$skip) {
  $skip[] = 'civicrm/mailgun/drop';
  $skip[] = 'civicrm/mailgun/bounce';
  $skip[] = 'civicrm/mailgun/unsubscribe';
}

/**
 * Shim missing function "getallheaders" where php is not run as an apache module
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
      $headers = array();
      foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
          $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
      }
      return $headers;
    }
}
