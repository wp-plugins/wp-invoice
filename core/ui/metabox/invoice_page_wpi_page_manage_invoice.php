<?php

/**
 * Hidden metabox information stored in DB.
 * WordPress gets this information using the get_hidden_meta_boxes function:
 * $hidden = get_hidden_meta_boxes($page); (template.php line 2905)
 * The get_hidden_meta_boxes() is in (template.php line 3007), the data is stored in
 * user options: $hidden = (array) get_user_option( "meta-box-hidden_$page", 0, false );
 * The meta-box-hidden_$page is updated in (admin-ajax.php line 997) via Ajax calls
 * $page = 'admin_page_wpi_invoice_edit'
 */
function message_meta_box($this_invoice) {
  global $wpi_settings;
  ?>
  <div id="send_notification_box" class="hidden postbox">
    <h3 class='hndle'><span><?php _e("Send Notification") ?></span></h3>
    <div class="inside">
      <div id="submitpost" class="submitbox">
        <div id="minor-publishing">
          <div id="minor-publishing-actions">
            <div id="preview-action" style="text-align: left">
              <table id="wpi_invoice_notification_table">
                <tr>
                  <th><?php _e('To:'); ?></th>
                  <td><input id="wpi_notification_send_to" class="input_field"  name="wpi_invoice_notification[email_address]" value="<?php echo $this_invoice['user_data']['user_email']; ?>" /></td>
                </tr>
                <tr>
                  <th><?php _e('Template:'); ?></th>
                  <td>
                    <select id="wpi_change_notification">
                      <option value="0"></option>
                      <?php
                      global $wpi_settings;
                      for ($i = 1; $i <= count($wpi_settings['notification']); $i++) {
                        echo '<option value=' . $i . '>' . $wpi_settings['notification'][$i]['name'] . '</option>';
                      }
                      ?>
                    </select>
                  </td>
                </tr>
                <tr>
                  <th><?php _e('Subject:'); ?></th>
                  <td><input id="wpi_notification_subject" class="input_field" name="wpi_invoice_notification[subject]" value="<?php echo!empty($this_invoice['subject']) ? $this_invoice['subject'] : ''; ?>" /></td>
                </tr>

                <tr>
                  <th><?php _e('Message:'); ?></th>
                  <td><textarea id="wpi_notification_message" name="wpi_invoice_notification[notification_message]" class="wpi_notification_message " value=""></textarea></td>
                </tr>

              </table>
            </div>
            <div class="clear"></div>
          </div>
        </div>
        <div class="major-publishing-actions clearfix">
          <div class="alignleft">
            <span class="wpi_cancel" onclick="wpi_show_notification_box();"><?php _e('Cancel'); ?></span>
          </div>
          <div id="wpi_template_loading"  style="display:none;" ></div>
          <input type="submit" class="alignright button-primary" value="Send Notification" id="wpi_send_notification">
        </div>
      </div>
    </div>
  </div>
  <?php
}

function postbox_publish($this_invoice) {

  global $wpi_settings;
  $invoice_id = $this_invoice['invoice_id'];

  $status_names = apply_filters('wpi_invoice_statuses', $wpi_settings['invoice_statuses']);
  if (!empty($this_invoice['status']))
    $status_label = ( $status_names[$this_invoice['status']] ? $status_names[$this_invoice['status']] : $this_invoice['status']);
  ?>
  <div id="submitpost" class="submitbox">
    <div id="minor-publishing">
      <ul class="wpi_publish_seetings">
        <li class="wpi_hide_until_saved"><a target="_blank" class="wpi_new_win" id="view_online" href="<?php echo get_invoice_permalink(!empty($this_invoice['invoice_id']) ? $this_invoice['invoice_id'] : '' ); ?>">View Online</a></li>
        <li class="wpi_hide_until_saved"><span onclick="wpi_show_paycharge_box();" class="wpi_link" id="wpi_button_show_paycharge_box">Enter Payment</span></li>
        <li class="wpi_hide_until_saved"><span onclick='wpi_show_notification_box();' class="wpi_link" id="wpi_button_show_notification">Send Notification</span></li>
        <?php if ($wpi_settings['allow_deposits'] == 'true') : ?>
          <li class="wpi_not_for_recurring wpi_hide_deposit_option wpi_not_for_quote">
            <?php $app_title = __("Allow Partial Payment", WP_INVOICE_TRANS_DOMAIN); ?>
            <?php echo WPI_UI::checkbox("name=wpi_invoice[deposit]&value=true&label={$app_title}", ((!empty($this_invoice['deposit_amount']) && (int) $this_invoice['deposit_amount'] > 0) ? true : false)) ?></li>
          <li class="wpi_deposit_settings">
            <table class="wpi_deposit_settings">
              <tr>
                <th><?php _e("Minimum Payment", WP_INVOICE_TRANS_DOMAIN); ?></th>
                <td><?php echo WPI_UI::input("id=wpi_meta_deposit_amount&name=wpi_invoice[deposit_amount]&value=" . (!empty($this_invoice['deposit_amount']) ? $this_invoice['deposit_amount'] : 0)); ?></td>
              </tr>
            </table>
          </li>
        <?php endif; ?>
        <?php if (!empty($wpi_settings['allow_quotes']) && $wpi_settings['allow_quotes'] == 'true') { ?>
          <li class="wpi_quote_option wpi_not_for_recurring"><?php echo WPI_UI::checkbox("name=wpi_invoice[quote]&value=true&label=Quote", ($this_invoice['status'] == 'quote' ? true : false)) ?></li>
        <?php } ?>
        <?php /* if ($wpi_settings['terms_acceptance_required'] == 'true') { ?>
          <li><?php echo WPI_UI::checkbox("name=wpi_invoice[terms_acceptance_required]&value=true&label=Require acceptance of Terms before payment.", $this_invoice['meta']['terms_acceptance_required']) ?></li>
          <?php } */ ?>
        <?php if ($wpi_settings['show_recurring_billing'] == 'true') { ?>
          <li class="wpi_turn_off_recurring"><?php echo WPI_UI::checkbox("name=wpi_invoice[recurring][active]&value=true&label=Recurring Bill", (!empty($this_invoice['recurring']) ? $this_invoice['recurring']['active'] : false)) ?></li>
          <li class="wpi_recurring_bill_settings <?php if (!empty($this_invoice['recurring']) && $this_invoice['recurring']['active'] != 'on') {
            ?>hidden<?php } ?>">
            <table class="wpi_recurring_bill_settings">
              <tr>
                <th><?php _e("Bill Every", WP_INVOICE_TRANS_DOMAIN) ?></th>
                <td>
                  <?php echo WPI_UI::input("name=wpi_invoice[recurring][length]&value=" . (!empty($this_invoice['recurring']) ? $this_invoice['recurring']['length'] : '') . "&class=wpi_small wpi_bill_every_length"); ?>
                  <?php echo WPI_UI::select("name=wpi_invoice[recurring][unit]&values=" . serialize(apply_filters('wpi_schedule_units', array("months" => __("Month(s)", WP_INVOICE_TRANS_DOMAIN), "days" => __("Day(s)", WP_INVOICE_TRANS_DOMAIN)))) . "&current_value=" . (!empty($this_invoice['recurring']) ? $this_invoice['recurring']['unit'] : '')); ?>
                </td>
              </tr>
              <tr>
                <th><?php _e("Billing Cycles", WP_INVOICE_TRANS_DOMAIN) ?></th>
                <td><?php echo WPI_UI::input("id=wpi_meta_recuring_cycles&name=wpi_invoice[recurring][cycles]&value=" . (!empty($this_invoice['recurring']) ? $this_invoice['recurring']['cycles'] : '') . "&class=wpi_small"); ?></td>
              </tr>
              <tr>
                <th>Send Invoice</th>
                <td>
                  <script type="text/javascript">var recurring_send_invoice_automatically = '<?php echo!empty($this_invoice['recurring']['send_invoice_automatically']) ? $this_invoice['recurring']['send_invoice_automatically'] : 'on'; ?>';</script>
                  <?php echo WPI_UI::checkbox("name=wpi_invoice[recurring][send_invoice_automatically]&value=true&label=Automatically.", !empty($this_invoice['recurring']['send_invoice_automatically']) ? $this_invoice['recurring']['send_invoice_automatically'] : 'on'); ?>
                </td>
              </tr>
              <tr class="wpi_recurring_start_date" style="display:<?php echo!empty($this_invoice['recurring']) && $this_invoice['recurring']['send_invoice_automatically'] == 'on' ? 'none;' : ''; ?>">
                <th>Date:</th>
                <td>
                  <div>
                    <?php echo WPI_UI::select("id=r_start_date_mm&name=wpi_invoice[recurring][start_date][month]&values=months&current_value=" . (!empty($this_invoice['recurring']) ? $this_invoice['recurring']['start_date']['month'] : '')); ?>
                    <?php echo WPI_UI::input("id=r_start_date_jj&name=wpi_invoice[recurring][start_date][day]&value=" . (!empty($this_invoice['recurring']) ? $this_invoice['recurring']['start_date']['day'] : '') . "&special=size='2' maxlength='2' autocomplete='off'") ?>
                    <?php echo WPI_UI::input("id=r_start_date_aa&name=wpi_invoice[recurring][start_date][year]&value=" . (!empty($this_invoice['recurring']) ? $this_invoice['recurring']['start_date']['year'] : '') . "&special=size='2' maxlength='4' autocomplete='off'") ?><br />
                    <span onclick="wp_invoice_add_time('r_start_date', 7);" class="wp_invoice_click_me">In One Week</span> | <span onclick="wp_invoice_add_time('r_start_date', 30);" class="wp_invoice_click_me">In 30 Days</span> | <span onclick="wp_invoice_add_time('r_start_date', 'clear');" class="wp_invoice_click_me">Clear</span>
                  </div>
                </td>
              </tr>
            </table>
          </li>
        <?php } ?>
      </ul>
      <table class="form-table">
        <?php /**
          <tr class="column-publish-invoice-date">
          <th>Invoice Date</th>
          <td><div class="timestampdiv"  style="display:block;">
          <?php echo WPI_UI::select("name=wpi_invoice[meta][invoice_date][month]&values=months&current_value={$this_invoice['meta']['invoice_date']['month']}"); ?>
          <?php echo WPI_UI::input("class=jj&name=wpi_invoice[meta][invoice_date][day]&value={$this_invoice['meta']['invoice_date']['day']}&special=size='2' maxlength='2' autocomplete='off'") ?>
          <?php echo WPI_UI::input("class=aa&name=wpi_invoice[meta][invoice_date][year]&value={$this_invoice['meta']['invoice_date']['year']}&special=size='2' maxlength='4' autocomplete='off'") ?> </div>
          </tr> */ ?>
        <tr class="column-publish-due-date wpi_not_for_recurring wpi_not_for_quote">
          <th>Due Date</th>
          <td><div class="timestampdiv" style="display:block;">
              <?php echo WPI_UI::select("id=due_date_mm&name=wpi_invoice[due_date_month]&values=months&current_value=" . (!empty($this_invoice['due_date_month']) ? $this_invoice['due_date_month'] : '')); ?>
              <?php echo WPI_UI::input("id=due_date_jj&name=wpi_invoice[due_date_day]&value=" . (!empty($this_invoice['due_date_day']) ? $this_invoice['due_date_day'] : '') . "&special=size='2' maxlength='2' autocomplete='off'") ?>
              <?php echo WPI_UI::input("id=due_date_aa&name=wpi_invoice[due_date_year]&value=" . (!empty($this_invoice['due_date_year']) ? $this_invoice['due_date_year'] : '') . "&special=size='2' maxlength='4' autocomplete='off'") ?><br />
              <span onclick="wp_invoice_add_time('due_date', 7);" class="wp_invoice_click_me">In One Week</span> | <span onclick="wp_invoice_add_time('due_date', 30);" class="wp_invoice_click_me">In 30 Days</span> | <span onclick="wp_invoice_add_time('due_date','clear');" class="wp_invoice_click_me">Clear</span> </div></td>
        </tr>
        <tr class="invoice_main column-publish-invoice_id">
          <th>Invoice ID </th>
          <td><?php
            $custom_invoice_id = !empty($this_invoice['custom_id']) ? $this_invoice['custom_id'] : '';
            if (!$custom_invoice_id && $wpi_settings['increment_invoice_id'] == 'true') {
              $highest_custom_id = WPI_Functions::get_highest_custom_id();
              $custom_invoice_id = ($highest_custom_id ? ($highest_custom_id + 1) : $this_invoice['invoice_id']);
              echo WPI_UI::input("name=wpi_invoice[meta][custom_id]&value=$custom_invoice_id");
            } else {
                ?>
              <input style="width: 80px;" class="input_field wp_invoice_custom_invoice_id<?php
          if (empty($this_invoice['custom_id'])) {
            echo " wp_invoice_hidden";
          }
          ?>" name="wpi_invoice[meta][custom_id]" value="<?php echo!empty($this_invoice['custom_id']) ? $this_invoice['custom_id'] : ''; ?>">
              <span class="wp_invoice_custom_invoice_id"><?php echo $this_invoice['invoice_id']; ?></span> <a onClick='jQuery(".wp_invoice_custom_invoice_id").toggle(); return false;' class="wp_invoice_click_me <?php
                                                                                                          if (!empty($this_invoice['custom_id'])) {
                                                                                                            echo " wp_invoice_hidden";
                                                                                                          }
                                                                                                          ?>" href="#">Custom Invoice ID</a>
  <?php } ?>
          </td>
        </tr>
        <tr class="invoice_main column-publish-global_tax">
          <th>Global Tax</th>
          <td>
  <?php echo WPI_UI::input("id=wp_invoice_tax&name=wpi_invoice[meta][tax]&value=" . (!empty($this_invoice['tax']) ? $this_invoice['tax'] : '')) ?>
          </td>
        </tr>
        <tr class="invoice_main column-publish-global_tax">
          <th>Tax Method</th>
          <td>
  <?php $tax_method = !empty($this_invoice['tax_method']) ? $this_invoice['tax_method'] : (isset($wpi_settings['tax_method']) ? $wpi_settings['tax_method'] : ''); ?>
  <?php echo WPI_UI::select("id=wpi_tax_method&name=wpi_invoice[tax_method]&values=" . serialize(array('before_discount' => __('Before Discount'), 'after_discount' => __('After Discount'))) . "&current_value={$tax_method}"); ?>
          </td>
        </tr>
      </table>
    </div>
    <div id="major-publishing-actions" class="clearfix">
      <div id="delete-action" class="wpi_hide_until_saved">
        <a href="<?php echo admin_url($wpi_settings['links']['overview_page']) . "&action=trash&post=" . (!empty($this_invoice['ID']) ? $this_invoice['ID'] : '') . "&_wpnonce=" . wp_create_nonce('wpi-status-change-' . (!empty($this_invoice['ID']) ? $this_invoice['ID'] : '')); ?>" class="submitdelete deletion">Trash Invoice</a>
      </div>
      <div id="publishing-action">
        <input type="submit" class="alignright button-primary" value="<?php _e('Save'); ?>" id="wpi_save_invoice">
      </div>
    </div>
  </div>
  <?php
}

function recurring_billing_box() {
  ?>
  <div class="postbox column-recurring-billing" id="wp_invoice_client_info_div">
    <h3>
      <label for="link_name">
      <?php _e("Recurring Billing", WP_INVOICE_TRANS_DOMAIN) ?>
      </label>
    </h3>
    <div id="wp_invoice_enable_recurring_billing" class="wp_invoice_click_me" <?php if ($invoice->recurring) { ?>style="display:none;"<?php } ?>>
  <?php _e("Create a recurring billing schedule for this invoice.", WP_INVOICE_TRANS_DOMAIN) ?>
    </div>
    <div class="wp_invoice_enable_recurring_billing" <?php if (!$invoice->recurring) { ?>style="display:none;"<?php } ?>>
      <table class="form-table" id="add_new_invoice">
        <tr>
          <th><a class="wp_invoice_tooltip" title="<?php _e("A name to identify this subscription by in addition to the invoice id. (ex: 'standard hosting')", WP_INVOICE_TRANS_DOMAIN) ?>">
  <?php _e("Subscription Name", WP_INVOICE_TRANS_DOMAIN) ?>
            </a></th>
          <td><?php echo WPI_UI::input('wp_invoice_subscription_name', $wp_invoice_subscription_name); ?></td>
        </tr>
        <tr>
          <th><?php _e("Start Date", WP_INVOICE_TRANS_DOMAIN) ?></th>
          <td><span style="<?php if ($recurring_auto_start) { ?>display:none;<?php } ?>" class="wp_invoice_timestamp">
                <?php _e("Start automatically as soon as the customer enters their billing information. ", WP_INVOICE_TRANS_DOMAIN) ?>
              <span class="wp_invoice_click_me" onclick="jQuery('.wp_invoice_timestamp').toggle();">
                <?php _e("Specify Start Date", WP_INVOICE_TRANS_DOMAIN) ?>
              </span></span>
            <div style="<?php if (!$recurring_auto_start) { ?>display:none;<?php } ?>" class="wp_invoice_timestamp"> <?php echo WPI_UI::draw_select('wp_invoice_subscription_start_month', array("01" => "Jan", "02" => "Feb", "03" => "Mar", "04" => "Apr", "05" => "May", "06" => "Jun", "07" => "Jul", "08" => "Aug", "09" => "Sep", "10" => "Oct", "11" => "Nov", "12" => "Dec"), $wp_invoice_subscription_start_month); ?> <?php echo WPI_UI::input('wp_invoice_subscription_start_day', $wp_invoice_subscription_start_day, ' size="2" maxlength="2" autocomplete="off" '); ?>, <?php echo WPI_UI::input('wp_invoice_subscription_start_year', $wp_invoice_subscription_start_year, ' size="4" maxlength="4" autocomplete="off" '); ?> <span onclick="wp_invoice_subscription_start_time(7);" class="wp_invoice_click_me">
                <?php _e("In One Week", WP_INVOICE_TRANS_DOMAIN) ?>
              </span> | <span onclick="wp_invoice_subscription_start_time(30);" class="wp_invoice_click_me">
  <?php _e("In 30 Days", WP_INVOICE_TRANS_DOMAIN) ?>
              </span> | <span onclick="jQuery('.wp_invoice_timestamp').toggle();wp_invoice_subscription_start_time('clear');"  class="wp_invoice_click_me">
              <?php _e("Start automatically", WP_INVOICE_TRANS_DOMAIN) ?>
              </span> </div></td>
        </tr>
        <tr>
          <th><a class="wp_invoice_tooltip"  title="<?php _e("This will be the number of times the client will be billed. (ex: 12)", WP_INVOICE_TRANS_DOMAIN) ?>">
  <?php _e("Bill Every", WP_INVOICE_TRANS_DOMAIN) ?>
            </a></th>
          <td><?php echo WPI_UI::input('wp_invoice_subscription_length', $wp_invoice_subscription_length, ' size="3" maxlength="3" autocomplete="off" '); ?> <?php echo WPI_UI::draw_select('wp_invoice_subscription_unit', array("months" => __("month(s)", WP_INVOICE_TRANS_DOMAIN), "days" => __("days", WP_INVOICE_TRANS_DOMAIN)), $wp_invoice_subscription_unit); ?></td>
        </tr>
        <tr>
          <th><a class="wp_invoice_tooltip"  title="<?php _e("Keep it under the maximum of 9999.", WP_INVOICE_TRANS_DOMAIN) ?>">
  <?php _e("Total Billing Cycles", WP_INVOICE_TRANS_DOMAIN) ?>
            </a></th>
          <td><?php echo WPI_UI::input('wp_invoice_subscription_total_occurances', $wp_invoice_subscription_total_occurances, ' size="4" maxlength="4" autocomplete="off" '); ?></td>
        </tr>
        <tr>
          <th></th>
          <td><?php _e("All <b>recurring billing</b> fields must be filled out to activate recurring billing. ", WP_INVOICE_TRANS_DOMAIN) ?>
            <span onclick="wp_invoice_cancel_recurring()" class="wp_invoice_click_me">
  <?php _e("Cancel Recurring Billing", WP_INVOICE_TRANS_DOMAIN) ?>
            </span></td>
        </tr>
      </table>
    </div>
  </div>
  <?php
}

function postbox_user_new($this_invoice) {
  global $wpi_settings;
  ?>
  <div class="postbox">
    <h3 class="hndle">New User Information</h3>
    <div class="inside">
      <table class="form-table wp_invoice_new_user">
        <tr>
          <th>Email</th>
          <td id="wpi_user_email"><?php echo $_REQUEST['wpi']['new_invoice']['user_email']; ?>
        <?php echo WPI_UI::input("type=hidden&name=wpi_invoice[user_data][user_email]&value={$_REQUEST['wpi']['new_invoice']['user_email']})") ?>
          </td>
        </tr>
        <?php
        $custom_user_information = apply_filters('wpi_user_information', $wpi_settings['user_meta']['custom']);
        $user_information = array_merge($wpi_settings['user_meta']['required'], $custom_user_information);
        foreach ($user_information as $field_id => $field_name) {
          ?>
          <tr>
            <th><?php _e($field_name) ?></th>
            <td><?php echo WPI_UI::input("name=wpi_invoice[user_data][$field_id]&value=" . $this_invoice['user_data'][$field_id]); ?></td>
          </tr>
              <?php } ?>
        <tr>
          <th>
            <a class="wp_invoice_tooltip" title="<?php _e("If checked a WordPress user account will be created, otherwise the new user will only be visible within WP-Invoice.", WP_INVOICE_TRANS_DOMAIN) ?>">
              <?php _e("Create WordPress User Account?", WP_INVOICE_TRANS_DOMAIN) ?>
            </a>
          </th>
          <td><input  onclick="if(jQuery(this).is(':checked')) { jQuery('#wpi_new_user_username input').val('<?php echo $_REQUEST['wpi']['new_invoice']['user_email']; ?>'); jQuery('#wpi_new_user_username').show();} else { jQuery('#wpi_new_user_username input').val('');  jQuery('#wpi_new_user_username').hide();}"  type="checkbox" name='wpi_invoice[user_data][create_wp_account]'>
            <label for="wpi_invoice[user_data][create_wp_account]">
  <?php _e("Yes", WP_INVOICE_TRANS_DOMAIN) ?>
            </label></td>
        </tr>
        <tr class="hidden" id="wpi_new_user_username">
          <th>Username</th>
          <td><?php echo WPI_UI::input("name=wpi_invoice[user_data][username]"); ?></td>
        </tr>
      </table>
    </div>
  </div>
  <?php
}

function postbox_user_existing($this_invoice) {
  global $wpi_settings, $wpdb;
  $user_emails = $wpdb->get_col("SELECT user_email FROM {$wpdb->users}");

  $custom_user_information = apply_filters('wpi_user_information', $wpi_settings['user_meta']['custom']);
  $user_information = array_merge($wpi_settings['user_meta']['required'], $custom_user_information);
  //WPI_Functions::qc($this_invoice['user_data']);
  $new_user = false;
  if (!empty($this_invoice['user_data']['user_email']) && is_array($user_emails)) {
    $new_user = !in_array($this_invoice['user_data']['user_email'], $user_emails);
  }
  ?>

  <div class="wpi_user_email_selection_wrapper">
    <select class="wpi_user_email_selection" name="wpi_invoice[user_data][user_email]">
      <?php
      if ($new_user) :
        ?>
        <option selected="selected" value="<?php echo esc_attr($this_invoice['user_data']['user_email']); ?>"><?php echo esc_attr($this_invoice['user_data']['user_email']); ?></option>
        <?php
      else:
        ?>
        <option></option>
      <?php
      endif;
      ?>
  <?php foreach ($user_emails as $user_email) { ?>
        <option <?php selected($user_email, $this_invoice['user_data']['user_email']); ?>value="<?php echo esc_attr($user_email); ?>"><?php echo esc_attr($user_email); ?></option>
  <?php } ?>
    </select>
  </div>

  <table class="form-table wp_invoice_new_user">

    <?php foreach ($user_information as $field_id => $field_name) { ?>
      <tr>
        <th><?php _e($field_name) ?></th>
        <td><?php echo WPI_UI::input("name=wpi_invoice[user_data][$field_id]&class=wpi_{$field_id}&value=" . (!empty($this_invoice['user_data'][$field_id]) ? $this_invoice['user_data'][$field_id] : '')); ?></td>
      </tr>
  <?php } ?>
  </table>
  <?php
  do_action('wpi_integrate_crm_user_panel', $this_invoice['user_data']['ID']);
}

function postbox_payment_methods($this_invoice) {
  global $wpi_settings;
  if (!empty($this_invoice['billing'])) {
    $this_invoice['billing'] = apply_filters('wpi_billing_method', $this_invoice['billing']);
    ?>
    <table class="form-table">

      <tr class="column-payment-method-default wpi_not_for_quote">
        <th><?php _e("Default Payment Option") ?></th>
        <td>
          <select id="wp_invoice_payment_method" name="wpi_invoice[default_payment_method]">
            <?php foreach ($this_invoice['billing'] as $key => $payment_option) : ?>
            <?php if (!isset($payment_option['name']))
              continue; ?>
              <option value="<?php echo $key; ?>" <?php echo ($this_invoice['default_payment_method'] == $key) ? 'selected="selected"' : ''; ?> ><?php echo $payment_option['name']; ?></option>
          <?php endforeach; ?>
          </select>&nbsp;&nbsp;
          <?php
          if (count($this_invoice['billing']) > 1) {
            echo WPI_UI::checkbox("class=wpi_client_change_payment_method&name=wpi_invoice[client_change_payment_method]&value=true&label=Client can change payment option.", !empty( $this_invoice['client_change_payment_method'] )? ( $this_invoice['client_change_payment_method'] == 'on' ? true : false ) : false);
          }
          ?>
        </td>
      </tr>

      <tr class='wpi_not_for_quote wpi-payment-setting column-paymenth-method-<?php echo $key; ?>'>
        <th><?php _e("Accepted Payments") ?></th>
        <td>
          <ul class="wpi_settings_list">
              <?php foreach ($this_invoice['billing'] as $key => $value) : ?>
              <?php if (empty($value['name']))
                break; ?>
              <li class="clearfix">
      <?php echo WPI_UI::checkbox("name=wpi_invoice[billing][{$key}][allow]&id={$key}&value=true&label={$value['name']}&class=wpi_billing_section_show", $value['allow'] == 'on' ? true : false) ?>
      <?php /* <div class="wpi_notice"><?php _e("Notice the ") ?><span onClick="wpi_select_payment_method('<?php echo $key; ?>');"><u><?php echo $value['name']; ?><?php _e(" Tab") ?></u></span><?php _e(" below. ") ?></div> */ ?>
              </li>
    <?php endforeach; ?>
          </ul>
        </td>
      </tr>


      <tr class="wpi_advanced_payment_options column-publish-currency">
        <th><?php _e("Default Currency") ?></th>
        <td>
          <select name="wpi_invoice[default_currency_code]">
    <?php foreach ($wpi_settings['currency']['types'] as $value => $currency_x) : ?>
              <option value="<?php echo $value; ?>" <?php echo ($this_invoice['default_currency_code'] == $value) ? 'selected="selected"' : ''; ?>"><?php echo $value; ?> - <?php echo $currency_x; ?></option>
    <?php endforeach; ?>
          </select>
        </td>
      </tr>



      <tr class="wpi_advanced_payment_options wpi_not_for_quote">
        <td colspan="2">
          <div class="wp_invoice_accordion">
    <?php foreach ($this_invoice['billing'] as $key => $value) : ?>
      <?php if (empty($this_invoice['default_payment_method']))
        $this_invoice['default_payment_method'] = key($this_invoice['billing']); ?>
                  <?php if (empty($value['name']))
                    break; ?>
              <div class="<?php echo $key; ?>-setup-section wp_invoice_accordion_section">
                <h3 id="<?php echo $key; ?>-setup-section-header" <?php if ($this_invoice['default_payment_method'] == $key) { ?>aria-expanded="true"<?php } else { ?>aria-expanded="false"<?php } ?>>
                  <span class="selector"><?php echo $value['name'] ?></span>
                </h3>
                <div style="display:<?php echo $this_invoice['default_payment_method'] == $key ? 'block' : 'none'; ?>">
      <?php echo WPI_UI::input("type=hidden&name=wpi_invoice[billing][{$key}][default_option]&class=billing-default-option billing-{$key}-default-option&value={$value['default_option']}") ?>
                  <table class="form-table">
                        <?php
                        foreach ($value['settings'] as $key2 => $setting_value) :
                          $setting_value['value'] = urldecode($setting_value['value']);
                          $setting_value['type'] = !empty($setting_value['type']) ? $setting_value['type'] : 'input';
                          ?>
                      <tr>
                        <th width="300"><span class="<?php echo (!empty($setting_value['description']) ? "wp_invoice_tooltip" : ""); ?>" title="<?php echo (!empty($setting_value['description']) ? $setting_value['description'] : ''); ?>"><?php echo $setting_value['label']; ?></span></th>
                        <td>
                          <?php if ($setting_value['type'] == 'select') : ?>
                            <?php echo WPI_UI::select("name=wpi_invoice[billing][{$key}][settings][{$key2}][value]&values=" . serialize($setting_value['data']) . "&current_value={$setting_value['value']}"); ?>
                          <?php elseif ($setting_value['type'] == 'textarea') : ?>
                            <?php echo WPI_UI::textarea("name=wpi_invoice[billing][{$key}][settings][{$key2}][value]&value={$setting_value['value']}"); ?>
                          <?php elseif ($setting_value['type'] == 'readonly') : ?>
                            <?php $setting_value['value'] = urlencode($setting_value['value']); ?>
                            <?php echo WPI_UI::textarea("name=wpi_invoice[billing][{$key}][settings][{$key2}][value]&value={$setting_value['value']}&special=readonly='readonly'"); ?>
                          <?php else : ?>
                            <?php echo WPI_UI::input("name=wpi_invoice[billing][{$key}][settings][{$key2}][value]&value={$setting_value['value']}"); ?>
                          <?php endif; ?>
        <?php if (!empty($setting_value['special']) && is_array($setting_value['special']) && $setting_value['type'] != 'select') : ?>
                        <?php $s_count = 0; ?>
                            <br />
          <?php foreach ($setting_value['special'] as $s_label => $s_value): ?>
                              <span class="wp_invoice_click_me" onclick="jQuery('input[name=\'wpi_invoice[billing][<?php echo $key; ?>][settings][<?php echo $key2; ?>][value]\']').val('<?php echo $s_value; ?>');"><?php echo $s_label; ?></span>
                    <?php echo (++$s_count < count($setting_value['special']) ? ' | ' : '' ); ?>
                  <?php endforeach; ?>
        <?php endif; ?>
                        </td>
                      </tr>
      <?php endforeach; ?>
                  </table>
                </div>
              </div>
    <?php endforeach; ?>
          </div>
        </td>
      </tr>

      <tr>
        <th></th>
        <td class="wpi_toggle_advanced_payment_options"><span class="wpi_link"><?php _e('Toggle Advanced Payment Options'); ?></span></td>
      </tr>

    </table>
  <?php } else { ?>
    <table class="form-table">
      <tr>
        <th><?php _e("Payment Method") ?></th>
        <td>
          <input type="hidden" name="wpi_invoice[default_payment_method]" value="manual" />
          <p>To manage payment settings you should accept at least one payment method. Visit <a href="<?php echo admin_url('admin.php?page=wpi_page_settings#wpi_tab_payment'); ?>">Payment Settings page</a> to setup.</p>
          <p>If you do not want to use any payment venue then <a href="<?php echo admin_url('admin.php?page=wpi_page_settings#wpi_tab_payment'); ?>">setup Manual Payment information</a>.</p>
        </td>
      </tr>
      <tr class="wpi_advanced_payment_options column-publish-currency">
        <th><?php _e("Default Currency") ?></th>
        <td>
          <select name="wpi_invoice[default_currency_code]">
    <?php foreach ($wpi_settings['currency']['types'] as $value => $currency_x) : ?>
              <option value="<?php echo $value; ?>" <?php echo ($this_invoice['default_currency_code'] == $value) ? 'selected="selected"' : ''; ?>"><?php echo $value; ?> - <?php echo $currency_x; ?></option>
    <?php endforeach; ?>
          </select>
        </td>
      </tr>
      <tr>
        <th></th>
        <td class="wpi_toggle_advanced_payment_options"><span class="wpi_link"><?php _e('Toggle Advanced Payment Options'); ?></span></td>
      </tr>
    </table>

  <?php
  }
}

function status_meta_box($this_invoice) {
  // Only display this if $_REQUEST[wpi][new_invoice] or $_REQUEST[wpi][existing_invoice] are passed
  $hidden = '';
  if (!empty($_REQUEST['wpi']['new_invoice'])) {
    $hidden = ' hidden ';
  }
  ?>
  <div id="postbox_status_and_history" class="postbox <?php echo $hidden; ?>">
    <h3 class="hndle"><?php _e("Invoice Status and History") ?></h3>
    <div class="inside" style="margin:0;padding:0;">
      <div id="submitbox" class="submitbox" style="overflow: auto; max-height: 150px;">
        <table id="wpi_enter_payments" class="form-table hidden" >
          <tr>
            <th><?php _e("Event Type") ?></th>
            <td>
  <?php echo WPI_UI::select("name=event_type&values=" . serialize(array('add_payment' => 'Receive Payment', 'add_charge' => 'Add Charge', 'do_adjustment' => 'Administrative Adjustment'))); ?>
              <span class="wpi_recurring_options">Note: Recurring bills cannot have administrative adjustments or additional charges, only received payments.</span>
            </td>
          </tr>
          <tr>
            <th><?php _e("Event Amount") ?></th>
            <td>
              <?php echo WPI_UI::input("type=text&name=wpi_event_amount&class=wpi_money&special=autocomplete='off'"); ?>
              <span id="event_tax_holder" class="hidden">
                <b style="padding:5px;"><?php _e("Charge Tax") ?></b><?php echo WPI_UI::input("type=text&name=wpi_event_tax&class=wpi_money&special=autocomplete='off'"); ?>%
              </span>
            </td>
          </tr>
          <tr>
            <th><?php _e("Event Date & Time") ?></th>
            <td>
  <?php echo WPI_UI::input("type=text&name=wpi_event_date&class=wpi_date"); ?>
  <?php echo WPI_UI::input("type=text&name=wpi_event_time&class=wpi_time"); ?>
            </td>
          </tr>
          <tr>
            <th><?php _e("Event Note") ?></th>
            <td>
  <?php echo WPI_UI::input("name=wpi_event_note"); ?>
            </td>
          </tr>
          <tr>
            <th>&nbsp;</th>
            <td>
            <?php wp_nonce_field('wpi_process_manual_event_nonce', 'wpi_process_manual_event_nonce'); ?>
              <input type="button" class="button" value="Process Charge / Payment"  id="wpi_process_manual_event" />
              <input type="button" class="button" value="Cancel" onclick="wpi_show_paycharge_box();" />
            </td>
          </tr>
        </table>
        <div style="padding: 5px;">
          <table class="form-table" id="wpi_invoice_status_table">
  <?php
  if (!empty($this_invoice['log']) && is_array($this_invoice['log'])) {
    if (!empty($this_invoice['ID'])) {
      WPI_Functions::get_status($this_invoice['ID']);
    }
  }
  ?>
          </table>
        </div>
      </div>
      <div class="footer_functions">
        <span class="wpi_clickable" onclick="jQuery('.wpi_event_update').toggle();">Toggle History Detail</span>
      </div>
    </div>
  </div>
  <?php } ?>
