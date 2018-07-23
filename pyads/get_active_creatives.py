# Import appropriate modules from the client library.
from googleads import dfp
from datetime import datetime
from tqdm import tqdm



def main(client):

  line_item_service = client.GetService('LineItemService', version='v201805')

  # Create a statement to select line items.
  statement = dfp.StatementBuilder()

  # Retrieve a small amount of line items at a time, paging
  # through until all line items have been retrieved.

  text_to_look_for = "http://"

  pages = 20
  response = line_item_service.getLineItemsByStatement(statement.ToStatement(
  ))

  active_line_items = {}

  # print(int(response['totalResultSetSize']))
  #
  # print(int(int(response['totalResultSetSize'])/statement.limit))

  for i in tqdm(range(int(int(response['totalResultSetSize'])/statement.limit))):
    response = line_item_service.getLineItemsByStatement(statement.ToStatement(
    ))
    if 'results' in response and len(response['results']):
    # if 'results' in response and pages > 0:
      for line_item in response['results']:
        if line_item['endDateTime'] is not None:
          date = line_item['endDateTime']['date']
          if datetime(date['year'], date['month'], date['day']) > datetime.now():
            active_line_items[line_item['id']] = line_item['name']
        else:
          active_line_items[line_item['id']] = line_item['name']
      statement.offset += statement.limit
      pages -= 1
    else:
      break

  print('Line items found: {}'.format(len(active_line_items)))

  lica_service = client.GetService(
      'LineItemCreativeAssociationService', version='v201805')

  # Get All creatives associated with active line items
  print('GET CREATIVES FROM LINE ITEMS')

  creatives_to_scan = {}
  for line_item_id in tqdm(active_line_items):
    statement = (dfp.StatementBuilder()
                 .Where('lineItemId = :lineItemId')
                 .WithBindVariable('lineItemId', line_item_id))

    while True:
      response = lica_service.getLineItemCreativeAssociationsByStatement(
        statement.ToStatement())
      if 'results' in response and len(response['results']):
        for lica in response['results']:
          if lica['status'] == "ACTIVE":
            creatives_to_scan[lica['creativeId']] = lica['lineItemId']
        statement.offset += statement.limit
      else:
        break

  print('Numer of creatives asociated with line items: {}'.format(len(creatives_to_scan)))

  creative_service = client.GetService(
      'CreativeService', version='v201805')

  # Look for text in all creatives

  bad_boy_creatives = {}

  print('SCANNING CREATIVES')

  for creative_id in tqdm(creatives_to_scan):
    statement = (dfp.StatementBuilder()
                 .Where('creativeId = :creativeId')
                 .WithBindVariable('creativeId', creative_id))

    while True:
      response = creative_service.getCreativesByStatement(statement.ToStatement())
      if 'results' in response and len(response['results']):
        for creative in response['results']:
          # Print out some information for each line item creative association.
          # if lica['status'] == "ACTIVE":
          #   creatives_to_scan[lica['creativeId']] = lica['lineItemId']
          # print(creative)
          if 'snippet' in creative and text_to_look_for in creative['snippet']:
            bad_boy_creatives[creative['id']] = creative['name']
            continue
          if 'expandedSnippet' in creative and text_to_look_for in creative['expandedSnippet']:
            bad_boy_creatives[creative['id']] = creative['name']
            continue
          if 'htmlSnippet' in creative and text_to_look_for in creative['htmlSnippet']:
            bad_boy_creatives[creative['id']] = creative['name']
            continue
          for field in creative['customFieldValues']:
            print(field)

        statement.offset += statement.limit
      else:
        break

  print('Numer of  BAD creatives: {}'.format(len(bad_boy_creatives)))

if __name__ == '__main__':
  yaml_string = "dfp: " + "\n" + \
                "  application_name: Wikia - DFP\n" + \
                "  network_code: " + str(5441) + "\n" + \
                "  path_to_private_key_file: config/access.json\n"

  # Initialize the DFP client.
  dfp_client = dfp.DfpClient.LoadFromString(yaml_string)
  main(dfp_client)