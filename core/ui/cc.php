<form method="post" name="checkout_form" id="checkout_form" class="online_payment_form" onsubmit="process_cc_checkout(); return false;" class="clearfix">
	<input type="hidden" name="amount" value="<?php echo $invoice->amount; ?>">
	<input type="hidden" name="user_id" value="<?php echo $invoice->user_id; ?>">
	<input type="hidden" name="email_address" value="<?php echo $invoice->user_class->user_email; ?>">
	<input type="hidden" name="invoice_num" value="<?php echo  $invoice_id; ?>">
	<input type="hidden" name="currency_code" id="currency_code"  value="<?php echo $invoice->currency; ?>">
	<input type="hidden" name="wp_invoice_id_hash" value="<?php echo $invoice->hash; ?>" />
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

		<li>
		<label class="inputLabel" for="phonenumber"><?php _e('Phone Number', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<input name="phonenumber" class="input_field"  type="text" id="phonenumber" size="40" maxlength="50" value="<?php print $invoice->user_class->phonenumber; ?>" />
		</li>

		<li>
		<label for="address"><?php _e('Address', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("address",$invoice->user_class->streetaddress); ?>
		</li>

		<li>
		<label for="city"><?php _e('City', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php echo wp_invoice_draw_inputfield("city",$invoice->user_class->city); ?>
		</li>

	<?php if(get_option('wp_invoice_fe_state_selection') != 'Hide') { ?>
		<li id="state_field">
		<label for="state"><?php _e('State', WP_INVOICE_TRANS_DOMAIN); ?></label>
	<?php if(get_option('wp_invoice_fe_state_selection') == 'Dropdown') { ?>
		<?php 
 		print wp_invoice_draw_select('state',wp_invoice_state_array($invoice->user_class->wpi_localization),$invoice->user_class->state);  ?>
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
		<?php
		echo wp_invoice_draw_select('country',wp_invoice_country_array(),$invoice->user_class->country); ?>
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="card_num"><?php _e('Credit Card Number', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<input name="card_num" autocomplete="off" onkeyup="cc_card_pick();"  id="card_num" class="credit_card_number input_field"  type="text"  size="22"  maxlength="22" />
		</li>

		<li class="hide_after_success nocard"  id="cardimage" style=" background: url(<?php echo WP_Invoice::frontend_path(); ?>/core/images/card_array.png) no-repeat;">
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="exp_month"><?php _e('Expiration Date', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<?php _e('Month', WP_INVOICE_TRANS_DOMAIN); ?> <?php echo wp_invoice_draw_select('exp_month',wp_invoice_month_array()); ?>
		<?php _e('Year', WP_INVOICE_TRANS_DOMAIN); ?> <select name="exp_year" id="exp_year"><?php print wp_invoice_printYearDropdown(); ?></select>
		</li>

		<li class="hide_after_success">
		<label class="inputLabel" for="card_code"><?php _e('Security Code', WP_INVOICE_TRANS_DOMAIN); ?></label>
		<input id="card_code" autocomplete="off"  name="card_code" class="input_field"  style="width: 70px;" type="text" size="4" maxlength="4" />
		</li>

		<li id="wp_invoice_process_wait">
		<label for="submit"><span></span>&nbsp;</label>
		<button type="submit" id="cc_pay_button" class="hide_after_success submit_button"><?php printf(__('Pay %s', WP_INVOICE_TRANS_DOMAIN), $invoice->display_amount); ?></button>
		</li>	
		
	<br class="cb" />	
		</ol>
	</fieldset>
</form>
&nbsp;<div id="wp_cc_response"></div>	