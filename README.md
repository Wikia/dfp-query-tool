# GAM Query Tool

Application allows communication with Google Ad Manager through API and serves Tableau Web Data Connector. It can be used to mass create Prebid.js lines and for other big changes within GAM inventory. 

Project developed during the Wikia Summer Hackathon 2016.

## Installation

GAM (previously DFP) Query Tool is a project written in PHP and maintained using [Composer](https://getcomposer.org/). In order to install all dependencies and generate autoload files simply run:

```bash
composer install
```

## Configuration

Duplicate [auth.sample.ini](./config/auth.sample.ini) file, rename it to remove `.sample`. Fill it with Google Ad Manager OAuth2 connection credentials (or ask other team member to use shared, GAM Tableau credentials. Remember to set `networkCode` to `5441`:

```ini
[AD_MANAGER]
networkCode = "5441"

applicationName = "GAM Tableau"

[OAUTH2]
clientId = "clientId.apps.googleusercontent.com"
clientSecret = "clientSecretHash"
refreshToken = "refreshTokenHash"
```

## Browser usage

Project can be hosted and used via internet browser, but this approach is inefective: web forms are synchronous and they report timeouts during more complex tasks. Also lack of progress and error log makes it harder to use.

## CLI usage

Run `app/console` from the root project directory to list all available commands.

```ini
adunits
  adunits:archive                              Archive ad units.
creatives
  creatives:associate                          Associates existing creative to all line items in order
  creatives:deactivate                         Deactivates associated creatives in all line items in order
  creatives:find                               Finds all creatives with given text in snippet code.
key-values
  key-values:get                               Get GAM ids of given key and its values
line-items
  line-items:child-content-eligibility:update  Update Child Content Eligibility field in given order
  line-items:create                            Creates line items in the order (with associated creative)
  line-items:find-by-key                       Find line items by used keys in the targeting
  line-items:key-values:add                    Add key-values pair to line item custom targeting
  line-items:key-values:remove                 Remove key-values pair from line item custom targeting
lint
  lint:yaml                                    Lints a file and outputs encountered errors
order
  order:key-values:add                         Add key-values pair to all line items custom targeting in order
  order:key-values:remove                      Remove key-values pair from all line item custom targeting in order
reports
  reports:fetch                                Downloads data to database.
suggested-adunits
  suggested-adunits:approve                    Approve all suggested ad units in queue.
```

### Create line items

Prepare JSON based on [prebid20.sample.json](./line-item-presets/prebid20.sample.json), [prebid50.sample.json](./line-item-presets/prebid50.sample.json) or [amazonDisplay.sample.json](./line-item-presets/amazonDisplay.sample.json) and execute command:

```bash
app/console line-item:create ./line-item-presets/<your-configuration>.json
``` 

It will create multiple line items in the provided order with associated creative.

### Update Child Content Eligibility

Child Content Eligibility is an option in Google Ad Manager lines introduced and switched to "Disallow" for all line items late 2019 / early 2020. This query can automatically switch it back to "Allow" for given orders:

```bash
app/console line-items:child-content-eligibility:update comma,separated,order,ids
```

## Cron jobs

A cron job is defined in k8s-cron-jobs directory:
* It is designed to periodically (4 times a day) approve suggested ad units
* It runs in k8s in the dev env
* It uses manually created credentials `adeng-query-tool-credentials`

To see logs, go to https://dashboard.poz-dev.k8s.wikia.net:30080/#!/job?namespace=dev, select job with name starting from `dfp-query-tool-` and click on "Logs" icon.
