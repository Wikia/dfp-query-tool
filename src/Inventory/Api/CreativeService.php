<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\Util\v202105\StatementBuilder;
use Google\AdsApi\AdManager\v202105\Size;
use Google\AdsApi\AdManager\v202105\TemplateCreative;
use Google\AdsApi\AdManager\v202105\CreativeTemplateService;
use Google\AdsApi\AdManager\v202105\StringCreativeTemplateVariableValue;

class CreativeService {
	const PAGE_SIZE = 250;

	private $creativeService;
	private $creativeTemplateService;

	function __construct() {
		$this->creativeService = AdManagerService::get(\Google\AdsApi\AdManager\v202105\CreativeService::class);
		$this->creativeTemplateService = AdManagerService::get(CreativeTemplateService::class);
	}

	public function createFromTemplate($form) {
		list($width, $height) = explode('x', trim($form['sizes']));

		$creative = new TemplateCreative();
		$creative->setName($form['creativeName']);
		$creative->setAdvertiserId($form['advertiserId']);
		$creative->setCreativeTemplateId($form['creativeTemplateId']);
		$creative->setSize(new Size(intval($width), intval($height), false));

		$this->setCreativeTemplateVariableValues($creative, $form['variables']);

		$result = $this->creativeService->createCreatives([$creative]);

		return $result[0]->getId();
	}

	public function find($output, $fragments = [])
	{
		try {
			$statementBuilder = (new StatementBuilder())
				->orderBy('id DESC')
				->limit(self::PAGE_SIZE);

			$this->findResults(function () use ($statementBuilder) {
				return $this->creativeService->getCreativesByStatement(
					$statementBuilder->toStatement()
				);
			}, $statementBuilder, $output, 'creative', $fragments);
		} catch (\Exception $e) {
			printf("%s\n", $e->getMessage());
		}
	}

	public function findCreativeTemplates($output, $fragments = [])
	{
		try {
			$statementBuilder = (new StatementBuilder())
				->orderBy('id DESC')
				->limit(self::PAGE_SIZE);

			$this->findResults(function () use ($statementBuilder) {
				return $this->creativeTemplateService->getCreativeTemplatesByStatement(
					$statementBuilder->toStatement()
				);
			}, $statementBuilder, $output, 'creative template', $fragments);
		} catch (\Exception $e) {
			printf("%s\n", $e->getMessage());
		}
	}

	private function findResults($getPageCallback, $statementBuilder, $output, $type, $fragments)
	{
		$found = [];
		$creatives = [];
		$times = [];
		foreach ($fragments as $fragment) {
			$found[$fragment] = 0;
		}

		$iteration = 1;
		do {
			$start = microtime(true);
			try {
				$page = $getPageCallback();
			} catch (\Exception $e) {
				printf("Error occurred");
				$statementBuilder->increaseOffsetBy(self::PAGE_SIZE);
				continue;
			}
			$i = $page->getStartIndex();
			$totalResultSetSize = $page->getTotalResultSetSize();

			if ($page->getResults() !== null) {
				foreach ($page->getResults() as $creative) {
					if (method_exists($creative, 'getSnippet')) {
						$snippets = [$creative->getSnippet()];
					} elseif (method_exists($creative, 'getCreativeTemplateVariableValues')) {
						$variables = $creative->getCreativeTemplateVariableValues();
						$snippets = [];
						if ($variables !== null) {
							foreach ($variables as $variable) {
								if (method_exists($variable, 'getValue')) {
									$snippets[] = $variable->getValue();
								}
							}
						}
					} elseif (method_exists($creative, 'getHtmlSnippet')) {
						$snippets = [$creative->getHtmlSnippet()];
					} else {
						continue;
					}

					foreach ($fragments as $fragment) {
						foreach ($snippets as $snippet) {
							if (strpos(strtolower($snippet), $fragment) !== false) {
								$found[$fragment] += 1;
								$class = get_class($creative);
								$creatives[] = [
									'id' => $creative->getId(),
									'type' => substr($class, strrpos($class, '\\') + 1),
									'name' => $creative->getName(),
									'fragment' => $fragment
								];
								break;
							}
						}
					}
				}

				$duration = microtime(true) - $start;
				$times[] = $duration;
				$meanTime = array_sum($times)/count($times);

				printf(
					"Processed %d/%d, ETA: %.2fs\n",
					min($i + self::PAGE_SIZE, $totalResultSetSize),
					$totalResultSetSize,
					$meanTime * ($totalResultSetSize - $i) / self::PAGE_SIZE
				);
			}
			$statementBuilder->increaseOffsetBy(self::PAGE_SIZE);
			$iteration += 1;
		} while ($statementBuilder->getOffset() < $totalResultSetSize);

		$file = fopen($output, 'w');
		foreach ($creatives as $creative) {
			$line = sprintf("%d\t%s\t%s\t%s\n", $creative['id'], $creative['type'], $creative['name'], $creative['fragment']);
			fwrite($file, $line);
		}
		fclose($file);
		print("\n\n");
		foreach ($fragments as $fragment) {
			printf(
				"Found %d/%d %ss containing \"%s\"\n",
				$found[$fragment],
				$totalResultSetSize,
				$type,
				$fragment
			);
		}
	}

	private function setCreativeTemplateVariableValues($creative, $variables)
	{
		if (!is_array($variables) || empty($variables)) {
		    return;
		}

		$creativeTemplateVariables = [];
		foreach($variables as $var => $val) {
			$creativeTemplateVariables[] = new StringCreativeTemplateVariableValue($var, $val);
		}

		$creative->setCreativeTemplateVariableValues($creativeTemplateVariables);
	}
}
