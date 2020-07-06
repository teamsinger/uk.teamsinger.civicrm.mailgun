uk.teamsinger.civicrm.mailgun
==========================

[Mailgun](http://www.mailgun.com/) bounce processing for [CiviCRM](https://civicrm.org/)

### Installation Instructions
* Follow the instructions to install extensions manually in the [CiviCRM documentation](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/#installing-a-new-extension).
* Go to Mail Account Settings (**Administer menu » CiviMail » Mail Accounts**) and click **Edit** next to the account that has "Bounce Processing" in the *Used For* column.
[ to set the protocol for MailgunDB for the account used for Bounce Processing.
  * Set *Protocol* to **MailgunDB**.
  * Set *Password* to your Mailgun API Key.
  * Set *Email Domain* to your Mailgun domain (e.g. **mg.example.org**).
* In your Mailgun control panel, [Configure Webhooks](https://documentation.mailgun.com/api-webhooks.html#webhooks) setting under the **Legacy Webhooks** section:
  * Dropped messages to https://www.example.com/civicrm/mailgun/drop
  * Hard bounces to https://www.example.com/civicrm/mailgun/bounce
  * Unsubscribes to https://www.example.com/civicrm/mailgun/unsubscribe

### Testing

While Mailgun has a built in function to test webhooks, it is not very descriptive of the errors it receives. The bash script below proved useful (replace the APIKEY and webhook endpoint with your own).

```
#!/bin/bash
APIKEY="key-aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
TIMESTAMP="$(date +%s)"
TOKEN="IAmUsuallyRandom"
SIGNITURE="$(echo -n "${TIMESTAMP}${TOKEN}" | openssl dgst -sha256 -hmac "${APIKEY}" | awk '{print $2}')"
curl "http://example.com/index.php?option=com_civicrm&task=civicrm/mailgun/drop&format=raw" \
    -F timestamp="${TIMESTAMP}" \
    -F token="${TOKEN}" \
    -F signature="${SIGNITURE}" \
    -F recipient="john@example.com" \
    -F description="test description" \
    -F reason="Testing"
```

Sponsored by [Whirled Cinema](https://www.whirledcinema.com)
