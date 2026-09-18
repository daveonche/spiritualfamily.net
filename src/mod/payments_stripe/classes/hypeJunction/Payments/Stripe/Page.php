<?php

namespace hypeJunction\Payments\Stripe;

class Page {
	
	/**
	 * Add publishable key to JS elgg._data
	 * 
	 * @param string $hook   "elgg.data"
	 * @param string $type   "page"
	 * @param array  $return Page data
	 * @param array  $params Hook params
	 * @return array
	 */
	public static function setPublishableKey($hook, $type, $return, $params) {

		$mode = elgg_get_plugin_setting('environment', 'payments', 'sandbox');
		if ($mode == 'production') {
			$key = elgg_get_plugin_setting('live_publishable_key', 'payments_stripe');
		} else {
			$key = elgg_get_plugin_setting('test_publishable_key', 'payments_stripe');
		}

		$return['stripe']['key'] = $key;
		return $return;
	}
}
