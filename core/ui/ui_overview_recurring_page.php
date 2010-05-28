<div class="wrap">
<?php

	if(!$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('log')."';")) { $warning_message = ""; }
	
	if($warning_message) echo "<div id='message' class='error' ><p>$warning_message</p></div>";
	if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>";
	
	$all_invoices = $wpdb->get_results("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num != ''");

?>	
	<form id="invoices-filter" action="" method="post" >
	<input type="hidden" name="wp_invoice_recurring_billing" value="true" >
	<h2><?php _e('Recurring Billing', WP_INVOICE_TRANS_DOMAIN); ?>
	<a class="button add-new-h2" href="<?php echo admin_url("admin.php?page=new_invoice"); ?>">Add New</a>
	</h2>	
	
	<div class="wp_invoice_error_wrapper">
	<?php if(count($errors) > 0): ?>
	<div class="error"><p>
		<?php foreach($errors as $error): ?>
			<?php echo $error; ?><br />
		<?php endforeach; ?>
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

	
	<div class="tablenav clearfix">
	
	<div class="alignleft">
	<select id="wp_invoice_action" name="wp_invoice_action">
		<option value="-1" selected="selected"><?php _e('-- Actions --', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="send_invoice" name="sendit" ><?php _e('Send Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="archive_invoice" name="archive" ><?php _e('Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="unrachive_invoice" name="unarchive" ><?php _e('Un-Archive Invoice(s)', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_paid" name="mark_as_paid" ><?php _e('Mark as Paid', WP_INVOICE_TRANS_DOMAIN); ?></option>
		<option value="mark_as_unpaid" name="mark_as_unpaid" ><?php _e('Unset Paid Status', WP_INVOICE_TRANS_DOMAIN); ?></option>
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
	<?php print_column_headers("web-invoice_page_recurring_billing") ?>
	</tr>
	</thead>

	<tfoot>
	<tr class="thead">
	<?php print_column_headers("web-invoice_page_recurring_billing", false) ?>
	</tr>
	</tfoot>

	<tbody id="invoices" class="list:invoices invoice-list">
	<?php
	$style = '';
	$x_counter = 0;
	foreach ($all_invoices as $invoice) {	
		
		//$invoice = new WP_Invoice_GetInfo($invoice_id);
		$invoice_class = new wp_invoice_get($invoice->invoice_num);
		$invoice = $invoice_class->data;
		
		
		if(!$invoice->is_recurring)
			continue;
			
		if($invoice->is_archived)
			$archived_count++;
			
		$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
		echo "\n\t" . wp_invoice_invoice_row($invoice);
		$x_counter++;
	}
	
	if($x_counter == 0) { ?>
	<tr><td colspan="00" align="center"><div style="padding: 20px;"><?php _e('You have not created any recurring invoices yet, ', WP_INVOICE_TRANS_DOMAIN); ?><a href="admin.php?page=new_invoice"><?php _e('create one now.', WP_INVOICE_TRANS_DOMAIN); ?></a></div></td></tr>
	<?php }	?>
	
	</tbody>
	</table>
	</form>
	
	<?php 
	if($archived_count++) { ?>
		<a href="" id="wp_invoice_show_archived">Show / Hide Archived</a>
	<?php }	
	wp_invoice_overview_page_footer(); ?>
	
</div>