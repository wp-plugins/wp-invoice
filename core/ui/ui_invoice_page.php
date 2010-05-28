<div class="wrap">
<h2><?php _e('Manage Invoice', WP_INVOICE_TRANS_DOMAIN); ?></h2>

	<?php // wpi_qc($invoice_class); ?>
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
</div>