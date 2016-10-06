uk.teamsinger.civicrm.mailgun
==========================

[Mailgun](http://www.mailgun.com/) bounce processing for [CiviCRM](https://civicrm.org/)

### Installation Instructions
1. [Install extension](http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions#Extensions-Installinganewextension) following **Manual installation of native extensions**
2. Update the Mail Protocol [Option Group](https://www.example.com/civicrm/admin/options?reset=1) to add [MailgunDB](https://raw.githubusercontent.com/teamsinger/uk.teamsinger.civicrm.mailgun/master/documentation/mailgundb-option-group.png)
3. Update [Mail Account settings](https://www.example.com/civicrm/admin/mailSettings?reset=1) adding the [Mailgun API key](https://help.mailgun.com/hc/en-us/articles/203380100-Where-can-I-find-my-API-key-and-SMTP-credentials-) as the password and updating the protocol for MailgunDB for the account used for Bounce Processing.
4. Add webhook paths to [skip IDS checks](#user-content-skip-ids-checks)
5. [Configure Webhooks](https://documentation.mailgun.com/api-webhooks.html#webhooks) setting:
 1. Dropped messages to https://www.example.com/civicrm/mailgun/drop
 2. Hard bounces to https://www.example.com/civicrm/mailgun/bounce

### Skip IDS Checks

When the webhooks are called these can trigger IDS checks in Civi. To get around this [CRM/Core/IDS.php](https://github.com/civicrm/civicrm-core/blob/master/CRM/Core/IDS.php) has been patched to allow additional paths to be skipped. These paths need adding by adding the following to settings.php (Drupal).
```
define( 'CIVICRM_IDS_SKIP', serialize( array('civicrm/mailgun/drop', 'civicrm/mailgun/bounce') ) );
```

Sponsored by [Whirled Cinema](https://www.whirledcinema.com)
