<?php

/**
 * Stripe Payments
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2016, Ismayil Khayredinov
 * @copyright Copyright (c) 2016, Social Business World
 */
require_once __DIR__ . '/autoloader.php';

use hypeJunction\Payments\Stripe\Page;
use hypeJunction\Payments\Stripe\Payments;
use hypeJunction\Payments\Stripe\Router;

elgg_register_event_handler('init', 'system', function() {

	elgg_extend_view('elgg.css', 'payments/stripe.css');
	
	elgg_define_js('Stripe', [
		'src' => 'https://js.stripe.com/v2/stripe.js',
		'exports' => 'Stripe',
	]);

	elgg_register_plugin_hook_handler('elgg.data', 'page', [Page::class, 'setPublishableKey']);

	elgg_register_plugin_hook_handler('route', 'payments', [Router::class, 'controller'], 100);
	elgg_register_plugin_hook_handler('public_pages', 'walled_garden', [Router::class, 'setPublicPages']);

	elgg_register_plugin_hook_handler('refund', 'payments', [Payments::class, 'refundTransaction']);

	elgg_register_action('payments/checkout/stripe', __DIR__ . '/actions/payments/checkout/stripe.php', 'public');
});


