<form method="post" name="checkout_form" id="checkout_form" class="online_payment_form" onsubmit="process_cc_checkout(); return false;" class="clearfix">
<input type="hidden" name="amount" value="<?php echo $invoice->display('amount'); ?>">
<input type="hidden" name="user_id" value="<?php echo $invoice->recipient('user_id'); ?>">
<input type="hidden" name="email_address" value="<?php echo $invoice->recipient('email_address'); ?>">
<input type="hidden" name="invoice_num" value="<?php echo  $invoice_id; ?>">
<input type="hidden" name="currency_code" id="currency_code"  value="<?php echo $invoice->display('currency'); ?>">
<input type="hidden" name="wp_invoice_id_hash" value="<?php echo $invoice->display('hash'); ?>" />


<fieldset id="credit_card_information">
	<ol>
	
	<li>
	<label for="first_name">First Name</label>
	<?php echo wp_invoice_draw_inputfield("first_name",$invoice->recipient('first_name')); ?>
	</li>
	
	<li>
	<label for="last_name">Last Name</label>
	<?php echo wp_invoice_draw_inputfield("last_name",$invoice->recipient('last_name')); ?>
	</li>

	<li>
	<label for="email">Email Address</label>
	<?php echo wp_invoice_draw_inputfield("email_address",$invoice->recipient('email_address')); ?>
	</li>
	
	
	<li>
	<label class="inputLabel" for="phonenumber">Phone Number</label>
	<input name="phonenumber" class="input_field"  type="text" id="phonenumber" size="40" maxlength="50" value="<?php print $invoice->recipient('phonenumber'); ?>">
	</li>

	<li>
	<label for="address">Address</label>
	<?php echo wp_invoice_draw_inputfield("address",$invoice->recipient('streetaddress')); ?>
	</li>

	<li>
	<label for="city">City</label>
	<?php echo wp_invoice_draw_inputfield("city",$invoice->recipient('city')); ?>
	</li>

	<li>
	<label for="state">State/Province</label>
	<?php print wp_invoice_draw_select('state',wp_invoice_state_array(),$invoice->recipient('state'));  ?>
	</li>

	<li>
	<label for="zip">Zip/Postal Code</label>
	<?php echo wp_invoice_draw_inputfield("zip",$invoice->recipient('zip')); ?>
	</li>

	<li>
	<label for="country">Country</label>
	<?php echo wp_invoice_draw_select('country',wp_invoice_country_array(),"US"); ?>
	</li>


	<li class="hide_after_success">
	<label class="inputLabel" for="card_num">Credit Card Number</label>
	<input name="card_num" autocomplete="off" onkeyup="cc_card_pick();"  id="card_num" class="credit_card_number input_field"  type="text"  size="22"  maxlength="22">
	</li>

	<li class="hide_after_success nocard"  id="cardimage" style=" background: url(<?php echo WP_Invoice::frontend_path(); ?>/core/images/card_array.png) no-repeat;">
	</li>

	<li class="hide_after_success">
	<label class="inputLabel" for="exp_month">Expiration Date</label>
	Month <select name="exp_month" id="exp_month"><?php print wp_invoice_printMonthDropdown(); ?></select>			
	Year <select name="exp_year" id="exp_year"><?php print wp_invoice_printYearDropdown(); ?></select>
	</li>

	<li class="hide_after_success">
	<label class="inputLabel" for="card_code">Security Code</label>
	<input id="card_code" autocomplete="off"  name="card_code" class="input_field"  style="width: 70px;" type="text" size="4" maxlength="4">
	</li>		
	
	<li id="wp_invoice_process_wait">
	<label for="submit"><span></span>&nbsp;</label>
	<button type="submit" id="cc_pay_button" class="hide_after_success submit_button">
	Pay <?php echo $invoice->display('display_amount'); ?></button>
	</li>	
	
	<br class="cb" />	
	</ol>
</fieldset>

</form>

&nbsp;
<div id="wp_cc_response"></div>	