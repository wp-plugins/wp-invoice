<?php 
global $wpi_settings;

//WPI_Functions::qc($wpi_settings);
//WPI_Functions::qc($_REQUEST['wpi_settings']['globals']);
//WPI_Functions::qc($wpi_settings[pdf]);
//WPI_Functions::qc($wpi_settings[globals][favorite_countries]);

$wpi_settings_tabs = array(
  'basic' => array(
    'label' => __('Basic'),
    'callback' => array('WPI_Settings_page','basic')
  ),
  'display' => array(
    'label' => __('Display'),
    'callback' => array('WPI_Settings_page','display')
  ),
  'payment' => array(
    'label' => __('Payment'),
    'callback' => array('WPI_Settings_page','payment')
  ),
  'email_templates' => array(
    'label' => __('E-Mail Templates'),
    'callback' => array('WPI_Settings_page','email_templates')
  ),
  'log' => array(
    'label' => __('Log'),
    'callback' => array('WPI_Settings_page','log')
  ),
  'predefined' => array(
    'label' => __('Line Items'),
    'callback' => array('WPI_Settings_page','predefined')
  ),
  'help'=> array(
    'label' => __('Help'),
    'callback' => array('WPI_Settings_page','help')
  )
);
  
 

// Allow third-party plugins and premium features to insert and remove tabs via API
$wpi_settings_tabs = apply_filters('wpi_settings_tabs', $wpi_settings_tabs);

?>
<script type="text/javascript">

jQuery(document).ready( function() {
  var wp_invoice_settings_page = jQuery("#wp_invoice_settings_page").tabs({cookie: {expires: 30}});
    // The following runs specific functions when a given tab is loaded
  jQuery('#wp_invoice_settings_page').bind('tabsshow', function(event, ui) {
    var selected = wp_invoice_settings_page.tabs('option', 'selected'); 

    if(selected == 5) { }
  });
  // @TODO: Simple hack to fix setting page scrolling down on load. But cause of it not found.
  jQuery(this).scrollTop(0);
});

</script>

<div class="wrap">
  <form method='post' id="wpi_settings_form">
  <?php echo WPI_UI::input("type=hidden&name=wpi_settings_update&value=true")?>
  <h2><?php _e("WP-Invoice Global Settings", WP_INVOICE_TRANS_DOMAIN) ?></h2>
  
  <?php WPI_Functions::print_messages(); ?>
  
  <div id="wp_invoice_settings_page" class="wp_invoice_tabbed_content">
      <ul class="wp_invoice_settings_tabs">
        <?php foreach($wpi_settings_tabs as $tab_id => $tab) {  if(!is_callable($tab['callback'])) continue; ?>
          <li><a href="#wpi_tab_<?php echo $tab_id; ?>"><?php echo $tab['label']; ?></a></li>
        <?php } ?> 
      </ul>      
      
    <?php foreach($wpi_settings_tabs as $tab_id => $tab) {    ?>
      <div id="wpi_tab_<?php echo $tab_id; ?>" class="wp_invoice_tab" >
        <?php 
        if(is_callable($tab['callback'])) {
          call_user_func($tab['callback'], $wpi_settings); 
        } else {
          echo __('Warning:') . ' ' . implode(':', $tab['callback']) .' ' .  __('not found') . '.';
        }
        ?>
      </div>    
    <?php } ?>    
    
</div><?php /* end: #wp_invoice_settings_page */ ?>
<div id="poststuff" class="metabox-holder">
  <div id="submitdiv" class="postbox" style="">
    <div class="inside">
      <div id="major-publishing-actions">
        <div id="publishing-action">
          <input type="submit" value="Save All Settings" class="button-primary">
        </div>
        <div class="clear"></div>
      </div>
    </div>
  </div>
</div>
  </form>
</div><?php /* end: .wrap */ ?>
 
 
 
 
 <?php
 
 class WPI_Settings_page {
 
 
  function basic($wpi_settings) { 

    global $wpdb;
  ?>
         <table class="form-table">
          <tr>
            <th width="200"><?php _e("Business Name", WP_INVOICE_TRANS_DOMAIN) ?></th>
            <td><?php echo WPI_UI::input("name=business_name&group=wpi_settings&value={$wpi_settings['business_name']}")?> </td>
          </tr>
          <tr>
            <th width="200"><a class="wp_invoice_tooltip"  title="This will display on the invoice page when printed for clients' records.">
              <?php _e("Business Address") ?></a></th>
            <td><?php echo WPI_UI::textarea("name=business_address&group=wpi_settings&value={$wpi_settings['business_address']}")?> </td>
          </tr>
          <tr>
            <th width="200"><?php _e("Business Phone", WP_INVOICE_TRANS_DOMAIN) ?></th>
            <td><?php echo WPI_UI::input("name=business_phone&group=wpi_settings&value={$wpi_settings['business_phone']}")?> </td>
          </tr>

          <tr>
            <th width="200"><?php _e("Email Address", WP_INVOICE_TRANS_DOMAIN) ?></th>
            <td><?php echo WPI_UI::input("name=email_address&group=wpi_settings&value={$wpi_settings['email_address']}")?> </td>
          </tr>
      
          <tr>
            <th><?php _e("When creating an invoice", WP_INVOICE_TRANS_DOMAIN) ?></th>
            <td>
              <ul class="wpi_settings_list">
                <li><?php echo WPI_UI::checkbox("name=increment_invoice_id&group=wpi_settings&value=true&label=Automatically increment the invoice's custom ID by one.",$wpi_settings['increment_invoice_id'])?></li>
              </ul>
            </td>
          </tr>          
          <tr>
            <th><?php _e("After a customer pays", WP_INVOICE_TRANS_DOMAIN) ?></th>
            <td><ul class="wpi_settings_list">
                <li><?php echo WPI_UI::checkbox("name=send_thank_you_email&group=wpi_settings&value=true&label= Email a payment confirmation to the client.", $wpi_settings['send_thank_you_email']); ?></li>
                <li><?php echo WPI_UI::checkbox("name=cc_thank_you_email&group=wpi_settings&value=true&label= Email me a payment notification.",$wpi_settings['cc_thank_you_email']); ?></li>
              </ul></td>
          </tr>
          <tr>
            <th><?php _e("When viewing an invoice", WP_INVOICE_TRANS_DOMAIN) ?></th>
            <td><ul class="wpi_settings_list">
                <li>
                  <label for="wpi_settings[web_invoice_page]">Display invoices on the
                  <select name='wpi_settings[web_invoice_page]'>
                  <option></option>
                  <?php $list_pages = $wpdb->get_results("SELECT ID, post_title, post_name, guid FROM ". $wpdb->prefix ."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title");
            $wp_invoice_web_invoice_page = $wpi_settings['web_invoice_page'];
            foreach ($list_pages as $page)
            { 
            echo "<option  style='padding-right: 10px;'";
            if(isset($wp_invoice_web_invoice_page) && $wp_invoice_web_invoice_page == $page->ID) echo " SELECTED ";
            echo " value=\"".$page->ID."\">". $page->post_title . "</option>\n"; 
            }
            echo "</select>";?>
                   page. </label>
                </li>
         
        <li><?php echo WPI_UI::checkbox("name=replace_page_title_with_subject&group=wpi_settings&value=true&label=Replace page title with invoice subject when viewing invoice.", $wpi_settings['replace_page_title_with_subject']); ?></li>
        <li><?php echo WPI_UI::checkbox("name=replace_page_heading_with_subject&group=wpi_settings&value=true&label=Replace page heading with invoice subject when viewing invoice.", $wpi_settings['replace_page_heading_with_subject']); ?></li>
        <li><?php echo WPI_UI::checkbox("name=hide_page_title&group=wpi_settings&value=true&label=Hide page title completely.", $wpi_settings['hide_page_title']); ?></li>
                <li><?php echo WPI_UI::checkbox("name=force_https&group=wpi_settings&value=true&label= Enforce HTTPS.", $wpi_settings['force_https']); ?> </li>
                <li><?php echo WPI_UI::checkbox("name=show_business_address&group=wpi_settings|globals&value=true&label= Show my business name and address.", $wpi_settings['globals']['show_business_address']);?> </li>
                <li><?php echo WPI_UI::checkbox("name=show_quantities&group=wpi_settings|globals&value=true&label= Show quantity breakdowns in the itemized list.", $wpi_settings['globals']['show_quantities']);?> </li>
              </ul></td>
          </tr>
          <tr>
            <th> <a class="wp_invoice_tooltip"  title="<?php _e('Select whether to overwrite all page content, insert at the bottom of the content, or to look for the [wp-invoice] tag.', WP_INVOICE_TRANS_DOMAIN); ?>">
              <?php _e('How to Insert Invoice', WP_INVOICE_TRANS_DOMAIN); ?>
              </a></th>
            <td><?php echo WPI_UI::select("name=where_to_display&group=wpi_settings&values=".serialize(array("overwrite" => "Overwrite All Page Content", "below_content" => "Place Below Content","above_content" => "Above Content","replace_tag" => "Replace [wp-invoice] Tag"))."&current_value={$wpi_settings['where_to_display']}"); ?> If using the tag, place <span class="wp_invoice_explanation">[wp-invoice]</span> somewhere within your page content. </td>
          </tr>      
       
          <tr>
            <th><?php _e("Advanced settings", WP_INVOICE_TRANS_DOMAIN) ?></th>
            <td>
              <ul class="wpi_settings_list">
                <li><?php echo WPI_UI::checkbox("name=allow_deposits&group=wpi_settings&value=true&label=Allow partial payments.", $wpi_settings['allow_deposits']); ?></li>
                <!--<li><?php echo WPI_UI::checkbox("name=terms_acceptance_required&group=wpi_settings&value=true&label=Show checkbox for mandatory terms acceptance.", $wpi_settings['terms_acceptance_required']); ?></li>-->
                <li><?php echo WPI_UI::checkbox("name=show_recurring_billing&group=wpi_settings&value=true&label= Show recurring billing options.", $wpi_settings['show_recurring_billing']); ?></li>
                <li>
                  <label for="wpi_tax_method">Calculate Taxable Subtotal <?php echo WPI_UI::select("name=tax_method&group=wpi_settings&values=".serialize(array("after_discount" => "After Discount","before_discount" => "Before Discount"))."&current_value=".(!empty($wpi_settings['tax_method']) ? $wpi_settings['tax_method'] : "")); ?> </label>
                </li>
                <li>
                  <label for="wpi_user_level">Minimum user level to manage WP-Invoice <?php echo WPI_UI::select("name=user_level&group=wpi_settings&values=".serialize(array("level_0" => "Subscriber","level_0" => "Contributor","level_2" => "Author","level_5" => "Editor","level_8" => "Administrator"))."&current_value={$wpi_settings['user_level']}"); ?> </label>
                </li>
              </ul>
            </td>
          </tr>
 
        </table>
        
  <?php } /* end "Basic" */ 
  
  
  function display($wpi_settings) { ?>
  
    
   <table class="form-table">
    <tr>
      <th>Use Custom Templates</th>
      <td>
      <ul>
      <li>
      <?php 
      // uncheck box if wpi folder is not found
      if(!file_exists($wpi_settings['frontend_template_path'])) {
        $no_template_folder = true;
      }        
      
      echo WPI_UI::checkbox("class=use_custom_templates&name=wpi_settings[use_custom_templates]&value=yes&label=Use custom templates. If checked, WP-Invoice will use templates in the 'wpi' folder in your active theme's folder.", WPI_Functions::is_true($wpi_settings['use_custom_templates']) ); ?>
      </li>
      <li class="wpi_use_custom_template_settings" style="<?php echo (empty($wpi_settings['use_custom_templates']) || $wpi_settings['use_custom_templates'] == 'no' ? 'display:none;' : ''); ?>">
      <?php if($no_template_folder) { ?>
      <span class="wpi_red_notification">Note: Currently there is no "wpi" folder in your active template's folder.</span>
      <?php } else { ?>
      <span class="wpi_green_notification">A "wpi" folder has been found, any files with the proper file names will be used instead of the default template files.</span>
      <?php } ?>
      </li>
      </li>
       <li><?php echo WPI_UI::checkbox("name=wpi_settings[install_use_custom_templates]&value=yes&label=Install/re-install templates. If checked, WP-Invoice will attempt to install the templates inside the <b>wpi</b> folder in your active theme's folder.", false); ?></li>
       <li><?php echo WPI_UI::checkbox("name=wpi_settings[do_not_load_theme_specific_css]&value=yes&label=Do NOT load theme specific style.", WPI_Functions::is_true($wpi_settings['do_not_load_theme_specific_css']) ); ?></li>
       <li><?php echo WPI_UI::checkbox("name=wpi_settings[use_css]&value=yes&label=Include default CSS styles.", WPI_Functions::is_true($wpi_settings['use_css']) ); ?></li>
    </ul>
      
      </td>
          </tr>
 
             
      
</table>
  
  
  <?php } 
  
  
  function payment($wpi_settings) { ?>
    <table class="form-table">
          <tr>
            <th><?php _e("Default Currency:");?></th>
            <td><?php echo WPI_UI::select("name=wpi_settings[currency][default_currency_code]&values=".serialize($wpi_settings['currency']['types'])."&current_value={$wpi_settings['currency']['default_currency_code']}"); ?></td>
          </tr>
          <tr>
            <th><a class="wp_invoice_tooltip"  title="Special proxy must be used to process credit card transactions on GoDaddy servers.">Using Godaddy Hosting</a></th>
            <td><?php echo WPI_UI::select("name=using_godaddy&group=wpi_settings&values=yon&current_value={$wpi_settings['using_godaddy']}"); ?> </td>
          </tr>
          <tr class="column-payment-method-default">
            <th><?php _e("Default Payment Method:") ?></th>
            <td ><select id="wp_invoice_payment_method">
                <?php foreach ($wpi_settings['installed_gateways'] as $key => $payment_option) { ?>
                <option value="<?php echo $key; ?>" <?php if($payment_option['object']->options['default_option']) { echo "SELECTED"; } ?>><?php echo $payment_option['name']; ?></option>
                <?php } ?>
              </select>&nbsp;&nbsp;
            <?php echo WPI_UI::checkbox("class=wpi_client_change_payment_method&name=wpi_settings[client_change_payment_method]&value=yes&label=Client can change payment option.", WPI_Functions::is_true($wpi_settings['client_change_payment_method']))?>
 
            </td>
          </tr>
 
          <?php foreach($wpi_settings['installed_gateways'] as $key => $value) { ?>
          <tr class='wpi-payment-setting column-paymenth-method-<?php echo $key; ?>'>
            <th><?php _e("Accept ") ?>
              <?php echo $value['name']; ?>?</th>
            <td><?php echo WPI_UI::checkbox("&name=wpi_settings[billing][{$key}][allow]&id={$key}&value=true&label=Yes&class=wpi_billing_section_show", $value['object']->options['allow']);?>
              <div class="wpi_notice">
                <?php _e("Notice the ") ?>
                <span onClick="wpi_select_payment_method('<?php echo $key; ?>');"><u><?php echo $value['name']; ?>
                <?php _e(" Tab ") ?>
                </u></span>
                <?php _e(" below. ") ?>
              </div></td>
          </tr>
          <?php } ?>
          <tr>
            <th>&nbsp;</th>
            <td><div class="wp_invoice_accordion">
                <?php foreach($wpi_settings['installed_gateways'] as $key => $value) { ?>
        <div class="<?php echo $key; ?>-setup-section wp_invoice_accordion_section">
                  <h3 id="<?php echo $key; ?>-setup-section-header"><a href="#" class="selector"><?php echo $value['name'] ?></a></h3>
                  <div> <?php echo !empty($wpi_settings['billing'][$key])?WPI_UI::input("type=hidden&name=wpi_settings[billing][{$key}][default_option]&class=billing-default-option billing-{$key}-default-option&value={$wpi_settings['billing'][$key]['default_option']}"):'';?>
                    <table class="form-table">
                      
                      <?php if ( $value['object']->options['settings'] ) foreach($value['object']->options['settings'] as $key2 => $setting_value) { 
                        $setting_value['value'] = urldecode($setting_value['value']);
                        $setting_value['type'] = !empty( $setting_value['type'] ) ? $setting_value['type'] : 'input' ;
                        ?>
                      <tr>
                        <th width="300"><span class="<?php echo (!empty($setting_value['description']) ? "wp_invoice_tooltip" : ""); ?>" title="<?php echo (!empty($setting_value['description']) ? $setting_value['description'] : ''); ?>"><?php echo $setting_value['label']; ?></span></th>
                        <td>
                          <?php if ($setting_value['type'] == 'select') : ?>
                            <?php echo WPI_UI::select("name=wpi_settings[billing][{$key}][settings][{$key2}][value]&values=" . serialize($setting_value['data']) . "&current_value={$setting_value['value']}"); ?>
                          <?php elseif ($setting_value['type'] == 'textarea') : ?>
                            <?php echo WPI_UI::textarea("name=wpi_settings[billing][{$key}][settings][{$key2}][value]&value={$setting_value['value']}"); ?>
                          <?php elseif ($setting_value['type'] == 'readonly') : ?>
                          <?php $setting_value['value'] = urlencode($setting_value['value']); ?>
                            <?php echo WPI_UI::textarea("name=wpi_settings[billing][{$key}][settings][{$key2}][value]&value={$setting_value['value']}&special=readonly='readonly'"); ?>
                          <?php else : ?>
                            <?php echo WPI_UI::input("name=wpi_settings[billing][{$key}][settings][{$key2}][value]&value={$setting_value['value']}"); ?>
                          <?php endif; ?>
                          <?php if (!empty($setting_value['special']) && is_array($setting_value['special']) && $setting_value['type'] != 'select') : ?>
                            <?php $s_count = 0; ?>
                            <br/>
                            <?php foreach($setting_value['special'] as $s_label => $s_value): ?>
                              <span class="wp_invoice_click_me" onclick="jQuery('input[name=\'wpi_settings[billing][<?php echo $key; ?>][settings][<?php echo $key2; ?>][value]\']').val('<?php echo $s_value; ?>');"><?php echo $s_label; ?></span>
                              <?php echo (++$s_count < count($setting_value['special']) ? ' | ' : '' ); ?>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php } ?>
                    </table>
                  </div>
                </div>
                <?php } ?>
              </div></td>
          </tr>
        </table>
  
  <?php }
    
  
  function email_templates($wpi_settings) { ?>
    <?php $notifications_array = apply_filters('wpi_email_templates', $wpi_settings['notification']); ?>
    <?php //WPI_Functions::qc($notifications_array); ?>
    <table class="ud_ui_dynamic_table widefat form-table" style="margin-bottom:8px;" auto_increment="true">
    <thead>
      <tr>
        <th><?php _e('Name'); ?></th>
        <th style="width:150px;"><?php _e('Subject'); ?></th>
        <th style="width:400px;"><?php _e('Content'); ?></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($notifications_array as $slug => $notification):  ?>
      <tr class="wpi_dynamic_table_row" slug="<?php echo $slug; ?>" new_row="false">
        <td>
          <div style="position:relative;">
            <span class="row_delete">&nbsp;</span>
            <?php echo WPI_UI::input("name=wpi_settings[notification][{$slug}][name]&value={$notification['name']}&type=text&style=width:150px;margin-left:35px;")?>
          </div>
        </td>
        <td>
          <?php echo WPI_UI::input("name=wpi_settings[notification][{$slug}][subject]&value={$notification['subject']}&type=text&style=width:240px;")?> 
        </td>
        <td>
          <?php echo WPI_UI::textarea("class=wpi_notification_template_content&name=wpi_settings[notification][{$slug}][content]&value=".urlencode($notification['content']))?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="3">
          <input type='button' class="button wpi_button wpi_add_row" value="<?php _e('Add Template'); ?>"/>
        </th>
      </tr>
    </tfoot>
    </table>
    
    <div class="wpi_yellow_notification" style="margin-top:10px;">
    <p><b>Available Variables:</b><br />
    %invoice_id% - Invoice ID<br />
    %link% - URL of invoice<br />
    %recipient% - Name or business name of receipient<br />
    %amount% - Due Balance<br />
    %subject% - Invoice title<br />
    %description% - Description of Invoice<br />
    %business_name% - Business Name<br />
    %business_email% - Business Email Address<br />
    Variables may be used in subject or the body of the notification email.</p>
    </div>
    <?php
  }
  
  function log($wpi_settings) { ?>
    <?php $wpi_log = get_option('wpi_log'); ?> 
    <?php if(is_array($wpi_log)) : ?>
    <table class="form-table widefat" style="margin-bottom:10px;border-collapse:separate;">
    <thead>
    <tr>
      <th style="width: 200px;">Time</th>
      <th style="width: 600px;" >Event</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach(array_reverse($wpi_log) as $event) : ?>
      <tr>
        <td><?php echo date("F j, Y, g:i a", $event[0]); ?></td>
        <td><?php echo $event[1]; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
      <th style="width: 200px;">Time</th>
      <th>Event</th>
    </tr>
    </tfoot>
    </table>
    <?php endif;
  }
  
  function predefined($wpi_settings) { ?>
    <p>Setup your common services and products in here to streamline invoice creation.  You can also add discount in here by using negative quantities.</p>
    <script type="text/javascript">
    jQuery(document).ready( function() {
      wpi_recalc_totals();
    });
    </script>
    <?php 
    // Create some blank rows if non exist
    if(!is_array($wpi_settings['predefined_services']))  {
      $wpi_settings['predefined_services'][1] = true;
      $wpi_settings['predefined_services'][2] = true;
    }
    ?>
    <div id="wpi_predefined_services_div">
    <table id="itemized_list" class="ud_ui_dynamic_table itemized_list form-table widefat" auto_increment="true">
    <thead>
    <tr>
      <th style="width:400px;"><?php _e("Name & Description", WP_INVOICE_TRANS_DOMAIN) ?></th>
      <th style="width:40px;"><?php _e("Qty.", WP_INVOICE_TRANS_DOMAIN) ?></th>
      <th style="width:40px;"><?php _e("Price", WP_INVOICE_TRANS_DOMAIN) ?></th>
      <th style="width:40px;"><?php _e("Tax", WP_INVOICE_TRANS_DOMAIN) ?></th>
      <th style="width:40px;"><?php _e("Total", WP_INVOICE_TRANS_DOMAIN) ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($wpi_settings['predefined_services'] as $slug => $itemized_item) : ?>
      <tr class="wpi_dynamic_table_row wp_invoice_itemized_list_row" slug="<?php echo $slug; ?>" new_row="false">
        <td>
          <div class="flexible_width_holder">
            <div class="flexible_width_holder_content"> <span class="row_delete">&nbsp;</span>
              <input type="text" class="item_name input_field" name="wpi_settings[predefined_services][<?php echo $slug; ?>][name]" value="<?php echo esc_attr($itemized_item['name']); ?>" />
              <span class="wpi_add_description_text">&nbsp;<span class="content">Toggle Description</span></span>
            </div>
          </div>
          <div class="flexible_width_holder">
            <div class="flexible_width_holder_content">
              <textarea style="display:<?php echo (empty($itemized_item['description']) ? 'none' : 'block'); ?>" name="wpi_settings[predefined_services][<?php echo $slug; ?>][description]" class="item_description"><?php echo esc_attr($itemized_item['description']); ?></textarea>
            </div>
          </div>
        </td>
        <td>
          <span class="row_quantity"><input type="text" autocomplete="off"  value="<?php echo esc_attr($itemized_item['quantity']); ?>" name="wpi_settings[predefined_services][<?php echo $slug; ?>][quantity]" id="qty_item_<?php echo $slug; ?>"  class="item_quantity input_field"></span>
        </td>
        <td>
          <span class="row_price"><input type="text" autocomplete="off" value="<?php echo esc_attr($itemized_item['price']); ?>"  name="wpi_settings[predefined_services][<?php echo $slug; ?>][price]" id="price_item_<?php echo $slug; ?>" class="item_price input_field"></span>
        </td>
        <td>
          <span class="row_tax"><input type="text" autocomplete="off" value="<?php echo esc_attr($itemized_item['tax']); ?>"  name="wpi_settings[predefined_services][<?php echo $slug; ?>][tax]" id="price_item_<?php echo $slug; ?>" class="item_tax input_field"></span>
        </td>
        <td>
          <span class="row_total" id="total_item_<?php echo $slug; ?>" ></span>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
    <tr>
      <th colspan="5">
        <input type='button' class="button wpi_button wpi_add_row" value="Add Line Item"/>
      </th>
    </tr>
    </tfoot>
    </table>
    </div>
    <?php
  }
  
  function help($wpi_settings) { ?>
  
    <script type='text/javascript'>
      jQuery(document).ready(function() {
          /** Do the JS for our view link */
        jQuery('#wpi_settings_view').click(function(e){
        e.preventDefault();
        jQuery('#wpi_settings_row').toggle();
        });
        /** Do the JS for loading an invoice */
        jQuery('#wpi_load_invoice').click(function(e){
          e.preventDefault();
        
        var data = {
          action: 'my_special_action',
          whatever: 1234
        };

        jQuery('#wpi_load_invoice_details').load(
          ajaxurl, { 
            action: 'wpi_debug_get_invoice', 
            invoice_id: jQuery("#wpi_load_invoice_number").val()
          },
          function(){
            jQuery("#wpi_load_invoice_row").slideDown();
          }
        );
        });
    
    });
    </script>
  
     <table class="form-table" >
        <!-- Not updated 
        <tr>
          <th>Load Dummy Invoices</td>
          <td>#<?php echo WPI_UI::input('name=wp_invoice_load_dummy_quantity'); ?></td>
        </tr>
        -->
        <tr>
          <th>Load Invoice</th>
          <td>
            #<?php echo WPI_UI::input('name=wpi_load_invoice_number'); ?>
            <a id="wpi_load_invoice" href="#" class="button">Go</a>
          </td>
        </tr>
        <tr class="hidden" id="wpi_load_invoice_row">
          <td colspan="2">
            <div id="wpi_load_invoice_details" class="ui-state-highlight ui-corner-all" style="overflow:scroll;height: 300px;width:50%;">
              <?php echo WPI_Functions::pretty_print_r($wpi_settings); ?>
            </div>
          </td>
        </tr>
        <tr>
          <th>View $wpi_settings</th>
          <td>
            <a id="wpi_settings_view" href="#" class="button">Show/Hide</a>
          </td>
        </tr>
        <tr class="hidden" id="wpi_settings_row">
          <td colspan="2">
            <div class="ui-state-highlight ui-corner-all" style="overflow:scroll;height: 300px;width:50%;">
              <?php echo WPI_Functions::pretty_print_r($wpi_settings); ?>
            </div>
          </td>
        </tr>
 
      </table>
  
  <?php }
  
  
  
  
  
  } /* end class WPI_Settings_page */ ?>
      