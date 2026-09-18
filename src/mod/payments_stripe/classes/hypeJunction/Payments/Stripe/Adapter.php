<?php

namespace hypeJunction\Payments\Stripe;

use hypeJunction\Payments\Amount;
use hypeJunction\Payments\CreditCard;
use hypeJunction\Payments\GatewayInterface;
use hypeJunction\Payments\Payment;
use hypeJunction\Payments\Refund;
use hypeJunction\Payments\Transaction;
use hypeJunction\Payments\TransactionInterface;
use Stripe\Account;
use Stripe\Charge;
use Stripe\CountrySpec;
use Stripe\Error\Base;
use Stripe\Error\Card;
use Stripe\Stripe;

class Adapter implements GatewayInterface {

	const API_VERSION = "2016-07-06";

	/**
	 * @var mixed
	 */
	protected $source;

	/**
	 * {@inheritdoc}
	 */
	public function pay(TransactionInterface $transaction) {

		if (!$this->source) {
			$transaction->setStatus(TransactionInterface::STATUS_FAILED);
			$error = elgg_echo('payments:stripe:no_source');
			return elgg_error_response($error);
		}

		$this->setup();

		$merchant = $transaction->getMerchant();
		$customer = $transaction->getCustomer();

		$description = $transaction->getDisplayName();
		if (!$description) {
			$description = "Payment to {$merchant->getDisplayName()}";
		}

		$order = $transaction->getOrder();
		$address = null;
		if ($order) {
			$shipping = $order->getShippingAddress();
			if ($shipping) {
				$address = [
					'city' => $shipping->locality,
					'country' => $shipping->country_code,
					'line1' => $shipping->street_address,
					'line2' => $shipping->extended_address,
					'postal_code' => $shipping->postal_code,
					'state' => $shipping->region,
				];
			}
		}

		$amount = $transaction->getAmount();

		try {
			$charge = Charge::create(array(
						'amount' => $amount->getAmount(),
						'currency' => $amount->getCurrency(),
						'source' => $this->source,
						'description' => $description,
						'metadata' => [
							'invoice_id' => $transaction->guid,
							'transaction_id' => $transaction->getId(),
						],
						'shipping' => [
							'name' => $customer->name,
							'phone' => $customer->phone,
							'address' => $address,
						],
						'receipt_email' => $customer->email,
						'statement_descriptor' => substr($description, 0, 22),
			));

			$transaction->stripe_charge_id = $charge->id;

			$source = $charge->source;

			$brands = [
				'Visa' => 'visa',
				'MasterCard' => 'mastercard',
				'American Express' => 'amex',
				'JCB' => 'jcb',
				'Diners Club' => 'diners',
				'Discover' => 'discover',
			];

			$cc = new CreditCard();
			$cc->last4 = $source->last4;
			$cc->brand = elgg_extract($source->brand, $brands, $source->brand);
			$cc->id = $source->id;
			$cc->exp_month = $source->exp_month;
			$cc->exp_year = $source->exp_year;

			$transaction->setFundingSource($cc);

			$this->updateTransactionStatus($transaction);

			$data = [
				'entity' => $transaction,
				'action' => 'pay',
			];

			$message = elgg_echo("payments:stripe:pay:{$transaction->getStatus()}");
			return elgg_ok_response($data, $message, $merchant->getURL());
		} catch (Card $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getJsonBody()), true), 'ERROR');

			$transaction->setStatus(TransactionInterface::STATUS_FAILED);

			$error = elgg_echo("payments:stripe:card_error:{$ex->getStripeCode()}");
			return elgg_error_response($error);
		} catch (Base $ex) {

			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getJsonBody()), true), 'ERROR');

			$transaction->setStatus(TransactionInterface::STATUS_FAILED);

			$error = elgg_echo("payments:stripe:api_error");
			return elgg_error_response($error);
		}
	}

	/**
	 * Update transaction status
	 *
	 * @param TransactionInterface $transaction Transaction
	 * @return TransactionInterface
	 */
	public function updateTransactionStatus(TransactionInterface $transaction) {

		$this->setup();

		if (!$transaction->stripe_charge_id) {
			return $transaction;
		}

		try {
			$charge = Charge::retrieve($transaction->stripe_charge_id);
		} catch (Base $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getJsonBody()), true), 'ERROR');
			return $transaction;
		}

		if ($charge->status == 'pending') {
			if ($transaction->status != TransactionInterface::STATUS_PAYMENT_PENDING) {
				$transaction->setStatus(TransactionInterface::STATUS_PAYMENT_PENDING);
			}
		} else if ($charge->status == 'failed') {
			if ($transaction->status != TransactionInterface::STATUS_FAILED) {
				$transaction->setStatus(TransactionInterface::STATUS_FAILED);
			}
		} else if ($charge->amount_refunded > 0) {
			$payments = $transaction->getPayments();
			$payment_ids = array_map(function($payment) {
				return $payment->stripe_refund_id;
			}, $payments);

			$processor_fee = $transaction->getProcessorFee()->getAmount();

			foreach ($charge->refunds->AutoPagingIterator() as $stripe_refund) {

				if (in_array($stripe_refund->id, $payment_ids)) {
					continue;
				}

				$stripe_balance_transaction = \Stripe\BalanceTransaction::retrieve($stripe_refund->balance_transaction);
				$processor_fee += $stripe_balance_transaction->fee;
				
				$refund = new Refund();
				$refund->setTimeCreated((int) $stripe_refund->created)
						->setAmount(new Amount(-$stripe_refund->amount, strtoupper($stripe_refund->currency)))
						->setPaymentMethod('stripe')
						->setDescription(elgg_echo('payments:refund'));
				$refund->stripe_refund_id = $stripe_refund->id;
				$transaction->addPayment($refund);
			}

			if ($charge->refunded) {
				if ($transaction->status != TransactionInterface::STATUS_REFUNDED) {
					$transaction->setStatus(TransactionInterface::STATUS_REFUNDED);
				}
			} else {
				if ($transaction->status != TransactionInterface::STATUS_PARTIALLY_REFUNDED) {
					$transaction->setStatus(TransactionInterface::STATUS_PARTIALLY_REFUNDED);
				}
			}

			$transaction->setProcessorFee(new Amount($processor_fee, $charge->currency));
		} else {
			if ($transaction->status != TransactionInterface::STATUS_PAID) {
				$payment = new Payment();
				$payment->setTimeCreated((int) $charge->created)
						->setAmount(new Amount((int) $charge->amount, strtoupper($charge->currency)))
						->setPaymentMethod('stripe')
						->setDescription(elgg_echo('payments:payment'));
				$payment->stripe_payment_id = $charge->id;
				$transaction->addPayment($payment);
				$transaction->setStatus(TransactionInterface::STATUS_PAID);

				$stripe_balance_transaction = \Stripe\BalanceTransaction::retrieve($charge->balance_transaction);

				$transaction->setProcessorFee(new Amount($stripe_balance_transaction->fee, $stripe_balance_transaction->currency));
			}
		}
	}

	/**
	 * Set source for the payment
	 * 
	 * @param mixed $source Source
	 * @return void
	 */
	public function setPaymentSource($source) {
		$this->source = $source;
	}

	/**
	 * {@inheritdoc}
	 */
	public function refund(TransactionInterface $transaction) {
		$this->setup();

		if (!$transaction->stripe_charge_id) {
			return $transaction;
		}

		try {
			\Stripe\Refund::create([
				'charge' => $transaction->stripe_charge_id,
				'metadata' => [
					'invoice_id' => $transaction->guid,
					'transaction_id' => $transaction->getId(),
				],
			]);
			$this->updateTransactionStatus($transaction);
			return true;
		} catch (Base $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getJsonBody()), true), 'ERROR');
			return false;
		}

		return true;
	}

	/**
	 * Setup Stripe client
	 * @return void
	 */
	public function setup() {

		$plugin = elgg_get_plugin_from_id('payments_stripe');
		$settings = $plugin->getAllSettings();

		$mode = elgg_get_plugin_setting('environment', 'payments', 'sandbox');

		if ($mode == 'production') {
			$secret = elgg_extract('live_secret_key', $settings);
		} else {
			$secret = elgg_extract('test_secret_key', $settings);
		}

		Stripe::setApiKey($secret);
		Stripe::setApiVersion(self::API_VERSION);
		Stripe::setAppInfo('payments_stripe', '1.0', 'http://github.com/hypeJunction/payments_stripe');
	}

	/**
	 * Returns Stripe account
	 * @return Account|null
	 */
	public function getAccount() {

		$this->setup();

		try {
			$account = Account::retrieve();
			return $account;
		} catch (Base $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getJsonBody()), true), 'ERROR');
		}
	}

	/**
	 * Returns country spec
	 * @return CountrySpec|null
	 */
	public function getCountrySpec() {

		try {
			$account = $this->getAccount();
			$spec = CountrySpec::retrieve($account->country);
			return $spec;
		} catch (Base $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getJsonBody()), true), 'ERROR');
		}
	}

	/**
	 * Digest a webhook
	 * @return bool
	 */
	public function digestWebhook() {

		$this->setup();

		try {
			$request_content = _elgg_services()->request->getContent();
			$event_json = json_decode($request_content);
			$event = \Stripe\Event::retrieve($event_json->id);
		} catch (Base $ex) {
			elgg_log($ex->getMessage() . ': ' . print_r(json_decode($ex->getJsonBody()), true), 'ERROR');
			return false;
		}

		switch ($event->type) {
			case 'charge.failed' :
			case 'charge.pending' :
			case 'charge.refunded' :
			case 'charge.succeeded' :
			case 'charge.updated' :
				$charge = $event->data->object;
				$transactions = elgg_get_entities_from_metadata([
					'types' => 'object',
					'subtypes' => Transaction::SUBTYPE,
					'metadata_name_value_pairs' => [
						'name' => 'stripe_charge_id',
						'value' => $charge->id,
					],
					'limit' => 0,
				]);
				if (!$transactions) {
					return false;
				}
				$transaction = array_shift($transactions);
				$this->updateTransactionStatus($transaction);
				return true;
		}

		$params = [
			'webhook_event' => $event,
			'transaction' => $transaction,
		];

		if (elgg_trigger_plugin_hook('digest:webhook', 'stripe', $params, true)) {
			return true;
		}
	}

}
