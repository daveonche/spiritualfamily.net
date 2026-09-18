<?php

namespace hypeJunction\Payments\Stripe;

class Router {

	/**
	 * Route payment pages
	 *
	 * @param string $hook   "route"
	 * @param string $type   "payments"
	 * @param mixed  $return New route
	 * @param array  $params Hook params
	 * @return array
	 */
	public static function controller($hook, $type, $return, $params) {

		if (!is_array($return)) {
			return;
		}

		$segments = (array) elgg_extract('segments', $return);

		if ($segments[0] !== 'stripe') {
			return;
		}

		$forward_reason = null;

		$adapter = new Adapter();
		switch ($segments[1]) {
			case 'webhook' :
				if ($adapter->digestWebhook()) {
					echo 'Webhook digested';
					return false;
				}
				$forward_url = '';
				$forward_reason = (string) ELGG_HTTP_BAD_REQUEST;
				break;
		}

		if (isset($forward_url)) {
			forward($forward_url, $forward_reason);
		}
	}

	/**
	 * Add IPN processor to public pages
	 *
	 * @param string $hook   "public_pages"
	 * @param string $type   "walled_garden"
	 * @param array  $return Public pages
	 * @param array  $params Hook params
	 * @return array
	 */
	public static function setPublicPages($hook, $type, $return, $params) {
		$return[] = 'payments/stripe/.*';
		return $return;
	}

}
