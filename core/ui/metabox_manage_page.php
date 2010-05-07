<?php

 
	function wp_invoice_metabox_history($ic) {
	$invoice_id = $ic->invoice_id;
	
	if(isset($invoice_id) && wp_invoice_paid_status($invoice_id) || wp_invoice_recurring_started($invoice_id) || wp_invoice_query_log($invoice_id, 'subscription_error')) { 
	?>

	
	<div class="updated wp_invoice_status">
		<?php if(wp_invoice_paid_status($invoice_id)) { ?>
		<h2><?php _e('Invoice Paid', WP_INVOICE_TRANS_DOMAIN); ?></h2>
		<?php foreach(wp_invoice_query_log($invoice_id, 'paid') as $info) {
			echo sprintf(__('%s on ', WP_INVOICE_TRANS_DOMAIN), $info->value) . date('M d, Y \a\t h:m a', strtotime($info->time_stamp)) . "<br />";
		} ?>
		<?php } ?>
		
		<?php if(wp_invoice_recurring_started($invoice_id)) { ?>
		<h2><?php _e('Recurring Billing Initiated', WP_INVOICE_TRANS_DOMAIN); ?></h2>
		<?php foreach(wp_invoice_query_log($invoice_id, 'subscription') as $info) {
		echo $info->value . " on " . $info->time_stamp . "<br />";
		} } ?>	
		
		<?php 
		$subscription_errors = wp_invoice_query_log($invoice_id, 'subscription_error');
		
		if($subscription_errors) { ?>
		<h2><?php _e('Recurring Billing Problems', WP_INVOICE_TRANS_DOMAIN); ?></h2>
		<ol>
		<?php
		foreach($subscription_errors as $info) {
		echo "<li>" . sprintf(__('%s on ', WP_INVOICE_TRANS_DOMAIN), $info->value). $info->time_stamp . "</li>";
		} ?>
		</ol>
		<?php } ?>	
	</div>
	<?php } ?>	
		
	<div class="wp_invoice_status">

		<ul id="invoice_history_log">
		<?php echo wp_invoice_get_invoice_status($invoice_id,'100'); ?>
		</ul>
	</div>
	
	
	
	
	
	<?php  }
	
	
	
	function wp_invoice_metabox_publish($ic) { ?>
	<div id="minor-publishing">

<div id="misc-publishing-actions">
<table class="form-table">

	
	<tr class="invoice_main">
		<th>Invoice ID </th>
		<td style="font-size: 1.1em; padding-top:7px;">
		<input class="wp_invoice_custom_invoice_id<?php if(empty($ic->custom_invoice_id)) { echo " wp_invoice_hidden"; } ?>" name="wp_invoice_custom_invoice_id" value="<?php echo $ic->custom_invoice_id;?>">
		<?php if(isset($invoice_id)) { echo $invoice_id; } else { echo rand(10000000, 90000000);}  ?> <a class="wp_invoice_custom_invoice_id wp_invoice_click_me <?php if(!empty($ic->custom_invoice_id)) { echo " wp_invoice_hidden"; } ?>" href="#">Custom Invoice ID</a>
		
		</td>
	</tr>

	<tr class="invoice_main">
		<th>Tax </th>
		<td style="font-size: 1.1em; padding-top:7px;">
			<input style="width: 35px;"  name="wp_invoice_tax" id="wp_invoice_tax" autocomplete="off" value="<?php echo $ic->tax ?>">%</input>
		</td>
	</tr>

		<tr class="">
		<th>Currency</th>
		<td>
			<select name="wp_invoice_currency_code">
				<?php foreach(wp_invoice_currency_array() as $value=>$currency_x) {
				echo "<option value='$value'"; if($ic->currency_code == $value) echo " SELECTED"; echo ">$value - $currency_x</option>\n";
				}
				?>
			</select> 
		</td>
	</tr>
	
	<tr class="">
		<th>Due Date</th>
		<td>
			<div id="timestampdiv" style="display:block;">
			<select id="mm" name="wp_invoice_due_date_month">
			<option></option>
			<option value="1" <?php if($ic->due_date_month == '1') echo " selected='selected'";?>>Jan</option>
			<option value="2" <?php if($ic->due_date_month == '2') echo " selected='selected'";?>>Feb</option>
			<option value="3" <?php if($ic->due_date_month == '3') echo " selected='selected'";?>>Mar</option>
			<option value="4" <?php if($ic->due_date_month == '4') echo " selected='selected'";?>>Apr</option>
			<option value="5" <?php if($ic->due_date_month == '5') echo " selected='selected'";?>>May</option>
			<option value="6" <?php if($ic->due_date_month == '6') echo " selected='selected'";?>>Jun</option>
			<option value="7" <?php if($ic->due_date_month == '7') echo " selected='selected'";?>>Jul</option>
			<option value="8" <?php if($ic->due_date_month == '8') echo " selected='selected'";?>>Aug</option>
			<option value="9" <?php if($ic->due_date_month == '9') echo " selected='selected'";?>>Sep</option>
			<option value="10" <?php if($ic->due_date_month == '10') echo " selected='selected'";?>>Oct</option>
			<option value="11" <?php if($ic->due_date_month == '11') echo " selected='selected'";?>>Nov</option>
			<option value="12" <?php if($ic->due_date_month == '12') echo " selected='selected'";?>>Dec</option>
			</select>
			<input type="text" id="jj" name="wp_invoice_due_date_day" value="<?php echo $ic->due_date_day; ?>" size="2" maxlength="2" autocomplete="off" />, 
			<input type="text" id="aa" name="wp_invoice_due_date_year" value="<?php echo $ic->due_date_year; ?>" size="4" maxlength="5" autocomplete="off" />
			
			<div>
				<span onclick="wp_invoice_add_time(7);" class="wp_invoice_click_me">In One Week</span> | 
				<span onclick="wp_invoice_add_time(30);" class="wp_invoice_click_me">In 30 Days</span> |
				<span onclick="wp_invoice_add_time('clear');" class="wp_invoice_click_me">Clear</span>
			</div>
			</div> 
		</td>
	</tr>
	

</table>
</div>
<div class="clear"></div>
</div>

<div id="major-publishing-actions">


<div id="publishing-action">
	<input type="submit"  name="save" class="button-primary" value="Save and Preview"> 	
</div>
<div class="clear"></div>
</div>

<?php }



	function wp_invoice_metabox_invoice_details($ic) { 	?>
	
	<table class="form-table" id="wp_invoice_main_info">

	<tr class="invoice_main">
		<th><?php _e("Subject", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<td>
			<input  id="invoice_subject" class="subject"  name='subject' value='<?php echo $ic->subject; ?>'>
		</td>
	</tr>
	

	
	<tr class="invoice_main"><th><?php _e("Description / PO", WP_INVOICE_TRANS_DOMAIN) ?></th><td><textarea class="invoice_description_box" name='description' value=''><?php echo $ic->description; ?></textarea></td></tr>
	
	<tr class="invoice_main">
		<th><?php _e("Itemized List", WP_INVOICE_TRANS_DOMAIN) ?></th>
	<td>
		<table id="invoice_list" class="itemized_list">
		<tr>
		<th class="id"><?php _e("ID", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<th class="name"><?php _e("Name", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<th class="description"><?php _e("Description", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<th class="quantity"><?php _e("Quantity", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<th class="price"><?php _e("Unit Price", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<th class="item_total"><?php _e("Total", WP_INVOICE_TRANS_DOMAIN) ?></th>
		</tr>

		<?php
		$counter = 1;
		foreach($ic->itemized_array as $itemized_item){	 ?>
		
		<tr valign="top">
			<td valign="top" class="id"><?php echo $counter; ?></td>
			<td valign="top" class="name"><input class="item_name" name="itemized_list[<?php echo $counter; ?>][name]" value="<?php echo stripslashes($itemized_item[name]); ?>" /></td>
			<td valign="top" class="description"><textarea style="height: 25px;" name="itemized_list[<?php echo $counter; ?>][description]" class="item_description autogrow"><?php echo stripslashes($itemized_item[description]); ?></textarea></td>
			<td valign="top" class="quantity"><input autocomplete="off"  value="<?php echo stripslashes($itemized_item[quantity]); ?>" name="itemized_list[<?php echo $counter; ?>][quantity]" id="qty_item_<?php echo $counter; ?>"  class="item_quantity"></td>
			<td valign="top" class="price"><input autocomplete="off" value="<?php echo stripslashes($itemized_item[price]); ?>"  name="itemized_list[<?php echo $counter; ?>][price]" id="price_item_<?php echo $counter; ?>"  class="item_price"></td>
			<td valign="top" class="item_total" id="total_item_<?php echo $counter; ?>" ></td>
		</tr>

		
		<?php $counter++; } ?>
		</table>
	</td>
	</tr>

	<tr class="invoice_main">
		<th style='vertical-align:bottom;text-align:right;'><p><a href="#" id="add_itemized_item">Add Another Item</a><br /><span class='wp_invoice_light_text'></span></p></th>
		<td>
			<table class="itemized_list">

			<tr>
			<td align="right"><?php _e("Invoice Total:", WP_INVOICE_TRANS_DOMAIN) ?></td>
			<td class="item_total"><span id='amount'></span></td>
			</tr>
			
			<tr>
			<td align="right"><span class="wp_invoice_enable_recurring_billing" <?php if(!$ic->recurring_billing) { ?>style="display:none;"<?php } ?>><?php _e("Recurring Invoice Total:", WP_INVOICE_TRANS_DOMAIN) ?></span></td>
			<td class="item_total"><span  class="wp_invoice_enable_recurring_billing" <?php if(!$ic->recurring_billing) { ?>style="display:none;"<?php } ?> id='recurring_total'></span></td>
			</tr>
			
			</table>
		</td>
	</tr>

</table>
	
	
	
	
	
	
	
	
	<?php
	}

	function wp_invoice_metabox_billing($ic) {
 	$invoice_id = $ic->invoice_id;
	
	// Have to convert these manually because we use the same payment_processing.php file for here and for settings page
	
	$wp_invoice_payment_method 	= $ic->payment_method;
	$wp_invoice_paypal_allow 	= $ic->paypal_allow;
	$wp_invoice_paypal_address 	= $ic->paypal_address;
	
	
 	$wp_invoice_cc_allow 				= $ic->cc_allow;
	$wp_invoice_gateway_tran_key 		= $ic->gateway_tran_key;
	$wp_invoice_gateway_url 			= $ic->gateway_url;
	$wp_invoice_recurring_gateway_url 	= $ic->recurring_gateway_url;
	
	
 	?>
	
<table class="form-table">
		</tr>	
			<tr>
			<th><?php _e("Default Payment Method:") ?></th>
			<td>
				<?php
				$payment_array = wp_invoice_accepted_payment($invoice_id); ?>
				<select id="wp_invoice_payment_method" name="wp_invoice_payment_method">
				<?php foreach ($payment_array as $payment_option) { ?>
				<option name="<?php echo $payment_option['name']; ?>" value="<?php echo $payment_option['name']; ?>" <?php if($payment_option['default']) { echo "SELECTED"; } ?>><?php echo $payment_option['nicename']; ?></option>
				<?php } ?>
				</select>
			</tr>	
	

	<tr>
		<th><?php _e("Client can change payment method:") ?></th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_client_change_payment_method',array("yes" => __('Yes', WP_INVOICE_TRANS_DOMAIN), "no" => __('No', WP_INVOICE_TRANS_DOMAIN)), $ic->client_change_payment_method); ?>
		</td>

	<tr>
		<th>&nbsp;</th>
		<td>

		<?php require_once("payment_processing.php"); ?>



</td></tr>
</table>

<?php }

	function wp_invoice_metabox_recurring_billing() { ?>

	
	
	
<div id="wp_invoice_enable_recurring_billing" class="wp_invoice_click_me" <?php if($ic->recurring_billing) { ?>style="display:none;"<?php } ?>>
	<?php _e("Create a recurring billing schedule for this invoice.", WP_INVOICE_TRANS_DOMAIN) ?>
</div>

<div class="wp_invoice_enable_recurring_billing" <?php if(!$ic->recurring_billing) { ?>style="display:none;"<?php } ?>>

<table class="form-table" id="">
	<tr>
		<th><a class="wp_invoice_tooltip" title="<?php _e("A name to identify this subscription by in addition to the invoice id. (ex: 'standard hosting')", WP_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Subscription Name", WP_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_subscription_name',$ic->subscription_name); ?></td>
	</tr>

	<tr>
		<th><?php _e("Start Date", WP_INVOICE_TRANS_DOMAIN) ?></th>
		<td>
			
			
			<span style="<?php if($recurring_auto_start) { ?>display:none;<?php } ?>" class="wp_invoice_timestamp"><?php _e("Start automatically as soon as the customer enters their billing information. ", WP_INVOICE_TRANS_DOMAIN) ?><span class="wp_invoice_click_me" onclick="jQuery('.wp_invoice_timestamp').toggle();"><?php _e("Specify Start Date", WP_INVOICE_TRANS_DOMAIN) ?></span></span>
			<div style="<?php if(!$recurring_auto_start) { ?>display:none;<?php } ?>" class="wp_invoice_timestamp">
			<?php echo wp_invoice_draw_select('wp_invoice_subscription_start_month', array("01" => "Jan","02" => "Feb","03" => "Mar","04" => "Apr","05" => "May","06" => "Jun","07" => "Jul","08" => "Aug","09" => "Sep","10" => "Oct","11" => "Nov","12" => "Dec"), $ic->subscription_start_month); ?>
			<?php echo wp_invoice_draw_inputfield('wp_invoice_subscription_start_day', $ic->subscription_start_day, ' size="2" maxlength="2" autocomplete="off" '); ?>,
			<?php echo wp_invoice_draw_inputfield('wp_invoice_subscription_start_year', $ic->subscription_start_year, ' size="4" maxlength="4" autocomplete="off" '); ?>
			<div>
				<span onclick="wp_invoice_subscription_start_time(7);" class="wp_invoice_click_me"><?php _e("In One Week", WP_INVOICE_TRANS_DOMAIN) ?></span> |
				<span onclick="wp_invoice_subscription_start_time(30);" class="wp_invoice_click_me"><?php _e("In 30 Days", WP_INVOICE_TRANS_DOMAIN) ?></span> |
				<span onclick="jQuery('.wp_invoice_timestamp').toggle();wp_invoice_subscription_start_time('clear');"  class="wp_invoice_click_me"><?php _e("Start automatically", WP_INVOICE_TRANS_DOMAIN) ?></span>
			</div>
			</div> 
		</td>
	</tr>
	
	<tr>
		<th><a class="wp_invoice_tooltip"  title="<?php _e("This will be the number of times the client will be billed. (ex: 12)", WP_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Bill Every", WP_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_subscription_length', $ic->subscription_length,' size="3" maxlength="3" autocomplete="off" '); ?>
		<?php echo wp_invoice_draw_select('wp_invoice_subscription_unit', array("months" => __("month(s)", WP_INVOICE_TRANS_DOMAIN), "days"=> __("days", WP_INVOICE_TRANS_DOMAIN)), $ic->subscription_unit); ?></td>
	</tr>

	<tr>
		<th><a class="wp_invoice_tooltip"  title="<?php _e("Keep it under the maximum of 9999.", WP_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Total Billing Cycles", WP_INVOICE_TRANS_DOMAIN) ?></a></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_subscription_total_occurances', $ic->subscription_total_occurances,' size="4" maxlength="4" autocomplete="off" '); ?></td>
	</tr>
		
	<tr>
		<th></th>
		<td><?php _e("All <b>recurring billing</b> fields must be filled out to activate recurring billing. ", WP_INVOICE_TRANS_DOMAIN) ?><span onclick="wp_invoice_cancel_recurring()" class="wp_invoice_click_me"><?php _e("Cancel Recurring Billing", WP_INVOICE_TRANS_DOMAIN) ?></span></td>
	</tr>
</table>
	


</div>
	
	
	
	
	
	
	
	
	<?php } 

	function wp_invoice_user_metabox($ic) { ?>
	
 


<table class="form-table" id="">

	
	<?php if($ic->create_new_user) {

	?>
	<input type="hidden" name="create_new_user">
	
	<tr>
		<th><?php _e("Email Address") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_new_user_email_address', ''); ?></td>
	</tr>

	<tr>
		<th><?php _e("First Name") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_first_name', ''); ?></td>
	</tr>
		
	<tr>
		<th><?php _e("Last Name") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_last_name', ''); ?></td>
	</tr>
		
	<tr>
		<th><?php _e("Company") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_company_name', ''); ?></td>
	</tr>
	
	<tr>
		<th><?php _e("Username") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_new_user_username', ''); ?> If username already exists, a random name will be created to the tune of "wp_invoice_[random_number]"</td>
	</tr>

	<tr>
		<th><?php _e("Street Address") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_streetaddress', ''); ?></td>
	</tr>
	
	<tr>
		<th><?php _e("City") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_city', ''); ?></td>
	</tr>
	
	<tr>
		<th><?php _e("State") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_state', ''); ?></td>
	</tr>
	
	<tr>
		<th><?php _e("ZIP") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_zip', ''); ?></td>
	</tr>
	
	<tr>
		<th>&nbsp;</th>
		<td><input type="checkbox" name="wp_invoice_send_new_user_email" id="wp_invoice_send_new_user_email" value="true">&nbsp;<label for="wp_invoice_send_new_user_email">Send this user an email with username and password.</label></td>
	</tr>


	
	<?php } else { ?>
	<tr>
		<th><?php _e("Email Address") ?></th>
		<td><?php echo $ic->user_email; ?> <a class="wp_invoice_click_me" href="user-edit.php?user_id=<?php echo $ic->user_id; ?>#billing_info"><?php _e('Go to User Profile', WP_INVOICE_TRANS_DOMAIN); ?></a>
		</td>
	</tr>

	<tr>
		<th><?php _e("Company") ?></th>
		<td><?php echo wp_invoice_draw_inputfield('wp_invoice_company_name', $ic->company_name); ?></td>
	</tr>
	
	<tr style="height: 90px;">
		<th><?php _e("Billing Information") ?></th>
		<td>
			<div id="wp_invoice_edit_user_from_invoice">
			  <span class="wp_invoice_make_editable<?php if(!$ic->first_name) echo " wp_invoice_unset"; ?>" id="wp_invoice_first_name"><?php echo ($ic->first_name ? $ic->first_name : "Set First Name"); ?></span>
			  <span class="wp_invoice_make_editable<?php if(!$ic->last_name) echo " wp_invoice_unset"; ?>" id="wp_invoice_last_name"><?php echo ($ic->last_name ? $ic->last_name : "Set Last Name"); ?></span><br /> 
			  <span class="wp_invoice_make_editable<?php if(!$ic->streetaddress) echo " wp_invoice_unset"; ?>" id="wp_invoice_streetaddress"><?php echo ($ic->streetaddress ? $ic->streetaddress : "Set Street Address"); ?></span><br />
			  <span class="wp_invoice_make_editable<?php if(!$ic->city) echo " wp_invoice_unset"; ?>" id="wp_invoice_city"><?php echo ($ic->city ? $ic->city : "Set City"); ?></span>
			  <span class="wp_invoice_make_editable<?php if(!$ic->state) echo " wp_invoice_unset"; ?>" id="wp_invoice_state"><?php echo ($ic->state ? $ic->state : "Set State"); ?></span>
			  <span class="wp_invoice_make_editable<?php if(!$ic->zip) echo " wp_invoice_unset"; ?>" id="wp_invoice_zip"><?php echo ($ic->zip ? $ic->zip : "Set Zip Code"); ?></span>
			</div>
		</td>
	</tr>
	<?php } ?>
	

	
	
</table>
 



	<?php }
?>