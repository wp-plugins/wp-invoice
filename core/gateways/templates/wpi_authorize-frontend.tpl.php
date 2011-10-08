<form method="post" name="online_payment_form" id="online_payment_form-<?php print $this->type; ?>" class="online_payment_form <?php print $this->type; ?> clearfix">
  <input type="hidden" id="wpi_action" name="wpi_action" value="wpi_gateway_process_payment" />
  <input type="hidden" id="wpi_form_type" name="type" value="<?php print $this->type; ?>" />
  <input type="hidden" id="wpi_form_invoice_id" name="invoice_id" value="<?php print $invoice['invoice_id']; ?>" />
  <input type="hidden" name="wp_invoice[hash]" value="<?php echo wp_create_nonce($invoice['invoice_id'] .'hash');; ?>" />
  <input type="hidden" id="payment_amount" name="cc_data[amount]" value="<?php echo $invoice['net']; ?>" />
  <input type="hidden" name="cc_data[user_id]" value="<?php echo $invoice['user_data']['user_email']; ?>" />
  <input type="hidden" name="cc_data[invoice_id]" value="<?php echo  $invoice['invoice_id']; ?>" />
  <input type="hidden" name="cc_data[currency_code]" id="currency_code"  value="<?php echo $invoice['default_currency_code']; ?>" />

<fieldset id="credit_card_information">
  <ol>
    <li>
    <label for="first_name"><?php _e('First Name', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input type="text" class="text-input"  name="cc_data[first_name]" value="<?php echo !empty($invoice['user_data']['first_name'])?$invoice['user_data']['first_name']:'';?>" />
    </li>

    <li>
    <label for="last_name"><?php _e('Last Name', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input type="text" class="text-input"  name="cc_data[last_name]" value="<?php echo !empty($invoice['user_data']['last_name'])?$invoice['user_data']['last_name']:'';?>" />
    </li>

    <li>
    <label for="email"><?php _e('Email Address', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input type="text" class="text-input"  name="cc_data[email_address]" value="<?php echo !empty($invoice['user_data']['user_email'])?$invoice['user_data']['user_email']:'';?>" />
    </li>

    <li>
    <label class="inputLabel" for="phonenumber"><?php _e('Phone Number', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input type="text" class="text-input"  name="cc_data[phonenumber]" class="input_field"  type="text" id="phonenumber" maxlength="50" value="<?php echo !empty($invoice['user_data']['phonenumber'])?$invoice['user_data']['phonenumber']:''; ?>" />
    </li>

    <li>
    <label for="address"><?php _e('Address', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input type="text" class="text-input" name="cc_data[address]" value="<?php echo (isset($invoice['user_data']['streetaddress']) ? $invoice['user_data']['streetaddress'] : "");?>" />
    </li>

    <li>
    <label for="city"><?php _e('City', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input type="text" class="text-input"  name="cc_data[city]" value="<?php echo (isset($invoice['user_data']['city']) ? $invoice['user_data']['city'] : "");?>" />
    </li>

    <?php 
//WPI_Functions::qc( $invoice );
    switch ( strtolower( $wpi_settings['state_selection'] ) ) {
      case 'hide':
      break;
      
      case 'dropdown':
        echo '<li id="state_field"><label for="state">State</label>';        
        echo WPI_UI::select("name=cc_data[state]&current_value=". (isset($invoice['user_data']['state']) ? $invoice['user_data']['state'] : "") ."&values=us_states");
        echo '</li>';
      break;
    
      case 'input_field':
        echo '<li id="state_field"><label for="state">State</label>';        
        echo "<input type='text' class='text-input'  name='cc_data[state]' value='". (isset($invoice['user_data']['state']) ? $invoice['user_data']['state'] : "") ."' />";
        echo '</li>';
      break;
    
    }
    
    ?>

    <li>
    <label for="zip"><?php _e('Zip Code', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input type="text" class="text-input"  id="zip" name="cc_data[zip]" value="<?php echo !empty($invoice['user_data']['zip'])?$invoice['user_data']['zip']:'';?>" />
    </li>

    <li>
    <label for="country"><?php _e('Country', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <?php echo WPI_UI::select("name=cc_data[country]&current_value=". (!empty($invoice['user_data']['country']) ? $invoice['user_data']['country'] : "") ."&values=countries"); ?>
    </li>

    <li class="hide_after_success">
      <label class="inputLabel" for="cc_data[card_num]"><?php _e('Credit Card Number', WP_INVOICE_TRANS_DOMAIN); ?></label>
      <input type="text" class="text-input"  name="cc_data[card_num]" autocomplete="off" id="card_num" class="credit_card_number input_field"  type="text"  size="22"  maxlength="22" />
    </li>    

    <li class="hide_after_success">
      <label><?php _e('Expiration Date', WP_INVOICE_TRANS_DOMAIN); ?></label>
      
      <ul class="wpi_inline_fields">
        <li>
            <?php echo WPI_UI::select("name=cc_data[exp_month]&values=months"); ?>
        </li>
        <li>
          <?php echo WPI_UI::select("name=cc_data[exp_year]&values=years"); ?>
        </li>
      </ul>
    </li>

    <li class="hide_after_success">
    <label type="text" class="inputLabel" for="card_code"><?php _e('Security Code', WP_INVOICE_TRANS_DOMAIN); ?></label>
    <input id="card_code" autocomplete="off"  name="cc_data[card_code]" class="input_field" style="width: 70px;" type="text" size="4" maxlength="4" />
    </li>
    
    <li id="wp_invoice_process_wait">
    <label for="submit"><span></span>&nbsp;</label>
    <button type="submit" id="cc_pay_button" class="hide_after_success submit_button">Process Payment of <?php echo (!empty($wpi_settings['currency']['symbol'][$invoice['default_currency_code']]) ? $wpi_settings['currency']['symbol'][$invoice['default_currency_code']] : "$"); ?><span id="pay_button_value"><?php echo WPI_Functions::money_format($invoice['net']); ?></span></button>
    <img style="display: none;" class="loader-img" src="<?php echo WPI_URL; ?>/core/css/images/processing-ajax.gif" alt="" />
    </li>
    
    <br class="cb" />  
  </ol>
</fieldset>