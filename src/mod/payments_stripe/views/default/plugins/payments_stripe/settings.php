<?php

$entity = elgg_extract('entity', $vars);

$url = elgg_normalize_url("payments/stripe/webhook");
echo elgg_format_element('div', [], elgg_echo('payments:stripe:webhok_url', [$url]));

$fields = [
	'test_secret_key',
	'test_publishable_key',
	'live_secret_key',
	'live_publishable_key',
];

foreach ($fields as $field) {
	echo elgg_view_field([
		'#type' => 'text',
		'#label' => elgg_echo("payments:stripe:$field"),
		'name' => "params[$field]",
		'value' => $entity->$field,
	]);
}
