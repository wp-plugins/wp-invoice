<div class="wrap">
<h2><?php _e('Manage Invoice', WP_INVOICE_TRANS_DOMAIN); ?></h2>
<div id="wpi_js_conflict_check" class="error">
	<p>If you can see this message, there is a JavaScript conflict with one of the other plugins.  Please disable your plugins one-by-one to determine which plugin is causing the issue.  Once you find the problem, please contact TwinCitiesTech.com and we will add code to remove the given plugin's code from WP-Invoice pages. Thank you.</p>
</div>

	<?php // wpi_qc($invoice_class); ?>
	<?php if($warning_message) echo "<div id=\"message\" class='error' ><p>$warning_message</p></div>"; ?>
	<?php if($message) echo "<div id=\"message\" class='updated fade' ><p>$message</p></div>"; ?>
	
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function(jQuery) {
			jQuery('.if-js-closed').removeClass('if-js-closed').addClass('closed');
			postboxes.add_postbox_toggles('invoices_page_new_invoice');
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
				<?php $side_meta_boxes = do_meta_boxes('invoices_page_new_invoice', 'side', $invoice_class); ?>
			</div>
			
			
			<div id="post-body" class="<?php echo $side_meta_boxes ? 'has-sidebar' : 'has-sidebar'; ?>">
				<div id="post-body-content">
				
<div id="titlediv">
<div id="titlewrap">
	<label for="title" id="title-prompt-text" style="visibility: hidden;" class="hide-if-no-js">Enter title here</label>
	<input type="text" autocomplete="off" id="title" value="<?php echo $invoice_class->subject; ?>" tabindex="1" size="30" name="subject">
</div>
<?php 
 // Only show if saved
if(!empty($invoice_class->invoice_id)): ?>
	
<div class="inside">
	<div id="edit-slug-box">
	<strong>Invoice Link:</strong>
	<span id="sample-permalink"><div style="display:inline-block;"><?php echo wp_invoice_build_invoice_link($invoice_class->invoice_id); ?></div></span>
	<span id="view-post-btn"><a target="_blank" class="button" href="<?php echo wp_invoice_build_invoice_link($invoice_class->invoice_id); ?>">View</a></span>
	</div>
</div>
<?php endif; ?>
</div>
			
	 
	
					<?php do_meta_boxes('invoices_page_new_invoice', 'normal', $invoice_class); ?>
				</div>
			</div>
		</div>
	</div>
	</form>


<br class="cb" />
</div>