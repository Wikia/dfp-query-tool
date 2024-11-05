# GAM Query Tool

Application allows communication with Google Ad Manager through API and serves Tableau Web Data Connector. It can be used to mass create Prebid.js lines and for other big changes within GAM inventory. 

Project developed during the Wikia Summer Hackathon 2016.

## Installation

GAM (previously DFP) Query Tool is a project written in PHP and maintained using [Composer](https://getcomposer.org/). In order to install all dependencies and generate autoload files simply run:

```bash
composer install
```

## Configuration

Duplicate [auth.sample.ini](./config/auth.sample.ini) file, rename it to remove `.sample`. Fill it with Google Ad Manager OAuth2 connection credentials (by visiting https://console.cloud.google.com/console and running `php ./GenerateUserCredentials.php` or ask other team member to use shared, GAM Tableau credentials. Remember to set `networkCode` to `5441`:

```ini
[AD_MANAGER]
networkCode = "5441"

applicationName = "GAM Tableau"

[OAUTH2]
clientId = "clientId.apps.googleusercontent.com"
clientSecret = "clientSecretHash"
refreshToken = "refreshTokenHash"
```

If the google cloud console login doesn't work, you can try visiting a specific project in the console.cloud.google.com by using [this link](https://console.cloud.google.com/apis/credentials?project=fandom-oauth-login).
This will send you to the fandom-oauth-login project, where you can generate new credentials or access existing ones. You should see the clientId and clientSecret under the `Dfp Query Tool` **OAuth 2.0 Client IDs** section.

If you try visiting this link, and you can't access it, get redirect to a different page, or just get a blank screen, then reach out to OPS to get access. You can send them the above link. They should know how to get you access.

Once you have access and have filled out the above, you can run the `GetRefreshToken.php` script with ```php GetRefreshToken.php``` to get the refreshToken.
Place the token in the `auth.ini` file (which should just be a copy of the `auth.sample.ini` file), and you should be good to go.

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
  key-values:remove-values                     Remove values from given key
line-items
  line-items:child-content-eligibility:update  Update Child Content Eligibility field in given order
  line-items:create                            Creates line items in the order (with associated creative)
  line-items:find-by-key                       Find line items by used keys in the targeting
  line-items:key-values:add                    Add key-values pair to line item custom targeting
  line-items:key-values:remove                 Remove key-values pair from line item custom targeting
  line-items:key-values:update                 Update key-values targeting pair in all lines in given order
lint
  lint:yaml                                    Lints a file and outputs encountered errors
order
  order:key-values:add                         Add key-values pair to all line items custom targeting in order
  order:key-values:remove                      Remove key-values pair from all line item custom targeting in order
  order:add-creatives                          Add new creatives to all line items in the order
  order:override-creative-sizes                Overrides creative sizes in all line items in the order
reports
  reports:fetch                                Downloads data to database.
suggested-adunits
  suggested-adunits:approve                    Approve all suggested ad units in queue.
generate
  bidders-slots-json                           Generates and prints in the output JSON config for slots for selected Prebid.js bidder
```

### Create line items

Prepare JSON based on [prebid20.sample.json](./line-item-presets/prebid20.sample.json), [prebid50.sample.json](./line-item-presets/prebid50.sample.json) or [amazonDisplay.sample.json](./line-item-presets/amazonDisplay.sample.json) and execute command:

```bash
app/console line-items:create ./line-item-presets/<your-configuration>.json
``` 

It will create multiple line items in the provided order with associated creative.

WARNING 1. [prebid20.sample.json](./line-item-presets/prebid20.sample.json) is the default file, [prebid50.sample.json](./line-item-presets/prebid50.sample.json) is for bidders with `maxCpm` set to `EXTENDED_MAX_CPM`.

WARNING 2. While creating video line items add `"isVideo": true,`

Extra context for how the JSON files are constructed and what each field means
- "orderId" - the order id that the line item will be created in
- "iterator" - These are prices in dollars and cents. This is used as the main looping element
- "priceMap" - An optional field which is mainly used for Amazon line item updates, since their prices are hashed.
The price map has to have the same amount of elements in it as the iterator, otherwise an error is thrown. 
  An example of a price map can be found in [this file](./line-item-presets/amazonDisplayPriority4-sponsorship.sample.json).
- lineItemName - The name of the line item. You can add in %%element%% in the title, to include the price of the line item in the title if you want.
  For example, if your title is "Fandom TAM/A9 Display - $%%element%%", and if the price of the line item is 5.00, then the full line item name will be
  "Fandom TAM/A9 Display - $5.00"
- "sizes" - This refers to the sizes that should be set on the line items that are created.
- "sameAdvertiser" - TBD
- "type" - The price type of the line item. This is the string version of the price type. This has to be set along with the "priority" field.
- "priority" - The price type of the line item. This is the numeric version of the price type. This has to be set along with the "type" field.
- "rate" - The actual price that'll be set on the order. This corresponds to the value from the `iterator` field when its value is set to `%%element%%`, as its being looped through.
For example, if the values in your `iterator` field as `5.00,5.05,5.10`, then you'll have three line items created, with the price of 5.00 5.05 and 5.10 respectively.
- keys - The key in the key/value pair in GAM that should be set on the line item. This goes hand in hand with the `operators` and `values` field in the JSON mapping.
- operators - The operator that will be present on the key value pair. The two values can be "IS" and "IS_NOT", which basically means equal or not equals to. This field goes hand in hand with the `keys` and `values` fields.
"values" - The values that will be set on each respective key. To sum all of this up, if your keys field is set to `"amznbid","src"`, your operators field is set to `"IS","IS"` and your values field is set to `%%priceMapElement%%`,`mobile,gpt`
  and the iterator and priceMap element are `5.00` and `y2bcw` then your key values in GAM will be as follows:
  - `amznbid` is any of `y2bcw`
  - `src` is any of `mobile` `gpt`
  

### Update Child Content Eligibility

Child Content Eligibility is an option in Google Ad Manager lines introduced and switched to "Disallow" for all line items late 2019 / early 2020. This query can automatically switch it back to "Allow" for given orders:

```bash
app/console line-items:child-content-eligibility:update comma,separated,order,ids
```

### Remove values from given key
This command allows you to remove values from a given key. This command checks if any of the given values are being used.

```bash
app/console key-values:remove-values test_key test_value1, test_value2 
```

There are 2 options that we can use:

```bash
--dry-run  # Run without actually removing anything; to force the removal go with --dry-run=no

--skip-line-item-check # Run without scanning if the key-values are being used in any line-item custom targeting
```

### Update key-values targeting pair

This command allows you to update values for given key in all lines in given orders. You can use key previously not assigned to the line or use already existing one just to add new values.

First parameter is a comma-separated list of orders that needs to be updated.

Second parameter is a targeting key that should be changed

Third parameter is a comma-separated values that key should have after running the command. Be advised: this command is not clearing up old key values before adding new ones. If all values existed in some line - the update of this line will be skipped.

Fourth parameter (optional) is a key-val operator (IS or IS NOT, default IS).

```bash
app/console line-items:key-values:update comma,separated,order,ids key comma,separated,values operator
```

### Add creatives to line items in an order

If you have an order with many line items which you want to reuse the same creative template you need to execute:
```bash
app/console order:add-creatives --order ORDER_ID --creative-template CREATIVE_TEMPLATE_ID
```
or its simpler version:
```bash
app/console order:add-creatives -o ORDER_ID -c CREATIVE_TEMPLATE_ID
```
It will get all line items in the given order (`ORDER_ID`), create a new creative based on the given creative template (`CREATIVE_TEMPLATE_ID`) and assign it to all the lines.

The new creative's names will be built based on the line-item name and its first creative placeholder size.

If your creative requires string variables you can use `--creative-variables` option:
```
app/console order:add-creatives --order ORDER_ID --creative-template CREATIVE_TEMPLATE_ID --creative-variables VARIABLES_WITH_VALUES_PAIRS
```
or its shortcut `-r`:
```
app/console order:add-creatives -o 2666092254 -c 11899731 -r VARIABLES_WITH_VALUES_PAIRS
```
The `VARIABLES_WITH_VALUES_PAIRS` is a string of pairs separated by `;`, each pair is a combination of two strings separated by `:`, for example:
* `creativeVariable:creativeVariableValue` - passed to the script will set one variable and its value in creative,
* `var1:val1;var2:val2;var3:val3` - passed to the script will set three variables and their values to in creative. 

You can additionally add a suffix to creative template's name by passing optional option:
```bash
app/console order:add-creatives --order ORDER_ID --creative-template CREATIVE_TEMPLATE_ID --creative-suffix "SUFFIX"
```
or the simpler version:
```bash
app/console order:add-creatives -o ORDER_ID -c CREATIVE_TEMPLATE_ID -s "SUFFIX"
```
There is an option to add multiple creatives per line item in the order. The --multiple-creatives-per-line-item option
allows for a positive integer to be passed to the command. This will create that many creatives per line item in the order.
This option needs the --force-new-creative option to be passed in. Passing in a value of 5 for example, will create 5 new creative for each line item in the order.
For example, you can run the following command
```bash
app/console order:add-creatives -o ORDER_ID -c CREATIVE_TEMPLATE_ID --force-new-creative --multiple-creatives-per-line-item 5 
```

You can also pass in overridden creative sizes with the --override-creative-size option, and passing in single or command separated creatives sizes such as `1x1,300x250` or just `300x250` for a single size. 
This option requires the --force-new-creative option to be turned on. Please note, that due to restrictions set in GAM and the GAM API, the new creative sizes MUST BE a subset of the
line item sizes. For example, if the line item that you'll be attaching the new creatives to has sizes of 300x250,320x250, but you pass in a creatives size of 1x1 and 300x250, then the creative
will NOT be linked with that line item. The reason for this is that the 1x1 size is not present on the line item, even though the 300x250 size is. More info on this issue, and the associated error can be found
[here](https://developers.google.com/ad-manager/api/reference/v202308/OrderService.RequiredSizeError.Reason).

Use the below command to add in creative size overrides:
```bash
app/console order:add-creatives -o ORDER_ID -c CREATIVE_TEMPLATE_ID --force-new-creative --multiple-creatives-per-line-item 2 --override-creative-size '1x1,300x250'
```

Going off of the above option of having multiple creatives per line item, and the previously mentioned suffix option, there's also an option to append an index value at the end of each creative's name.
This option is `--append-loop-index`. It does not require another option to be present, but it makes the most sense to activate when the `--multiple-creatives-per-line-item` option is added. If you just activate it,
and don't add in the former option or add it in with a value of 1, then the index will always be 1. This will effectively add a '(1)' at the end of your creative name. If the `--multiple-creatives-per-line-item` option is added
with a value of 2 for example, then two creatives will be added to each line item, and the index of (1) and (2) will be appended to the end of each creative name. The index is offset by 1, so that we don't start counting from 0.

For example, you can run the following command with the :
```bash
app/console order:add-creatives -o ORDER_ID -c CREATIVE_TEMPLATE_ID --force-new-creative --multiple-creatives-per-line-item 2 --override-creative-size '1x1,300x250' --append-loop-index
```

This way the new creative's names will be built based on the line-item name, its first creative placeholder size and given suffix, for example new creative's name can look like:
`ztest MR 300x250 - 300x250 (test)`
where:
* `ztest MR 300x250 0.01` was the line item name (the price part was removed),
* `300x250` is the first creative placeholder size,
* `(test)` is the given suffix
The command which created such creative could have looked like this:
```bash
app/console order:add-creatives -o 123456 -c 1234567890 -s "(test)"
```

If you want to create new creative per each line in order add `--force-new-creative=1` option or its shorter version: `-f1`

Examples:
* `app/console order:add-creatives --order=2666092254 --creative-template=11899731 --creative-variables="bidderName:indexExchange;test1:test2"`
* `app/console order:add-creatives --order 2666092254 --creative-template 11899731 --creative-variables "var1:val1;bidderName:indexExchange;test1:test2"`
* `app/console order:add-creatives -o 2666092254 -c 11899731 -r "bidderName:indexExchange" -s "(send all-bids)"`
* `app/console order:add-creatives -o 01234567890 -c 01234567890 -s "(send all-bids)" -f1`

### Generate JSON slots config for Prebid.js bidder

Most likely when adding a new bidder you'll get a link to spreadsheet with all the slots, sizes and IDs needed for the integration to work. If you just copy the slot name, sizes and ID columns and put to a CSV similar to the example placed in generate-bidders-slots-json/generate-bidders-slots-json-sample.csv you can run one command to get the slots' config which you can put in AdEngine JS file. For example for Pubmatic bidder the command looks like this:

`app/console generate:bidders-slots-json -b pubmatic -f generate-bidders-slots-json/generate-bidders-slots-json-sample.csv`

Different bidders require different slot configs and CSV structures, more examples:
* AppNexus: `app/console generate:bidders-slots-json -b appnexus -f generate-bidders-slots-json/generate-bidders-slots-json-appnexus-sample.csv`,
* Magnite (Rubicon): AppNexus: `app/console generate:bidders-slots-json -b magnite -f generate-bidders-slots-json/generate-bidders-slots-json-magnite-sample.csv`,

## Cron jobs

A cron job is defined in k8s-cron-jobs directory:
* It is designed to periodically (4 times a day) approve suggested ad units
* It runs in k8s in the dev env
* It uses manually created credentials `adeng-query-tool-credentials`
* Its main job is to run `app/console suggested-adunits:approve`

To see logs, go to https://dashboard.poz-dev.k8s.wikia.net:30080/#!/job?namespace=dev, select job with name starting from `dfp-query-tool-` and click on "Logs" icon.

### How to build and deploy new version for cron jobs?

1. Bump the version in the TAG variable in `Makefile` and the `k8s-cron-jobs/dfp-query-tool-poz-dev.yaml` file
2. Run `make build` in order to build a dfp-query-tool image
3. Run `make push` in order to push the image to artifactory
4. Verify in [artifactory](https://artifactory.wikia-inc.com/ui/repos/tree/General/dockerv2-local%2Faden%2Fdfp-query-tool) if the image with correct tag (version) has been created
5. Run `make delete` in order to remove existing cronjob from k8s
6. Run `make deploy` in order to create new cronjob in k8s
7. Verify in [k8s dashboard](https://dashboard.poz-dev.k8s.wikia.net:30080/#/search?namespace=dev&q=dfp-query) if the container has been created

### Troubleshooting

If the built image somehow does not get pushed to the k8s check the latest version of [`k8s-deployer`](https://artifactory.wikia-inc.com/ui/repos/tree/General/dockerv2-local%2Fops%2Fk8s-deployer) and update it in the `dfp-query-tool-poz-dev.yaml` file.

In order to get to the k8s dashboard you need to use [Valut](https://wikia-inc.atlassian.net/wiki/spaces/OPS/pages/132317429/Vault%2BFor%2BEngineers) to get the dashboard-user token.

When adding a new command, with a new php file to the app/console file, make sure to run `composer install`. You may get some odd errors about the php file not being found, if you do not do this.

## Development

Useful resources for future development:
* https://developers.google.com/ad-manager/api/rel_notes
* https://github.com/googleads/googleads-php-lib/tree/master/examples/AdManager

### Tests

You can run `phpunit` tests executing:
```sh
$ ./vendor/bin/phpunit test
``` 
or (just a visual difference):
```sh
$ ./vendor/bin/phpunit test --color --testdox
``` 
