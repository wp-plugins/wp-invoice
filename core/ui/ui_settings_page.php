<div class="wrap">
 <form method='POST'>
 <?php 	wp_nonce_field( 'wpi_update_settings'); ?>
<h2><?php _e("WP-Invoice Global Settings", WP_INVOICE_TRANS_DOMAIN) ?></h2>

	<div class="wp_invoice_error_wrapper">
		<?php if(is_array($errors) && count($errors) > 0): ?>
		<div class="error"><p>
			<?php foreach($errors as $error): ?>
				<?php echo $error; ?><br />
			<?php endforeach; ?>
		</p></div>
		<?php endif; ?>
		<?php if(!is_array($errors) && !empty($errors)): ?>
		<div class="error"><p>
		<?php echo $errors; ?>
		</p></div>
		<?php endif; ?>
	</div>

	<?php if(count($messages) > 0): ?>
	<div class="updated fade"><p>
		<?php foreach($messages as $message): ?>
			<?php echo $message; ?><br />
		<?php endforeach; ?>
	</p></div>
	<?php endif; ?>
	
	
<div id="wp_invoice_settings_page" class="wp_invoice_tabbed_content"> 
  <ul class="wp_invoice_settings_tabs"> 
    <li><a class="selected" href="#tab1"><?php _e("Basic Settings") ?></a></li> 
    <li><a href="#tab2"><?php _e("Display Settings") ?></a></li> 
    <li><a href="#tab3"><?php _e("Payment Settings") ?></a></li> 
    <li><a href="#tab4"><?php _e("Internationalization") ?></a></li> 
    <li><a href="#tab5"><?php _e("E-Mail Templates") ?></a></li> 
    <li><a href="#tab6"><?php _e("Invoice Lookup") ?></a></li> 
    <li><a href="#tab7"><?php _e("Troubleshooting") ?></a></li> 
  </ul> 
  <div id="tab1" class="wp_invoice_tab" >
		<table class="form-table">



		<tr>
			<th width="200"><?php _e("Business Name:", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td>
			<input name="wp_invoice_business_name" type="text" class="input_field" value="<?php echo stripslashes(get_option('wp_invoice_business_name')); ?>">
			</td>
		</tr>
		<tr>
			<th width="200"><a class="wp_invoice_tooltip"  title="This will display on the invoice page when printed for clients' records.">Business Address</a>:</th>
			<td>
			<textarea name="wp_invoice_business_address" ><?php echo stripslashes(get_option('wp_invoice_business_address')); ?></textarea>
			</td>
		</tr>


		<tr>
			<th width="200"><?php _e("Business Phone", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td>
			<input name="wp_invoice_business_phone" type="text"  class="input_field" value="<?php echo stripslashes(get_option('wp_invoice_business_phone')); ?>">
			</td>
		</tr>

		<tr>
			<th><a class="wp_invoice_tooltip"  title="<?php _e("Address used to send out e-mail to client with web invoice link.", WP_INVOICE_TRANS_DOMAIN) ?>"><?php _e("Return eMail Address", WP_INVOICE_TRANS_DOMAIN) ?></a>:</th>
			<td>
			<input name="wp_invoice_email_address" class="input_field" type="text" value="<?php echo stripslashes(get_option('wp_invoice_email_address')); ?>">
			</td>
		</tr>

		<tr>
			<th width="200"><a class="wp_invoice_tooltip"  title="Message included in reminder emails.">Invoice Reminder Message</a>:</th>
			<td>
			<textarea name="wp_invoice_reminder_message" ><?php echo stripslashes(get_option('wp_invoice_reminder_message')); ?></textarea>
			</td>
		</tr>


		<tr>
			<th><a class="wp_invoice_tooltip"  title="An email will be sent automatically to client thanking them for their payment."><?php _e("Send Payment Confirmation:", WP_INVOICE_TRANS_DOMAIN) ?></a></th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_send_thank_you_email',array("yes" => __("Yes", WP_INVOICE_TRANS_DOMAIN),"no" => __("No", WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_send_thank_you_email')); ?>
			</td>
		</tr>
		
			<tr>
			<th><a class="wp_invoice_tooltip"  title="An email will be sent to you when a payment is made."><?php _e("Notify Me When Payment is Made:", WP_INVOICE_TRANS_DOMAIN) ?></a></th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_cc_thank_you_email',array("yes" => __("Yes", WP_INVOICE_TRANS_DOMAIN),"no" => __("No", WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_cc_thank_you_email')); ?>
			</td>
		</tr>
		
		<tr>
			<th>Minimum User Level to Manage WP-Invoice</a>:</th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_user_level',array("level_0" => "Subscriber","level_0" => "Contributor","level_2" => "Author","level_5" => "Editor","level_8" => "Administrator"), get_option('wp_invoice_user_level')); ?>
			</td>
		</tr>
		
		<tr>
			<th><?php _e("Show Recurring Options:", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_use_recurring',array("yes" => __("Yes", WP_INVOICE_TRANS_DOMAIN),"no" => __("No", WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_use_recurring')); ?>
			</td>
		</tr>
		

		
		</table>
  
  </div> 
  <div id="tab2"  class="wp_invoice_tab">
  
  		<table class="form-table">
		
			<tr class="invoice_main">
			<th><a class="wp_invoice_tooltip"  title="Select the page where your invoices will be displayed. Clients must follow their secured link, simply opening the page will not show any invoices.">Page to Display Invoices</a>:</th>
			<td>
			<select name='wp_invoice_web_invoice_page'>
			<option></option>
			<?php $list_pages = $wpdb->get_results("SELECT ID, post_title, post_name, guid FROM ". $wpdb->prefix ."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title");
			$wp_invoice_web_invoice_page = get_option('wp_invoice_web_invoice_page');
			foreach ($list_pages as $page)
			{ 
			echo "<option  style='padding-right: 10px;'";
			if(isset($wp_invoice_web_invoice_page) && $wp_invoice_web_invoice_page == $page->ID) echo " SELECTED ";
			echo " value=\"".$page->ID."\">". $page->post_title . "</option>\n"; 
			}
			echo "</select>";?>
			</td>
		</tr>

		<tr>
			<th> <a class="wp_invoice_tooltip"  title="<?php _e('Select whether to overwrite all page content, insert at the bottom of the content, or to look for the [wp-invoice] tag.', WP_INVOICE_TRANS_DOMAIN); ?>"><?php _e('How to Insert Invoice:', WP_INVOICE_TRANS_DOMAIN); ?></a></th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_where_to_display',array("overwrite" => "Overwrite All Page Content", "bellow_content" => "Place Bellow Content","above_content" => "Above Content","replace_tag" => "Replace [wp-invoice] Tag"), get_option('wp_invoice_where_to_display')); ?>
			If using the tag, place <span class="wp_invoice_explanation">[wp-invoice]</span> somewhere within your page content.
			</td>
		</tr>
		
		<tr>
			<th> <a class="wp_invoice_tooltip"  title="<?php _e('If enforced, WordPress will automatically reload the invoice page into HTTPS mode even if the user attemps to open it in non-secure mode.', WP_INVOICE_TRANS_DOMAIN); ?>"><?php _e('Enforce HTTPS:', WP_INVOICE_TRANS_DOMAIN); ?></a></th>
			<td>
			<select  name="wp_invoice_force_https">
			<option value="true" style="padding-right: 10px;"<?php if(get_option('wp_invoice_force_https') == 'true') echo 'selected="yes"';?>><?php _e('Yes', WP_INVOICE_TRANS_DOMAIN); ?></option>
			<option value="false" style="padding-right: 10px;"<?php if(get_option('wp_invoice_force_https') == 'false') echo 'selected="yes"';?>><?php _e('No', WP_INVOICE_TRANS_DOMAIN); ?></option>
			</select> <a href="http://www.dpbolvw.net/click-2456790-10379064" alt="GoDaddy.com" class="wp_invoice_click_me"><?php _e('Do you need an SSL Certificate?', WP_INVOICE_TRANS_DOMAIN); ?></a>
			</td>
		</tr>
		
		<tr>
			<th><a class="wp_invoice_tooltip"  title="Disable this if you want to use your own stylesheet."><?php _e('Use CSS:', WP_INVOICE_TRANS_DOMAIN); ?></a></th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_use_css',array("yes" => __('Yes', WP_INVOICE_TRANS_DOMAIN), "no" => __('No', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_use_css')); ?>
			</td>
		</tr>

		<tr>
			<th><a class="wp_invoice_tooltip"  title="Show your business name and address on invoice."><?php _e('Show Address on Invoice:', WP_INVOICE_TRANS_DOMAIN); ?></a></th>
			<td>
			<?php echo wp_invoice_draw_select('wp_invoice_show_business_address',array("yes" => __('Yes', WP_INVOICE_TRANS_DOMAIN), "no" => __('No', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_show_business_address')); ?>
			</td>
		</tr>

		<tr>
			<th width="200"><a class="wp_invoice_tooltip"  title="Show quantity breakdowns in the itemized list on the front-end."><?php _e('Quantities on Front End:', WP_INVOICE_TRANS_DOMAIN); ?></a></th><td>
			<?php echo wp_invoice_draw_select('wp_invoice_show_quantities',array("Show" => __('Show', WP_INVOICE_TRANS_DOMAIN), "Hide" => __('Hide', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_show_quantities')); ?>
			</td>
		</tr>		
	
				
		<tr>
			<th width="200"><a class="wp_invoice_tooltip"  title="Text to display at the top of an invoice."><?php _e('Invoice Welcome Line:', WP_INVOICE_TRANS_DOMAIN); ?></a></th><td>
			<?php 
			
			$wp_invoice_welcome_line = get_option('wp_invoice_welcome_line');
			$wp_invoice_welcome_line = (!empty($wp_invoice_welcome_line) ? $wp_invoice_welcome_line : "Welcome, %name%!");
			
			echo wp_invoice_draw_inputfield('wp_invoice_welcome_line', $wp_invoice_welcome_line); ?><br />
			Use the following tag to insert the payer's name: %name%<br />
			Example: <b>Welcome, %name%!</b>
			
			</td>
		</tr>		
		
		<tr>
			<th width="200"><a class="wp_invoice_tooltip"  title="What to display for states on checkout page."><?php _e('State Display:', WP_INVOICE_TRANS_DOMAIN); ?></a></th><td>
			<?php echo wp_invoice_draw_select('wp_invoice_fe_state_selection',array("Dropdown" => __('Dropdown', WP_INVOICE_TRANS_DOMAIN), "Input_Field" => __('Input Field', WP_INVOICE_TRANS_DOMAIN), "Hide" => __('Hide Completely', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_fe_state_selection')); ?>
			</td>
		</tr>
		</table>
  
  </div> 
  <div id="tab3"  class="wp_invoice_tab">
    	<table class="form-table">
			  
			<tr>
				<th><?php _e("Default Currency:");?></th>
				<td>
				<?php echo wp_invoice_draw_select('wp_invoice_default_currency_code',wp_invoice_currency_array(),get_option('wp_invoice_default_currency_code')); ?>
				</td>
			</tr>

			<tr>
				<th><a class="wp_invoice_tooltip"  title="Special proxy must be used to process credit card transactions on GoDaddy servers.">Using Godaddy Hosting</a></th>
				<td>
				<?php echo wp_invoice_draw_select('wp_invoice_using_godaddy',array("yes" => __('Yes', WP_INVOICE_TRANS_DOMAIN), "no" => __('No', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_using_godaddy')); ?>
				</td>
			</tr>


			<tr>
				<th><?php _e("Client can change payment method:") ?></th>
				<td>
				<?php echo wp_invoice_draw_select('wp_invoice_client_change_payment_method',array("yes" => __('Yes', WP_INVOICE_TRANS_DOMAIN), "no" => __('No', WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_client_change_payment_method')); ?>
				</td>
			</tr>

			<tr>
				<th><?php _e("Default Payment Method:") ?></th>
				<td>
				
					<?php
					$payment_array = wp_invoice_accepted_payment('global'); ?>
					<select id="wp_invoice_payment_method" name="wp_invoice_payment_method">
					<?php foreach ($payment_array as $payment_option) { ?>
					<option name="<?php echo $payment_option['name']; ?>"  value="<?php echo $payment_option['name']; ?>"  <?php if($payment_option['default']) { echo "SELECTED"; } ?>><?php echo $payment_option['nicename']; ?></option>
					<?php } ?>
					</select>

				</td>
			</tr>
			
			<tr>
				<th>&nbsp;</th>
				<td>
				<?php include WP_INVOICE_UI_PATH . "payment_processing.php"; ?>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="tab4"  class="wp_invoice_tab">
		<table class="form-table" >
		<tr>
			<th><?php _e("Phone Format", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td>
				<?php
				$wp_invoice_phone_number_format = get_option('wp_invoice_phone_number_format');
				// Set default to US if nothing is set due to upgrade
				$wp_invoice_phone_number_format = (!empty($wp_invoice_phone_number_format) ? $wp_invoice_phone_number_format : "xxx-xxx-xxxx")
				?>
				<input type="radio" name="wp_invoice_phone_number_format" <?php if($wp_invoice_phone_number_format == 'xxx-xxx-xxxx') echo 'CHECKED'; ?> value="xxx-xxx-xxxx"> 555-555-5555<br>
				<input type="radio" name="wp_invoice_phone_number_format" <?php if($wp_invoice_phone_number_format == 'xxxxx-xx-xxx') echo 'CHECKED'; ?> value="xxxxx-xx-xxx"> 55555-555-555<br>
				<input type="radio" name="wp_invoice_phone_number_format" <?php if($wp_invoice_phone_number_format == 'xxxxx-xx-xxxxxx') echo 'CHECKED'; ?> value="xxxxx-xx-xxxxxx"> 55555-555555<br>
			</td>
		</tr>
			
		<tr>
			<th width="200"><a class="wp_invoice_tooltip"  title=""><?php _e('Tax Label:', WP_INVOICE_TRANS_DOMAIN); ?></a></th><td>
			<?php echo wp_invoice_draw_inputfield('wp_invoice_custom_label_tax', get_option('wp_invoice_custom_label_tax')); ?>
			</td>
		</tr>	
		
		<tr>
			<th><?php _e("States", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td>
			
			</td>
		</tr>
		</table>
	</div>
	
	
	<div id="tab5"  class="wp_invoice_tab">

	<table class="form-table" >
		<tr>
			<th><?php _e("<b>Invoice Notification</b> Subject", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_invoice_subject', get_option('wp_invoice_email_send_invoice_subject')); ?></td>
		</tr>
			<tr>
			<th><?php _e("<b>Invoice Notification</b> Content", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_invoice_content', get_option('wp_invoice_email_send_invoice_content')); ?></td>
		</tr>

		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<th><?php _e("<b>Reminder</b> Subject", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_reminder_subject', get_option('wp_invoice_email_send_reminder_subject')); ?></td>
		</tr>
			<tr>
			<th><?php _e("<b>Reminder</b> Content", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_reminder_content', get_option('wp_invoice_email_send_reminder_content')); ?></td>
		</tr>		


		<tr><td colspan="2">&nbsp;</td></tr>

		<tr>
			<th><?php _e("<b>Receipt</b> Subject", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_inputfield('wp_invoice_email_send_receipt_subject', get_option('wp_invoice_email_send_receipt_subject')); ?></td>
		</tr>
			<tr>
			<th><?php _e("<b>Receipt</b> Content", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_textarea('wp_invoice_email_send_receipt_content', get_option('wp_invoice_email_send_receipt_content')); ?></td>
		</tr>		

		<tr>
		<td colspan="2"><input type="checkbox" id="wp_invoice_load_original_email_templates" name="wp_invoice_load_original_email_templates"><label for="wp_invoice_load_original_email_templates"> Load Original Content</label></td>
		</tr>
	
		</table>
	</div>	
	
	<div id="tab6"  class="wp_invoice_tab">
	<table class="form-table" >
		<tr>
			<td colspan="2">
			<p>Allow your clients to pull up their invoices by entering their invoice ID into a form. Include the form by either posting PHP code into your template or by using a shortcode.</p>
			<p><b>PHP Tag</b> - insert into a template file: <span class="wp_invoice_explanation">&#60;&#63;&#112;&#104;&#112;&#32;&#119;&#112;&#95;&#105;&#110;&#118;&#111;&#105;&#99;&#101;&#95;&#108;&#111;&#111;&#107;&#117;&#112;&#40;&#41;&#59;&#32;&#63;&#62;</span></p>
			<p><b>Shortcode</b> - insert into a page or post content: <span class="wp_invoice_explanation"><?php echo '[wp-invoice-lookup]'; ?></span></p>
			</td>
		</tr>
		
		<tr>
			<th><?php _e("Lookup Text", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_inputfield('wp_invoice_lookup_text', get_option('wp_invoice_lookup_text')); ?></td>
		</tr>

		<tr>
			<th><?php _e("Submit Button Text", WP_INVOICE_TRANS_DOMAIN) ?></th>
			<td><?php echo wp_invoice_draw_inputfield('wp_invoice_lookup_submit', get_option('wp_invoice_lookup_submit')); ?></td>
		</tr>
	
		<tr>
			<td colspan="2">
				<p>Note: This feature is also available in the form of a <a href="<?php echo admin_url('widgets.php'); ?>">widget</a>.</p>
			</td>
		</tr>

		</table>
	</div>
	
	<div id="tab7"  class="wp_invoice_tab">
		<table class="form-table" >
		<tr>
			<th>Systems Check</th>
			<td>
			
			<b>cURL: </b>
			
			<?php 
			if(!function_exists('curl_exec'))
				echo "Not turned on on your server, credit card processing will not work. If you have access to your php.ini file, activate <b>extension=php_curl.dll</b>.";
			else
				echo "Good.";
			?>
			
			</td>
		</tr>
		<tr>
			<th>Database Tables</th>
			<td>
				
			<p>Check to see if the database tables are installed properly.  If not, try deactivating and reactivating the plugin, if that doesn't work, <a href="http://twincitiestech.com/contact-us/">contact us</a>. </p>
			<?php 
			echo "Main Table - ";  if($wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('main')."';")) {echo "Good";} else {echo "Not Found"; }
			echo "<br />Meta Table - "; if($wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('meta')."';")) {echo "Good";} else {echo "Not Found"; }
			echo "<br />Log Table - ";  if($wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('log')."';")) {echo "Good";} else {echo "Not Found"; }
			?>

			</td>
		</tr>

		<tr>
			<th>Invoice Class Check</th>
			<td>
			Type in an invoice number to test the invoice class: <input name="wp_invoice_class_check" style="width: 100px;" /><input type="submit" value="Get Invoice" />
			<?php if(!empty($_REQUEST[wp_invoice_class_check])): ?>
				<pre style="background-color:#FFEBE8;border-color:#CC0000;margin:5px 0 15px;padding: 10px;-moz-border-radius:3px 3px 3px 3px;border-style:solid;border-width:1px;"><?php 
				$invoice_class = new wp_invoice_get($_REQUEST[wp_invoice_class_check]);
				print_r($invoice_class->data); ?>
				</pre>
			<?php endif; ?>
			</td>
		
		<tr>
			<th>Uninstall</th>
			<td colspan="2"><a id="delete_all_wp_invoice_databases" href="admin.php?page=invoice_settings&wp_invoice_action=complete_removal">Remove All WP-Invoice Databases</a> - Only do this if you want to completely remove the plugin.  All invoices and logs will be gone... forever.</td>
		</tr>
		
		

		</table>
	</div>

</div> 
  
 
<script type="text/javascript"> 
  jQuery("#wp_invoice_settings_page ul").idTabs(); 
</script>




<div id="poststuff" class="metabox-holder">
<div id="submitdiv" class="postbox" style="">	

<div class="inside">

<div id="major-publishing-actions">


<div id="publishing-action">
	<input type="submit" value="Save All Settings" class="button-primary"></div>
<div class="clear"></div>
</div>


</div>
</div>
</div>



</form>
</div>