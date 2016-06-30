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
			$filter = ReportService::DIMENSION_MAPPING[$type];
			$key = $type . '_' . $i;
			$statements[] = $filter . ' = :' . $key;
			if ($value === '') {
				continue;
			}
			$statementBuilder->WithBindVariableValue($key, self::parseValue($type, $value));
			$i++;
		}
		$statementBuilder->Where(implode(' and ', $statements));

		return $statementBuilder->ToStatement();
	}

	private static function parseValue($type, $value) {
		if (strpos($type, 'Id') !== false || strpos($type, '_id') !== false) {
			$value = intval($value);
		}

		return $value;
	}
}