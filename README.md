# GAM query tool

Application allows communication with GAM through API and serves Tableau web-data-connector. Project developed during the Wikia Summer Hackathon 2016.

## CLI usage

### Create line items

Prepare json based on [prebid20.sample.json](./line-item-presets/prebid20.sample.json) and execute command:

```bash
app/console line-item:create ./line-item-presets/<your-configuration>.json
``` 

It will create multiple line items in the provided order with associated creative.

## Cron jobs

A cronjob is defined in k8s-cron-jobs directory.
It is designed to periodically (4 times a day) approve suggested ad units.
It runs in k8s in the dev env.
It uses manually created credentials `adeng-query-tool-credentials`.

To see logs, go to https://dashboard.poz-dev.k8s.wikia.net:30080/#!/job?namespace=dev,
select job with name starting from `dfp-query-tool-` and click on "Logs" icon.
