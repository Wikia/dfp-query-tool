# Import appropriate modules from the client library.
# from googleads import dfp
from datetime import datetime
from tqdm import tqdm
from googleads import ad_manager
import googleads

import sys

def main(client):

  # line_item_service = client.GetService('LineItemService', version='v201805')

  # Create a statement to select line items.
  # statement = dfp.StatementBuilder()

  # Retrieve a small amount of line items at a time, paging
  # through until all line items have been retrieved.

  text_to_look_for = "http://"

  pages = 1
  # response = line_item_service.getLineItemsByStatement(statement.ToStatement(
  # ))

  active_line_items = {}
  line_items = {}

  # print("FETCHING LINE ITEMS")
  #
  # for i in tqdm(range(int(int(response['totalResultSetSize'])/statement.limit))):
  #   response = line_item_service.getLineItemsByStatement(statement.ToStatement(
  #   ))
  #   # if 'results' in response and len(response['results']):
  #   if 'results' in response and pages > 0:
  #     for line_item in response['results']:
  #       line_items[line_item['id']] = line_item
  #     statement.offset += statement.limit
  #     pages -= 1
  #   else:
  #     break
  #
  # print('Line items fetched: {}'.format(len(line_items)))
  #
  # pages = 1
  #
  # lica_service = client.GetService(
  #     'LineItemCreativeAssociationService', version='v201805')
  #
  # statement = dfp.StatementBuilder()
  #
  # # Get All creatives associated with active line items
  # print('FETCHING ASSOCIATIONS')
  #
  # creatives_to_scan = {}
  # associations = {}
  #
  # response = lica_service.getLineItemCreativeAssociationsByStatement(statement.ToStatement(
  # ))
  #
  # lica_id = 0
  # for i in tqdm(range(int(int(response['totalResultSetSize'])/statement.limit))):
  #   response = lica_service.getLineItemCreativeAssociationsByStatement(statement.ToStatement(
  #   ))
  #   # if 'results' in response and len(response['results']):
  #   if 'results' in response and pages > 0:
  #     for lica in response['results']:
  #       associations[lica_id] = lica
  #       lica_id += 1
  #     statement.offset += statement.limit
  #     pages -= 1
  #   else:
  #     break
  #
  # print('Associations items fetched: {}'.format(len(associations)))

  pages = 1



  creative_service = client.GetService('CreativeService', version='v201805')

  creative_statement = (ad_manager.StatementBuilder()
               #          .Where('creativeID = :creativeType')
               # .WithBindVariable('creativeType', 'CustomCreative')
                        )
  creative_statement.limit = 1
  # creative_statement.offset = 150

  creatives = {}

  response = creative_service.getCreativesByStatement(creative_statement.ToStatement())

  creative_statement.limit = 500

  limit = int(response['totalResultSetSize'])

  for i in tqdm(range(int(limit / creative_statement.limit))):
    try:
      response = creative_service.getCreativesByStatement(creative_statement.ToStatement())
      if 'results' in response and len(response['results']):
      # if 'results' in response and pages > 0:
        for creative in response['results']:
          creatives[creative['id']] = creative
          # print(creative['id'])
        creative_statement.offset += creative_statement.limit
        pages -= 1
      else:
        creative_statement.offset += creative_statement.limit
        if creative_statement.offset > limit:
          break
      # print(creative_statement.offset)
      # print(limit)
    except:
      if 'results' in response and len(response['results']):
        for creative in response['results']:
          creatives[creative['id']] = creative
          # print(creative['id'])
      creative_statement.offset += creative_statement.limit
      pages -= 1
      # print(response)

  print('Creatives items fetched: {}'.format(len(creatives)))
  #
  # for line_item in tqdm(line_items):
  #   date = line_item['endDateTime']['date']
  #   if datetime(date['year'], date['month'], date['day']) > datetime.now():
  #     active_line_items[line_item['id']] = line_item
  #   else:
  #     active_line_items[line_item['id']] = line_item
  #
  # print('ACTIVE line items: {}'.format(len(active_line_items)))
  #
  # for lica in tqdm(associations):
  #   if lica['lineItemId'] in active_line_items and lica['status'] == "ACTIVE":
  #     creatives_to_scan[lica['creativeId']] = lica['lineItemId']
  #
  # print('ASSOCIATED CREATIVES FOUND: {}'.format(len(creatives_to_scan)))
  #
  # bad_boy_creatives = {}
  #
  # print('SCANNING CREATIVES')
  #
  # for creative in tqdm(creatives_to_scan):
  #   if 'snippet' in creative and text_to_look_for in creative['snippet']:
  #     bad_boy_creatives[creative['id']] = creative['name']
  #     continue
  #   if 'expandedSnippet' in creative and text_to_look_for in creative['expandedSnippet']:
  #     bad_boy_creatives[creative['id']] = creative['name']
  #     continue
  #   if 'htmlSnippet' in creative and text_to_look_for in creative['htmlSnippet']:
  #     bad_boy_creatives[creative['id']] = creative['name']
  #     continue
  #   for field in creative['customFieldValues']:
  #     print(field)
  #
  # print('Numer of  BAD CREATIVES: {}'.format(len(bad_boy_creatives)))
  # for item in bad_boy_creatives:
  #   print(item)


if __name__ == '__main__':
  sys.stdout = open("log.txt", "w")

  yaml_string = "ad_manager: " + "\n" + \
                "  application_name: Wikia - DFP\n" + \
                "  network_code: " + str(5441) + "\n" + \
                "  path_to_private_key_file: config/access.json\n"

  # Initialize the DFP client.
  dfp_client = ad_manager.AdManagerClient.LoadFromString(yaml_string)
  main(dfp_client)

  sys.stdout.close()