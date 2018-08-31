# Import appropriate modules from the client library.
from datetime import datetime
from tqdm import tqdm
import shelve
from googleads import ad_manager
import argparse

def fetch_line_items(client):

  active_line_items = {}
  db_line_items = shelve.open('data/line_items.db')

  line_item_service = client.GetService('LineItemService', version='v201805')

  # Create a statement to select line items.
  statement = ad_manager.StatementBuilder()

  # Retrieve a small amount of line items at a time, paging
  # through until all line items have been retrieved.
  response = line_item_service.getLineItemsByStatement(statement.ToStatement())

  for i in tqdm(range(int(int(response['totalResultSetSize']) / statement.limit))):
    response = line_item_service.getLineItemsByStatement(statement.ToStatement(
    ))
    if 'results' in response and len(response['results']):
      for line_item in response['results']:
        db_line_items[str(line_item['id'])] = line_item
        if line_item['endDateTime'] is not None:
          date = line_item['endDateTime']['date']
          if datetime(date['year'], date['month'], date['day']) > datetime.now():
            active_line_items[line_item['id']] = line_item['name']
        else:
          active_line_items[line_item['id']] = line_item['name']
      statement.offset += statement.limit
    else:
      break

  db_line_items['active_line_items'] = active_line_items
  db_line_items.close()

  print("LINE ITEMS FETCHED: {}".format(len(active_line_items)))


def fetch_associatiated_creatives(client):

  creatives = {}
  db_creatives = shelve.open('data/creatives.db')

  lica_service = client.GetService(
    'LineItemCreativeAssociationService', version='v201805')

  # Get All creatives associated with active line items
  print('GET CREATIVES FROM LINE ITEMS')

  for line_item_id in tqdm(active_line_items):
    statement = (ad_manager.StatementBuilder())

    while True:
      response = lica_service.getLineItemCreativeAssociationsByStatement(
        statement.ToStatement())
      if 'results' in response and len(response['results']):
        for lica in response['results']:
          if lica['status'] == "ACTIVE":
            creatives[lica['creativeId']] = lica['lineItemId']
            db_creatives[str(lica['creativeId'])] = lica['lineItemId']
        statement.offset += statement.limit
      else:
        break

  db_creatives['creatives'] = creatives
  db_creatives.close()

  print('Numer of associations fetched: {}'.format(len(creatives)))


def fetch_creatives(client, creatives_to_scan):

  db_creatives_to_scan = shelve.open('data/creatives_to_scan.db')

  creative_service = client.GetService(
    'CreativeService', version='v201805')

  # Look for text in all creatives
  print('SCANNING CREATIVES')

  for creative_id in tqdm(creatives_to_scan):
    statement = (ad_manager.StatementBuilder()
                 .Where('creativeId = :creativeId')
                 .WithBindVariable('creativeId', creative_id))

    while True:
      response = creative_service.getCreativesByStatement(statement.ToStatement())
      if 'results' in response and len(response['results']):
        for creative in response['results']:
          db_creatives_to_scan[str(creative['id'])] = creative
        statement.offset += statement.limit
      else:
        break

  print('Numer of creatives feteched: {}'.format(len(db_creatives_to_scan.items())))


def main(client):
  parser = argparse.ArgumentParser(description='--fetch if you want to fetch dfp configs')
  parser.add_argument('--fetch', help='use if you want to fetch data', action="store_true")
  args = parser.parse_args()

  text_to_look_for = "http://"

  if args.fetch:
    input("Press Enter to FETCH ...")
    fetch_line_items(client)
    fetch_associatiated_creatives(client)
  else:
    creatives_to_scan = {}

    db_line_items = shelve.open('data/line_items.db')
    db_creatives = shelve.open('data/creatives.db')

    active_line_items = db_line_items['active_line_items']
    creatives = db_creatives['creatives_to_scan']

    for creative_id, line_item_id in tqdm(creatives.items()):
      if line_item_id in active_line_items:
        creatives_to_scan[creative_id] = creatives[creative_id]

    print('Numer of creatives associatiated with line items: {}'.format(len(creatives_to_scan)))

    if args.fetch:
      fetch_creatives(client, creatives_to_scan)
    else:
      db_bad_creatives = shelve.open('data/bad_creatives.db')

      bad_creatives = {}

      db_creatives_to_scan = shelve.open('data/creatives_to_scan.db')

      print(len(creatives_to_scan))

      for creative_id, creative in tqdm(db_creatives_to_scan.items()):
        if 'snippet' in creative and text_to_look_for in creative['snippet']:
          bad_creatives[creative['id']] = creative['name']
          db_bad_creatives[str(creative['id'])] = creative
          continue
        if 'expandedSnippet' in creative and text_to_look_for in creative['expandedSnippet']:
          bad_creatives[creative['id']] = creative['name']
          db_bad_creatives[str(creative['id'])] = creative
          continue
        if 'htmlSnippet' in creative and text_to_look_for in creative['htmlSnippet']:
          bad_creatives[creative['id']] = creative['name']
          db_bad_creatives[str(creative['id'])] = creative
          continue

    print('Numer of bad creatives found: {}'.format(len(bad_creatives)))


if __name__ == '__main__':
  yaml_string = "ad_manager: " + "\n" + \
                "  application_name: Wikia - DFP\n" + \
                "  network_code: " + str(5441) + "\n" + \
                "  path_to_private_key_file: config/access.json\n"

  # Initialize the DFP client.
  dfp_client = ad_manager.AdManagerClient.LoadFromString(yaml_string)
  main(dfp_client)
