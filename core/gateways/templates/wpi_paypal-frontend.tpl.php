<form action="<?php echo $invoice['billing']['wpi_paypal']['settings']['test_mode']['value']; ?>" method="post" name="online_payment_form" id="online_payment_form-<?php print $this->type; ?>" class="wpi_checkout online_payment_form <?php print $this->type; ?> clearfix">
  <input type="hidden" id="wpi_action" name="wpi_action" value="wpi_gateway_process_payment" />
  <input type="hidden" id="wpi_form_type" name="type" value="<?php print $this->type; ?>" />
  <input type="hidden" id="wpi_form_invoice_id" name="invoice_id" value="<?php print $invoice['invoice_id']; ?>" />
  <input type="hidden" name="wp_invoice[hash]" value="<?php echo wp_create_nonce($invoice['invoice_id'] .'hash');; ?>" />
  <input type="hidden" name="currency_code" value="<?php echo $invoice['default_currency_code']; ?>">
  <input type="hidden" name="no_shipping" value="1">
  <input type="hidden" name="upload" value="1">
  <input type="hidden" name="cmd" value="_xclick">
  <input type="hidden" name="business" value="<?php echo $invoice['billing']['wpi_paypal']['settings']['paypal_address']['value']; ?>">
  <input type="hidden" name="return" value="<?php echo get_invoice_permalink($invoice['invoice_id']); ?>">
  <input type="hidden" name="rm" value="2">
  <input type="hidden" name="cancel_return" value="<?php echo get_invoice_permalink($invoice['invoice_id']); ?>">
  <input type="hidden" id="payment_amount" name="amount" value="<?php echo number_format( (float)$invoice['net'], 2, '.', '' ); ?>">
  <input type="hidden" name="cbt" value="Go back to Merchant">
  <input type="hidden" name="item_name" value="<?php echo $invoice['post_title']; ?>"> 
  <input type="hidden" name="invoice" id="invoice_id" value="<?php echo $invoice['invoice_id']; ?>">
	
	<div id="credit_card_information">
		
		<?php do_action('wpi_payment_fields_paypal', $invoice); ?>
		
		<ul id="wp_invoice_process_wait">
			<li>
				<label>&nbsp;</label>
				<button type="submit" id="cc_pay_button" class="hide_after_success submit_button">Process Payment of <?php echo (!empty($wpi_settings['currency']['symbol'][$invoice['default_currency_code']]) ? $wpi_settings['currency']['symbol'][$invoice['default_currency_code']] : "$"); ?><span id="pay_button_value"><?php echo WPI_Functions::money_format($invoice['net']); ?></span></button>
				<img style="display: none;" class="loader-img" src="<?php echo WPI_URL; ?>/core/css/images/processing-ajax.gif" alt="" />
			</li>
		</ul>
		
	</div>