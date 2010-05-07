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

	// The error takes precedence over others being that nothing can be done w/o tables
	if(!$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('log')."';")) { $warning_message = ""; }
	
	if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";
	
	// Need to filter out recurring invoices
	$all_invoices = $wpdb->get_results("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num != ''");

 
?>		 
	
	<script>
	 pagenow = 'toplevel_page_web-invoice_page_overview';
	</script>
	<form id="invoices-filter" action="" method="post" >
	<h2><?php _e('Invoice Overview', WP_INVOICE_TRANS_DOMAIN); ?>
	<a class="button add-new-h2" href="<?php echo admin_url("admin.php?page=new_invoice"); ?>">Add New</a>
	</h2>
	<div class="tablenav clearfix">
	
	<div class="alignleft">
	<select id="wp_invoice_action" name="wp_invoice_action">
		<option value="-1" selected="selected"><?php _e('-- Actions --', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="send_invoice" name="sendit" ><?php _e('Send Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="send_reminder" name="sendit" ><?php _e('Send Reminder(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="archive_invoice" name="archive" ><?php _e('Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="unrachive_invoice" name="unarchive" ><?php _e('Un-Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_sent" name="mark_as_sent" ><?php _e('Mark as Sent', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_paid" name="mark_as_paid" ><?php _e('Mark as Paid', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_unpaid" name="mark_as_unpaid" ><?php _e('Unset Paid Status', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option  value="delete_invoice" name="deleteit" ><?php _e('Delete', WP_INVOICE_TRANS_DOMAIN); ?></option>
	</select>
	<input type="submit" value="Apply" id="submit_bulk_action" class="button-secondary action" />
	</div>

	<div class="alignright">
		<ul class="subsubsub" style="margin:0;">
		<li><?php _e('Filter:', WP_INVOICE_TRANS_DOMAIN); ?></li>
		<li><a href='#' class="" id="">All Invoices</a> |</li>
		<li><a href='#'  class="paid" id="">Paid</a> |</li>
		<li><a href='#'  class="sent" id="">Unpaid</a> |</li>
		<li><?php _e('Custom: ', WP_INVOICE_TRANS_DOMAIN); ?><input type="text" id="FilterTextBox" class="search-input" name="FilterTextBox" /> </li>
		</ul>
	</div>
	</div>
	<br class="clear" />
	
	<table class="widefat fixed" cellspacing="0"  id="invoice_sorter_table">
	<thead>
	<tr class="thead">
	<?php print_column_headers("toplevel_page_web-invoice_page_overview") ?>
	</tr>
	</thead>

	<tfoot>
	<tr class="thead">
	<?php print_column_headers("toplevel_page_web-invoice_page_overview", false) ?>
	</tr>
	</tfoot>

	<tbody id="invoices" class="list:invoices invoice-list">
	<?php
	$style = '';
	$x_counter = 0;
	foreach ($all_invoices as $invoice) {	
		if(wp_invoice_meta($invoice->invoice_num,'wp_invoice_recurring_billing'))
			continue;
			
		$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
		echo "\n\t" . wp_invoice_invoice_row($invoice);
		$x_counter++;
	}
	
	if($x_counter == 0) { ?>
	<tr><td colspan="00" align="center"><div style="padding: 20px;"><?php _e('You have not created any invoices yet, ', WP_INVOICE_TRANS_DOMAIN); ?><a href="admin.php?page=new_invoice"><?php _e('create one now.', WP_INVOICE_TRANS_DOMAIN); ?></a></div></td></tr>
	<?php }	?>
	
	</tbody>
	</table>

	 
 
	<?php if($wpdb->query("SELECT meta_value FROM `".WP_Invoice::tablename('meta')."` WHERE meta_value = 'archived'")) { ?><a href="" id="wp_invoice_show_archived">Show / Hide Archived</a><?php }?>
	</form> 
	<div class="wp_invoice_stats">Total of Displayed Invoices: <span id="wp_invoice_total_owed"></span></div>
	<div class="wp_invoice_support">Do you like WP-Invoice?  <a href="http://wordpress.org/extend/plugins/wp-invoice/">Show your support by giving us 5 stars on WordPress!</a> | Improve WP-Invoice by <a href="http://wpinvoice.uservoice.com/pages/general">Submitting Your Ideas</a>.</div>
<?php

	// wp_invoice_options_manageInvoice();
	 if(wp_invoice_is_not_merchant()) wp_invoice_cc_setup(false);

}

/*
	Draw invoice row for overview tables
*/
	function wp_invoice_invoice_row($invoice) {
	
		$overview_link = 'admin.php?page=web-invoice_page_overview';
	
		//Basic Settings
		$invoice_id = $invoice->invoice_num;
		$subject = $invoice->subject;
		$invoice_link = wp_invoice_build_invoice_link($invoice_id);
		$user_id = $invoice->user_id;	
				
		//Determine if unique/custom id used
		$custom_id = wp_invoice_meta($invoice_id,'wp_invoice_custom_invoice_id');
		$display_id = ($custom_id ? $custom_id : $invoice_id);
		   
		// Determine Currency
		$currency_code = wp_invoice_determine_currency($invoice_id);		
		$show_money = wp_invoice_currency_symbol($currency_code) . wp_invoice_currency_format($invoice->amount);
		
		// Determine What to Call Recipient
		$profileuser = @get_user_to_edit($user_id);
		$first_name = $profileuser->first_name;
		$last_name = $profileuser->last_name;
		$user_nicename = $profileuser->user_nicename;
		if(empty($first_name) || empty($last_name)) $call_me_this = $user_nicename; else $call_me_this = $first_name . " " . $last_name;
		
		// Color coding
		if(wp_invoice_paid_status($invoice_id)) $class_settings .= " alternate ";
		if(wp_invoice_meta($invoice_id,'archive_status') == 'archived')  $class_settings .= " wp_invoice_archived ";

		//Days since sent
		
		// Days Since Sent
		if(wp_invoice_paid_status($invoice_id)) { 
		$days_since = "<span style='display:none;'>-1</span>".__(' Paid', WP_INVOICE_TRANS_DOMAIN); }
		else { 
			if(wp_invoice_meta($invoice_id,'sent_date')) {

			$date1 = wp_invoice_meta($invoice_id,'sent_date');
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
		<span class='view'><a href='$invoice_link'>View</a> | </span>";
		
		if(wp_invoice_meta($invoice_id,'archive_status') == 'archived') {
			$row_actions .= "<span class='unarchive'><a href='$overview_link&wp_invoice_action=unrachive_invoice&multiple_invoices[0]=$invoice_id' class=''>Un-Archive</a> | </span>";
		}
		
		if(wp_invoice_meta($invoice_id,'archive_status') != 'archived') {
			$row_actions .= "<span class='archive'><a href='$overview_link&wp_invoice_action=archive_invoice&multiple_invoices[0]=$invoice_id' class=''>Archive</a> | </span>";
		}
			
		$row_actions .= "<span class='delete'><a onclick='return wp_invoice_confirm_delete();' href='$overview_link&wp_invoice_action=delete_invoice&multiple_invoices[0]=$invoice_id' class='submitdelete'>Delete</a></span>";
		
		
		$row_actions .= "</div>";
		
		
		// Setup display
			
		$r = "<tr id='invoice-$invoice_id' class='{$invoice_id}_row $class_settings'>";
		$columns = get_column_headers("toplevel_page_web-invoice_page_overview");
		$hidden = get_hidden_columns("toplevel_page_web-invoice_page_overview");

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
				$r .= "<td $attributes><a class='row-title' href='$invoice_edit_link' title='Edit $subject'>$subject</a>";
				$r .= $row_actions;
				$r .= "</td>";

				break;
			case 'balance':
				$r .= "<td $attributes>$show_money</td>";
			break;			
			case 'user':
				$r .= "<td $attributes>". ($call_me_this ? "<a href='user-edit.php?user_id=$user_id'>$call_me_this" : "User Deleted") . "</a></td>";
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
				$r .= "<td $attributes><a href='$invoice_edit_link'>$display_id</a></td>";
			break;			
			
			case 'user_email':
				$r .= "<td $attributes><a href='mailto:{$profileuser->user_email}'>{$profileuser->user_email}</a></td>";
			break;			
			
			case 'wp_username':
				$r .= "<td $attributes><a href='user-edit.php?user_id=$user_id'>{$profileuser->user_login}</a></td>";
			break;			
			
			case 'company_name':
				$r .= "<td $attributes>{$profileuser->company_name}</td>";
			break;			

			
			case 'due_date':
				
				$due_date_string = strtotime(wp_invoice_meta($invoice_id,'wp_invoice_due_date_day') . "-" . wp_invoice_meta($invoice_id,'wp_invoice_due_date_month') . "-" . wp_invoice_meta($invoice_id,'wp_invoice_due_date_year'));
				if(!empty($due_date_string))
					$r .= "<td $attributes sortvalue='".date("Y-m-d", $due_date_string)."'>" . date("M d, Y", $due_date_string). "</td>";
				else 
					$r .= "<td $attributes>&nbsp;</td>";
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

function wp_invoice_recurring_overview($message='') {
	global $wpdb;
	if(!$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('log')."';")) { $warning_message = ""; }
	
	if($warning_message) echo "<div id='message' class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";
	
	$all_invoices = $wpdb->get_results("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num != ''");

?>	
	<form id="invoices-filter" action="" method="post" >
	<input type="hidden" name="wp_invoice_recurring_billing" value="true" >
	<h2><?php _e('Recurring Billing Overview', WP_INVOICE_TRANS_DOMAIN); ?></h2>
	
	<?php if(wp_invoice_is_not_merchant() && (get_option('wp_invoice_moneybookers_merchant') == 'False')) { ?>
	<div class="wp_invoice_rounded_box">
		<p><b>You need a credit card processing account to use recurring billing. </b> You may get an ARB (Automated Recurring Billing) account from <a href="http://twincitiestech.com/links/MerchantPlus.php">MerchantPlus</a> (800-546-1997), <a href="http://twincitiestech.com/links/MerchantExpress.php">MerchantExpress.com</a> (888-845-9457) or <a href="http://twincitiestech.com/links/MerchantWarehouse.php">MerchantWarehouse</a> (866-345-5959).</p>
		<p>	Once you have an account, enter in your username and transaction key into the <a href="admin.php?page=invoice_settings">settings page</a>.</p>
	</div>	
	<?php } ?>
	
	<div class="tablenav clearfix">
	
	<div class="alignleft">
	<select id="wp_invoice_action" name="wp_invoice_action">
		<option value="-1" selected="selected"><?php _e('-- Actions --', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="send_invoice" name="sendit" ><?php _e('Send Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="archive_invoice" name="archive" ><?php _e('Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="unrachive_invoice" name="unarchive" ><?php _e('Un-Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="stop_wp_invoice_recurring_billing" name="mark_as_sent" ><?php _e('Stop Recurring Billing', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option  value="delete_invoice" onClick="if(confirm('<?php _e('If you delete a recurring invoice, the subscription will be cancelled.', WP_INVOICE_TRANS_DOMAIN); ?>')) {return true;} return false;"><?php _e('Delete', WP_INVOICE_TRANS_DOMAIN); ?></option>
	</select>
	<input type="submit" value="<?php _e('Apply', WP_INVOICE_TRANS_DOMAIN); ?>" class="button-secondary action" />
	</div>

	<div class="alignright">
		<ul class="subsubsub" style="margin:0;">
		<li><?php _e('Filter: ', WP_INVOICE_TRANS_DOMAIN); ?><input type="text" id="FilterTextBox" class="search-input" name="FilterTextBox" /> </li>
		</ul>
	</div>
	</div>
	<br class="clear" />


	<table class="widefat fixed" cellspacing="0"  id="invoice_sorter_table">
	<thead>
	<tr class="thead">
	<?php print_column_headers("toplevel_page_web-invoice_page_overview") ?>
	</tr>
	</thead>

	<tfoot>
	<tr class="thead">
	<?php print_column_headers("toplevel_page_web-invoice_page_overview", false) ?>
	</tr>
	</tfoot>

	<tbody id="invoices" class="list:invoices invoice-list">
	<?php
	$style = '';
	$x_counter = 0;
	foreach ($all_invoices as $invoice) {	
	
		if(!wp_invoice_meta($invoice->invoice_num,'wp_invoice_recurring_billing'))
			continue;

		$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
		echo "\n\t" . wp_invoice_invoice_row($invoice);
		$x_counter++;
	}
	
	if($x_counter == 0) { ?>
	<tr><td colspan="00" align="center"><div style="padding: 20px;"><?php _e('You have not created any recurring invoices yet, ', WP_INVOICE_TRANS_DOMAIN); ?><a href="admin.php?page=new_invoice"><?php _e('create one now.', WP_INVOICE_TRANS_DOMAIN); ?></a></div></td></tr>
	<?php }	?>
	
	</tbody>
	</table>
	
		<?php if($wpdb->query("SELECT meta_value FROM `".WP_Invoice::tablename('meta')."` WHERE meta_value = 'archived'")) { ?><a href="" id="wp_invoice_show_archived">Show / Hide Archived</a><?php }?>
		</form> 
		<?php
	// wp_invoice_options_manageInvoice();
	 if(wp_invoice_is_not_merchant()) wp_invoice_cc_setup(false);
}

function wp_invoice_saved_preview($invoice_id) { 
	


	global $wp_invoice_email_variables;
	
	$invoice_class = new load_wp_invoice();
	$invoice_class->load_existing($invoice_id);

	$wp_invoice_email_variables = wp_invoice_email_variables($invoice_id);

?>
<div class="metabox-holder">
	<h2><?php _e('Save and Preview', WP_INVOICE_TRANS_DOMAIN); ?></h2>
	<p><?php _e('This is what your invoice will appear like in the email message. The recipient will see the itemized list after following their link to your website.', WP_INVOICE_TRANS_DOMAIN); ?> <a href="<?php echo wp_invoice_build_invoice_link($invoice_id); ?>">	<?php _e('View Web Invoice.', WP_INVOICE_TRANS_DOMAIN);?></a></p>

	<form method="post" action="admin.php?page=web-invoice_page_overview">
	<input type="hidden" value="<?php echo $invoice_id; ?>" name="invoice_id" >

		
	<div id="submitdiv" class="postbox" style="">	
	<h3 class="hndle"><span><?php _e('Notification Message', WP_INVOICE_TRANS_DOMAIN); ?></span></h3>
	<div class="inside">
	<div id="minor-publishing">

	<div id="misc-publishing-actions">
	<table class="form-table">

		
		<tr class="invoice_main">
			<th><?php _e('Subject:', WP_INVOICE_TRANS_DOMAIN); ?></th>
			<td style="font-size: 1.1em; padding-top:7px;">
			<?php echo preg_replace_callback('/(%([a-z_]+)%)/', 'wp_invoice_email_apply_variables', get_option('wp_invoice_email_send_invoice_subject')); ?>
			</td>
		</tr>
		
		<tr class="invoice_main">
			<th><?php _e('Email To:', WP_INVOICE_TRANS_DOMAIN); ?></th>
			<td style="font-size: 1.1em; padding-top:7px;"><?php echo $invoice_class->user_email; ?></td>
		</tr>

		<tr class="invoice_main">
			<th><?php _e('Email Message:', WP_INVOICE_TRANS_DOMAIN); ?></th>
			<td style="font-size: 1.1em; padding-top:7px;">
			<?php echo wp_invoice_draw_textarea("wp_invoice_email_message_content", wp_invoice_show_email($invoice_id)); ?>
			<?php echo wp_invoice_draw_textarea("wp_invoice_email_message_content_original", wp_invoice_show_email($invoice_id, true), ' style="display:none; "'); ?>
			
			
			<span class="wp_invoice_click_me" onclick="wp_invoice_restore_original()"><?php _e('Reset Email Based on Template', WP_INVOICE_TRANS_DOMAIN); ?></span>
			</td>
		</tr>

		
	</table>
	</div>
	<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">


	<div id="publishing-action">
		<input type="submit" value="<?php _e('Continue Editing', WP_INVOICE_TRANS_DOMAIN); ?>" name="wp_invoice_action" class="button-secondary" />
		<input type="submit" value="<?php _e('Save for Later', WP_INVOICE_TRANS_DOMAIN); ?>" name="wp_invoice_action" class="button-secondary" />
		
		<?php if(!$invoice_class->user_deleted): ?>
		<input type="submit" value="<?php _e('Email to Client', WP_INVOICE_TRANS_DOMAIN); ?>"  name="wp_invoice_action" class="button-primary" />
		<?php else: ?>
		<input type="button" onclick="alert('Invoice cannot be sent because the user has been deleted. \nThis invoice may only be used as a template.');" value="<?php _e('Email to Client', WP_INVOICE_TRANS_DOMAIN); ?>"  name="wp_invoice_action" class="button-primary" />
		<?php endif; ?>
	</div>
	<div class="clear"></div>
	</div>


	</div>
	</div>
	</form>
	<?php _e('Do not use the back button or you could have duplicates.', WP_INVOICE_TRANS_DOMAIN); ?>
</div>
<?php

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
	

	$user_id 					= (!empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : false);
	$copy_from_template 		= (!empty($_REQUEST['copy_from_template']) ? $_REQUEST['copy_from_template'] : false);
	$invoice_id 				= (!empty($_REQUEST['invoice_id']) ? $_REQUEST['invoice_id'] : false);
	$wp_invoice_invoice_link 	= get_permalink(get_option('wp_invoice_web_invoice_page'));
	$warning_message 			.= wp_invoice_detect_config_erors();
	
	
 
	// New Invoice From Template
	if($copy_from_template && $user_id) {
		$invoice_class = new load_wp_invoice();
		$invoice_class->create_from_template($_POST['copy_from_template'], $_POST['user_id']);	
 	}
	
	// Invoice Exists, we are modifying it, and it is NOT a template copy
	if($invoice_id && !$copy_from_template) {
		$invoice_class = new load_wp_invoice();
		$invoice_class->load_existing($invoice_id);
	}

	// Brand New Invoice
	if(!$invoice_id && $user_id && !$copy_from_template) {
		$invoice_class = new load_wp_invoice();
		$invoice_class->create_new($_REQUEST['user_id']);
	}
	
	//Whether recurring bill will start when client pays, or a date is specified
	if($invoice_class->wp_invoice_subscription_start_month && $invoice_class->wp_invoice_subscription_start_year && $invoice_class->wp_invoice_subscription_start_day) 
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
 
 
	// Create new invoice_id if one not set
	if(empty($invoice_id)) 
		$invoice_id = rand(10000000, 90000000);
		
		
	// Begin UI
	?>
	
	
	<h2><?php _e('Manage Invoice', WP_INVOICE_TRANS_DOMAIN); ?></h2>

	<?php if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>"; ?>
	<?php if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>"; ?>
	
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function(jQuery) {
			jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			postboxes.add_postbox_toggles('web-invoice_page_new_invoice');
		});
		//]]>
	</script>
	
	<form id='new_invoice_form' action="admin.php?page=new_invoice&wp_invoice_action=save_and_preview" method='POST'>

	<input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
	<input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
	<input type="hidden" name="amount" id="total_amount" value="<?php echo $amount; ?>" />

	<?php
	wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); 
 	
	?>
		
	<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
		
			<div id="side-info-column" class="inner-sidebar">
				<?php $side_meta_boxes = do_meta_boxes('web-invoice_page_new_invoice', 'side', $invoice_class); ?>
			</div>
			
			
			<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
				<div id="post-body-content">
					<?php do_meta_boxes('web-invoice_page_new_invoice', 'normal', $invoice_class); ?>
				</div>
			</div>
		</div>
	</div>
	</form>


<br class="cb" />

 
<?php
}

function wp_invoice_show_welcome_message() {

global $wpdb; ?>

<h2>WP-Invoice Setup Steps</h2>

	<ol style="list-style-type:decimal;padding-left: 20px;" id="wp_invoice_first_time_setup">
<?php 
	$wp_invoice_web_invoice_page = get_option("wp_invoice_web_invoice_page");
	$wp_invoice_paypal_address = get_option("wp_invoice_paypal_address");
	$wp_invoice_moneybookers_address = get_option("wp_invoice_moneybookers_address");
	$wp_invoice_googlecheckout_address = get_option("wp_invoice_googlecheckout_address");
	$wp_invoice_gateway_username = get_option("wp_invoice_gateway_username");
	$wp_invoice_payment_method = get_option("wp_invoice_payment_method");

?>
	<form action="admin.php?page=new_invoice" method='POST'>
	<input type="hidden" name="wp_invoice_action" value="first_setup">
<?php if(empty($wp_invoice_web_invoice_page) ) { ?>
	<li><a class="wp_invoice_tooltip"  title="Your clients will have to follow their secure link to this page to see their invoice. Opening this page without following a link will result in the standard page content begin shown.">Select a page to display your web invoices</a>:  
		<select name='wp_invoice_web_invoice_page'>
		<option></option>
		<?php $list_pages = $wpdb->get_results("SELECT ID, post_title, post_name, guid FROM ". $wpdb->prefix ."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title");
		foreach ($list_pages as $page)
		{ 
		echo "<option  style='padding-right: 10px;'";
		if(isset($wp_invoice_web_invoice_page) && $wp_invoice_web_invoice_page == $page->ID) echo " SELECTED ";
		echo " value=\"".$page->ID."\">". $page->post_title . "</option>\n"; 
		} ?>
		</select>
	</li>
<?php } ?>
	
<?php if(empty($wp_invoice_payment_method)) { ?>
	<li>Select how you want to accept money: 
		<select id="wp_invoice_payment_method" name="wp_invoice_payment_method">
		<option></option>
		<option value="paypal" style="padding-right: 10px;"<?php if(get_option('wp_invoice_payment_method') == 'paypal') echo 'selected="yes"';?>>PayPal</option>
		<option value="cc" style="padding-right: 10px;"<?php if(get_option('wp_invoice_payment_method') == 'cc') echo 'selected="yes"';?>>Credit Card</option>
		</select> 

		<li class="paypal_info payment_info">Your PayPal username: <input id='wp_invoice_paypal_address' name="wp_invoice_paypal_address" class="search-input input_field"  type="text" value="<?php echo stripslashes(get_option('wp_invoice_paypal_address')); ?>"></li>
		
		<li class="gateway_info payment_info">
		<a class="wp_invoice_tooltip"  title="Your credit card processor will provide you with a gateway username.">Gateway Username</a>
		<input AUTOCOMPLETE="off" name="wp_invoice_gateway_username" class="input_field search-input" type="text" value="<?php echo stripslashes(get_option('wp_invoice_gateway_username')); ?>">
		</li>
				
		<li class="gateway_info payment_info">
		<a class="wp_invoice_tooltip"  title="You will be able to generate this in our credit card processor's control panel.">Gateway Transaction Key</a>
		<input AUTOCOMPLETE="off" name="wp_invoice_gateway_tran_key" class="input_field search-input" type="text" value="<?php echo stripslashes(get_option('wp_invoice_gateway_tran_key')); ?>">
		</li>

		<li class="gateway_info payment_info">
		Gateway URL	
		<input name="wp_invoice_gateway_url" class="input_field search-input" type="text" value="<?php echo stripslashes(get_option('wp_invoice_gateway_url')); ?>">
		</li>

<?php } ?>

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
	</ol>
	
	<input type='submit' class='button' value='Save Settings and Create Invoice'>
	</form>
	<?php  if(wp_invoice_is_not_merchant()) wp_invoice_cc_setup(false); ?>
	
<?php
}

function wp_invoice_show_settings()
{
global $wpdb;
	

if(isset($_POST['wp_invoice_billing_meta'])) {
	$wp_invoice_billing_meta = explode('
	',$_POST['wp_invoice_billing_meta']);
	$wp_invoice_billing_meta = wp_invoice_fix_billing_meta_array($wp_invoice_billing_meta);
	update_option('wp_invoice_billing_meta', urlencode(serialize($wp_invoice_billing_meta)));
}

if(get_option('wp_invoice_billing_meta') != '') $wp_invoice_billing_meta = unserialize(urldecode(get_option('wp_invoice_billing_meta')));



if(!$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('meta')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('log')."';")) { $warning_message = "The plugin database tables are gone, deactivate and reactivate plugin to re-create them."; }if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>";




?>

<div class="wrap">
<form method='POST'>
<h2><?php _e("WP-Invoice Global Settings", WP_INVOICE_TRANS_DOMAIN) ?></h2>


<div id="wp_invoice_settings_page" class="wp_invoice_tabbed_content"> 
  <ul class="wp_invoice_settings_tabs"> 
    <li><a class="selected" href="#tab1"><?php _e("Basic Settings") ?></a></li> 
    <li><a href="#tab2"><?php _e("Display Settings") ?></a></li> 
    <li><a href="#tab3"><?php _e("Payment Settings") ?></a></li> 
    <li><a href="#tab4"><?php _e("E-Mail Templates") ?></a></li> 
    <li><a href="#tab5"><?php _e("Invoice Lookup") ?></a></li> 
    <li><a href="#tab6"><?php _e("Troubleshooting") ?></a></li> 
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
			<th width="200"><a class="wp_invoice_tooltip"  title=""><?php _e('Tax Label:', WP_INVOICE_TRANS_DOMAIN); ?></a></th><td>
			<?php echo wp_invoice_draw_inputfield('wp_invoice_custom_label_tax', get_option('wp_invoice_custom_label_tax')); ?>
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
				<?php 
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
				require_once("ui/payment_processing.php"); ?>
				</td>
			</tr>
		</table>
	</div>
	
	<div id="tab4"  class="wp_invoice_email_templates wp_invoice_tab">


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
	
	<div id="tab5"  class="wp_invoice_tab">
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
	
	<div id="tab6"  class="wp_invoice_tab">
		<table class="form-table" >
		<tr>
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
			<td colspan="2"><a id="delete_all_wp_invoice_databases" href="admin.php?page=new_invoice&wp_invoice_action=complete_removal">Remove All WP-Invoice Databases</a> - Only do this if you want to completely remove the plugin.  All invoices and logs will be gone... forever.</td>
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
<?php
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
	
	if(!$force_original && wp_invoice_meta($invoice_id, 'wp_invoice_email_message_content') != "") return str_replace("<br />", "\n",wp_invoice_meta($invoice_id, 'wp_invoice_email_message_content'));
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
		<td align=\"right\">Invoice Total:</td>
		<td  colspan=\"2\" style=\"text-align: right;\" class=\"grand_total\">";

		$response .= wp_invoice_currency_symbol($currency_code) . wp_invoice_currency_format($amount);
		$response .= "</td></table>";

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
	
	
	if(isset($_POST['first_name'])) update_usermeta($invoice->user_class->user_id, 'first_name', $_POST['first_name']);
	if(isset($_POST['last_name'])) update_usermeta($invoice->user_class->user_id, 'last_name', $_POST['last_name']);

	if(get_option('wp_invoice_send_thank_you_email') == 'yes') wp_invoice_send_email_receipt($invoice_id);
	
	wp_invoice_paid($invoice_id);
	wp_invoice_update_log($invoice_id,'paid',"PayPal Reciept: (" . $_REQUEST['receipt_id']. ")");
	if(isset($_REQUEST['payer_email'])) wp_invoice_update_log($invoice_id,'paid',"PayPal payee user email: (" . $_REQUEST['payer_email']. ")");

	
	return '<div id="invoice_page" class="clearfix">
	<div id="invoice_overview" class="cleafix">
	<h2 class="invoice_page_subheading">'.$invoice->user_class->callsign. ', thank you for your payment!</h2>
	<p><strong>Invoice ' . $invoice->display_id . ' has been paid.</strong></p>
	</div>
	</div>';
}

function wp_invoice_show_already_paid($invoice_id) {
 	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;

	return "<p>Thank you, this invoice was paid on {$invoice->paid_date}</p>";
}

function wp_invoice_show_invoice_overview($invoice_id) {
//$invoice = new WP_Invoice_GetInfo($invoice_id);
$invoice_class = new wp_invoice_get($invoice_id);
$invoice = $invoice_class->data;
?>
<div id="invoice_overview" class="clearfix">
	<h2 id="wp_invoice_welcome_message" class="invoice_page_subheading">Welcome, <?php echo $invoice->user_class->callsign; ?>!</h2>
	<p class="wp_invoice_main_description">We have sent you invoice <b><?php echo $invoice->display_id; ?></b> with a total amount of <?php echo $invoice->display_amount; ?>.</p>
	<?php if($invoice->due_date) { ?> <p class="wp_invoice_due_date">Due Date: <?php echo $invoice->due_date; } ?>	
	<?php if($invoice->description) { ?><p><?php echo $invoice->description;  ?></p><?php  } ?>
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
//$invoice = new WP_Invoice_GetInfo($invoice_id);
$invoice_class = new wp_invoice_get($invoice_id);
$invoice = $invoice_class->data;

?>
<pre>
<?php // print_r($invoice); ?>
</pre>
<div id="billing_overview" class="clearfix">
<h2 class="invoice_page_subheading">Billing Information</h2>

<?php
// count how many payment options we have availble

// Create payment array

$payment_array = wp_invoice_accepted_payment($invoice_id);


//show dropdown if it is allowed, and there is more than one payment option
if($invoice->client_change_payment_method == 'yes' && count($payment_array) > 1) { ?>

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

function wp_invoice_show_recurring_info($invoice_id) {
	//$invoice = new WP_Invoice_GetInfo($invoice_id);
	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;
?>
<div id="recurring_info" class="clearfix">
	<?php if($invoice->display('due_date')) { ?> <p class="wp_invoice_due_date">Due Date: <?php echo $invoice->due_date; } ?>	
	<h2 id="wp_invoice_welcome_message" class="invoice_page_subheading">Welcome, <?php echo $invoice->user_class->callsign; ?>!</h2>
	<?php if($invoice->display('description')) { ?><p><?php echo $invoice->description;  ?></p><?php  } ?>
	
	<p class="recurring_info_breakdown">This is a recurring bill, id: <b><?php echo $invoice->display_id; ?></b>.</p>
	<p>You will be billed <?php echo $invoice->display_billing_rate; ?> in the amount of <?php echo $invoice->display_amount; 
	
	// Determine if startning now or t a set date
	if ($invoice->subscription_start_day != '' && $invoice->subscription_start_month !='' && $invoice->subscription_start_year  != '')
		echo $invoice->subscription_start_day .", ". $invoice->subscription_start_month .", ".  $invoice->subscription_start_year;
	?>.</p>

	<?php echo wp_invoice_draw_itemized_table($invoice_id); ?> 
	
</div>
<?php
}


function wp_invoice_draw_user_selection_form($user_id = false) {
	global $wpdb; ?>


	<form action="admin.php?page=new_invoice" method='POST'>
		<table class="form-table" id="get_user_info">
			<tr class="invoice_main">
				<th><?php if(isset($user_id)) { ?>Start New Invoice For: <?php } else { ?>Create New Invoice For:<?php } ?></th>
				<td> 

					<select name='user_id' class='user_selection'>
					<option></option>
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
					<option value="create_new_user">-- Create New User --</option>
					</select>
					<input type='submit' class='button' id="wp_invoice_create_new_invoice" value='Create New Invoice'> 
					
					
					<?php if(wp_invoice_number_of_invoices() > 0) { ?><span id="wp_invoice_copy_invoice" class="wp_invoice_click_me">copy from another</span>
					<br />


			<div class="wp_invoice_copy_invoice">
			<?php 	$all_invoices = $wpdb->get_results("SELECT * FROM ".WP_Invoice::tablename('main')); ?>
			<select name="copy_from_template">
<option SELECTED value=""></option>
		<?php 	foreach ($all_invoices as $invoice) { 
		$profileuser = @get_user_to_edit($invoice->user_id);
		?>
		
		<option value="<?php echo $invoice->invoice_num; ?>"><?php if(wp_invoice_recurring($invoice->invoice_num)) {?>(recurring)<?php } ?> <?php echo $invoice->subject . " - $" .$invoice->amount; ?> </option>
		
		<?php } ?>
		
		</select><input type='submit' class='button' value='New Invoice from Template'> <span id="wp_invoice_copy_invoice_cancel" class="wp_invoice_click_me">cancel</span>
			</div>
<?php } ?>	
					
				</td>
			</tr>
			
		</table>
	</form>
 


<?php
}
?>