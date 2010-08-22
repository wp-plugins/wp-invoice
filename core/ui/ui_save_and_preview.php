<div class="wrap">

<div class="metabox-holder">
	<h2><?php _e('Save and Preview', WP_INVOICE_TRANS_DOMAIN); ?></h2>
	<p><?php _e('This is what your invoice will appear like in the email message. The recipient will see the itemized list after following their link to your website.', WP_INVOICE_TRANS_DOMAIN); ?> <a href="<?php echo wp_invoice_build_invoice_link($invoice_id); ?>">	<?php _e('View Web Invoice.', WP_INVOICE_TRANS_DOMAIN);?></a></p>

<?php /// wpi_qc($invoice_class); ?>
<?php if($invoice_class->is_recurring): ?>
	<form method="post" action="admin.php?page=recurring_billing">
<?php endif; ?>

<?php if(!$invoice_class->is_recurring): ?>
	<form method="post" action="admin.php?page=web-invoice_page_overview">
<?php endif; ?>

	<input type="hidden" value="<?php echo $invoice_id; ?>" name="invoice_id" >
	<input type="hidden" value="post_save_and_preview" name="action" >

		
	<div id="submitdiv" class="postbox" style="">	
	<h3 class="hndle"><span><?php _e('Notification Message', WP_INVOICE_TRANS_DOMAIN); ?></span></h3>
	<div class="inside">
	<div id="minor-publishing">

	<div id="misc-publishing-actions">
	<table class="form-table">

		
		<tr class="invoice_main">
			<th><?php _e('Subject:', WP_INVOICE_TRANS_DOMAIN); ?></th>
			<td style="padding-top:7px;">
			<input class="subject" name="wp_invoice_email_message_subject" style="width: 400px;" value="<?php echo preg_replace_callback('/(%([a-z_]+)%)/', 'wp_invoice_email_apply_variables', (!empty($invoice_class->email_message_subject) ? $invoice_class->email_message_subject : get_option('wp_invoice_email_send_invoice_subject'))); ?>" />
			</td>
		</tr>
		
		<tr class="invoice_main">
			<th><?php _e('Email To:', WP_INVOICE_TRANS_DOMAIN); ?></th>
			<td style="font-size: 1.1em; padding-top:7px;"><?php echo $invoice_class->user_class->user_email; ?></td>
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
</div>