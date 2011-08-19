<div id="invoice_page" class="wpi_invoice_form wpi_payment_form clearfix">
    <div class="wpi_left_col">
        <b class='wpi_greeting'>Welcome, <?php recipients_name(); ?>!</b>
 
        <div class="invoice_description">
          <div class="invoice_top_message">
            <?php if(is_quote()) : ?>
                  <p>We have sent you a quote in the amount of <?php balance_due(); ?>.</p>
            <?php endif; ?>

            <?php if(is_invoice()) : ?>
                  <p>We have sent you invoice <?php invoice_id(); ?> with a balance of <?php balance_due(); ?>.</p>
            <?php endif; ?>

            <?php if(is_recurring()): ?>
                    <p>This is a recurring bill.</p>
            <?php endif; ?>
            
            </div>
            <div class="invoice_description_custom">
            <?php the_description(); ?>
            </div>

            <?php if(is_payment_made()): ?>
                You've made payments, but still owe: <?php balance_due(); ?>
            <?php endif; ?>
        </div>
    
      <div class="wpi_itemized_table">
          <?php show_itemized_table(); ?>
      </div>
      
    <?php do_action('wpi_front_end_left_col_bottom'); ?>
    </div>

    <div class="wpi_right_col">      
  
    <?php if(allow_partial_payments()): ?>
      <div class="partial_payments">
      <p class='wpi_text_partial_payments'><?php _e('This invoice allows partial payments, please select the amount you would like to pay.'); ?></p>
      <?php show_partial_payments(); ?>
      </div>
    <?php endif; ?>
    

   <?php if ( show_business_info() ) { ?>       
    <?php wp_invoice_show_business_information(); ?>   
   <?php } ?>
   
   <?php if(!is_quote()) { ?>
    <div class="wpi_billing_overview">
        <?php show_payment_selection("Select your favorite way of paying"); ?>
        <?php
          $method = !empty($invoice['default_payment_method'])?$invoice['default_payment_method']:'wpi_paypal';
          $wpi_settings['installed_gateways'][$method]['object']->frontend_display($invoice);
        ?>
    </div>
  <?php } ?>
 
<?php do_action('wpi_front_end_right_col_bottom'); ?>
</div>

</div>