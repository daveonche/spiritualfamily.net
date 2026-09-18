<?php
echo elgg_view_field([
	'#type' => 'fieldset',
	'align' => 'horizontal',
	'legend' => elgg_echo('payments:stripe:card'),
	'fields' => [
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:name'),
			'required' => true,
			'data-stripe' => 'name',
		],
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:number'),
			'required' => true,
			'data-stripe' => 'number',
			'length' => 12,
		],
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:cvc'),
			'required' => true,
			'data-stripe' => 'cvc',
			'length' => 4,
		],
			[
			'#type' => 'fieldset',
			'#label' => elgg_echo('payments:stripe:card:expiry'),
				'required' => true,
			'align' => 'horizontal',
			'fields' => [
					[
					'#type' => 'select',
					'options' => range(1, 12),
					'required' => true,
					'data-stripe' => 'exp_month',
				],
					[
					'#type' => 'select',
					'options' => range((int) date('Y'), (int) date('Y') + 20),
					'required' => true,
					'data-stripe' => 'exp_year',
				],
			]
		],
	]
]);

echo elgg_view_field([
	'#type' => 'fieldset',
	'align' => 'horizontal',
	'legend' => elgg_echo('payments:stripe:billing'),
	'fields' => [
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:address_line1'),
			'required' => true,
			'data-stripe' => 'address_line1',
			'name' => 'billing[street_address]',
		],
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:address_line2'),
			'data-stripe' => 'address_line2',
			'name' => 'billing[extended_address]',
		],
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:address_city'),
			'required' => true,
			'data-stripe' => 'address_city',
			'name' => 'billing[locality]',
		],
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:address_state'),
			'required' => true,
			'data-stripe' => 'address_state',
			'name' => 'billing[region]',
		],
			[
			'#type' => 'text',
			'#label' => elgg_echo('payments:stripe:card:address_zip'),
			'required' => true,
			'data-stripe' => 'address_zip',
			'name' => 'billing[postal_code]',
		],
			[
			'#type' => 'country',
			'#label' => elgg_echo('payments:stripe:card:address_country'),
			'required' => true,
			'data-stripe' => 'address_country',
			'name' => 'billing[country_code]',
		],
	]
]);

echo elgg_format_element('div', [
	'class' => 'stripe-card-error',
		], elgg_format_element('p'));
?>
<script>
	require(['input/stripe/card']);
</script>