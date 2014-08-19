<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'ezc/Base/src/ezc_bootstrap.php';
require_once 'ezc/autoload/mail_autoload.php';
class CRM_Mailing_MailStore_Database extends CRM_Mailing_MailStore {

  /**
   * Connect to the supplied dir and make sure the two mail dirs exist
   * @todo why does it need folders? This can probably be removed
   *
   * @param string $dir  dir to operate upon
   *
   * @return void
   */
  function __construct($dir) {
    $this->_dir = $dir;

//    $this->_ignored = $this->maildir(implode(DIRECTORY_SEPARATOR, array('CiviMail.ignored', date('Y'), date('m'), date('d'))));
//    $this->_processed = $this->maildir(implode(DIRECTORY_SEPARATOR, array('CiviMail.processed', date('Y'), date('m'), date('d'))));
  }

  /**
   * Return the next X messages from the mail store
   *
   * @param int $count  number of messages to fetch FIXME: ignored in CiviCRM 2.2 (assumed to be 0, i.e., fetch all)
   *
   * @return array      array of ezcMail objects
   */
  function fetchNext($count = 0) {
    $mails = array();

    if ($this->_debug) {

      print "fetching $count messages\n";

    }

    $query = "SELECT * FROM mailgun_bounces WHERE processed = 0";
    $query_params = array();

    if ($count > 0) {
      $query .= " LIMIT %1";
      $query_params[1] = array($count, 'Int');
    }

    $dao = CRM_Core_DAO::executeQuery($query, $query_params);
    while ($dao->fetch()) {
      $set = new ezcMailVariableSet($dao->email);

      $parser = new ezcMailParser;
      //set property text attachment as file CRM-5408
      $parser->options->parseTextAttachmentsAsFiles = TRUE;

      $mail = $parser->parseMail($set);

      if (!$mail) {
        return CRM_Core_Error::createAPIError(ts('Email ID %1 could not be parsed',
            array(1 => $dao->id)
          ));
      }

      $mails[$dao->id] = $mail[0];
    }

    if ($this->_debug && (count($mails) <= 0)) {
      print "No messages found\n";
    }

    return $mails;
  }

  /**
   * Mark the specified message as ignored
   *
   * @param integer $id  id of email to mark ignored
   *
   * @return void
   */
  function markIgnored($id) {
    if ($this->_debug) {
      print "marking $id as ignored\n";
    }

    // @todo write code
  }

  /**
   * Mark the specified message as processed
   *
   * @param integer $id  id of email to mark as processed
   *
   * @return void
   */
  function markProcessed($id) {
    if ($this->_debug) {
      print "marking $id as processed\n";
    }

    // @todo write code
  }
}

