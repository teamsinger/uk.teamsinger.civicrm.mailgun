uk.teamsinger.civicrm.mailgun
==========================

[Mailgun](http://www.mailgun.com/) bounce processing for [CiviCRM](https://civicrm.org/)

### Installation Instructions
1. [Install extension](http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions#Extensions-Installinganewextension) following **Manual installation of native extensions**
2. Update the Mail Protocol [Option Group](https://www.example.com/civicrm/admin/options?reset=1) to add [MailgunDB](https://raw.githubusercontent.com/teamsinger/uk.teamsinger.civicrm.mailgun/master/documentation/mailgundb-option-group.png)
3. Update [Mail Account settings](https://www.example.com/civicrm/admin/mailSettings?reset=1) to set the protocol for MailgunDB for the account used for Bounce Processing.
4. [Configure Webhooks](https://documentation.mailgun.com/api-webhooks.html#webhooks) setting:
 1. Dropped messages to https://www.example.com/civicrm/mailgun/drop
 2. Hard bounces to https://www.example.com/civicrm/mailgun/bounce

Sponsored by [Whirled Cinema](https://www.whirledcinema.com)
