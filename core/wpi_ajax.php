<?php
class WPI_Ajax {



  /**
   * Check for availability of premium features and download them
   *
   * @since 3.01
   *
   */
  function check_plugin_updates() {
    echo WPI_Functions::check_for_premium_features(true);
  }



  /**
   * Return user data in JSON format
   *
   * @todo add hooks to accomodate different user values
   * @since 3.0
   *
   */
  function get_user_date($user_email = false) {
    global $wpdb;

      if(!$user_email) {
        return;
      }

      $user_id = email_exists($user_email);

      if(!$user_id) {
        return;
      }

      $user_data['first_name'] = get_user_meta($user_id, 'first_name', true);
      $user_data['last_name'] = get_user_meta($user_id, 'last_name', true);
      $user_data['company_name'] = get_user_meta($user_id, 'company_name', true);
      $user_data['phonenumber'] = get_user_meta($user_id, 'phonenumber', true);
      $user_data['streetaddress'] = get_user_meta($user_id, 'streetaddress', true);
      $user_data['city'] = get_user_meta($user_id, 'city', true);
      $user_data['state'] = get_user_meta($user_id, 'state', true);
      $user_data['zip'] = get_user_meta($user_id, 'zip', true);

      if($user_data) {
        echo json_encode(array('succes' => 'true', 'user_data' => $user_data));
      }

  }


  /**
   * Function for displaying WPI Data Table rows
   *
   * Ported from WP-CRM
   *
   * @since 3.0
   *
   */
  function wpi_list_table() {
    global $wpi_settings;

    include WPI_Path . '/core/ui/class_wpi_object_list_table.php';

    //** Get the paramters we care about */
    $sEcho = $_REQUEST['sEcho'];
    $per_page = $_REQUEST['iDisplayLength'];
    $iDisplayStart = $_REQUEST['iDisplayStart'];
    $iColumns = $_REQUEST['iColumns'];
    $sColumns = $_REQUEST['sColumns'];
    $order_by = $_REQUEST['iSortCol_0'];
    $sort_dir = $_REQUEST['sSortDir_0'];
    $current_screen = $wpi_settings['pages']['main'];

    //** Parse the serialized filters array */
    parse_str($_REQUEST['wpi_filter_vars'], $wpi_filter_vars);
    $wpi_search = $wpi_filter_vars['wpi_search'];

    $sColumns = explode("," , $sColumns);

    //* Init table object */
    $wp_list_table = new WPI_Object_List_Table(array(
      "ajax" => true,
      "per_page" => $per_page,
      "iDisplayStart" => $iDisplayStart,
      "iColumns" => $iColumns,
      "current_screen" => $current_screen
    ));

    if ( in_array( $sColumns[$order_by], $wp_list_table->get_sortable_columns() ) ) {
      $wpi_search['sorting'] = array(
        'order_by' => $sColumns[$order_by],
        'sort_dir' => $sort_dir
      );
    }

    $wp_list_table->prepare_items($wpi_search);

    if ( $wp_list_table->has_items() ) {
      foreach ( $wp_list_table->items as $count => $item ) {
        $data[] = $wp_list_table->single_row( $item );
      }
    } else {
      $data[] = $wp_list_table->no_items();
    }

    return json_encode(array(
      'sEcho' => $sEcho,
      'iTotalRecords' => count($wp_list_table->all_items),
      // @TODO: Why iTotalDisplayRecords has $wp_list_table->all_items value ? Maxim Peshkov
      'iTotalDisplayRecords' =>count($wp_list_table->all_items),
      'aaData' => $data
    ));
  }

  /**
   * Import legacy invoice data via ajax call
   *
   * @since 3.0
   *
   */
   static function update_wpi_option() {
    global $wpdb, $wpi_settings;

    $type = $_REQUEST['import_type'];

    if(WPI_Settings::setOption($_REQUEST['option'], $_REQUEST['value'], $_REQUEST['group'])) {
      $return['success'] = 'true';
    } else {
      $return['success'] = 'false';
    }

    die(json_encode($return));
  }


    /**
        Updates usermeta - mostly for updating screen options
    */
    function update_user_option() {
      global $user_ID;
      if(!isset($user_ID))
        die();
      $meta_key = $_REQUEST['meta_key'];
      $meta_value = $_REQUEST['meta_value'];
      if(empty($meta_value))
        $meta_value = false;
      update_user_option($user_ID, $meta_key, $meta_value, true);
      die();
    }

    /**
        Process special invoice-related event
    */
    function process_manual_event() {
      global $wpdb;
      
      $invoice_id   = $_REQUEST['invoice_id'];
      $event_type   = $_REQUEST['event_type'];
      $event_amount = $_REQUEST['event_amount'];
      $event_note   = $_REQUEST['event_note'];
      $event_date   = $_REQUEST['event_date'];
      $event_time   = $_REQUEST['event_time'];
      $event_tax    = $_REQUEST['event_tax'];
      $timestamp    = strtotime( $event_date.' '.$event_time );

      if(empty($event_note) || empty($event_amount) || !is_numeric($event_amount)) {
        die( json_encode( array('success' => 'false', 'message' => __('Please enter a note and numeric amount.', WPI)) ) );
      }
      
      if($event_type == 'add_payment' && !empty($event_amount)) {
        $event_amount = $event_amount;
        $event_note = WPI_Functions::currency_format(abs($event_amount), $invoice_id)." paid in - $event_note";
      }

      if($event_type == 'add_charge' && !empty($event_amount)) {
        $name = $event_note;
        $event_note = "".WPI_Functions::currency_format($event_amount, $invoice_id)." charge added - $event_note";
        $core = WPI_Core::getInstance();
        $charge_item = $core->Functions->add_itemized_charge( $invoice_id, $name, $event_amount, $event_tax );
      }

      if($event_type == 'do_adjustment' && !empty($event_amount)) {
        $event_note = WPI_Functions::currency_format($event_amount, $invoice_id)." adjusted - $event_note";
      }

      $invoice = new WPI_Invoice();
      $invoice->load_invoice("id=$invoice_id");
      $insert_id = $invoice->add_entry("attribute=balance&note=$event_note&amount=$event_amount&type=$event_type&time=$timestamp");

      if($insert_id) {
        $response = array( 'success' => 'true', 'message' => sprintf(__('Event Added: %1s.', WPI), $event_note));
      } else {
        $response = array( 'success' => 'false',  'message' =>  sprintf(__('Could not save entry in invoice log. %1s', WPI), ''));
      }
      
      $invoice->save_invoice();

      if ( !empty( $charge_item ) && $event_type == 'add_charge' ) {
        $response['charge_item'] = $charge_item;
      }

      die( json_encode( $response ) );
    }

    /**
     * Returns notification email based on pased values
     * 
     * @global object $wpdb
     * @global array $wpi_settings 
     */
    function get_notification_email() {
      global $wpdb, $wpi_settings;

      $template_id = intval($_REQUEST['template_id']);
      $invoice_id = intval($_REQUEST['wpi_invoiceid']);

      $invoice = get_invoice(wpi_invoice_id_to_post_id($invoice_id));
      $currency_symbol = (!empty($wpi_settings['currency']['symbol'][$invoice['default_currency_code']]) ? $wpi_settings['currency']['symbol'][$invoice['default_currency_code']] : "$");
      $invoice_id = (!empty($invoice['meta']['custom_id']) ? $invoice['meta']['custom_id'] : $invoice['invoice_id']);

      //** Get creator user data */
      $creator = get_userdata( $invoice['post_author'] );
      
      //** Due Date */
      $due_date = get_due_date( $invoice );
      $due_date = $due_date ? $due_date : __('Due date is not set');
      
      //** Load Templates */
      $template_array = apply_filters('wpi_email_templates', $wpi_settings['notification']);

      $ary['NotificationContent'] = $template_array[$template_id]['content'];
      
      //**
      // Tags which can be used in Content of notification email 
      //*/

      //** Invoice ID */
      $ary['NotificationContent'] = str_replace("%invoice_id%", $invoice_id, $ary['NotificationContent']);

      //** Format description */
      $desc = (!empty($invoice['post_content']) ? strip_tags( $invoice['post_content'] ) : "No description given.");
      $ary['NotificationContent'] = str_replace("%description%", $desc, $ary['NotificationContent']);

      //** Recipient name */
      $ary['NotificationContent'] = str_replace("%recipient%", $invoice['user_data']['display_name'], $ary['NotificationContent']);

      //** Invoice link */
      $ary['NotificationContent'] = str_replace("%link%", get_invoice_permalink($invoice['invoice_id']), $ary['NotificationContent']);

      //** Invoice balance */
      $ary['NotificationContent'] = str_replace("%amount%", $currency_symbol . wp_invoice_currency_format($invoice['net']), $ary['NotificationContent']);

      //** Invoice subject/title */
      $ary['NotificationContent'] = str_replace("%subject%", $invoice['post_title'], $ary['NotificationContent']);

      //** Business name according to business settings */
      $ary['NotificationContent'] = str_replace("%business_name%", $wpi_settings['business_name'], $ary['NotificationContent']);

      //** Business email according to business settings */
      $ary['NotificationContent'] = str_replace("%business_email%", $wpi_settings['email_address'], $ary['NotificationContent']);

      //** Invoice creator name */
      $ary['NotificationContent'] = str_replace("%creator_name%", $creator->display_name, $ary['NotificationContent']);

      //** Invoice creator email */
      $ary['NotificationContent'] = str_replace("%creator_email%", $creator->user_email, $ary['NotificationContent']);

      //** Invoice Due Date */ 
      $ary['NotificationContent'] = str_replace("%due_date%", $due_date, $ary['NotificationContent']);

      //** @todo: Recurring */
      $ary['NotificationContent'] = str_replace("%recurring%", '', $ary['NotificationContent']);

      $ary['NotificationSubject'] = $template_array[$template_id]['subject'];

      //**
      // Tags which can be used in Subject of notification email 
      //*/

      //** Invoice ID */
      $ary['NotificationSubject'] = str_replace("%invoice_id%",$invoice_id,$ary['NotificationSubject']);

      //** Recipients name */
      $ary['NotificationSubject'] = str_replace("%recipient%",$invoice['user_data']['display_name'],$ary['NotificationSubject']);

      //** Invoice balance */
      $ary['NotificationSubject'] = str_replace("%amount%",$invoice['net'],$ary['NotificationSubject']);

      //** Invoice subject/title */
      $ary['NotificationSubject'] = str_replace("%subject%",$invoice['post_title'],$ary['NotificationSubject']);

      $aryJson = array();
      $aryJson['wpi_content'] = $ary['NotificationContent'];
      $aryJson['wpi_subject'] = $ary['NotificationSubject'];

      die(json_encode($aryJson));
    }

  /**
   * This function sends our our notifications from the admin screen
   * @since 3.0
   */
  function send_notification(){
    //** Setup, and send our e-mail */
    $headers = "From: ".get_bloginfo()." <".get_bloginfo('admin_email').">\r\n";
    $message = html_entity_decode($_REQUEST['body'], ENT_QUOTES, 'UTF-8');
    $subject = html_entity_decode($_REQUEST['subject'], ENT_QUOTES, 'UTF-8');
    $to = $_REQUEST['to'];

    //** Validate for empty fields data */
    if(empty($to) || empty($subject) || empty($message)) {
      die(json_encode(array("status" => 500, "msg" => "The fields should not be empty. Please, check the fields data and try to send notification again.")));
    }

    if (wp_mail($to, $subject, $message, $headers)) {
      $pretty_time = date(get_option('time_format') . " " . get_option('date_format'));
      $text = "Notification Sent".(isset($_REQUEST['template']) && !empty($_REQUEST['template']) ? " (".$_REQUEST['template'].")" : "")." to {$to} at {$pretty_time}.";
      WPI_Functions::log_event(wpi_invoice_id_to_post_id($_REQUEST['invoice_id']), 'invoice', 'notification', '', $text, time());
      die(json_encode(array("status" => 200, "msg" => "Successfully sent the invoice notification!")));
    }
    die(json_encode(array("status" => 500, "msg" => "Unable to send the e-mail. Please, try again later.")));
  }

  /**
   * Save invoice from Ajax
   */
  function save_invoice() {
    $invoice_id = WPI_Functions::save_invoice($_REQUEST['wpi_invoice']);
    if ($invoice_id) {
      echo "Saved. <a target='_blank' href='" . get_invoice_permalink($invoice_id) . "'>View Invoice</a>. Invoice id #<span id='new_invoice_id'>$invoice_id</span>.";
    } else {
      echo "There was a problem with saving the invoice. Reference the log for troubleshooting.";
    }
    die();
  }

  /**
      Returns invoice status using the get_status function, then dies.
  */
  function show_invoice_status() {
    $invoice_id = intval($_REQUEST['invoice_id']);
    WPI_Functions::get_status(wpi_invoice_id_to_post_id($invoice_id));
    die();
  }

  function show_invoice_charges() {
    $invoice_id = intval($_REQUEST['invoice_id']);
    WPI_Functions::get_charges(wpi_invoice_id_to_post_id($invoice_id));
    die();
  }

  /**
  Used to save hidden columns.
  May not be necessary with newer version of WP
  */
  function wpi_columns() {
    global $user_ID;
    if(isset($_POST['columns'])) {
      $temp_columns  = explode(',', $_POST['columns']);
      foreach($temp_columns as $key => $value) {
        $settings['columns'][$value] = 'hidden';
      }
    }
    // save all settings to user settings
    update_user_meta($user_ID, $_POST['page'], $settings);
    echo 1;
    exit;
  }

  function payment_select(){
    global $invoice, $wpi_settings;

    $invoice = WPI_Invoice::load_invoice(array('id'=>$_POST['invoice'], 'return' => true));
    $invoice = (array)$invoice;
    $invoice['itemized'] = unserialize($invoice['itemized']);
    $invoice['meta'] = unserialize($invoice['meta']);
    $invoice['discount'] = unserialize($invoice['discount']);
    $invoice['log'] = unserialize($invoice['log']);
    $invoice['billing'] = unserialize($invoice['billing']);

    if($wpi_settings['use_custom_templates'] != 'yes' || !file_exists(TEMPLATEPATH.'/wpi/'.$_POST['slug'].'.php')){
      include realpath(__DIR__).'/template/payment_methods/'.$_POST['slug'].'.php';
    }else {
      include TEMPLATEPATH.'/wpi/'.$_POST['slug'].'.php';
    }
    die;
  }

  /**
   * This function prints out our invoice data for debugging purposes
   * @since 3.0
  */
  function debug_get_invoice(){
    global $wpi_settings;
    if(!isset($_REQUEST['invoice_id'])) die("Please enter an invoice id.");
    $this_invoice = new WPI_Invoice();
    $this_invoice->load_invoice("id=".$_REQUEST['invoice_id']);
    echo WPI_Functions::pretty_print_r($this_invoice->data);
    die();
  }

  function revalidate() {
    WPI_Functions::total_revalidate();
  }
}
