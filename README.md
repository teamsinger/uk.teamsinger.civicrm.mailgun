uk.teamsinger.civicrm.mailgun
==========================

[Mailgun](http://www.mailgun.com/) bounce processing for [CiviCRM](https://civicrm.org/)

### Installation Instructions
1. [Install extension](http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions#Extensions-Installinganewextension) following **Manual installation of native extensions**
2. Update the Mail Protocol [Option Group](https://www.example.com/civicrm/admin/options?reset=1) to add [MailgunDB](https://raw.githubusercontent.com/teamsinger/uk.teamsinger.civicrm.mailgun/master/documentation/mailgundb-option-group.png)
3. Update [Mail Account settings](https://www.example.com/civicrm/admin/mailSettings?reset=1) to set the protocol for MailgunDB for the account used for Bounce Processing.
3. Add webhook paths to [skip IDS checks](#user-content-skip-ids-checks)
4. [Configure Webhooks](https://documentation.mailgun.com/api-webhooks.html#webhooks) setting:
 1. Dropped messages to https://www.example.com/civicrm/mailgun/drop
 2. Hard bounces to https://www.example.com/civicrm/mailgun/bounce

### Skip IDS Checks

When the webhooks are called these can trigger IDS checks in Civi. To get around this [CRM/Core/IDS.php](https://github.com/civicrm/civicrm-core/blob/master/CRM/Core/IDS.php) has been patched to allow additional paths to be skipped. These paths need adding by adding the following to settings.php (Drupal) or (base)/components/com_civicrm/civicrm.settings.php (Joomla).
```
define( 'CIVICRM_IDS_SKIP', serialize( array('civicrm/mailgun/drop', 'civicrm/mailgun/bounce') ) );
```

### Testing

While Mailgun has a built in function to test webhooks, it is not very descriptive of the errors it receives. The bash script below proved useful (replace out APIKEY).

```
#!/bin/bash
APIKEY="key-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
TIMESTAMP="$(date +%s)"
TOKEN="IAmUsuallyRandom"
SIGNITURE="$(echo -n "${TIMESTAMP}${TOKEN}" | openssl dgst -sha256 -hmac "${APIKEY}" | awk '{print $2}')"
curl "http://example.com/index.php?option=com_civicrm&task=civicrm/mailgun/drop&format=raw" \
    -F timestamp="$(TIMESTAMP)" \
    -F token="$(TOKEN)" \
    -F signature="$(SIGNITURE)"
    -F recipient="john@example.com" \
    -F description="test description" \
    -F reason="Testing" \
```

Sponsored by [Whirled Cinema](https://www.whirledcinema.com)
