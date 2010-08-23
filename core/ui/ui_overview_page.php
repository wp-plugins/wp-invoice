<div class="wrap">
<?php

	// The error takes precedence over others being that nothing can be done w/o tables
	if(!$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('log')."';")) { $warning_message = ""; }
	
 
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
	$archived_count = 0;
	foreach ($all_invoices as $invoice) {	

		//$invoice = new WP_Invoice_GetInfo($invoice_id);
		$invoice_class = new wp_invoice_get($invoice->invoice_num);
		$invoice = $invoice_class->data;
		
		if($invoice->is_recurring)
			continue;		
			
		if($invoice->is_archived)
			$archived_count++;
			
		$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
		echo "\n\t" . wp_invoice_invoice_row($invoice);
		$x_counter++;
	}
	
	if($x_counter == 0) { ?>
	<tr><td colspan="00" align="center"><div style="padding: 20px;"><?php _e('You have not created any invoices yet, ', WP_INVOICE_TRANS_DOMAIN); ?><a href="admin.php?page=new_invoice"><?php _e('create one now.', WP_INVOICE_TRANS_DOMAIN); ?></a></div></td></tr>
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