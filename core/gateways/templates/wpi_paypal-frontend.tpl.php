<form action="<?php echo $invoice['billing']['wpi_paypal']['settings']['test_mode']['value']; ?>" method="post" name="online_payment_form" id="online_payment_form-<?php print $this->type; ?>" class="online_payment_form <?php print $this->type; ?> clearfix">
  <input type="hidden" id="wpi_action" name="wpi_action" value="wpi_gateway_process_payment" />
  <input type="hidden" id="wpi_form_type" name="type" value="<?php print $this->type; ?>" />
  <input type="hidden" id="wpi_form_invoice_id" name="invoice_id" value="<?php print $invoice['invoice_id']; ?>" />
  <input type="hidden" name="wp_invoice[hash]" value="<?php echo wp_create_nonce($invoice['invoice_id'] .'hash');; ?>" />
  <input type="hidden" name="currency_code" value="<?php echo $invoice['default_currency_code']; ?>">
  <input type="hidden" name="no_shipping" value="1">
  <input type="hidden" name="upload" value="1">
  <input type="hidden" name="cmd" value="_xclick">
  <input type="hidden" name="business" value="<?php echo $invoice['billing']['wpi_paypal']['settings']['paypal_address']['value']; ?>">
  <input type="hidden" name="return" value="<?php echo get_invoice_permalink($invoice['invoice_id']); ?>">
  <input type="hidden" name="rm" value="2">
  <input type="hidden" name="cancel_return" value="<?php echo get_invoice_permalink($invoice['invoice_id']); ?>">
  <input type="hidden" id="payment_amount" name="amount" value="<?php echo $invoice['net']; ?>">
  <input type="hidden" name="cbt" value="Go back to Merchant">
  <input type="hidden" name="item_name" value="<?php echo $invoice['post_title']; ?>"> 
  <input type="hidden" name="invoice" id="invoice_id" value="<?php echo $invoice['invoice_id']; ?>">

<fieldset id="credit_card_information">
  <ol>
    <li>
      <label for="first_name"><?php _e('First Name', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input" name="first_name" value="<?php echo !empty($invoice['user_data']['first_name'])?$invoice['user_data']['first_name']:'';?>" />
    </li>
    <li>
      <label for="last_name"><?php _e('Last Name', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input" name="last_name" value="<?php echo !empty($invoice['user_data']['last_name'])?$invoice['user_data']['last_name']:'';?>" />
    </li>
    <li>
      <label for="email"><?php _e('Email Address', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input" name="email_address" value="<?php echo !empty($invoice['user_data']['user_email'])?$invoice['user_data']['user_email']:'';?>" />
    </li>
    <?php $phonenumber = !empty($invoice['user_data']['phonenumber']) ? $invoice['user_data']['phonenumber'] : "---"; ?>
    <?php list($night_phone_a, $night_phone_b, $night_phone_c) = split('[/.-]', $phonenumber); ?>
    <li>
      <label for="night_phone_a"><?php _e('Phone Number', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input small" name="night_phone_a" value="<?php echo $night_phone_a;?>" style="width:47px;" size="4" maxlength="3" />
      <input type="text" class="text-input small" name="night_phone_b" value="<?php echo $night_phone_b;?>" style="width:47px;" size="4" maxlength="3" />
      <input type="text" class="text-input small" name="night_phone_c" value="<?php echo $night_phone_c;?>" style="width:47px;" size="4" maxlength="4" />
    </li>

    <li>
      <label for="address"><?php _e('Address', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input" name="address1" value="<?php echo !empty($invoice['user_data']['streetaddress'])?$invoice['user_data']['streetaddress']:'';?>" />
    </li>
    <li>
      <label for="city"><?php _e('City', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input" name="city" value="<?php echo !empty($invoice['user_data']['city'])?$invoice['user_data']['city']:'';?>" />
     </li>
    <?php 
    switch ( strtolower( $wpi_settings['state_selection'] ) ) {
      case 'hide':
        break;
      case 'dropdown':
        echo '<li id="state_field"><label for="state">State</label>';                
        echo WPI_UI::select("name=state&current_value=".(!empty($invoice['user_data']['state'])?$invoice['user_data']['state']:'')."&values=us_states");
        echo '</li>';
        break;
      case 'input_field':
        echo '<li id="state_field"><label for="state">State</label>';                
        echo "<input name='state' value='".(!empty($invoice['user_data']['state'])?$invoice['user_data']['state']:'')."' />";
        echo '</li>';
        break;
    }
    ?>
    <li>
      <label for="zip"><?php _e('Zip Code', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input" name="zip" value="<?php echo !empty($invoice['user_data']['zip'])?$invoice['user_data']['zip']:'';?>" />
    </li>
    <li>
      <label for="country"><?php _e('Country', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <?php echo WPI_UI::select("name=country&current_value=". (!empty($invoice['user_data']['country']) ? $invoice['user_data']['country'] : "") ."&values=countries"); ?>
    </li>
    <li>
      <label for="submit">&nbsp;</label>
      <input type="image" src="<?php echo $wpi_settings['installed_gateways']['wpi_paypal']['object']->options['settings']['button_url']['value']; ?>" class="paypal_button" name="submit" alt="Pay with PayPal">
    </li>

    <br class="cb" />    
  </ol>
</fieldset>