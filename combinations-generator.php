<?php

$input = [
	[ 'hb_' ],
	[ 'bidder_', 'pb_', 'adid_', 'size_' ],
	[
		'33across',
		'aol',
		'appnexus',
		'appnexusAst',
		'beachfront',
		'criteo',
		'gumgum',
		'indexExchange',
		'kargo',
		'lkqd',
		'onemobile',
		'openx',
		'pubmatic',
		'rubicon',
		'rubicon_display',
		'teads',
		'triplelift',
		'vmg',
		'wikia',
		'wikiaVideo',
	],
];

function generateCombinations($arrays, $i = 0)
{
	if (!isset($arrays[$i])) {
		return array();
	}
	if ($i == count($arrays) - 1) {
		return $arrays[$i];
	}

	$tmp = generateCombinations($arrays, $i + 1);

	$result = array();
	foreach ($arrays[$i] as $v) {
		foreach ($tmp as $t) {
			$result[] = implode('', [$v, $t]);
		}
	}

	return $result;
}

foreach (generateCombinations($input) as $row) {
	print(substr($row,  0, 20) . "\n");
}
