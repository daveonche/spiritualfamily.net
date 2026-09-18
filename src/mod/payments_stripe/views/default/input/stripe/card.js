define(function (require) {
	var Stripe = require('Stripe');
	var elgg = require('elgg');
	var $ = require('jquery');

	Stripe.setPublishableKey(elgg.data.stripe.key);

	$(document).on('submit', '.elgg-form:has([data-stripe])', function (e) {
		e.preventDefault();
		
		var $form = $(this);
		var label = $form.find('[type="submit"]').val();
		$form.find('[type="submit"]').prop('disabled', true).val(elgg.echo('payments:stripe:validating'));
		
		Stripe.card.createToken($form, function (status, response) {
			if (response.error) {
				$form.find('.stripe-card-error p').text(response.error.message);
				$form.find('[type="submit"]').prop('disabled', false).val(label);
				return false;
			} else {
				var token = response.id;
				$form.append($('<input type="hidden" name="stripe_token" />').val(token));
				$form.get(0).submit();
			}
		});
	});
});
