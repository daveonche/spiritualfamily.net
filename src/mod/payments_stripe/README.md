# Stripe Payments for Elgg

![Elgg 2.3](https://img.shields.io/badge/Elgg-2.3-orange.svg?style=flat-square)

## Features

 * API for handling payments via Stripe

## Acknowledgements

 * Plugin has been sponsored by [Social Business World] (https://socialbusinessworld.org "Social Business World")

## Notes

### Example

See actions/payments/checkout/stripe.php for usage example.

### Payment Status

You can use `'transaction:<status>', 'payments'` hooks to apply additional logic upon payment status changes.
Payments are synchronous and do not require a user to be forwarded to another location.

### Web hook events

Make sure to setup the webhooks via your Stripe dashboard. Webhook URL is listed in plugin settings.
Charge-related hooks will be digested by the plugin automatically. Other web hook event data can be digested with `'digest:webhook', 'stripe'` plugin hook.

### SSL

 * Your site must be served over HTTPS for the API requests and webhooks to work as expected

### Credentials

 * Login at https://stripe.com and create an account
 * Copy secret and publishable keys from Stripe Account settings > API keys to plugin settings
 * Add webhook endpoints via Stripe Account settings > Webhooks > Add endpoint (the endpoint URL is listed in plugin settings)

### Testing

 * You make test payments using card numbers listed here: https://stripe.com/docs/testing#cards
 * You can see test payments by toggling your Stripe dashboard to Test mode

### Forms/Actions

To display card information input form, use:

```php

// in your form
echo elgg_view('input/stripe/card');

// in your action
$token = get_input('stripe_token');
```

