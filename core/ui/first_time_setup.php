<style>
#screen-meta { display: none;}
</style>

<p style="font-size: 1.4em;">
Thank you for installing WP-Invoice! Complete the form below to configure the plugin's basics - more advanced settings can be configured later on the settings page. <br />
</p>
	<ol style="list-style-type:decimal;padding-left: 20px;" id="wp_invoice_first_time_setup">
<?php 
	$wp_invoice_web_invoice_page = get_option("wp_invoice_web_invoice_page");
	$wp_invoice_paypal_address = get_option("wp_invoice_paypal_address");
	$wp_invoice_moneybookers_address = get_option("wp_invoice_moneybookers_address");
	$wp_invoice_googlecheckout_address = get_option("wp_invoice_googlecheckout_address");
	$wp_invoice_gateway_username = get_option("wp_invoice_gateway_username");
	$wp_invoice_payment_method = get_option("wp_invoice_payment_method");

?>
	<form action="admin.php?page=web-invoice_page_overview" method='POST'>
	<input type="hidden" name="wp_invoice_action" value="first_setup">
<?php if(empty($wp_invoice_web_invoice_page) ) { ?>
	<li>Display my web invoices on the
		<select name='wp_invoice_web_invoice_page'>
		<option></option>
		<?php $list_pages = $wpdb->get_results("SELECT ID, post_title, post_name, guid FROM ". $wpdb->prefix ."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title");
		foreach ($list_pages as $page)
		{ 
		echo "<option  style='padding-right: 10px;'";
		if(isset($wp_invoice_web_invoice_page) && $wp_invoice_web_invoice_page == $page->ID) echo " SELECTED ";
		echo " value=\"".$page->ID."\">". $page->post_title . "</option>\n"; 
		} ?>
		</select> page
	</li>
<?php } ?>


	<li>
	<?php _e('Insert the invoice into the above page by ', WP_INVOICE_TRANS_DOMAIN); ?>
	<?php echo wp_invoice_draw_select('wp_invoice_where_to_display',array("overwrite" => "overwriting all page content", "bellow_content" => "placing the invoice below page content","above_content" => "placing it above the content","replace_tag" => "looking for and replacing the [wp-invoice] tag within page content"), get_option('wp_invoice_where_to_display')); ?>	
	</li>
	
 
	<li class="paypal_info payment_info">
		My PayPal username is 
		<input id='wp_invoice_paypal_address' name="wp_invoice_paypal_address" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('wp_invoice_paypal_address')); ?>">
	</li>
 
	<li>
		I<?php echo wp_invoice_draw_select('wp_invoice_use_recurring',array("yes" => __("want", WP_INVOICE_TRANS_DOMAIN),"no" => __("don't want", WP_INVOICE_TRANS_DOMAIN)), get_option('wp_invoice_use_recurring')); ?>		to use recurring billing features 	
	</li>

<?php /*
	<li>Send an invoice:
		<select name='user_id' class='user_selection'>
		<option ></option>
		<?php
		$get_all_users = $wpdb->get_results("SELECT * FROM ". $wpdb->prefix . "users LEFT JOIN ". $wpdb->prefix . "usermeta on ". $wpdb->prefix . "users.id=". $wpdb->prefix . "usermeta.user_id and ". $wpdb->prefix . "usermeta.meta_key='last_name' ORDER BY ". $wpdb->prefix . "usermeta.meta_value");
		foreach ($get_all_users as $user)
		{ 
		$profileuser = @get_user_to_edit($user->ID);
		echo "<option ";
		if(isset($user_id) && $user_id == $user->ID) echo " SELECTED ";
		if(!empty($profileuser->last_name) && !empty($profileuser->first_name)) { echo " value=\"".$user->ID."\">". $profileuser->last_name. ", " . $profileuser->first_name . " (".$profileuser->user_email.")</option>\n";  }
		else 
		{
		echo " value=\"".$user->ID."\">". $profileuser->user_login. " (".$profileuser->user_email.")</option>\n"; 
		}
		}
		?>
		</select>
	</li>
*/ 

 ?>
	</ol>
	
	<input type='submit' class='button' value='Save Settings' style="margin: 15px 0 0 40px;">
	</form>
	<?php  if(wp_invoice_is_not_merchant()) wp_invoice_cc_setup(false); ?>
	