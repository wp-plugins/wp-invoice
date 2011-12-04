<div id="invoice_page" class="wpi_invoice_form wpi_payment_form clearfix">
  <div class="wpi_left_col">
    <b class="wpi_greeting">Payment Receipt</b>

    <div class="invoice_description">
      <div class="invoice_top_message">

        <?php if(is_invoice()) : ?>
          <p>We have sent you invoice <?php invoice_id(); ?>. <?php paid_amount(); ?> was paid.</p>
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
    <?php show_invoice_history(); ?>
    <?php do_action('wpi_front_end_right_col_bottom'); ?>
  </div>

</div>