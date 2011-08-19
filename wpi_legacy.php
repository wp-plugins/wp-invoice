<?php
/* Check previous vesrion ( < 3.0 ) data before plugin Init */
add_action('wpi_pre_init', array('WPI_Legacy', 'init'));

/*
 * Class for importing plugin's legacy data.
 * @since 3.0
 */
class WPI_Legacy{
  
  /*
   * Contains all legacy general settings data,
   * which is used in previous versions ( < 3.0 )
   * and stored in {$wpdb->prefix}options table 
   * 
   * @since 3.0
   */
  private static $settings = array(
    'wp_invoice_custom_label_tax',
    'wp_invoice_custom_zip_label',
    'wp_invoice_lookup_text',
    'wp_invoice_lookup_submit',
    'wp_invoice_using_godaddy',
    'wp_invoice_fe_state_selection',
    'wp_invoice_email_address',
    'wp_invoice_business_name',
    'wp_invoice_business_address',
    'wp_invoice_show_business_address',
    'wp_invoice_user_level',
    'wp_invoice_web_invoice_page',
    'wp_invoice_where_to_display',
    'wp_invoice_moneybookers_address',
    'wp_invoice_googlecheckout_address',
    'wp_invoice_reminder_message',
    'wp_invoice_show_quantities',
    'wp_invoice_use_css',
    'wp_invoice_force_https',
    'wp_invoice_send_thank_you_email',
    'wp_invoice_business_phone',
    'wp_invoice_welcome_line',
    'wp_invoice_exclude_ips',
    'wp_invoice_use_recurring',
    'wp_invoice_cc_thank_you_email',
    'wp_invoice_client_change_payment_method',
    'wp_invoice_default_currency_code',
    'wp_invoice_payment_method',
    'wp_invoice_payment_method',
    'wp_invoice_paypal_allow',
    'wp_invoice_paypal_address',
    'wp_invoice_fe_paypal_link_url',
    'wp_invoice_cc_allow',
    'wp_invoice_gateway_username',
    'wp_invoice_gateway_tran_key',
    'wp_invoice_gateway_delim_char',
    'wp_invoice_gateway_encap_char',
    'wp_invoice_gateway_merchant_email',
    'wp_invoice_recurring_gateway_url',
    'wp_invoice_gateway_url',
    'wp_invoice_gateway_MD5Hash',
    'wp_invoice_gateway_test_mode',
    'wp_invoice_gateway_delim_data',
    'wp_invoice_gateway_relay_response',
    'wp_invoice_gateway_email_customer',
    'wp_invoice_email_send_invoice_subject',
    'wp_invoice_email_send_invoice_content',
    'wp_invoice_email_send_reminder_subject',
    'wp_invoice_email_send_reminder_content',
    'wp_invoice_email_send_receipt_subject',
    'wp_invoice_email_send_receipt_content'
  );
  
  /**
   * Checks version and calls data migration
   * @since 3.0
   *
   */
  function init() {
    if(self::legacy_version_exist()) {
      self::do_import();
    }
  }
  
  /**
   * Imports all legacy data and cleans up storage
   * @since 3.0
   *
   */
  function do_import () {
    global $wpdb, $wpi_settings;
    
    /* Get plugin Singleton object */
    $core = WPI_Core::getInstance();
    
    /* Try to import General Plugin Settings from old version */
    $legacy_settings = self::get_legacy_settings();
    if(!empty($legacy_settings)) {
      $core->Settings->SaveSettings($legacy_settings);
      self::clean_up('settings');
      $core->Functions->log("Global WP-Invoice Settings were successfully migrated to the new version.");
    }
    
    /* Creates schema tables if it doesn't exist. */
    $core->Functions->create_new_schema_tables();
    
    /* Boolean variables which show 'legacy logs' data migration's status */
    $legacy_logs = false;
    $legacy_logs_import_error = false;
    
    /* Try to import Invoices from old version */
    $legacy_invoices = self::get_legacy_invoices();
    if(is_array($legacy_invoices) && !empty($legacy_invoices)) {
      $errors = false;
      foreach($legacy_invoices as $i) {
        $invoice_id = $core->Functions->save_invoice($i, array('type' => 'import'));
        if($invoice_id) {
          
          /* Try to get Logs of Invoices from the old version */
          $logs = self::get_legacy_logs_by_id($invoice_id);
          
          if(!empty($logs)) {
            /* Imports logs to new table. */
            if( self::import_logs($logs) ) {
              $legacy_logs = true;
            } else {
              $legacy_logs_import_error = true;
            }
          }
          
          /* If invoice has 'paid' status we should add log of payment. */
          if($i['post_status'] == 'paid') {
            $invoice = new WPI_Invoice();
            $invoice->load_invoice("id=$invoice_id");
            
            if($i['recurring']['active'] == 'on' && !empty($i['recurring']['cycles'])) {
              $event_amount = $i['amount'] * $i['recurring']['cycles'];
            } else {
              $event_amount = $i['amount'];
            }
            $event_note = "Automatically created using legacy data";
            $event_note = $core->Functions->currency_format(abs($event_amount), $invoice_id)." paid in - $event_note";
            $timestamp = time();
            
            $invoice->add_entry("attribute=balance&note=$event_note&amount=$event_amount&type=add_payment&time=$timestamp");
            $invoice->save_invoice();
          }
        } else {
          $errors = true;
        }
      }
      self::clean_up('invoices');
      if(!$errors) {
        $core->Functions->log("Invoices were migrated to the new version with errors.");
      } else {
        $core->Functions->log("Invoices were successfully migrated to the new version.");
      }
    }
    
    if($legacy_logs) {
      /* Clean up Database. */
      self::clean_up('logs');
      if($legacy_logs_import_error) {
        $core->Functions->log("Log data were successfully migrated to the new version.");
      } else {
        $core->Functions->log("Log data were migrated to the new version with errors.");
      }
    }
    
    /* Set plugin to the latest version */
    update_option('wp_invoice_version', $core->version);
  }
  
  /**
   *
   * @since 3.0
   *
   */
  function legacy_version_exist () {
    global $wpdb, $wpi_settings;
    
    $version = get_option('wp_invoice_version');
    
    if ((int)$version < 3) {
      WPI_Functions::log("Old WP-Invoice version {$version} was found.");
      return true;
    }
    
    return false;
  }
  
  /**
   * Get all legacy general settings
   * @since 3.0
   *
   */
  function get_legacy_settings() {
    $data = array();
    
    $option = get_option('wp_invoice_custom_label_tax');
    if($option !== false) {
      $data['custom_label_tax'] = $option;
    }
    
    $option = get_option('wp_invoice_lookup_text');
    if($option !== false) {
      $data['lookup_text'] = $option;
    }
    
    $option = get_option('wp_invoice_lookup_submit');
    if($option !== false) {
      $data['lookup_submit'] = $option;
    }
    
    $option = get_option('wp_invoice_using_godaddy');
    if($option !== false) {
      $data['using_godaddy'] = $option;
    }
    
    $option = get_option('wp_invoice_fe_state_selection');
    if($option !== false) {
      $data['state_selection'] = $option;
    }
    
    $option = get_option('wp_invoice_email_address');
    if($option !== false) {
      $data['email_address'] = $option;
    }
    
    $option = get_option('wp_invoice_business_name');
    if($option !== false) {
      $data['business_name'] = $option;
    }
    
    $option = get_option('wp_invoice_business_address');
    if($option !== false) {
      $data['business_address'] = $option;
    }
    
    $option = get_option('wp_invoice_show_business_address');
    if($option !== false) {
      $data['globals']['show_business_address'] = ($option == 'yes') ? 'true' : 'false';
    }
    
    $option = get_option('wp_invoice_show_quantities');
    if($option !== false) {
      $data['globals']['show_quantities'] = (strtolower($option) == 'show') ? 'true' : 'false';
    }
    
    $option = get_option('wp_invoice_user_level');
    if($option !== false) {
      $data['user_level'] = str_replace('level_', '', $option);
    }
    
    $option = get_option('wp_invoice_web_invoice_page');
    if($option !== false) {
      $data['web_invoice_page'] = $option;
    }
    
    $option = get_option('wp_invoice_where_to_display');
    if($option !== false) {
      $data['where_to_display'] = $option;
    }
    
    $option = get_option('wp_invoice_use_css');
    if($option !== false) {
      $data['use_css'] = $option;
    }
    
    $option = get_option('wp_invoice_use_css');
    if($option !== false) {
      $data['use_css'] = $option;
    }
    
    $option = get_option('wp_invoice_force_https');
    if($option !== false) {
      $data['force_https'] = $option;
    }
    
    $option = get_option('wp_invoice_send_thank_you_email');
    if($option !== false) {
      $data['send_thank_you_email'] = ($option == 'yes') ? 'true' : 'false';
    }
    
    $option = get_option('wp_invoice_business_phone');
    if($option !== false) {
      $data['business_phone'] = $option;
    }
    
    $option = get_option('wp_invoice_cc_thank_you_email');
    if($option !== false) {
      $data['cc_thank_you_email'] = ($option == 'yes') ? 'true' : 'false';
    }
    
    $option = get_option('wp_invoice_client_change_payment_method');
    if($option !== false) {
      $data['client_change_payment_method'] = $option;
    }
    
    $option = get_option('wp_invoice_default_currency_code');
    if($option !== false) {
      $data['currency']['default_currency_code'] = $option;
    }
    
    /* Get Billing data */
    
    $option = get_option('wp_invoice_payment_method');
    if($option !== false) {
      $data['billing']['wpi_authorize']['default_option'] = ($option == 'cc') ? 'true' : '';
      $data['billing']['wpi_paypal']['default_option'] = ($option == 'paypal') ? 'true' : '';
    }
    
    $option = get_option('wp_invoice_paypal_allow');
    if($option !== false) {
      $data['billing']['wpi_paypal']['allow'] = ($option == 'yes') ? 'true' : 'false';
    }
    
    $option = get_option('wp_invoice_paypal_address');
    if($option !== false) {
      $data['billing']['wpi_paypal']['settings']['paypal_address']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_fe_paypal_link_url');
    if($option !== false) {
      $data['billing']['wpi_paypal']['settings']['button_url']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_cc_allow');
    if($option !== false) {
      $data['billing']['wpi_authorize']['allow'] = ($option == 'yes') ? 'true' : 'false';
    }
    
    $option = get_option('wp_invoice_gateway_username');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_username']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_tran_key');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_tran_key']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_delim_char');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_delim_char']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_encap_char');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_encap_char']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_merchant_email');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_merchant_email']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_recurring_gateway_url');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['recurring_gateway_url']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_url');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_url']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_MD5Hash');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_MD5Hash']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_test_mode');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_test_mode']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_delim_data');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_delim_data']['value'] = $option;
    }
    
    $option = get_option('wp_invoice_gateway_email_customer');
    if($option !== false) {
      $data['billing']['wpi_authorize']['settings']['gateway_email_customer']['value'] = $option;
    }
    
    /* Get Notification E-Mail Templates */
    $option = get_option('wp_invoice_email_send_invoice_subject');
    if($option !== false) {
      $data['notification'][1]['name'] = 'Invoice Notification';
      $data['notification'][1]['subject'] = $option;
      $data['notification'][1]['content'] = get_option('wp_invoice_email_send_invoice_content', '');
    }
    
    $option = get_option('wp_invoice_email_send_reminder_subject');
    if($option !== false) {
      $data['notification'][2]['name'] = 'Reminder';
      $data['notification'][2]['subject'] = $option;
      $data['notification'][2]['content'] = get_option('wp_invoice_email_send_reminder_content', '');
    }
    
    $option = get_option('wp_invoice_email_send_receipt_subject');
    if($option !== false) {
      $data['notification'][3]['name'] = 'Receipt';
      $data['notification'][3]['subject'] = $option;
      $data['notification'][3]['content'] = get_option('wp_invoice_email_send_receipt_content', '');
    }
    
    return $data;
  }
  
  /**
   * Get all legacy invoices
   * @since 3.0
   *
   */
  function get_legacy_invoices() {
    global $wpdb, $wpi_settings;
    
    $data = array();
    
    /* Determine if 'invoice_main' table exist and get invoices */
    $table_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}invoice_main'");
    if($table_exist) {
      $invoices = $wpdb->get_results("
        SELECT *
        FROM `{$wpdb->prefix}invoice_main`
      ", ARRAY_A);
    }
    
    /* Determine if invoices exist */
    if(empty($invoices)) {
      return $data;
    }
    
    /* Determine if 'invoice_meta' table exist and get invoice's meta data */
    $table_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}invoice_meta'");
    if($table_exist) {
      foreach ($invoices as $key => $invoice) {
        $meta = $wpdb->get_results("
          SELECT *
          FROM `{$wpdb->prefix}invoice_meta`
          WHERE `invoice_id` = '{$invoice['invoice_num']}'
        ", ARRAY_A);
        if(!empty($meta)) {
          foreach($meta as $m) {
            $invoices[$key][$m['meta_key']] = $m['meta_value'];
          }
        }
      }
    }
    
    /* Create data for migration */
    foreach ($invoices as $invoice) {
      /* Subject is the neccessary attribute. */
      if(empty($invoice['subject'])) {
        continue;
      }
        
      /* Set values */
      $i = array(
        'ID' => '0',
        'subject' => $invoice['subject'],
        'description' => $invoice['description'],
        'invoice_id' => $invoice['invoice_num'],
        'amount' => $invoice['amount'],
        'deposit' => 'off',
        'deposit_amount' => '',
        'due_date_month' => $invoice['wp_invoice_due_date_month'],
        'due_date_day' => $invoice['wp_invoice_due_date_day'],
        'due_date_year' => $invoice['wp_invoice_due_date_year'],
        'recurring' => array(
          'active' => ( $invoice['recurring_billing'] == '1' ? 'on' : 'off'),
          'length' => $invoice['wp_invoice_subscription_length'],
          'unit' => $invoice['wp_invoice_subscription_unit'],
          'cycles' => $invoice['wp_invoice_subscription_total_occurances'],
          'start_date' => array(
            'month' => $invoice['wp_invoice_subscription_start_month'],
            'day' => $invoice['wp_invoice_subscription_start_day'],
            'year' => $invoice['wp_invoice_subscription_start_year']
          )
        ),
        'meta' => array(
          'custom_id' => $invoice['wp_invoice_custom_invoice_id'],
          'tax' => $invoice['wp_invoice_tax'],
          'discount' => array(
            1 => array(
              'name' => '',
              'type' => 'amount',
              'amount' => '',
            )
          )
        ),
        'default_currency_code' => $invoice['wp_invoice_currency_code'],
        'client_change_payment_method' => ( $invoice['wp_invoice_client_change_payment_method'] == '1' ? 'on' : 'off')
      );
      
      if (!empty($invoice['wp_invoice_subscription_start_month']) && 
          !empty($invoice['wp_invoice_subscription_start_day']) && 
          !empty($invoice['wp_invoice_subscription_start_year'])) {
        $i['recurring']['send_invoice_automatically'] = 'off';
      } else {
        $i['recurring']['send_invoice_automatically'] = 'on';
      }
      
      /* Set status */
      if(!empty($invoice['paid_status']) && $invoice['paid_status'] == 'paid') {
        $i['post_status'] = 'paid';
      } else if (!empty($invoice['archive_status']) && $invoice['archive_status'] == 'archived') {
        $i['post_status'] = 'archived';
      } else {
        $i['post_status'] = 'active';
      }
      
      /* Set Itemized List */
      if(!empty($invoice['itemized'])) {
        $itemized_list = unserialize(urldecode($invoice['itemized']));
        if (is_array($itemized_list) ) {
          foreach ($itemized_list as $key => $item) {
            $item['tax'] = !empty($invoice['wp_invoice_tax']) ? $invoice['wp_invoice_tax'] : '';
            $itemized_list[$key] = $item;
          }
        } else {
          $itemized_list = array();
        }
        
        $i['itemized_list'] = $itemized_list;
      }
      
      /* Try to get User Email */
      if(!empty($invoice['user_id'])) {
        $i['user_data']['user_email'] = $wpdb->get_var("
          SELECT `user_email`
          FROM {$wpdb->users}
          WHERE `ID` = '{$invoice['user_id']}'
        ");
      }
      /* User email is the neccessary attribute. 
       * If it's empty we will not do import to avoid the issues in future. 
       */
      if(empty($i['user_data']['user_email'])) {
        continue;
      }
      
      /* Set default payment method */
      if(!empty($invoice['wp_invoice_payment_method']) && $invoice['wp_invoice_payment_method'] == 'paypal') {
        $i['default_payment_method'] = 'wpi_paypal';
      } else if (!empty($invoice['wp_invoice_payment_method']) && $invoice['wp_invoice_payment_method'] == 'vv') {
        $i['default_payment_method'] = 'wpi_authorize';
      } else {
        $i['default_payment_method'] = '';
      }
      
      /* Set BILLING attributes */
      
      /* Authorize.net Gateway */
      $i['billing']['wpi_authorize'] = array(
        'allow' => ( $invoice['wp_invoice_cc_allow'] == 'yes' ? 'on' : 'off'),
        'default_option' => ( $i['default_payment_method'] == 'wpi_authorize' ? 'true' : ''),
        'settings' => array(
          'gateway_username' => array(
            'value' => ( !empty($invoice['wp_invoice_gateway_username']) ? 
              $invoice['wp_invoice_gateway_username'] : $wpi_settings['billing']['wpi_authorize']['settings']['gateway_username']['value'] )
          ),
          'gateway_tran_key' => array(
            'value' => ( !empty($invoice['wp_invoice_gateway_tran_key']) ? 
              $invoice['wp_invoice_gateway_tran_key'] : $wpi_settings['billing']['wpi_authorize']['settings']['gateway_tran_key']['value'] )
          ),
          'gateway_url' => array(
            'value' => ( !empty($invoice['wp_invoice_gateway_url']) ? 
              $invoice['wp_invoice_gateway_url'] : $wpi_settings['billing']['wpi_authorize']['settings']['gateway_url']['value'] )
          ),
          'recurring_gateway_url' => array(
            'value' => ( !empty($invoice['wp_invoice_recurring_gateway_url']) ? 
              $invoice['wp_invoice_recurring_gateway_url'] : $wpi_settings['billing']['wpi_authorize']['settings']['recurring_gateway_url']['value'] )
          ),
          'gateway_test_mode' => array(
            'value' => $wpi_settings['billing']['wpi_authorize']['settings']['gateway_test_mode']['value']
          ),
          'gateway_delim_char' => array(
            'value' => $wpi_settings['billing']['wpi_authorize']['settings']['gateway_delim_char']['value']
          ),
          'gateway_encap_char' => array(
            'value' => $wpi_settings['billing']['wpi_authorize']['settings']['gateway_encap_char']['value']
          ),
          'gateway_email_customer' => array(
            'value' => $wpi_settings['billing']['wpi_authorize']['settings']['gateway_email_customer']['value']
          ),
          'gateway_MD5Hash' => array(
            'value' => $wpi_settings['billing']['wpi_authorize']['settings']['gateway_MD5Hash']['value']
          ),
          'gateway_delim_data' => array(
            'value' => $wpi_settings['billing']['wpi_authorize']['settings']['gateway_delim_data']['value']
          ),
          'gateway_merchant_email' => array(
            'value' => $wpi_settings['billing']['wpi_authorize']['settings']['gateway_merchant_email']['value']
          )
        )
      );
      
      /* PayPal Gateway */
      $i['billing']['wpi_paypal'] = array(
        'allow' => ( $invoice['wp_invoice_paypal_allow'] == 'yes' ? 'on' : 'off'),
        'default_option' => ( $i['default_payment_method'] == 'wpi_paypal' ? 'true' : ''),
        'settings' => array(
          'paypal_address' => array(
            'value' => ( !empty($invoice['paypal_address']) ? 
              $invoice['paypal_address'] : $wpi_settings['billing']['wpi_paypal']['settings']['paypal_address']['value'] )
          ),
          'button_url' => array(
            'value' => $wpi_settings['billing']['wpi_paypal']['settings']['button_url']['value']
          )
        )
      );
      
      $data[] = $i;
    }
    
    return $data;
  }
  
  /**
   * Get legacy logs of the invoice by ID
   * @since 3.0
   *
   */
  function get_legacy_logs_by_id($ID) {
    global $wpdb;
    
    $data = array();
    
    /* Get invoice id */
    $invoice_id = $wpdb->get_var("
      SELECT meta_value
      FROM `{$wpdb->postmeta}` 
      WHERE meta_key = 'invoice_id' 
      AND post_id = '{$ID}'
    ");
    
    if(!empty($invoice_id)) {
      /* Determine if 'invoice_log' table exist and get logs */
      $table_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}invoice_log'");
      if($table_exist) {
        $logs = $wpdb->get_results("
          SELECT *
          FROM {$wpdb->prefix}invoice_log
          WHERE invoice_id = '{$invoice_id}'
        ", ARRAY_A);
        
        if(!empty($logs)) {
          foreach($logs as $log) {
            /* Set action type */
            $action = '';
            switch ($log['action_type']) {
              case 'created':
                $action = 'create';
                break;
              case 'updated':
                $action = 'update';
                break;
              case 'contact':
                $action = 'notification';
                break;
              default:
                $action = $log['action_type'];
                break;
            }
            
            $data[] = array(
              'object_id' => $ID,
              'user_id' => '0',
              'attribute' => 'invoice',
              'action' => $action,
              'value' => '',
              'text' => trim($log['value']) . ' (Imported Log from old WPI Version)',
              'time' => strtotime($log['time_stamp'])
            );
          }
        }
      }
    }
    
    return $data;
  }
  
  /**
   * Imports invoice's logs to database
   * @since 3.0
   *
   */
  function import_logs($logs) {
    global $wpdb;
    
    if(is_array($logs)) {
      $table = $wpdb->prefix . "wpi_object_log";
      foreach($logs as $log){
        $wpdb->insert($table, $log);
      }
      return true;
    }
    
    return false;
  }
  
  /**
   * Remove legacy (old) data from database
   * @since 3.0
   *
   */
  function clean_up($type = 'all') {
    global $wpdb;
    
    switch ($type) {
        case 'settings':
          foreach (self::$settings as $option) {
            delete_option($option);
          }
          break;
        case 'invoices':
          /* Determine if 'invoice_main' table exist we remove it */
          $table_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}invoice_main'");
          if($table_exist) {
            /*
             *  @TODO: For now, to avoid data missing on migration fail, we just rename old table.
             *  Then (in future versions) table should be dropped extend of renaming. Maxim Peshkov
             */
            //$wpdb->query("DROP TABLE {$wpdb->prefix}invoice_main");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}invoice_main RENAME TO {$wpdb->prefix}invoice_main_backup");
          }
          /* Determine if 'invoice_meta' table exist we remove it */
          $table_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}invoice_meta'");
          if($table_exist) {
            /*
             *  @TODO: For now, to avoid data missing on migration fail, we just rename old table.
             *  Then (in future versions) table should be dropped extend of renaming. Maxim Peshkov
             */
            //$wpdb->query("DROP TABLE {$wpdb->prefix}invoice_meta");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}invoice_meta RENAME TO {$wpdb->prefix}invoice_meta_backup");
          }
          break;
        case 'logs':
          /* Determine if 'invoice_log' table exist we remove it */
          $table_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}invoice_log'");
          if($table_exist) {
            /*
             *  @TODO: For now, to avoid data missing on migration fail, we just rename old table.
             *  Then (in future versions) table should be dropped extend of renaming. Maxim Peshkov
             */
            //$wpdb->query("DROP TABLE {$wpdb->prefix}invoice_log");
            $wpdb->query("ALTER TABLE {$wpdb->prefix}invoice_log RENAME TO {$wpdb->prefix}invoice_log_backup");
          }
          break;
        case 'all':
          /* Remove all legacy data */
          self::clean_up('settings');
          self::clean_up('invoices');
          self::clean_up('logs');
          break;
    }
  }

}