<form method="post" name="online_payment_form" id="online_payment_form-<?php print $this->type; ?>" class="wpi_checkout online_payment_form <?php print $this->type; ?> clearfix">
  <input type="hidden" id="wpi_action" name="wpi_action" value="wpi_gateway_process_payment" />
  <input type="hidden" id="wpi_form_type" name="type" value="<?php print $this->type; ?>" />
  <input type="hidden" id="wpi_form_invoice_id" name="invoice_id" value="<?php print $invoice['invoice_id']; ?>" />
  <input type="hidden" name="wp_invoice[hash]" value="<?php echo wp_create_nonce($invoice['invoice_id'] .'hash');; ?>" />
  <input type="hidden" id="payment_amount" name="cc_data[amount]" value="<?php echo $invoice['net']; ?>" />
  <input type="hidden" name="cc_data[user_id]" value="<?php echo $invoice['user_data']['user_email']; ?>" />
  <input type="hidden" name="cc_data[invoice_id]" value="<?php echo  $invoice['invoice_id']; ?>" />
  <input type="hidden" name="cc_data[currency_code]" id="currency_code"  value="<?php echo $invoice['default_currency_code']; ?>" />

  <div id="credit_card_information">
		
		<?php do_action('wpi_payment_fields_authorize', $invoice); ?>

		<ul id="wp_invoice_process_wait">
			<li>
				<label for="submit"><span></span>&nbsp;</label>
				<button type="submit" id="cc_pay_button" class="hide_after_success submit_button">Process Payment of <?php echo (!empty($wpi_settings['currency']['symbol'][$invoice['default_currency_code']]) ? $wpi_settings['currency']['symbol'][$invoice['default_currency_code']] : "$"); ?><span id="pay_button_value"><?php echo WPI_Functions::money_format($invoice['net']); ?></span></button>
				<img style="display: none;" class="loader-img" src="<?php echo WPI_URL; ?>/core/css/images/processing-ajax.gif" alt="" />
			</li>
		</ul>
		<br class="cb" />
  </div>