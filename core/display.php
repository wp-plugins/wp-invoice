<?php
/*
	Created by TwinCitiesTech.com
	(website: twincitiestech.com       email : support@twincitiestech.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 3 of the License.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


function wp_invoice_list($atts = '') {

	global $current_user;

	 extract(shortcode_atts(array(
	      'due_invoice_title' => 'Due Invoice(s)',
	      'paid_invoice_title' => 'Paid Invoice(s)'
	  ), $atts));

	if(!isset($current_user->ID))
		return;

		$unpaid_invoice_array = wp_invoice_get_user_invoices("user_id={$current_user->ID}&status=unpaid");
		$paid_invoice_array = wp_invoice_get_user_invoices("user_id={$current_user->ID}&status=paid");

		if(!$unpaid_invoice_array && !$paid_invoice_array)
			return;

		ob_start();
        ?>

		<div class="wp_invoice_list">

		<?php if($unpaid_invoice_array) { ?>
		<h2><?php echo $due_invoice_title; ?></h2>
		<ul class="wpi_invoice_history_list wpi_due_invoices">
		<?php foreach($unpaid_invoice_array as $invoice) { ?>
			<li>
				<span class="wpi_invoice_link"><a href="<?php echo wp_invoice_build_invoice_link($invoice->invoice_num); ?>"><?php echo $invoice->subject; ?></a></span>
				<span class="wpi_invoice_due"><?php echo wp_invoice_currency_symbol(wp_invoice_determine_currency($invoice->invoice_num)) . wp_invoice_currency_format($invoice->amount); ?></span>
			</li>
		<?php  } ?>
		</ul>
		<?php } ?>

		<?php
		if($paid_invoice_array) { ?>

		<h2><?php echo $paid_invoice_title; ?></h2>
		<ul class="wpi_invoice_history_list wpi_paid_invoices">
			<?php foreach($paid_invoice_array as $invoice) {  ?>
				<li>
				<span class="wpi_invoice_link"><a href="<?php echo wp_invoice_build_invoice_link($invoice->invoice_num); ?>"><?php echo $invoice->subject; ?></a></span>
				<span class="wpi_invoice_due"><?php echo wp_invoice_currency_symbol(wp_invoice_determine_currency($invoice->invoice_num)) . wp_invoice_currency_format($invoice->amount); ?></span>
				</li>
			<?php  } ?>
		</ul>
		<?php } ?>


		</div>
		<?php 
		
		 $ret = ob_get_contents();
		ob_end_clean();
		
		return $ret;

}



function wp_invoice_lookup() {  ?>
<div class="wp_invoice_lookup">
	<form action="<?php echo get_permalink(get_option('wp_invoice_web_invoice_page')); ?>" method="POST">
	<label for="wp_invoice_lookup_input"><?php echo stripslashes(get_option('wp_invoice_lookup_text')); ?></label>
	<?php echo wp_invoice_draw_inputfield('wp_invoice_lookup_input', '',' AUTOCOMPLETE="off" '); ?>
	<input type="submit" value="<?php echo stripslashes(get_option('wp_invoice_lookup_submit')); ?>" class="wp_invoice_lookup_submit" />
	</form>
 </div>
<?php
}




function wp_invoice_default($message=''){
	global $wpdb;

	include WP_INVOICE_UI_PATH . 'ui_overview_page.php';
}

/*
	Draw invoice row for overview tables
*/
	function wp_invoice_invoice_row($invoice) {

		// wpi_qc($invoice);

		$invoice_id = $invoice->id;
		$user_class = $invoice->user_class;
		$overview_link = admin_url('admin.php?page=web-invoice_page_overview');


		// Color coding
		if(wp_invoice_paid_status($invoice_id)) $class_settings .= " alternate ";
		if(wp_invoice_meta($invoice_id,'archive_status') == 'archived')  $class_settings .= " wp_invoice_archived ";

			// Days Since Sent
		if($invoice->is_paid) {
			$days_since = "<span style='display:none;'>-1</span>".__(' Paid', WP_INVOICE_TRANS_DOMAIN); }
		else {
			if($invoice->sent_date) {

			$date1 = $invoice->sent_date;
			$date2 = date("Y-m-d", time());
			$difference = abs(strtotime($date2) - strtotime($date1));
			$days = round(((($difference/60)/60)/24), 0);
			if($days == 0) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Today. ', WP_INVOICE_TRANS_DOMAIN); }
			elseif($days == 1) { $days_since = "<span style='display:none;'>$days</span>".__('Sent Yesterday. ', WP_INVOICE_TRANS_DOMAIN); }
			elseif($days > 1) { $days_since = "<span style='display:none;'>$days</span>".sprintf(__('Sent %s days ago. ', WP_INVOICE_TRANS_DOMAIN),$days); }
			}
			else {
			$days_since ="<span style='display:none;'>999</span>".__('Not Sent', WP_INVOICE_TRANS_DOMAIN);	}
		}


		$invoice_edit_link = "admin.php?page=new_invoice&wp_invoice_action=doInvoice&invoice_id=$invoice_id";

		// Setup row actions
		$row_actions = "<div class='row-actions'>
		<span class='edit'><a href='$invoice_edit_link'>Edit</a> | </span>
		<span class='view'><a href='{$invoice->pay_link}'>View</a> | </span>";

		if($invoice->is_archived) {
			$row_actions .= "<span class='unarchive'><a href='$overview_link&wp_invoice_action=unrachive_invoice&multiple_invoices[0]=$invoice_id' class=''>Un-Archive</a> | </span>";
		}

		if(!$invoice->is_archived) {
			$row_actions .= "<span class='archive'><a href='$overview_link&wp_invoice_action=archive_invoice&multiple_invoices[0]=$invoice_id' class=''>Archive</a> | </span>";
		}

		$delete_url = admin_url("admin.php?page=web-invoice_page_overview&delete_single_invoice={$invoice_id}&_wpnonce=" . wp_create_nonce('wp_invoice_delete_' . $invoice_id));
		$row_actions .= "<span class='delete'><a onclick='return wp_invoice_confirm_delete();' href='$delete_url' class='submitdelete'>Delete</a></span>";


		$row_actions .= "</div>";


		// Setup display

		$r = "<tr id='invoice-$invoice_id' class='{$invoice_id}_row $class_settings'>";

		if($invoice->is_recurring) {
			$columns = get_column_headers("web-invoice_page_recurring_billing");
			$hidden = get_hidden_columns("web-invoice_page_recurring_billing");
		} else {
			$columns = get_column_headers("toplevel_page_web-invoice_page_overview");
			$hidden = get_hidden_columns("toplevel_page_web-invoice_page_overview");
		}

		foreach ( $columns as $column_name => $column_display_name ) {
		$class = "class=\"$column_name column-$column_name\"";

 		$style = '';
		if ( in_array($column_name, $hidden) )
			$style = ' style="display:none;"';

		$attributes = "$class$style";

		switch ($column_name) {
			case 'cb':
				$r .= "<th scope='row' class='check-column'><input type='checkbox' name='multiple_invoices[]' value='$invoice_id'></th>";
				break;
			case 'subject':
				$r .= "<td $attributes><a class='row-title' href='$invoice_edit_link' title='Edit $subject'>{$invoice->subject}</a>";
				$r .= $row_actions;
				$r .= "</td>";

				break;
			case 'balance':
				$r .= "<td $attributes>{$invoice->display_amount}</td>";
			break;

			case 'status':
				$r .= "<td $attributes>". ($days_since ? " $days_since " : "-")."</td>";
			break;

			case 'date_sent':

				$date_sent_string = strtotime(wp_invoice_meta($invoice_id,'sent_date'));
				if(!empty($date_sent_string))
					$r .= "<td $attributes sortvalue='".date("Y-m-d", $date_sent_string)."'>". date("M d, Y", $date_sent_string). "</td>";
				else
					$r .= "<td $attributes>&nbsp;</td>";

			break;

			case 'invoice_id':
				$r .= "<td $attributes><a href='$invoice_edit_link'>{$invoice->id}</a></td>";
			break;


			case 'due_date':

				$due_date_string = strtotime(wp_invoice_meta($invoice_id,'wp_invoice_due_date_day') . "-" . wp_invoice_meta($invoice_id,'wp_invoice_due_date_month') . "-" . wp_invoice_meta($invoice_id,'wp_invoice_due_date_year'));
				if(!empty($due_date_string))
					$r .= "<td $attributes sortvalue='".date("Y-m-d", $due_date_string)."'>" . date("M d, Y", $due_date_string). "</td>";
				else
					$r .= "<td $attributes>&nbsp;</td>";
			break;


			case 'user_email':
				$r .= "<td $attributes><a href='mailto:{{$user_class->user_email}'>{$user_class->user_email}</a></td>";
			break;

			case 'user':
				$r .= "<td $attributes>{$user_class->nickname}</td>";
			break;

			case 'wp_username':
				$r .= "<td $attributes>{$user_class->user_login}</td>";
			break;


			case 'company_name':
				$r .= "<td $attributes>{$user_class->company_name}&nbsp;</td>";
			break;

			// Recurring related
			case 'date_started':
				$r .= "<td $attributes>{$invoice->subscription_started}&nbsp;</td>";
			break;

			case 'end_date':
				$r .= "<td $attributes>-</td>";
			break;

			case 'total_cycles':
				$r .= "<td $attributes>{$invoice->subscription_total_occurances}&nbsp;</td>";
			break;



			default:
				$r .= "<td $attributes>";
 				$r .= "";
				$r .= "</td>";

		}
		}
	$r .= '</tr>';

	return $r;

	}
/*
	Obsolete function
*/
function wp_invoice_recurring_overview($message='') {
	global $wpdb;
	include WP_INVOICE_UI_PATH . 'ui_overview_recurring_page.php';
}

function wp_invoice_saved_preview($invoice_id) {



	global $wp_invoice_email_variables;

	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice_class = $invoice_class->data;

	$wp_invoice_email_variables = wp_invoice_email_variables($invoice_id);

	include WP_INVOICE_UI_PATH . 'ui_save_and_preview.php';

}

function wp_invoice_user_selection_screen() {
 	?>
	<style>
	#screen-meta {display:none;}
	</style>
	<div class="wrap">
		<div class="postbox" id="wp_new_invoice_div">
		<div class="inside">
		<?php wp_invoice_draw_user_selection_form(); ?>
		</div>
		</div>
	</div>

	<?php



}

function wp_invoice_options_manageInvoice($invoice_id = '',$message='')
{
	global $wpdb, $screen_layout_columns;

	require_once 'ui/metabox_manage_page.php';

	// Convert email to user_id (wpi_premium functionality)
	if(isset($_REQUEST[wp_invoice_userlookup]))
		$user_id = wp_invoice_convert_email_to_id($_REQUEST[wp_invoice_userlookup]);
	else
		$user_id = (!empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : false);


	$copy_from_template 		= (!empty($_REQUEST['copy_from_template']) ? $_REQUEST['copy_from_template'] : false);
	$invoice_id 				= (!empty($_REQUEST['invoice_id']) ? $_REQUEST['invoice_id'] : false);
	$wp_invoice_invoice_link 	= get_permalink(get_option('wp_invoice_web_invoice_page'));
	$warning_message 			.= wp_invoice_detect_config_erors();



	// New Invoice From Template
	if($copy_from_template && $user_id) {
		$invoice_class = new load_wp_invoice();
		$invoice_class->create_from_template($_POST['copy_from_template'], $user_id);
 	}

	// Invoice Exists, we are modifying it, and it is NOT a template copy
	if($invoice_id && !$copy_from_template) {
		$invoice_class = new load_wp_invoice();
		$invoice_class->load_existing($invoice_id);
		$invoice_class = $invoice_class->data;
	}

	// Brand New Invoice
	if(!$invoice_id && $user_id && !$copy_from_template) {
		$invoice_class = new load_wp_invoice();
		$invoice_class->create_new($user_id);
	}


	//Whether recurring bill will start when client pays, or a date is specified
	if($invoice_class->subscription_start_month && $invoice_class->subscription_start_year && $invoice_class->subscription_start_day)
		$invoice_class->recurring_auto_start = true;
	else
		$invoice_class->recurring_auto_start = false;


 	// Throw up a notice if user for invoice has been deleted, which is determined in load_existing() function
	if($invoice_class->user_deleted):
		$message .= "Notice: The user for this invoice has been deleted, this invoice may only be used as a template.";
	endif;


	// Load invoice-applicable metaboxes

	if($invoice_id && wp_invoice_get_invoice_status($invoice_id,'100'))
		add_meta_box('wp_invoice_metabox_history', __("This Invoice's History (<a href='admin.php?page=new_invoice&invoice_id=$invoice_id&wp_invoice_action=clear_log'>Clear Log</a>)",WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_history', 'web-invoice_page_new_invoice', 'normal', 'low');

	if(!$invoice_class->user_deleted):
		$wp_invoice_user_metabox_title = ($invoice_class->create_new_user ? "New User Information" : "User Information");
		add_meta_box('wp_invoice_user_metabox', __($wp_invoice_user_metabox_title,WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_user_metabox', 'web-invoice_page_new_invoice', 'normal', 'high');
	endif;

	if(!$user_id)
		$user_id = $invoice_class->user_id;

	// Create new invoice_id if one not set
	if(empty($invoice_id))
		$invoice_id = rand(10000000, 90000000);

	//wpi_qc($invoice_class);

	// Begin UI
	include WP_INVOICE_UI_PATH . 'ui_invoice_page.php';



}

function wp_invoice_overview_page_footer() {
?>
	<div class="wp_invoice_stats">Total of Displayed Invoices: <span id="wp_invoice_total_owed"></span></div>
	<div class="wp_invoice_support">
	Improve WP-Invoice by <a href="http://wpinvoice.uservoice.com/pages/general">submitting new ideas</a>. |
	Support further development by considering our affiliates <a href="http://www.merchantexpress.com/?id=3647">MerchantExpress.com</a> or <a href="https://support.merchantplus.com/partners/idevaffiliate.php?id=1089">MerchantPlus.com</a> to handle your credit card transactions.</a>.</div>
	<?php
}

function wp_invoice_show_welcome_message() {

	global $wpdb;
	include WP_INVOICE_UI_PATH . 'first_time_setup.php';

}

function wp_invoice_show_settings() {
global $wpdb;


	if(isset($_POST['wp_invoice_billing_meta'])) {
		$wp_invoice_billing_meta = explode('
		',$_POST['wp_invoice_billing_meta']);
		$wp_invoice_billing_meta = wp_invoice_fix_billing_meta_array($wp_invoice_billing_meta);
		update_option('wp_invoice_billing_meta', urlencode(serialize($wp_invoice_billing_meta)));
	}

	if(get_option('wp_invoice_billing_meta') != '') $wp_invoice_billing_meta = unserialize(urldecode(get_option('wp_invoice_billing_meta')));

	$errors .= wp_invoice_detect_config_erors();

	$wp_invoice_payment_method = get_option('wp_invoice_payment_method');

	$wp_invoice_paypal_allow = get_option('wp_invoice_paypal_allow');
	$wp_invoice_paypal_address = get_option('wp_invoice_paypal_address');
	$wp_invoice_fe_paypal_link_url = get_option('wp_invoice_fe_paypal_link_url');

	$wp_invoice_moneybookers_allow = get_option('wp_invoice_moneybookers_allow');
	$wp_invoice_moneybookers_address = get_option('wp_invoice_moneybookers_address');
	$wp_invoice_moneybookers_secret = get_option('wp_invoice_moneybookers_secret');
	$wp_invoice_moneybookers_ip = get_option('wp_invoice_moneybookers_ip');

	$wp_invoice_cc_allow = get_option('wp_invoice_cc_allow');
	$wp_invoice_gateway_username = get_option('wp_invoice_gateway_username');
	$wp_invoice_gateway_tran_key = get_option('wp_invoice_gateway_tran_key');
	$wp_invoice_gateway_url = get_option('wp_invoice_gateway_url');

	$wp_invoice_recurring_gateway_url = get_option('wp_invoice_recurring_gateway_url');
	$wp_invoice_gateway_test_mode = get_option('wp_invoice_gateway_test_mode');
	$wp_invoice_gateway_delim_char = get_option('wp_invoice_gateway_delim_char');
	$wp_invoice_gateway_encap_char = get_option('wp_invoice_gateway_encap_char');
	$wp_invoice_gateway_merchant_email = get_option('wp_invoice_gateway_merchant_email');
	$wp_invoice_gateway_email_customer = get_option('wp_invoice_gateway_email_customer');
	$wp_invoice_gateway_MD5Hash = get_option('wp_invoice_gateway_MD5Hash');

	$wp_invoice_alertpay_allow = get_option('wp_invoice_alertpay_allow');
	$wp_invoice_alertpay_secret = get_option('wp_invoice_alertpay_secret');

	$wp_invoice_invoice_link = get_permalink(get_option('wp_invoice_web_invoice_page'));

	// Email Templates

	$hide_advanced_paypal_features = true;
	$hide_advanced_cc_features = true;

	include WP_INVOICE_UI_PATH . 'ui_settings_page.php';


}



function wp_invoice_cc_setup($show_title = TRUE) {
if($show_title) { ?> 	<div id="wp_invoice_need_mm" style="border-top: 1px solid #DFDFDF; ">Do you need to accept credit cards?</div> <?php } ?>

<div class="wrap">
<div class="wp_invoice_credit_card_processors wp_invoice_rounded_box">
<p>WP-Invoice users are eligible for special credit card processing rates from <a href="http://twincitiestech.com/links/MerchantPlus.php">MerchantPlus</a> (800-546-1997) and <a href="http://twincitiestech.com/links/MerchantExpress.php">MerchantExpress.com</a> (888-845-9457). <a href="http://twincitiestech.com/links/MerchantWarehouse.php">MerchantWarehouse</a> (866-345-5959) was unable to offer us special rates due to their unique pricing structure. However, they are one of the most respected credit card processing companies and have our recommendation.
</p>
</div>
</div>

	<?php
}

function wp_invoice_dashboard() {
	// Daten lesen von Funktion fs_getfeeds()
	$content ="helo";
	echo $content;
}



function wp_invoice_show_email($invoice_id, $force_original = false) {
	global $wp_invoice_email_variables;
	$wp_invoice_email_variables = wp_invoice_email_variables($invoice_id);

	if(!$force_original && wp_invoice_meta($invoice_id, 'wp_invoice_email_message_content') != "")
		return str_replace("<br />", "\n",wp_invoice_meta($invoice_id, 'wp_invoice_email_message_content'));

	return str_replace("<br />", "\n",preg_replace_callback('/(%([a-z_]+)%)/',  'wp_invoice_email_apply_variables', get_option('wp_invoice_email_send_invoice_content')));

}

function wp_invoice_show_reminder_email($invoice_id) {

	global $wp_invoice_email_variables;
	$wp_invoice_email_variables = wp_invoice_email_variables($invoice_id);

	return preg_replace_callback('/(%([a-z_]+)%)/', 'wp_invoice_email_apply_variables', get_option('wp_invoice_email_send_reminder_content'));
}

function wp_invoice_show_receipt_email($invoice_id) {

	global $wp_invoice_email_variables;
	$wp_invoice_email_variables = wp_invoice_email_variables($invoice_id);

	return preg_replace_callback('/(%([a-z_]+)%)/', 'wp_invoice_email_apply_variables', get_option('wp_invoice_email_send_receipt_content'));
}


function wp_invoice_draw_itemized_table($invoice_id) {
	global $wpdb;


	$invoice_info = $wpdb->get_row("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
	$itemized = $invoice_info->itemized;
	$amount = $invoice_info->amount;

	$wp_invoice_tax = wp_invoice_meta($invoice_id,'wp_invoice_tax');
	if($wp_invoice_tax == '') $wp_invoice_tax = wp_invoice_meta($invoice_id,'tax_value');


	// Determine currency. First we check invoice-specific, then default code, and then we settle on USD
	$currency_code = wp_invoice_determine_currency($invoice_id);


	if($wp_invoice_tax) {
		$tax_free_amount = $amount*(100/(100+(100*($wp_invoice_tax/100))));
		$tax_value = $amount - $tax_free_amount;
		}


	if(!strpos($amount,'.')) $amount = $amount . ".00";
	$itemized_array = unserialize(urldecode($itemized));


	if(is_array($itemized_array)) {
		$response .= "<table id=\"wp_invoice_itemized_table\">
		<tr>\n";
		if(get_option('wp_invoice_show_quantities') == "Show") { $response .= '<th style="width: 40px; text-align: right;">Quantity</th>'; }
		$response .="<th>Item</th><th style=\"width: 70px; text-align: right;\">Cost</th>
		</tr> ";
		$i = 1;
		foreach($itemized_array as $itemized_item){
		//Show Quantites or not
		if(get_option('wp_invoice_show_quantities') == '') $show_quantity = false;
		if(get_option('wp_invoice_show_quantities') == 'Hide') $show_quantity = false;
		if(get_option('wp_invoice_show_quantities') == 'Show') $show_quantity = true;



		if(!empty($itemized_item[name])) {
		if(!strpos($itemized_item[price],'.')) $itemized_item[price] = $itemized_item[price] . ".00";

		if($i % 2) { $response .= "<tr>"; }
		else { $response .= "<tr  class='alt_row'>"; }

		//Quantities
		if($show_quantity) {
		$response .= "<td style=\"width: 70px; text-align: right;\">" . $itemized_item[quantity] . "</td>";	}

		//Item Name
		$response .= "<td>" . stripslashes($itemized_item[name]) . " <br /><span class='description_text'>" . stripslashes($itemized_item[description]) . "</span></td>";

		//Item Price
		if(!$show_quantity) {
		 $response .= "<td style=\"width: 70px; text-align: right;\">" . wp_invoice_currency_symbol($currency_code) .  wp_invoice_currency_format($itemized_item[quantity] * $itemized_item[price]) . "</td>";
		 } else {
		 $response .= "<td style=\"width: 70px; text-align: right;\">". wp_invoice_currency_symbol($currency_code) . wp_invoice_currency_format($itemized_item[price]) . "</td>";
		 }


		$response .="</tr>";
		$i++;
		}

		}
		if($wp_invoice_tax) {
		$response .= "<tr>";
		if(get_option('wp_invoice_show_quantities') == "Show") { $response .= "<td></td>"; }
		$response .= "<td>". get_option('wp_invoice_custom_label_tax') . " (". round($wp_invoice_tax,2). "%) </td><td style='text-align:right;' colspan='2'>" . wp_invoice_currency_symbol($currency_code) . wp_invoice_currency_format($tax_value)."</td></tr>";
		}

		$response .="
		<tr class=\"wp_invoice_bottom_line\">
		<th align=\"right\">Invoice Total:</th>
		<th colspan=\"2\" style=\"text-align: right;\" class=\"grand_total\">";

		$response .= wp_invoice_currency_symbol($currency_code) . wp_invoice_currency_format($amount);
		$response .= "</th></table>";

		return $response;
	}

}


function wp_invoice_draw_itemized_table_plaintext($invoice_id) {
	global $wpdb;
	$invoice_info = $wpdb->get_row("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
	$itemized = $invoice_info->itemized;
	$amount = $invoice_info->amount;
	if(!strpos($amount,'.')) $amount = $amount . ".00";

	$itemized_array = unserialize(urldecode($itemized));

	if(is_array($itemized_array)) {


		foreach($itemized_array as $itemized_item){
			if(!empty($itemized_item[name])) {
			$item_cost = $itemized_item[price] * $itemized_item[quantity];
			if(!strpos($item_cost,'.')) $item_cost = $item_cost . ".00";

		$response .= " $" . $item_cost . " \t - \t " . stripslashes($itemized_item[name]) . "\n";


		}
		}

		return $response;
	}

}



function wp_invoice_user_profile_fields()
{
	global $wpdb;
	global $user_id;


	$profileuser = @get_user_to_edit($user_id);
	?>

	<h3>Billing / Invoicing Info</h3>
	<a name="billing_info"></a>
	<table class="form-table" >

	<tr>
	<th><label for="company_name">Company Name</label></th>
	<td><input type="text" name="company_name" id="company_name" value="<?php echo get_usermeta($user_id,'company_name'); ?>" /></td>
	</tr>

	<tr>
	<th><label for="streetaddress">Street Address</label></th>
	<td><input type="text" name="streetaddress" id="streetaddress" value="<?php echo get_usermeta($user_id,'streetaddress'); ?>" /></td>
	</tr>

	<tr>
	<th><label for="city">City</label></th>
	<td><input type="text" name="city" id="city" value="<?php echo get_usermeta($user_id,'city'); ?>" /></td>
	</tr>

	<tr>
	<th><label for="state">State</label></th>
	<td><input type="text" name="state" id="state" value="<?php echo get_usermeta($user_id,'state'); ?>" /><br />
	<p class="note">Use two-letter state codes for safe credit card processing.</p></td>
	</tr>

	<tr>
	<th><label for="streetaddress">ZIP Code</label></th>
	<td><input type="text" name="zip" id="zip" value="<?php echo get_usermeta($user_id,'zip'); ?>" /></td>
	</tr>

	<tr>
	<th><label for="phonenumber">Phone Number</label></th>
	<td><input type="text" name="phonenumber" id="phonenumber" value="<?php echo get_usermeta($user_id,'phonenumber'); ?>" />
	<p class="note">Enforce 555-555-5555 format if you are using PayPal.</p></td>
	</tr>

	<tr>
	<th></th>
	<td>

	<input type='button' onclick="window.location='admin.php?page=new_invoice&user_id=<?PHP echo $user_id; ?>';" class='button' value='Create New Invoice For This User'>

	</td>
	</tr>


</table>
<?php
}

function wp_invoice_show_paypal_reciept($invoice_id) {

	//$invoice = new WP_Invoice_GetInfo($invoice_id);
 	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;

	// Have to depend on this return variable because PayPal API does not currently return any data from subscriptions
	if(isset($_REQUEST['paypal_recurring_return'])) {


		if(get_option('wp_invoice_send_thank_you_email') == 'yes') wp_invoice_send_email_receipt($invoice_id);

		wp_invoice_update_invoice_meta($invoice_id,'paid_status','paid');
		wp_invoice_update_invoice_meta($invoice_id,'subscription_started',date('Y-m-d'));
		wp_invoice_update_log($invoice_id,'paid',"PayPal Subscription started, but cannot be automatically verified, processed by ". $_SERVER['REMOTE_ADDR']);

 		return '<div id="invoice_page" class="clearfix">
		<div id="invoice_overview" class="cleafix">
		<h2 class="invoice_page_subheading">'.$invoice->user_class->callsign. ', thank you for your payment!</h2>
		<p><strong>Your recurring subscription has been started.</strong></p>
		</div>
		</div>';



	} else {

	if(isset($_POST['first_name'])) update_usermeta($invoice->user_class->user_id, 'first_name', $_POST['first_name']);
	if(isset($_POST['last_name'])) update_usermeta($invoice->user_class->user_id, 'last_name', $_POST['last_name']);

	if(get_option('wp_invoice_send_thank_you_email') == 'yes') wp_invoice_send_email_receipt($invoice_id);

	wp_invoice_paid($invoice_id);
	wp_invoice_update_log($invoice_id,'paid',"PayPal Payment Status: " . $_REQUEST['payment_status']);
	if(isset($_REQUEST['payer_email'])) wp_invoice_update_log($invoice_id,'paid',"PayPal payee user email: (" . $_REQUEST['payer_email']. ")");


	return '<div id="invoice_page" class="clearfix">
	<div id="invoice_overview" class="cleafix">
	<h2 class="invoice_page_subheading">'.$invoice->user_class->callsign. ', thank you for your payment!</h2>
	<p><strong>Invoice ' . $invoice->display_id . ' has been paid.</strong></p>
	</div>
	</div>';


	}
}

function wp_invoice_show_already_paid($invoice_id) {
 	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;

	return "<p>Thank you, this invoice was paid on {$invoice->paid_date}</p>";
}

function wp_invoice_show_invoice_overview($invoice_id) {
 $invoice_class = new wp_invoice_get($invoice_id);
$invoice = $invoice_class->data;
?>
<div id="invoice_overview" class="clearfix">
	<h2 id="wp_invoice_welcome_message" class="invoice_page_subheading"><?php echo wp_invoice_welcome_line($invoice->user_class->callsign); ?></h2>
	<p class="wp_invoice_main_description">We have sent you invoice <b><?php echo $invoice->display_id; ?></b> with a total amount of <?php echo $invoice->display_amount; ?>.</p>
	<?php if($invoice->due_date) { ?> <p class="wp_invoice_due_date">Due Date: <?php echo $invoice->due_date; } ?>
	<?php if($invoice->description) { ?><p><?php echo nl2br($invoice->description);  ?></p><?php  } ?>
	<?php echo wp_invoice_draw_itemized_table($invoice_id); ?>
</div>
<?php
}

function wp_invoice_show_business_address() {
?>
<div id="invoice_business_info" class="clearfix">
	<h2 class="invoice_page_subheading">Bill From:</h2>
	<p class="wp_invoice_business_name"><?php echo get_option('wp_invoice_business_name'); ?></p>
	<p class="wp_invoice_business_address"><?php echo nl2br(get_option('wp_invoice_business_address')); ?></p>
</div>

<?php
}


function wp_invoice_show_billing_information($invoice_id) {
 $invoice_class = new wp_invoice_get($invoice_id);
$invoice = $invoice_class->data;

?>
<div id="billing_overview" class="clearfix">
<h2 class="invoice_page_subheading">Billing Information</h2>

<?php
// count how many payment options we have availble

// Create payment array

$payment_array = wp_invoice_accepted_payment($invoice_id);

 //show dropdown if it is allowed, and there is more than one payment option
if($invoice->client_change_payment_method == 'yes' || $invoice->client_change_payment_method == '1' && count($payment_array) > 1) { ?>

<fieldset id="wp_invoice_select_payment_method">
	<ol>
	<li>
	<label for="first_name">Select Payment Method </label>
	<select id="wp_invoice_select_payment_method_selector" onChange="changePaymentOption()">
	<?php foreach ($payment_array as $payment_option) { ?>
		<option name="<?php echo $payment_option['name']; ?>" <?php if($payment_option['default']) { echo "SELECTED"; } ?>><?php echo $payment_option['nicename']; ?></option>
	<?php } ?>
	</select>
	</li>
	</ol>
</fieldset>
<?php } ?>

<?php // Include payment-specific UI files
 foreach ($payment_array as $payment_option) { ?>
	 <div class="<?php echo $payment_option['name']; ?>_ui payment_info"><?php include "ui/{$payment_option['name']}.php"; ?></div>
 <?php }  ?>



</div>

<?php
}

function wp_invoice_welcome_line($name = false) {

	$wp_invoice_welcome_line = get_option('wp_invoice_welcome_line');
	$wp_invoice_welcome_line = (!empty($wp_invoice_welcome_line) ? $wp_invoice_welcome_line : "Welcome, %name%!");

	// Empty value should never be passed unless something got screwed up. But just in case.
	if(!$name) {
		return "Welcome!";
	}

	return str_replace("%name%", $name, $wp_invoice_welcome_line);

}

function wp_invoice_show_recurring_info($invoice_id) {
 	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;
?>
<div id="recurring_info" class="clearfix">
	<?php if($invoice->due_date) { ?> <p class="wp_invoice_due_date">Due Date: <?php echo $invoice->due_date; } ?>
	<h2 id="wp_invoice_welcome_message" class="invoice_page_subheading"><?php echo wp_invoice_welcome_line($invoice->user_class->callsign); ?>!</h2>
	<?php if($invoice->description) { ?><p><?php echo nl2br($invoice->description);  ?></p><?php  } ?>

	<p class="recurring_info_breakdown">This is a recurring bill, id: <b><?php echo $invoice->display_id; ?></b>.</p>
	<p>You will be billed <?php echo $invoice->display_billing_rate; ?> in the amount of <?php echo $invoice->display_amount;

	// Determine if startning now or t a set date
	if ($invoice->subscription_start_day != '' && $invoice->subscription_start_month !='' && $invoice->subscription_start_year  != '') {

	}

		switch ($invoice->subscription_unit) {
			case 'days':
				$subscription_unit = "day(s)";
			break;
			case 'months':
				$subscription_unit = "month(s)";
			break;
			default:
				$subscription_unit = $invoice->subscription_unit;
			break;
		}

		echo " every " . $invoice->subscription_length . " " . $subscription_unit . " for " .$invoice->subscription_total_occurances . " " .  $subscription_unit;
		echo " starting on " . date_i18n(get_option('date_format'), strtotime($invoice->subscription_start_day ."-". $invoice->subscription_start_month ."_".  $invoice->subscription_start_year));
	?>.</p>

	<?php echo wp_invoice_draw_itemized_table($invoice_id); ?>

</div>
<?php
}


function wpi_qc($what, $title = false) {

		// Do nothing is user is not logged in to prevent headers errors
		if(!is_user_logged_in())
			return;

		?>
		<div class="error" onclick="jQuery(this).hide();" style="padding: 10px;">
		<b><?php echo $title; ?></b>
		<pre style="text-align: left; font-family: calibri; margin-top:10px;"><?php print_r($what); ?></pre>
		</div>


		<?php
	}


function wp_invoice_draw_user_selection_form($user_id = false) {
	global $wpdb;
	$user_array = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}users");
	include WP_INVOICE_UI_PATH . 'ui_user_selection.php';
}
?>