<script type="text/javascript">

<?php
// Create string of users to use for autocompletion script

		foreach($user_array as $key => $user) {

			$user_array_js_string .= "{name:'{$user->display_name}',email:'{$user->user_email}',ID:'{$user->ID}'}";
			$user_array_js_string .= ($key != end(array_keys($user_array)) ? ", " : "");
		}	
		
		// Display
		echo "var wp_invoice_users = [$user_array_js_string];";

?>		
jQuery(document).ready(function(){
 
 
jQuery("#wp_invoice_userlookup").autocomplete(wp_invoice_users, {
		minChars: 0,
		width: 400,
		scrollHeight: 600,
		matchContains: true,
		autoFill: false,
		formatItem: function(row, i, max) {
			return row.name +  " (" + row.email + ")";
		},
		formatMatch: function(row, i, max) {
			return row.name + " " + row.email;
		},
		formatResult: function(row) {
			return row.email;
		}
});

 jQuery("#wp_invoice_userlookup").focus();

 
});
</script>

	<form action="admin.php?page=new_invoice" method='POST'>
		<table class="form-table" id="get_user_info">
			<tr class="invoice_main">
				<th><?php if(isset($user_id)) { ?>Start New Invoice For: <?php } else { ?>Create New Invoice For:<?php } ?></th>
				<td> 

					<input name="wp_invoice_userlookup" class="input_field" id="wp_invoice_userlookup" style="width: 400px;"/>
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
 
