
<form action="https://www.paypal.com/us/cgi-bin/webscr" method="post" class="clearfix">

	<input type="hidden" name="business" value="<?php echo $invoice->paypal_address; ?>">
	<input type="hidden" name="no_shipping" value="1">
	<input type="hidden" name="currency_code" value="<?php echo $invoice->currency_code; ?>">


	
<?php if($invoice->tax_total == 0) : ?>
	<input type="hidden" name="tax"  value="<?php echo $invoice->tax_total; ?>">
<?php endif; ?>
	
<?php if($invoice->is_recurring): ?>
	<input type="hidden" name="return"  value="<?php echo $invoice->pay_link; ?>&paypal_recurring_return">
	<input type="hidden" name="cmd" value="_xclick-subscriptions">
	<input type="hidden" name="item_name" value="<?php echo ($invoice->subscription_name ? $invoice->subscription_name : $invoice->subject); ?>">
	<input type="hidden" name="item_number" value="<?php echo $invoice->invoice_num; ?>">
	<input type="hidden" name="cancel_return"  value="<?php echo $invoice->pay_link; ?>&return_info=cancel">
	<input type="hidden" name="invoice" id="invoice_num"  value="<?php echo  $invoice->invoice_num; ?>">
	<input type="hidden" name="srt"  value="<?php echo  $invoice->subscription_total_occurances; ?>">
	<input type="hidden" name="src"  value="1">
	
	<?php
		
		switch ($invoice->subscription_unit) {
			case 'days':
				$subscription_unit = "D";
			break;
			case 'months':
				$subscription_unit = "M";
			break;
			case 'weeks':
				$subscription_unit = "W";
			break;
			case 'years':
				$subscription_unit = "Y";
			break;
			
		}
	?>	
	<input type="hidden" name="p3"  value="<?php echo $invoice->subscription_length; ?>">
	<input type="hidden" name="t3"  value="<?php echo $subscription_unit; ?>">
	<input type="hidden" name="a3"  value="<?php echo $invoice->amount; ?>">

<?php endif; ?>

<?php if(!$invoice->is_recurring): ?>
	<input type="hidden" name="cmd" value="_ext-enter">
	<input type="hidden" name="return"  value="<?php echo $invoice->pay_link; ?>&paypal_return">
	<input type="hidden" name="rm" value="2">
	<input type="hidden" name="upload" value="1">

	<input type="hidden" name="notify_url"  value="<?php echo $invoice->pay_link; ?>">
	

	<input type="hidden" name="amount"  value="<?php echo $invoice->amount; ?>">
	<input type="hidden" name="cbt"  value="Mark Invoice as Paid">
	
	<?php
	// Convert Itemized List into PayPal Item List 
	if(is_array($invoice->itemized)) echo wp_invoice_create_paypal_itemized_list($invoice->itemized,$invoice_id);
	?>



<?php endif; ?>



	<fieldset id="credit_card_information">
	<ol>		

		<li>
		<label for="first_name"><?php _e('First Name', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("first_name",$invoice->user_class->first_name); ?>
		</li>

		<li>
		<label for="last_name"><?php _e('Last Name', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("last_name",$invoice->user_class->last_name); ?>
		</li>

		<li>
		<label for="email"><?php _e('Email Address', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("email_address",$invoice->user_class->user_email); ?>
		</li>

	<?php
		list($day_phone_a, $day_phone_b, $day_phone_c) = split('[/.-]', $invoice->user_class->phonenumber);
		?>
		<li>
		<label for="day_phone_a"><?php _e('Phone Number', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("night_phone_a",$day_phone_a,' style="width:25px;" size="3" maxlength="3" '); ?>
		<?php echo wp_invoice_draw_inputfield("night_phone_b",$day_phone_b,' style="width:25px;" size="3" maxlength="3" '); ?>
		<?php echo wp_invoice_draw_inputfield("night_phone_c",$day_phone_c,' style="width:35px;" size="4" maxlength="4" '); ?>
		</li>

		<li>
		<label for="address"><?php _e('Address', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("address1",$invoice->user_class->streetaddress); ?>
		</li>

		<li>
		<label for="city"><?php _e('City', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("city",$invoice->user_class->city); ?>
		</li>

		<?php if(get_option('wp_invoice_fe_state_selection') != 'Hide') { ?>
		<li id="state_field">
		<label for="state"><?php _e('State', WP_INVOICE_TRANS_DOMAIN); ?></label>
	<?php if(get_option('wp_invoice_fe_state_selection') == 'Dropdown') { ?>
		<?php print wp_invoice_draw_select('state',wp_invoice_state_array(),$invoice->user_class->state);  ?>
	<?php } ?>
	<?php if(get_option('wp_invoice_fe_state_selection') == 'Input_Field') { ?>
		<?php echo wp_invoice_draw_inputfield("state",$invoice->user_class->state); ?>
	<?php } ?>
		</li>
		<?php } ?>

		<li>
		<label for="zip"><?php echo ((get_option('wp_invoice_custom_zip_label') != '') ? get_option('wp_invoice_custom_zip_label') : 'Zip Code'); ?></label>
		<?php echo wp_invoice_draw_inputfield("zip",$invoice->user_class->zip); ?>
		</li>

		<li>
		<label for="country"><?php _e('Country', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_select('country',wp_invoice_country_array(),$invoice->user_class->country); ?>
		</li>

		<li>
		<label for="submit">&nbsp;</label>
		<input type="image"  src="<?php echo get_option('wp_invoice_fe_paypal_link_url'); ?>" style="border:0; padding:0; width: auto;" name="submit" alt="Make payments with PayPal - it's fast, free and secure!">
		</li>
		
		<br class="cb" />	
		</ol>
	</fieldset>
</form>