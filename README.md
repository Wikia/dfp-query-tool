# DFP query tool

Application allows communication with DFP through API and serves Tableau web-data-connector. Project developed during the Wikia Summer Hackathon 2016.

## CLI usage

### Create line items

Prepare json based on [prebid20.sample.json](./line-item-presets/prebid20.sample.json) and execute command:

```bash
app/console line-item:create ./line-item-presets/<your-configuration>.json
``` 

It will create multiple line items in the provided order with associated creative.
