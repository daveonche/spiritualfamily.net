<?php

return [

	'payments:method:stripe' => 'Credit Card',
	
	'payments:stripe:test_secret_key' => 'Test Secret Key',
	'payments:stripe:test_publishable_key' => 'Test Publishable Key',
	'payments:stripe:live_secret_key' => 'Live Secret Key',
	'payments:stripe:live_publishable_key' => 'Live Publishable Key',

	'payments:stripe:no_source' => 'Payment source is missing',
	'payments:stripe:card_error' => 'Payment with this card has failed',

	'payments:stripe:card_error:invalid_number' => 'The card number is not a valid credit card number',
	
	'payments:stripe:card_error:invalid_expiry_month' => 'The card\'s expiration month is invalid',
	'payments:stripe:card_error:invalid_expiry_year' => 'The card\'s expiration year is invalid',
	'payments:stripe:card_error:invalid_cvc' => 'The card\'s security code is invalid',
	'payments:stripe:card_error:incorrect_number' => 'The card number is incorrect',
	'payments:stripe:card_error:expired_card' => 'The card has expired',
	'payments:stripe:card_error:incorrect_cvc' => 'The card\'s security code is incorrect',
	'payments:stripe:card_error:incorrect_zip' => 'The card\'s zip code failed validation',
	'payments:stripe:card_error:card_declined' => 'The card was declined',
	'payments:stripe:card_error:missing' => 'There is no card on a customer that is being charged',
	'payments:stripe:card_error:processing_error' => 'An error occurred while processing the card',

	'payments:stripe:api_error' => 'There was a problem contacting the card processor. Please try again later',

	'payments:charges:stripe_fee' => 'Processing Fee',

	'payments:stripe:card' => 'Credit Card',
	'payments:stripe:card:name' => 'Cardholder Name',
	'payments:stripe:card:number' => 'Card Number',
	'payments:stripe:card:expiry' => 'Expires',
	'payments:stripe:card:cvc' => 'CVC',
	
	'payments:stripe:billing' => 'Billing Address',
	'payments:stripe:card:address_line1'=> 'Street Address',
	'payments:stripe:card:address_line2'=> 'Street Address 2',
	'payments:stripe:card:address_city'=> 'City/Town',
	'payments:stripe:card:address_state'=> 'Region/State',
	'payments:stripe:card:address_zip'=> 'Postal Code',
	'payments:stripe:card:address_country'=> 'Country',

	'payments:stripe:validating' => 'Validating...',
	
	'payments:stripe:pay:paid' => 'Your payment was successfully received',
	'payments:stripe:pay:failed' => 'Payment has failed',
	'payments:stripe:pay:payment_pending' => 'The charge was successful and the payment is pending',

	'payments:stripe:webhok_url' => '<b>Webhook URL</b>: %s',
];