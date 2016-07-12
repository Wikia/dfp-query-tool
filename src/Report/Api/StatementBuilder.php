<?php

namespace Report\Api;

use Symfony\Component\HttpFoundation\ParameterBag;

class StatementBuilder
{
	public static function build(ParameterBag $parameters) {
		$statementBuilder = new \StatementBuilder();
		$statements = [];
		$i = 1;
		foreach ($parameters->get('filters') as $type => $value) {
			if ($value === '') {
				continue;
			}
			$filter = ReportService::DIMENSION_MAPPING[$type];
			$statements[] = self::buildStatement($statementBuilder, $i, $filter, $type, $value);
			$i++;
		}
		$statementBuilder->Where(implode(' and ', $statements));

		return $statementBuilder->ToStatement();
	}

	private static function buildStatement($statementBuilder, $index, $filter, $type, $values) {
		if (!is_array($values)) {
			$values = explode(',', $values);
		}
		$keys = [];
		$i = 1;
		foreach ($values as $value) {
			$key = sprintf('%s_%d_%d', $type, $index, $i);
			$keys[] = ':' . $key;
			$statementBuilder->WithBindVariableValue($key, self::parseValue($type, trim($value)));
			$i++;
		}

		return sprintf('%s in (%s)', $filter, implode(',', $keys));
	}

	private static function parseValue($type, $value) {
		if (strpos($type, 'Id') !== false || strpos($type, '_id') !== false) {
			$value = intval($value);
		}

		return $value;
	}
}