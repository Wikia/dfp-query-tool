<?php

namespace Report\Database;

use Doctrine\DBAL\Exception\NonUniqueFieldNameException;
use Doctrine\Instantiator\Exception\InvalidArgumentException;
use Report\Api\ReportService;

class Database
{
	const TYPE_MAPPING = [
		'ad_unit_name' => 'TEXT',
		'creative_id' => 'BIGINT',
		'creative_name' => 'TEXT',
		'creative_size' => 'TEXT',
		'country' => 'TEXT',
		'custom_criteria' => 'TEXT',
		'date' => 'TEXT',
		'device_category' => 'TEXT',
		'key_values' => 'TEXT',
		'line_item_id' => 'BIGINT',
		'line_item_name' => 'TEXT',
		'order_id' => 'BIGINT',
		'order_name' => 'TEXT',
		'targeting_value_id' => 'BIGINT',
		'total_active_view_eligible_impressions' => 'BIGINT',
		'total_active_view_measurable_impressions' => 'BIGINT',
		'total_active_view_viewable_impressions' => 'BIGINT',
		'total_active_view_measurable_impressions_rate' => 'FLOAT',
		'total_active_view_viewable_impressions_rate' => 'FLOAT'
	];

	private $db;

	public function __construct($container) {
		$this->db = $container['db'];
	}

	public function updateTable($name, $query) {
		$createTableSql = sprintf(
			'CREATE TABLE IF NOT EXISTS %s (DATE DATE NOT NULL) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;',
			$name
		);
		$this->db->exec($createTableSql);
		$columnSql = <<<EOT
ALTER TABLE %s ADD %s %s NULL;
EOT;

		foreach ($query['dimensions'] as $column) {
			$sql = sprintf(
				$columnSql,
				$name,
				ReportService::DIMENSION_MAPPING[$column],
				self::TYPE_MAPPING[$column]
			);
			try {
				$this->db->exec($sql);
			} catch (NonUniqueFieldNameException $ignore) {}
		}
		foreach ($query['metrics'] as $column) {
			$sql = sprintf(
				$columnSql,
				$name,
				ReportService::COLUMN_MAPPING[$column],
				self::TYPE_MAPPING[$column]
			);
			try {
				$this->db->exec($sql);
			} catch (NonUniqueFieldNameException $ignore) {}
		}
	}

	public function insertResults($name, $query, $results) {
		if (!is_array($results)) {
			throw new InvalidArgumentException($results);
		}

		$date = new \DateTime('-1 day');
		$date->setTime(0, 0, 0);
		$this->removeDuplicates($name, $date);
		$columnsCanonical = [ 'date' ];
		$columns = [ 'DATE' ];
		$placeholders = [ '?' ];
		foreach ($query['dimensions'] as $dimension) {
			$columns[] = ReportService::DIMENSION_MAPPING[$dimension];
			$columnsCanonical[] = $dimension;
			$placeholders[] = '?';
		}
		foreach ($query['metrics'] as $metric) {
			$columns[] = ReportService::COLUMN_MAPPING[$metric];
			$columnsCanonical[] = $metric;
			$placeholders[] = '?';
		}

		$columnsString = implode(',', $columns);
		$dateString = $date->format('Y-m-d');
		$placeholdersString = implode(',', $placeholders);
		$sql = sprintf('INSERT INTO %s (%s) VALUES (%s);', $name, $columnsString, $placeholdersString);

		foreach ($results as $result) {
			$values = [ $dateString ];
			for ($i = 1; $i < count($columns); $i++) {
				$value = $result[$columns[$i]];
				if (self::TYPE_MAPPING[$columnsCanonical[$i]] === 'BIGINT') {
					$value = (int) $value;
				}
				if (self::TYPE_MAPPING[$columnsCanonical[$i]] === 'FLOAT') {
					$value = (float) $value;
				}
				$values[] = $value;
			}

			$this->db->executeUpdate($sql, $values);
		}
	}

	private function removeDuplicates($name, $date) {
		$query = sprintf(
			'DELETE FROM %s WHERE DATE = "%s"',
			$name,
			$date->format('Y-m-d')
		);

		$this->db->exec($query);
	}
}