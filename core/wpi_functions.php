<?php
/**
 *
 * Handles all functions
 *
 */
setlocale(LC_MONETARY, 'en_US');

class WPI_Functions {


  /**
   * PHP function to echoing a message to JS console
   * Ported from WP-Property
   *
   * @since 3.0.3
   */
  function console_log($text = false) {
    global $wpi_settings;

    if($wpi_settings['developer_mode'] != 'true') {
      return;
    }

    if(empty($text)) {
      return;
    }

    if(is_array($text) || is_object($text)) {
      $text = str_replace("\n", '', print_r($text, true));
    }

    //** Cannot use quotes */
    $text = str_replace('"', '-', $text);

    add_filter('wp_footer', create_function('$nothing,$echo_text = "'. $text .'"', 'echo \'<script type="text/javascript">if(typeof console == "object"){console.log("\' . $echo_text . \'");}</script>\'; '));
    add_filter('admin_footer', create_function('$nothing,$echo_text = "'. $text .'"', 'echo \'<script type="text/javascript">if(typeof console == "object"){console.log("\' . $echo_text . \'");}</script>\'; '));

  }


  /**
   * Retreives API key from UD servers.
   *
   * @todo Add loggin functionality so user can reference some log to see why key may not be updating.
   * @since 3.0.2
   *
   */
  function get_api_key($args = false){

    $defaults = array(
      'return_all' => false,
      'force_check' => false
    );

    $args = wp_parse_args( $args, $defaults );

    //** check if API key already exists */
    $ud_api_key = get_option('ud_api_key');

    //** if key exists, and we are not focing a check, return what we have */
    if($ud_api_key && !$args['force_check']) {
      return $ud_api_key;
    }

    $blogname = urlencode(str_replace(array('http://', 'https://'), '', get_bloginfo('url')));
    $system = 'wpi';
    $wpp_version = WP_INVOICE_VERSION_NUM;

    $check_url = "http://updates.usabilitydynamics.com/key_generator.php?system=$system&site=$blogname&system_version=$wpp_version";

    $response = @wp_remote_get($check_url);

    if(!$response) {
      return false;
    }

    //** Check for errors */
    if(is_object($response) && !empty($response->errors)) {
      /*
      foreach($response->errors as $errors) {
        $error_string .= implode(",", $errors);
        UD_F::log("API Check Error: " . $error_string);
      }
      */
      return false;
    }

    //** Quit if failture */
    if($response['response']['code'] != '200') {
      return false;
    }

    $response['body'] = trim($response['body']);

    //** If return is not in MD5 format, it is an error */
    if(strlen($response['body']) != 40) {

      if($args['return']) {
        return $response['body'];
      } else {
        /* UD_F::log("API Check Error: " . sprintf(__('An error occured during premium feature check: <b>%s</b>.','wpp'), $response['body'])); */
        return false;
      }
    }

    //** update wpi_key is DB */
    update_option('ud_api_key', $response['body']);

    // Go ahead and return, it should just be the API key
    return $response['body'];
  }




  /**
   * Function for performing a wpi_object search
   *
   *
   * @todo This function is not ready at all, it doesn't do any searching, just returns all invoices for testing datatables
   * @since 3.0
   *
   */
  static function query($search_vars = false) {
    global $wpdb;

    $sort_by = " ORDER BY post_modified DESC ";
    /** Start our SQL */
    $sql = "SELECT * FROM {$wpdb->posts} AS p WHERE post_type = 'wpi_object' ";

    if (!empty($search_vars)) {

      if (is_string($search_vars)) {
        $args = array();
        parse_str($search_vars, $args);
        $search_vars = $args;
      }

      /*
        $use_status_filter = false;
        if ( !empty( $search_vars['status'] ) ) {
        $use_status_filter = true;
        }
       */
      foreach ($search_vars as $primary_key => $key_terms) {

        //** Handle search_string differently, it applies to all meta values */
        if ($primary_key == 's') {
          /* First, go through the posts table */
          $tofind = strtolower($key_terms);
          $sql .= " AND (";
          $sql .= " p.ID IN (SELECT ID FROM {$wpdb->posts} WHERE LOWER(post_title) LIKE '%$tofind%')";
          /* Now go through the post meta table */
          $sql .= " OR p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE LOWER(meta_value) LIKE '%$tofind%')";
          $sql .= ")";
          continue;
        }

        // Type
        if ($primary_key == 'type') {
          if (empty($key_terms)) {
            continue;
          }

          if (is_array($key_terms)) {
            $key_terms = implode("','", $key_terms);
          }
          $sql .= " AND ";
          $sql .= " p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'type' AND meta_value IN ('{$key_terms}'))";
          continue;
        }

        // Status
        if ($primary_key == 'status') {
          if (empty($key_terms)) {
            continue;
          }

          if (is_array($key_terms)) {
            $sql .= " AND (";
            $i = 0;
            foreach ($key_terms as $term) {
              if (empty($term)) {
                continue;
              }
              if ($i > 0) {
                $sql .= " OR ";
              }
              $sql .= " post_status = '{$term}' ";
              $i++;
            }
            $sql .= ")";
          }
        }
        /*
          if ( !$use_status_filter ) {
          $sql .= " AND ( post_status = 'active' ) ";
          }
         */
        // Recipient
        if ($primary_key == 'recipient') {
          if (empty($key_terms)) {
            continue;
          }

          $user = get_user_by('id', $key_terms);

          $sql .= " AND ";
          $sql .= " p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'user_email' AND meta_value = '{$user->user_email}')";

          continue;
        }

        // Sorting
        if ($primary_key == 'sorting') {
          $sort_by = " ORDER BY {$key_terms['order_by']} {$key_terms['sort_dir']} ";
        }

        /* Date */
        if ($primary_key == 'm') {
          if (empty($key_terms) || (int) $key_terms == 0) {
            continue;
          }

          $key_terms = '' . preg_replace('|[^0-9]|', '', $key_terms);
          $sql .= " AND YEAR(post_date)=" . substr($key_terms, 0, 4);
          if (strlen($key_terms) > 5) {
            $sql .= " AND MONTH(post_date)=" . substr($key_terms, 4, 2);
          }
          if (strlen($key_terms) > 7) {
            $sql .= " AND DAYOFMONTH(post_date)=" . substr($key_terms, 6, 2);
          }
          if (strlen($key_terms) > 9) {
            $sql .= " AND HOUR(post_date)=" . substr($key_terms, 8, 2);
          }
          if (strlen($key_terms) > 11) {
            $sql .= " AND MINUTE(post_date)=" . substr($key_terms, 10, 2);
          }
          if (strlen($key_terms) > 13) {
            $sql .= " AND SECOND(post_date)=" . substr($key_terms, 12, 2);
          }
        }
      }
    }

    $sql = $sql . $sort_by;
    //echo $sql;
    $results = $wpdb->get_results($sql);

    return $results;
  }

  /*
   * Get Search filter fields
   */

  function get_search_filters() {
    global $wpi_settings, $wpdb;

    $filters = array();

    $default = array(array(
            'key' => 'all',
            'label' => __('All'),
            'amount' => 0
            ));

    if (isset($wpi_settings['types'])) {
      $f = $default;
      $i = 1;
      $all = 0;
      foreach ($wpi_settings['types'] as $key => $value) {
        $amount = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'type' AND meta_value = '{$key}'");
        $all = $all + $amount;
        if ($amount > 0) {
          $f[$i]['key'] = $key;
          $f[$i]['label'] = $value['label'];
          $f[$i]['amount'] = $amount;
          $i++;
        }
      }
      if ($all > 0) {
        $f[0]['amount'] = $all;
        $filters['type'] = $f;
      }
      // If there is only 1 type - hide Types option
      if ($i == 2) {
        unset($filters['type']);
      }
    }

    if (!empty($wpi_settings['statuses'])) {
      $f = array();
      $amount = 0;
      $i = 1;
      $all = 0;
      foreach ($wpi_settings['statuses'] as $status) {
        $amount = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = '{$status}' AND post_type = 'wpi_object'");
        $all = $all + $amount;
        if ($amount > 0) {
          $f[$i]['key'] = $status;
          $f[$i]['label'] = strtoupper(substr($status, 0, 1)) . substr($status, 1);
          $f[$i]['amount'] = $amount;
          $i++;
        }
      }
      if ($all > 0) {
        $filters['status'] = $f;
      }
    }

    return $filters;
  }

  /**
   * Convert a string to a url-like slug
   *
   *
   * @since 3.0
   */
  function slug_to_label($slug = false) {

    if (!$slug)
      return;

    $slug = str_replace("_", " ", $slug);
    $slug = ucwords($slug);
    return $slug;
  }

  /**
   * Convert a string into a number. Allow invoice ID to be passed for currency symbol localization
   *
   * @since 3.0
   *
   */
  static function currency_format($amount, $invoice_id = false) {
    global $wpi_settings;

    if ($invoice_id) {
      $invoice = get_invoice($invoice_id);
    }

    $currency_symbol = !empty($wpi_settings['currency']['symbol'][$invoice['default_currency_code']]) ? $wpi_settings['currency']['symbol'][$invoice['default_currency_code']] : '$';

    $amount = (float) $amount;

    return $currency_symbol . number_format($amount, 2, '.', ',');
  }

  /**
   * Run numbers for reporting purposes.
   *
   *
   * @since 3.0
   *
   */
  static function run_reports() {
    global $wpdb, $wpi_reports;

    //* Get all invoices */
    $invoice_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wpi_object' ");

    $totals = array();
    $objects = array();
    $r = array(
        'line_item_counts' => array()
    );

    foreach ($invoice_ids as $post_id) {
      $wpi_invoice_object = new WPI_Invoice();
      $wpi_invoice_object->load_invoice("id=$post_id");
      $objects[$post_id] = $wpi_invoice_object->data;
    }

    foreach ($objects as $object) {

      //** Total paid invoices per client  */
      if ($object['post_status'] == 'paid') {
        $r['collected_client_value'][$object['user_email']] = !empty($r['client_value'][$object['user_email']]) ? $r['client_value'][$object['user_email']] : 0 + $object['subtotal'] + $object['total_tax'] - $object['total_discount'];
        $r['total_paid'][] = $objects[$object['ID']]['subtotal'] + $objects[$object['ID']]['total_tax'] - $objects[$object['ID']]['total_discount'];;

        foreach ($object['itemized_list'] as $list_item) {
          $r['collected_line_items'][$list_item['name']] = !empty($r['collected_line_items'][$list_item['name']]) ? $r['collected_line_items'][$list_item['name']] : 0 + $list_item['line_total_after_tax'];
          if (!empty($r['line_item_counts'][$list_item['name']])) {
            $r['line_item_counts'][$list_item['name']]++;
          } else {
            $r['line_item_counts'][$list_item['name']] = 1;
          }
        }
      }

      if ($object['post_status'] == 'active') {
        $r['uncollected_client_value'][$object['user_email']] = !empty($r['uncollected_client_value'][$object['user_email']]) ? $r['uncollected_client_value'][$object['user_email']] : 0 + $object['subtotal'] + $object['total_tax'] - $object['total_discount'];
        $r['total_unpaid'][] = $objects[$object['ID']]['subtotal'] + $objects[$object['ID']]['total_tax'] - $objects[$object['ID']]['total_discount'];
      }
      
    }

    if (isset($r['collected_line_items']) && is_array($r['collected_line_items'])) {
      arsort($r['collected_line_items']);
    }

    if (isset($r['uncollected_client_value']) && is_array($r['uncollected_client_value'])) {
      arsort($r['uncollected_client_value']);
    }

    if (isset($r['collected_client_value']) && is_array($r['collected_client_value'])) {
      arsort($r['collected_client_value']);
    }

    //** Get highest grossing clients */


    if (isset($r['total_paid']) && is_array($r['total_paid'])) {
      $r['total_paid'] = array_sum($r['total_paid']);
    }

    if (isset($r['total_unpaid']) && is_array($r['total_unpaid'])) {
      $r['total_unpaid'] = array_sum($r['total_unpaid']);
    }

    //echo "<pre>" . print_r($r, true) . "</pre>";
    $wpi_reports = $r;
    return $r;
  }

  /**
   * Check if theme-specific stylesheet exists.
   *
   * get_option('template') seems better choice than get_option('stylesheet'), which returns the current theme's slug
   * which is a problem when a child theme is used. We want the parent theme's slug.
   *
   * @since 3.0
   *
   */
  static function has_theme_specific_stylesheet() {

    $theme_slug = get_option('template');

    if (file_exists(WPI_Path . "/core/template/theme-specific/{$theme_slug}.css")) {
      return true;
    }

    return false;
  }

  /**
   * Check if Payment method allowed
   * @param String $param
   * @return bool
   */
  function is_true($param) {
    if (empty($param))
      return false;
    return ( $param == 'true' || $param == 'on' || $param == 'yes' ) ? true : false;
  }

  /**
   * Fixes billing structure
   * @param array $wpi_settings_billings
   * @param array &$invoice_billings
   */
  function merge_billings($wpi_settings_billings, $invoice_billings) {
    if (!isset($invoice_billings) || !is_array($invoice_billings)) {
      $invoice_billings = array();
    }
    if (is_array($wpi_settings_billings)) {
      foreach ($wpi_settings_billings as $key => $value) {
        // TODO: Refactor on|yes|true off|no|false
        // WPI_Functions::is_true() used temporary
        if (!WPI_Functions::is_true($value['allow'])) {
          unset($invoice_billings[$key]);
        } else {
          if (!empty($invoice_billings[$key])) {
            if (!isset($invoice_billings[$key]['name'])) {
              $invoice_billings[$key]['name'] = $value['name'];
            }
            if (!isset($invoice_billings[$key]['allow'])) {
              $invoice_billings[$key]['allow'] = $value['allow'];
            }
            if (!isset($invoice_billings[$key]['default_option'])) {
              $invoice_billings[$key]['default_option'] = $value['default_option'];
            }
            if (!empty($value['settings'])) {
              foreach ($value['settings'] as $setting_key => $setting_value) {
                foreach ($setting_value as $setting_key_field => $setting_value_field) {
                  if (!isset($invoice_billings[$key]['settings'][$setting_key][$setting_key_field])) {
                    $invoice_billings[$key]['settings'][$setting_key][$setting_key_field] = $setting_value_field;
                  }
                }
              }
            }
          } else {
            $invoice_billings[$key] = $value;
          }
        }
      }
    }
  }

  function set_default_payment_method($wpi_settings_billings, $invoice_data) {
    $settings_dpm = '';

    if (!empty($wpi_settings_billings) && is_array($wpi_settings_billings)) {
      foreach ($wpi_settings_billings as $method => $value) {
        if ($value['default_option'] == 'true') {
          $settings_dpm = $method;
        }
      }
    }

    $invoice_data['default_payment_method'] = $settings_dpm;
  }

  /**
   * Returns an array of users
   * Used for user-email auto-completion.
   * @uses $wpdb
   * @since 3.0
   * @return array. Users List
   *
   */
  function build_user_array() {
    global $wpdb;

    return $wpdb->get_results("SELECT display_name,user_email,ID FROM {$wpdb->prefix}users", ARRAY_A);
  }

  /**
   * Handle user data updating
   *
   * Typically called when saving an invoice.
   * @since 3.0
   */
  function update_user($userdata) {

    $user_id = email_exists($userdata['user_email']);

    if ($user_id) {
      $userdata['ID'] = $user_id;
    }

    if (empty($userdata['ID']) && empty($userdata['user_email'])) {
      return false;
    }

    if ($user_id) {
      $user_id = wp_update_user($userdata);
    } else {
      if (empty($userdata['user_login'])) {
        if (!empty($userdata['first_name']) && !empty($userdata['last_name'])) {
          $userdata['display_name'] = $userdata['first_name'] . ' ' . $userdata['last_name'];
        } else {

        }
      }

      $userdata['user_login'] = $userdata['user_email'];

      if(empty($userdata['user_pass'])) {
        $userdata['user_pass'] = wp_generate_password( 12, false);
      }

      $user_id = wp_insert_user($userdata);
    }

    // Prevent entering of wrong phone number to avoid errors on front-end
    if (!preg_match('/\A[\d.+?]{0,3}-[\d.+?]{0,3}-[\d.+?]{0,4}\Z/si', $userdata['phonenumber'])) {
      if (preg_match('/\A[\d.+?]{0,10}\Z/si', $userdata['phonenumber'])) {
        $phonenumber = $userdata['phonenumber'];
        $userdata['phonenumber'] = substr($phonenumber, 0, 3) . '-' . substr($phonenumber, 3, 3) . '-' . substr($phonenumber, 6, 4);
      } else {
        $userdata['phonenumber'] = '';
      }
    }

    if (!is_object($user_id) && $user_id > 0) {
      /* Update user's meta data */
      $non_meta_data = array(
          'ID',
          'first_name',
          'last_name',
          'nickname',
          'description',
          'user_pass',
          'user_email',
          'user_url',
          'user_nicename',
          'display_name',
          'user_registered',
          'role'
      );
      foreach ($userdata as $key => $value) {
        if (!in_array($key, $non_meta_data)) {
          update_user_meta($user_id, $key, $value);
        }
      }

      return $user_id;
    }

    return $user_id;
  }

  /**
   * Add itemized charge like itemized list item
   *
   * @param int $invoice_id
   * @param string $name
   * @param float $amount
   * @param float $tax
   * @return array
   */
  function add_itemized_charge($invoice_id, $name, $amount, $tax) {

    $post_id = wpi_invoice_id_to_post_id($invoice_id);
    $charge_items = get_post_meta($post_id, 'itemized_charges', true);

    $new_item = array(
        'name' => $name,
        'amount' => $amount,
        'tax' => $tax,
        'before_tax' => $amount,
        'after_tax' => $amount + ($amount / 100 * $tax)
    );

    if (!empty($charge_items)) {
      $charge_items[] = $new_item;
    } else {
      $charge_items[0] = $new_item;
    }

    update_post_meta($post_id, 'itemized_charges', $charge_items);

    return end($charge_items);
  }

  /**
   * Loads invoice variables into post if it is a wpi_object
   *
   * @hooked_into setup_postdata()
   * @uses $wpdb
   * @since 3.0
   *
   */
  function the_post(&$post) {
    global $post;

    if ($post->post_type == 'wpi_object') {
      $this_invoice = new WPI_Invoice();
      $invoice_id = $post->ID;
      $this_invoice->load_invoice("id=$invoice_id");

      $t_post = (array) $post;
      $t_data = (array) $this_invoice->data;

      $t_post = WPI_Functions::array_merge_recursive_distinct($t_post, $t_data);
      $post = (object) $t_post;
    }
  }

  function objectToArray($object) {
    if (!is_object($object) && !is_array($object)) {
      return $object;
    }
    if (is_object($object)) {
      $object = get_object_vars($object);
    }
    return array_map(array('WPI_functions', 'objectToArray'), $object);
  }

  /**
    Generates a slug
   */
  function generateSlug($title) {
    $slug = preg_replace("/[^a-zA-Z0-9 ]/", "", $title);
    $slug = str_replace(" ", "_", $slug);
    return $slug;
  }

  /**
   * Figure out current page for front-end AJAX function
   */
  function current_page() {
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
      $pageURL .= "s";
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
      $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
  }

  /**
    Get users paid and pending invoices
   */
  function get_user_invoices($args) {
    global $wpdb;

    $defaults = array('user_id' => false, 'status' => false);
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);

    // User email and id are the same thing
    if (!$user_id && isset($user_email))
      $user_id = $user_email;

    // If nothing is set, nothing we can do
    if (!isset($user_id))
      return;

    $users_invoices = $wpdb->get_col("
          SELECT post_id
          FROM {$wpdb->postmeta} postmeta
          JOIN {$wpdb->posts} posts ON posts.ID = postmeta.post_id
          WHERE postmeta.meta_key = 'user_email'
            AND postmeta.meta_value = '" . $user_id . "'
            AND posts.post_type = 'wpi_object'
            AND posts.post_status = '$status'
        ");

    // Nothing found
    if (!is_array($users_invoices))
      return false;

    $return = array();

    foreach ($users_invoices as $post_id) {

      $invoice_id = wpi_post_id_to_invoice_id($post_id);

      $this_invoice = new WPI_Invoice();
      $this_invoice->load_invoice("id={$invoice_id}");

      if (!empty($status) && $status != $this_invoice->data['post_status'])
        continue;

      // Do not include quotes
      if ($this_invoice->data['type'] != 'invoice') {
        continue;
      }

      $return[] = $this_invoice;
    }

    return $return;
  }

  /**
    Add message to notice queve
   */
  function add_message($message, $type = 'good', $class = '') {
    global $wpi_messages;
    if (!is_array($wpi_messages))
      $wpi_messages = array();

    array_push($wpi_messages, array('message' => $message, 'type' => $type, 'class' => $class));
  }

  /**
    Display messages in queve
   */
  function print_messages() {
    global $wpi_messages;

    if (count($wpi_messages) < 1)
      return;

    $update_messages = array();
    $warning_messages = array();

    echo "<div id='wpi_message_stack'>";

    foreach ($wpi_messages as $message) {

      if ($message['type'] == 'good') {
        array_push($update_messages, array('message' => $message['message'], 'class' => $message['class']));
      }

      if ($message['type'] == 'bad') {
        array_push($warning_messages, array('message' => $message['message'], 'class' => $message['class']));
      }
    }

    if (count($update_messages) > 0) {
      echo "<div class='wpi_message wpi_yellow_notification'>";
      foreach ($update_messages as $u_message)
        echo "<div class='wpi_message_holder {$message['class']}' >{$u_message['message']}</div>";
      echo "</div>";
    }

    if (count($warning_messages) > 0) {
      echo "<div class='wpi_message wpi_red_notification'>";
      foreach ($warning_messages as $w_message)
        echo "<div class='wpi_message_holder {$w_message['class']}' >{$w_message['message']}</div>";
      echo "</div>";
    }

    echo "</div>";
  }

  /**
    Install custom templates in /wpi/ folder by copying them from core/templates folder
   */
  function install_templates() {
    global $wpi_settings;

    $custom_template_path = TEMPLATEPATH . "/wpi";
    $original_template_path = dirname(__FILE__) . "/template";

    if (!is_dir($custom_template_path)) {
      if (!@mkdir($custom_template_path))
        WPI_Functions::add_message(__("Unable to create 'wpi' folder in template folder. ", WPI), 'bad');
      return false;
    }

    $files_copied = 0;
    if ($dir = @opendir($original_template_path)) {
      while (($file = readdir($dir)) !== false) {
        unset($info);
        $info = pathinfo($file);
        if ($info['extension'] == 'php') {
          if (@copy($original_template_path . "/" . $file, "$custom_template_path/$file"))
            $files_copied++;
        }
      }
      closedir($dir);
    } else {
      WPI_Functions::add_message(__("Unable to open 'wpi' folder in template folder.", WPI). $original_template_path, 'bad');
      return false;
    }

    if ((intval($files_copied)) != 0) {
      WPI_Functions::add_message(sprintf(__("Success, (%s) template file(s) copied.", WPI), $files_copied));
      return true;
    } else {
      WPI_Functions::add_message(__("No template files copied.", WPI), 'bad');
      return false;
    }
  }

  /**
   * Checks if particular template exists in the template folder
   *
   * @TODO: the method should be revised. Maxim Peshkov
   */
  function wpi_use_custom_template($template) {
    global $wpi_settings;

    /* if custom templates are turned off, don't bother checking */
    if (!isset($wpi_settings['use_custom_templates']) || $wpi_settings['use_custom_templates'] != 'yes') {
      return false;
    }

    if (file_exists($wpi_settings['frontend_template_path'] . "$template")) {
      return true;
    }

    /* @TODO: So, what should the function return here? Check it. Maxim Peshkov. */
  }

  /**
    Determine WPI front-end template path
   */
  function template_path() {
    global $wpi_settings;
    $use_custom_templates = false;

    if (file_exists(TEMPLATEPATH . "/wpi/")) {
      return TEMPLATEPATH . "/wpi/";
    }


    // "/core/ui/frontend/"
  }

  /**
    Display invoice status formatted for back-end
   */
  function get_status($invoice_id) {

    $this_invoice = new WPI_Invoice();
    $this_invoice->load_invoice("id=$invoice_id");    

    if (is_array($this_invoice->data['log'])) {
      foreach (array_reverse($this_invoice->data['log']) as $event) {

        if (empty($event['text'])) {
          continue;
        }

        ?>
        <tr class="wpi_event_<?php echo $event['action']; ?> <?php if ($event['action'] == 'add_charge' || $event['action'] == 'do_adjustment')
          echo "wpi_not_for_recurring"; ?>">
          <th><?php echo date(get_option('time_format') . ' ' . get_option('date_format'), $event['time']); ?> </th>
          <td><?php echo $event['text']; ?></td>
        </tr>
        <?php
      }
    } else {
      ?>
      <tr class="wpi_event_error">
        <th colspan='2'>No log entries.</th>
      </tr>

      <?php
    }
  }

  /**
   * Render itemized charges list
   *
   * @param int $post_id
   */
  function get_charges($post_id) {

    $charges_list = get_post_meta($post_id, 'itemized_charges', true);

    $result = '';

    ob_start();

    if (!empty($charges_list)) {
      foreach ($charges_list as $key => $value) {
        ?>
        <li class="wp_invoice_itemized_charge_row clearfix" id="wp_invoice_itemized_charge_row_<?php echo $key; ?>">
          <span class="id hidden"><?php echo $key; ?></span>

          <div class="flexible_width_holder">
            <div class="flexible_width_holder_content">
              <span class="row_delete">&nbsp;</span>
              <input class="item_name input_field" name="wpi_invoice[itemized_charges][<?php echo $key; ?>][name]" value="<?php echo stripslashes($value['name']); ?>" />
            </div>
          </div>

          <span class="fixed_width_holder">
            <span class="row_amount">
              <input autocomplete="off" value="<?php echo stripslashes($value['amount']); ?>" name="wpi_invoice[itemized_charges][<?php echo $key; ?>][amount]" id="amount_item_<?php echo $key; ?>"  class="item_amount input_field">
            </span>
            <span class="row_charge_tax">
              <input autocomplete="off" value="<?php echo stripslashes($value['tax']); ?>"  name="wpi_invoice[itemized_charges][<?php echo $key; ?>][tax]" id="charge_tax_item_<?php echo $key; ?>"  class="item_charge_tax input_field">
            </span>
            <span class="row_total" id="total_item_<?php echo $key; ?>" ><?php echo $value['after_tax']; ?></span>
          </span>

        </li>
        <?php
      }

      $result .= ob_get_contents();
      ob_end_clean();
    }

    echo $result;
  }

  /**
   * Returns highest invoice ID
   * 
   * @global object $wpdb
   * @return longint 
   * @author korotkov@ud
   */
  function get_highest_custom_id() {
    global $wpdb;

    $invoices = get_posts(
      array(
        'post_type' => 'wpi_object',
        'numberposts' => 0,
        'post_status' => 'any'
      )
    );

    if (!count($invoices)) {
      return false;
    }

    $id_array = array();

    foreach ($invoices as $invoice) {
      $id_array[] = get_post_meta($invoice->ID, 'invoice_id', true);
      /** Get custom IDs too */
      $custom_id = get_post_meta($invoice->ID, 'custom_id', true);
      if ( $custom_id ) {
        $id_array[] = $custom_id;
      }
    }

    return @max($id_array);
  }

  /**
   * Removes empty values from array
   * 
   * @param array $array
   * @return array
   * @author korotkov@ud
   */
  function remove_blank_values($array) {
    if (!is_array($array))
      return false;
    foreach ($array as $key => $value) {
      if (!empty($value))
        $return[$key] = $value;
    }
    return $return;
  }

  /**
   * Run when a plugin is being activated
   * Handles the task of migrating from old version of WPI to new
   */
  function Activate() {
    global $wpdb, $wpi_settings;

    /* Setup WPI schedule to handle recurring invoices */
    wp_schedule_event(time(), 'hourly', 'wpi_hourly_event');
    /* Scheduling daily update event */
    wp_schedule_event(time(), 'daily', 'wpi_update');

    WPI_Functions::log(__("Schedule created with plugin activation.", WPI));

    /* Try to create new schema tables */
    WPI_Functions::create_new_schema_tables();

    /* Get previous activated version */
    $current_version = get_option('wp_invoice_version');

    /* If no version found at all, we do new install */
    if ($current_version == WP_INVOICE_VERSION_NUM) {
      WPI_Functions::log(__("Plugin activated. No older versions found, installing version ", WPI) . WP_INVOICE_VERSION_NUM . ".");
    } else if ((int) $current_version < 3) {
      /* Determine if legacy data exist */
      WPI_Legacy::init();
      WPI_Functions::log(__("Plugin activated.", WPI));
    }

    /* Update version */
    update_option('wp_invoice_version', WP_INVOICE_VERSION_NUM);

		WPI_Functions::check_for_premium_features();
  }

  function Deactivate() {
    wp_clear_scheduled_hook('wpi_hourly_event');
    wp_clear_scheduled_hook('wpi_update');
    WPI_Functions::log(__("Plugin deactivated.", WPI));
  }

  /**
   * Called by profile_update action/hook
   * Used to save profile settings for WP Users.
   * @param int $user_id User ID
   * @param object $old_user_data old value.
   * @return bool True on successful update, false on failure.
   */
  function save_update_profile($user_id, $old_user_data) {
    global $wpi_settings;

    if (empty($user_id) || $user_id == 0) {
      return false;
    }

    $custom_user_information = apply_filters('wpi_user_information', $wpi_settings['user_meta']['custom']);
    $user_information = array_merge($wpi_settings['user_meta']['required'], $custom_user_information);

    // On Adding/Editing Invoice user data exists in ['wpi_invoice']['user_data']
    $data = !empty($_POST['wpi_invoice']['user_data']) ? $_POST['wpi_invoice']['user_data'] : $_POST;

    if (!is_array($data)) {
      return false;
    }

    foreach ($user_information as $field_id => $field_name) {
      if (isset($data[$field_id])) {
        update_user_meta($user_id, $field_id, $data[$field_id]);
      }
    }
  }

  /*
   * Set Custom Screen Options
   */

  function wpi_screen_options() {
    global $current_screen;

    $output = '';

    switch ($current_screen->id) {

      case 'toplevel_page_wpi_main':

        break;

      case 'invoice_page_wpi_page_manage_invoice':
        $output .= '
        <div id="wpi_screen_meta" class="metabox-prefs">
          <label for="wpi_itemized-list-tax">
          <input type="checkbox" ' . (get_user_option('wpi_ui_display_itemized_tax') == 'true' ? 'checked="checked"' : '') . ' value="" id="wpi_itemized-list-tax" name="wpi_ui_display_itemized_tax" class="non-metabox-option">
          Row Tax</label>
        </div>';
        break;
    }

    return $output;
  }

  /**
   * Called by template_redirect to validate whether an invoice should be displayed
   */
  function validate_page_hash($md5_invoice_id) {
    global $wpdb, $wpi_settings, $post, $invoice_id;

    $invoice_id = $wpdb->get_var("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='invoice_id' AND MD5(meta_value) = '{$md5_invoice_id}'");

    if (!$invoice_id) {
      return false;
    }

    if ($wpi_settings['web_invoice_page'] != $post->ID) {
      return false;
    }


    // Verify HTTPS.  If its enforced, but not active, we reload page, and do the process again
    //print_r( $_SERVER );

    if (!function_exists('wp_https_redirect')) {
      session_start();
      if (!isset($_SESSION['https'])) {
        if ( $wpi_settings['force_https'] == 'true' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")) {
          $_SESSION['https'] = 1;
          header("Location: https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
          @session_destroy();
          exit;
        } else {
          if (session_id() != '')
            @session_destroy();
        }
      }
      //Added to see how the invoice looks once it is created...
      if ($wpi_settings['force_https'] == 'false') {
        if (session_id() != '')
          @session_destroy();
        // Nothing should be done here, this function is simply for validating.
        // If we got this far, means invoice_id and page are validated, and HTTPS is NOT enforced
        return true;
        //print $_SERVER['SERVER_NAME']; print $_SERVER['REQUEST_URI']; die;
        //header("Location: http://" . $_SERVER['SERVER_NAME']'/wp-login.php');
        //header("Location: http://localhost/wordpress/wp-login.php");
        //exit;
      }
    }

    // 5. Validation passed
    return true;
  }

  /**
    Displayed how many days it has been since a certain date.

   */
  function days_since($date1, $return_number = false) {
    if (empty($date1))
      return "";

    if (is_array($date1))
      $date1 = $date1[year] . "-" . $date1[month] . "-" . $date1[day];

    $date2 = date("Y-m-d");
    $date1 = date("Y-m-d", strtotime($date1));


    // determine if future or past
    if (strtotime($date2) < strtotime($date1))
      $future = true;

    $difference = abs(strtotime($date2) - strtotime($date1));
    $days = round(((($difference / 60) / 60) / 24), 0);

    if ($return_number)
      return $days;

    if ($days == 0) {
      return __('Today', WPI);
    } elseif ($days == 1) {
      return($future ? __(" Tomorrow ", WPI) : __(" Yesterday ", WPI));
    } elseif ($days > 1 && $days <= 6) {
      return ($future ? __sprintf(__(" in %s days ", WPI), $days) : " ".$days." ".__("days ago", WPI));
    } elseif ($days > 6) {
      return date(get_option('date_format'), strtotime($date1));
    }
  }

  function money_format($number, $format = '%!.2n') {
    $regex = '/%((?:[\^!\-]|\+|\(|\=.)*)([0-9]+)?' .
            '(?:#([0-9]+))?(?:\.([0-9]+))?([in%])/';

    if (setlocale(LC_MONETARY, 0) == 'C') {
      setlocale(LC_MONETARY, '');
    }

    $locale = localeconv();

    /* Hack. Sometimes there is the issue of space coding view. */
    $locale['mon_thousands_sep'] = ' ';

    preg_match_all($regex, $format, $matches, PREG_SET_ORDER);

    foreach ($matches as $fmatch) {
      $value = floatval($number);
      $flags = array(
          'fillchar' => preg_match('/\=(.)/', $fmatch[1], $match) ?
                  $match[1] : ' ',
          'nogroup' => preg_match('/\^/', $fmatch[1]) > 0,
          'usesignal' => preg_match('/\+|\(/', $fmatch[1], $match) ?
                  $match[0] : '+',
          'nosimbol' => preg_match('/\!/', $fmatch[1]) > 0,
          'isleft' => preg_match('/\-/', $fmatch[1]) > 0
      );

      $width = trim($fmatch[2]) ? (int) $fmatch[2] : 0;
      $left = trim($fmatch[3]) ? (int) $fmatch[3] : 0;
      $right = trim($fmatch[4]) ? (int) $fmatch[4] : $locale['int_frac_digits'];
      $conversion = $fmatch[5];

      $positive = true;

      if ($value < 0) {
        $positive = false;
        $value *= - 1;
      }
      $letter = $positive ? 'p' : 'n';

      $prefix = $suffix = $cprefix = $csuffix = $signal = '';

      $signal = $positive ? $locale['positive_sign'] : $locale['negative_sign'];
      switch (true) {
        case $locale["{$letter}_sign_posn"] == 1 && $flags['usesignal'] == '+':
          $prefix = $signal;
          break;
        case $locale["{$letter}_sign_posn"] == 2 && $flags['usesignal'] == '+':
          $suffix = $signal;
          break;
        case $locale["{$letter}_sign_posn"] == 3 && $flags['usesignal'] == '+':
          $cprefix = $signal;
          break;
        case $locale["{$letter}_sign_posn"] == 4 && $flags['usesignal'] == '+':
          $csuffix = $signal;
          break;
        case $flags['usesignal'] == '(':
        case $locale["{$letter}_sign_posn"] == 0:
          $prefix = '(';
          $suffix = ')';
          break;
      }
      if (!$flags['nosimbol']) {
        $currency = $cprefix .
                ($conversion == 'i' ? $locale['int_curr_symbol'] : $locale['currency_symbol']) .
                $csuffix;
      } else {
        $currency = '';
      }
      $space = $locale["{$letter}_sep_by_space"] ? ' ' : '';

      $value = number_format($value, $right, $locale['mon_decimal_point'], $flags['nogroup'] ? '' : $locale['mon_thousands_sep']);
      $value = @explode($locale['mon_decimal_point'], $value);

      $n = strlen($prefix) + strlen($currency) + strlen($value[0]);
      if ($left > 0 && $left > $n) {
        $value[0] = str_repeat($flags['fillchar'], $left - $n) . $value[0];
      }
      $value = is_array($value)?implode($locale['mon_decimal_point'], $value):$value;
      if ($locale["{$letter}_cs_precedes"]) {
        $value = $prefix . $currency . $space . $value . $suffix;
      } else {
        $value = $prefix . $value . $space . $currency . $suffix;
      }
      if ($width > 0) {
        $value = str_pad($value, $width, $flags['fillchar'], $flags['isleft'] ?
                        STR_PAD_RIGHT : STR_PAD_LEFT);
      }

      $format = str_replace($fmatch[0], $value, $format);
    }
    return $format;
  }

  /**
    We use this to merge two arrays.
    Used when loading default billing data, and then being updated by invoice-specific data
    Awesome function from http://us2.php.net/manual/en/function.array-merge-recursive.php
   */
  function &array_merge_recursive_distinct() {
    $aArrays = func_get_args();
    $aMerged = $aArrays[0];

    for ($i = 1; $i < count($aArrays); $i++) {
      if (is_array($aArrays[$i])) {
        foreach ($aArrays[$i] as $key => $val) {
          if (is_array($aArrays[$i][$key])) {
            $aMerged[$key] = (isset($aMerged[$key]) && is_array($aMerged[$key]) ) ? WPI_Functions::array_merge_recursive_distinct($aMerged[$key], $aArrays[$i][$key]) : $aArrays[$i][$key];
          } else {
            $aMerged[$key] = $val;
          }
        }
      }
    }

    return $aMerged;
  }

  /** @TODO: Update it to show Settings page link */
  function set_plugin_page_settings_link($links) {
    /* $settings_link = "<a href='{$core->options['links']['settings_page']}'>Settings</a>";
      array_unshift($links, $settings_link); */
    return $links;
  }

  // Checks whether all plugin tables exist via tables_exist function
  function check_tables() {
    global $wpdb;
    if (!WPI_Functions::tables_exist()) {
      $message = __("The plugin database tables are gone, deactivate and reactivate plugin to re-create them.", WPI);
    }
    WPI_UI::error_message($message);
  }

  function tables_exist() {
    global $wpdb;
    if (!$wpdb->query("SHOW TABLES LIKE '{$wpdb->base_prefix}wpi_object_log';"))
      return false;
    return true;
  }

  // Used for displaying variables in the UI, mostly for debugging
  function qc($what, $force = false) {
    global $wp_invoice_debug;

    if (is_array($what)) {
      $what = WPI_Functions::pretty_print_r($what, false);
    }

    if (is_array($what) || is_string($what)) { // this way we don't try and show classess
      if ($wp_invoice_debug || $force) {
        ?>
        <div  class="ui-state-error ui-corner-all" style="padding: 0 .7em;">
          <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
            <span class="message_content"><?php echo $what; ?></span></p>
        </div>
        <?php
      }
    } else {
      ?>
      <div  class="ui-state-error ui-corner-all" style="padding: 0 .7em;">
        <p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span>
          <span class="message_content"><pre><?php print_r($what); ?></pre></span></p>
      </div>
      <?php
    }

    // Add QC Message to Log
    //WPI_Functions::log($what)
  }

  // Logs events and saved them into WordPress option 'wpi_log'
  // This function is intended to ease troubleshooting later
  function log($what) {
    $wpi_log = get_option('wpi_log');

    // If no session log created yet, create one
    if (!is_array($wpi_log)) {
      $wpi_log = array();
      array_push($wpi_log, array(time(), "Log Started."));
    }

    // Insert event into session
    array_push($wpi_log, array(time(), $what));

    update_option('wpi_log', $wpi_log);

    return true;
  }

  // Alternative to print_r
  function pretty_print_r($data, $echo = false) {
    // Clean $_REQUEST array
    $result = '';
    if ($data == $_REQUEST) {
      foreach ($data as $key => $value) {
        $pattern = "/PHPSESSID|ui-tab/";
        if (preg_match($pattern, $key)) {
          unset($data[$key]);
        }
      }
    }
    if (is_array($data)) { //If the given variable is an array, print using the print_r function.
      $result .= "<pre class='wpi_class_pre'>\n";
      $result .= print_r($data, true);
      $result .= "</pre>";
    } elseif (is_object($data)) {
      $result .= "<pre>\n";
      var_dump($data, true);
      $result .= "</pre>";
    } else {
      $result .= "=========&gt; ";
      $result .= var_dump($data, true);
      $result .= " &lt;=========";
    }
    if ($echo == false)
      return $result;
    $echo;
  }

  function check_settings() {
    global $wpi_settings;
    if ($wpi_settings['web_invoice_page'] == '') {
      $message .= __('Invoice page not selected. ', WPI);
      $message .= __("Visit ", WPI) . "<a href='admin.php?page=wpi_page_settings'>".__('settings page', WPI)."</a>" . __(" to configure.", WPI);
    }

    if (!function_exists('curl_exec'))
      $message .= __("cURL is not turned on on your server, credit card processing will not work. If you have access to your php.ini file, activate <b>extension=php_curl.dll</b>.", WPI);

    WPI_UI::error_message($message);
  }

  function settings_action() {

    if(isset($_REQUEST['wpi_settings']) ) {

      if ($_REQUEST['page'] == 'wpi_page_settings') {
        unset($_REQUEST);
        wp_redirect(admin_url("admin.php?page=wpi_page_settings&message=updated"));
        exit;
      }

    }

  }

  /**
    Handles saving and updating
    Can also handle AJAX save/update function
   */
  function save_invoice($invoice, $args = '') {
    //die( json_encode($invoice) );

    /* Set function additional params */
    $defaults = array(
        'type' => 'default'
    );
    extract(wp_parse_args($args, $defaults), EXTR_SKIP);

    if ($type != 'import') {
      if (!wp_verify_nonce($_REQUEST['nonce'], 'wpi-update-invoice')) {
        die('Security check');
      }
    }

    /* Init New Invoice object from passed variables */
    $ni = new WPI_Invoice();

    $ni->set("ID={$invoice['ID']}");
    $ni->set("invoice_id={$invoice['invoice_id']}");

    //$ni->set("terms_acceptance_required={$invoice['terms_acceptance_required']}");

    $ni->set("subject={$invoice['subject']}");
    $ni->set("description={$invoice['description']}");

    //$ni->set("watermark={$invoice['meta']['watermark']}");

    if ($invoice['deposit'] == 'on' || $invoice['deposit'] == 'true') {
      $ni->set("deposit_amount={$invoice['deposit_amount']}");
    } else {
      $ni->set("deposit_amount=0");
    }

    $ni->set("due_date_year={$invoice['due_date_year']}");
    $ni->set("due_date_month={$invoice['due_date_month']}");
    $ni->set("due_date_day={$invoice['due_date_day']}");

    $ni->set("default_currency_code={$invoice['default_currency_code']}");

    if (!empty($invoice['meta']['terms'])) {
      $ni->set("terms={$invoice['meta']['terms']}");
    }
    $ni->set("tax={$invoice['meta']['tax']}");

    $ni->set("custom_id={$invoice['meta']['custom_id']}");

    /**
     * DETECTING INVOICE TYPE
     * (Changes for ability to use premium feature Quotes)
     *
     * @author Anton Korotkov
     *
     * There are three available types by default:
     *    - invoice
     *    - recurring
     */
    // 'invoice' is by default
    $invoice_type = 'invoice';

    // If $invoice object has type definition then use it
    if ( !empty( $invoice['type'] ) ) {
      $invoice_type = $invoice['type'];
    }
    
    // Save status of invoice (quote or not quote)
    if(isset ($invoice['quote'])) {
      if($invoice['quote'] == "on") {
        $ni->set("status=quote");
        $ni->set("is_quote=true");
        $invoice_type = 'quote';
      } else {
        $ni->set("status=null");
      }
    }

    // But if recurring settings are defined then invoice type should be recurring
    if ($invoice['recurring']['active'] == 'on' && !empty($invoice['recurring']['cycles'])) {
      $ni->create_schedule("unit={$invoice['recurring']['unit']}&length={$invoice['recurring']['length']}&cycles={$invoice['recurring']['cycles']}&send_invoice_automatically={$invoice['recurring']['send_invoice_automatically']}&start_date[month]={$invoice['recurring']['start_date']['month']}&start_date[day]={$invoice['recurring']['start_date']['day']}&start_date[year]={$invoice['recurring']['start_date']['year']}");
      $invoice_type = 'recurring';
    }

    // Finally set invoice type
    $ni->set("type=$invoice_type");

    /* Set invoice status */
    $status = (!empty($invoice['post_status']) ? $invoice['post_status'] : 'active');
    $ni->set("post_status={$status}");

    /* Add discounts if exist */
    if (is_array($invoice['meta']['discount'])) {
      foreach ($invoice['meta']['discount'] as $discount) {
        if (!empty($discount['name']) && !empty($discount['amount'])) {
          $ni->add_discount("name={$discount['name']}&type={$discount['type']}&amount={$discount['amount']}");
        }
      }
    }

    if (!empty($invoice['client_change_payment_method'])) {
      $ni->set("client_change_payment_method={$invoice['client_change_payment_method']}");
    }
    $ni->set("default_payment_method={$invoice['default_payment_method']}");

    $ni->set("tax_method={$invoice['tax_method']}");

    // It's bad idea to clear log, because all neccessary data such as payment information exist there
    //$ni->admin("clear_log={$invoice['admin']['clear_log']}");

    /* Manually set billing settings due to the complexity of the hierarchy */
    $ni->data['billing'] = !empty($invoice['billing']) ? $invoice['billing'] : array();

    /* Add line items */
    foreach ($invoice['itemized_list'] as $line_item) {
      $ni->line_item("name={$line_item['name']}&description={$line_item['description']}&quantity={$line_item['quantity']}&price={$line_item['price']}&tax_rate={$line_item['tax']}");
    }

    /* Add line items for charges */
    if (!empty($invoice['itemized_charges'])) {
      foreach ($invoice['itemized_charges'] as $charge_item) {
        $ni->line_charge("name={$charge_item['name']}&amount={$charge_item['amount']}&tax={$charge_item['tax']}");
      }
    }

    /*
     * Save Invoice Object to DB and update user
     * (trimming is a precaution because it could cause problems in inserted in DB w/ whitespace on end)
     */
    $ni->set("user_email=" . trim($invoice['user_data']['user_email']));

    if ($type != 'import') {
      WPI_Functions::update_user($invoice['user_data']);
    }

    $invoice_id = $ni->save_invoice();
    if ($invoice_id) {
      return $invoice_id;
    } else {
      return false;
    }
  }

  function SendNotificationInvoice() {

    $ni = new WPI_Invoice();
    $ni->SendNotificationLog($_REQUEST);
  }

  /**
   * Creates post type.
   *
   * Ran everytime.
   *
   * @since 3.0
   *
   */
  function register_post_type() {
    global $wpdb, $wpi_settings, $wp_properties;

    $wpi_settings['statuses'] = array();

    $labels = array(
        'name' => __('Invoices', WPI),
        'singular_name' => __('Invoice', WPI),
        'add_new' => __('Add New', WPI),
        'add_new_item' => __('Add New Invoice', WPI),
        'edit_item' => __('Edit Invoice', WPI),
        'new_item' => __('New Invoice', WPI),
        'view_item' => __('View Invoice', WPI),
        'search_items' => __('Search Invoices', WPI),
        'not_found' => __('No invoices found', WPI),
        'not_found_in_trash' => __('No invoices found in Trash', WPI),
        'parent_item_colon' => ''
    );


    // Register custom post types
    register_post_type('wpi_object', array(
        'labels' => $labels,
        'singular_label' => __('Invoice', WPI),
        'public' => false,
        'show_ui' => false,
        '_builtin' => false,
        '_edit_link' => $wpi_settings['links']['manage_invoice'] . '&wpi[existing_invoice][invoice_id]=%d',
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => $wp_properties['configuration']['base_slug']),
        'query_var' => $wp_properties['configuration']['base_slug'],
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => WPI_URL . "/core/css/images/wp_invoice.png"
    ));

    register_post_status('archived', array(
        'label' => _x('Archived', 'wpi_object'),
        'public' => false,
        '_builtin' => false,
        'label_count' => _n_noop('Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>'),
    ));
    $wpi_settings['statuses'][] = 'archived';

    register_post_status('active', array(
        'label' => _x('Active', 'wpi_object'),
        'public' => false,
        '_builtin' => false,
        'label_count' => _n_noop('Due Invoices <span class="count">(%s)</span>', 'Due Invoices <span class="count">(%s)</span>'),
    ));
    $wpi_settings['statuses'][] = 'active';

    register_post_status('paid', array(
        'label' => _x('Paid', 'wpi_object'),
        'public' => false,
        '_builtin' => false,
        'label_count' => _n_noop('Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>'),
    ));
    $wpi_settings['statuses'][] = 'paid';

    register_post_status('trash', array(
        'label' => _x('Trash', 'wpi_object'),
        'public' => false,
        '_builtin' => false,
        'label_count' => _n_noop('Trash  <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>'),
    ));
    $wpi_settings['statuses'][] = 'trash';

    register_post_status('pending', array(
        'label' => _x('Pending', 'wpi_object'),
        'public' => false,
        '_builtin' => false,
        'label_count' => _n_noop('Pending  <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>'),
    ));
    $wpi_settings['statuses'][] = 'pending';

    do_action('wpi_register_object');
  }

  /**
   * Creates WPI 3.0 Database Schema
   *
   * Creates
   *
   * @uses $wpdb
   * @since 3.0
   *
   */
  function create_new_schema_tables() {
    global $wpdb;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    dbDelta("CREATE TABLE {$wpdb->base_prefix}wpi_object_log (
      ID mediumint(9) NOT NULL auto_increment,
      blog_id mediumint(9) NOT NULL,
      object_id mediumint(9) NOT NULL,
      user_id mediumint(9) NOT NULL,
      attribute varchar(255) collate utf8_unicode_ci NOT NULL,
      action varchar(255) collate utf8_unicode_ci NOT NULL,
      value varchar(255) collate utf8_unicode_ci NOT NULL,
      text text collate utf8_unicode_ci NOT NULL,
      time bigint(11) NOT NULL default '0',
      UNIQUE KEY id (ID),
      KEY time (time),
      KEY object_id (object_id),
      KEY user_id (user_id),
      KEY event_type (action)
    ) ");

    WPI_Functions::log("Installation SQL queries ran.");
  }

  /**
   * This function loads our payment gateways
   * @since 3.0
   */
  function load_gateways() {
    global $wpi_settings;

    $default_headers = array(
        'Name' => __('Name', 'wpi_gateway'),
        'Version' => __('Version', 'wpi_gateway'),
        'Description' => __('Description', 'wpi_gateway')
    );

    if (!is_dir(WPI_Gateways_Path)) {
      return;
    }

    if ($premium_dir = opendir(WPI_Gateways_Path)) {

      if(file_exists(WPI_Gateways_Path . "/index.php")) {
        if(WP_DEBUG) {
          include_once(WPI_Gateways_Path . "/index.php");
        } else {
          @include_once(WPI_Gateways_Path . "/index.php");
        }
      }

      while (false !== ($file = readdir($premium_dir))) {
        if ($file == 'index.php')
          continue;

        if (end(explode(".", $file)) == 'php') {
          $slug = str_replace(array('.php'), '', $file);
          if (substr($slug, 0, 6) == "class_") {
            $t = split("class_", $slug);
            $slug = $t[1];
          }

          $plugin_data = @get_file_data(WPI_Gateways_Path . "/" . $file, $default_headers, 'plugin');
          $wpi_settings['installed_gateways'][$slug]['name'] = $plugin_data['Name'];
          $wpi_settings['installed_gateways'][$slug]['version'] = $plugin_data['Version'];
          $wpi_settings['installed_gateways'][$slug]['description'] = $plugin_data['Description'];

          if(WP_DEBUG) {
            include_once(WPI_Gateways_Path . "/" . $file);
          } else {
            @include_once(WPI_Gateways_Path . "/" . $file);
          }

          // Disable plugin if class does not exists - file is empty
          if (!class_exists($slug)) {
            unset($wpi_settings['installed_gateways'][$slug]);
          } else {
            /** Initialize the object, then update the billing permissions to show whats in the object */
            eval("\$wpi_settings['installed_gateways']['" . $slug . "']['object'] = new " . $slug . "();");
          }
        }
      }

      /** Sync our options */
      WPI_Gateway_Base::sync_billing_objects();
    }
  }

  /**
   * Check for premium features and load them
   * @since 3.0
   */
  function load_premium() {
    global $wpi_settings;

    $default_headers = array(
        'Name' => __('Name', WPI),
        'Version' => __('Version', WPI),
        'Description' => __('Description', WPI),
        'Minimum Core Version' => __('Minimum Core Version', WPI)
    );

    if (!is_dir(WPI_Premium))
      return;

    if ($premium_dir = opendir(WPI_Premium)) {

      if (file_exists(WPI_Premium . "/index.php"))
        @include_once(WPI_Premium . "/index.php");

      while (false !== ($file = readdir($premium_dir))) {
        if ($file == 'index.php')
          continue;

        if (end(@explode(".", $file)) == 'php') {

          $plugin_slug = str_replace(array('.php'), '', $file);
          if (substr($plugin_slug, 0, 6) == "class_") {
            $t = split("class_", $plugin_slug);
            $plugin_slug = $t[1];
          }

          $plugin_data = @get_file_data(WPI_Premium . "/" . $file, $default_headers, 'plugin');
          $wpi_settings['installed_features'][$plugin_slug]['name'] = $plugin_data['Name'];
          $wpi_settings['installed_features'][$plugin_slug]['version'] = $plugin_data['Version'];
          $wpi_settings['installed_features'][$plugin_slug]['description'] = $plugin_data['Description'];

          if($plugin_data['Minimum Core Version']) {
            $wpi_settings['installed_features'][$plugin_slug]['minimum_wpi_version'] = $plugin_data['Minimum Core Version'];
          }

          //** If feature has a Minimum Core Version and it is more than current version - we do not load **/
          $feature_requires_upgrade = (!empty($wpi_settings['installed_features'][$plugin_slug]['minimum_wpi_version']) && (version_compare(WP_INVOICE_VERSION_NUM, $wpi_settings['installed_features'][$plugin_slug]['minimum_wpi_version']) < 0) ? true : false);

          if($feature_requires_upgrade) {
            //** Disable feature if it requires a higher WPI version**/
            $wpi_settings['installed_features'][$plugin_slug]['disabled'] = 'true';
            $wpi_settings['installed_features'][$plugin_slug]['needs_higher_wpi_version'] = 'true';
          } else {
            $wpi_settings['installed_features'][$plugin_slug]['needs_higher_wpi_version'] = 'false';
						//$wpi_settings['installed_features'][$plugin_slug]['disabled'] = 'false';
          }

          // Check if the plugin is disabled
          if (empty($wpi_settings['installed_features'][$plugin_slug]['disabled'])) {
            $wpi_settings['installed_features'][$plugin_slug]['disabled'] = 'false';
          }
          if ($wpi_settings['installed_features'][$plugin_slug]['disabled'] != 'true') {

            include_once(WPI_Premium . "/" . $file);

            // Disable plugin if class does not exists - file is empty
            if (!class_exists($plugin_slug))
              unset($wpi_settings['installed_features'][$plugin_slug]);
            else
              $wpi_settings['installed_features'][$plugin_slug]['disabled'] = 'false';
          } else {
            // Feature not loaded because it is disabled
          }
        }
      }
    }
  }

  /**
   * Run manually when a version mismatch is detected.
   *
   * Holds official current version designation.
   * Called in admin_init hook.
   *
  **/
  function manual_activation() {

    $installed_ver = get_option( "wp_invoice_version" );
    $wpi_version = WP_INVOICE_VERSION_NUM;

    if(@version_compare($installed_ver, $wpi_version) == '-1') {
      //** We are upgrading */

      //** Update option to latest version so this isn't run on next admin page load */
      update_option( "wp_invoice_version", $wpi_version );
      
      //** Try to create new schema tables */
      WPI_Functions::create_new_schema_tables();

      //** Get premium features on activation */
      WPI_Functions::check_for_premium_features();

    }

    return;

  }

  /**
   * Checks for, and downloads, any premium features from TCT servers
   *
   * @uses $wpdb
   * @since 3.0
   *
   */
  function check_for_premium_features($return = false) {
    global $wpi_settings;

    $blogname = get_bloginfo('url');
    $blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
    $system = 'wpi';
    $wpi_version = WP_INVOICE_VERSION_NUM;

    $api_key = WPI_Functions::get_api_key(array('force_check' => true, 'return' => true));

    if(empty($api_key) || strlen($api_key) != 40) {
      if($return) {
        if(empty($api_key)) {
          $api_key = __("The API key could not be generated.", WPI);
        }
        return sprintf(__('An error occured during premium feature check: <b>%s</b>.',WPI), $api_key);
      } else {
        return;
      }
    }

    $check_url = "http://updates.usabilitydynamics.com/?system={$system}&site={$blogname}&system_version={$wpi_version}&api_key={$api_key}";

    $response = @wp_remote_get($check_url);

    if (!$response) {
      return;
    }

    // Check for errors
    if (is_object($response) && !empty($response->errors)) {

      foreach ($response->errors as $update_errrors) {
        $error_string .= implode(",", $update_errrors);
        WPI_Functions::log("Feature Update Error: " . $error_string);
      }

      if ($return) {
        return sprintf(__('An error occured during premium feature check: <b> %s </b>.', WPI), $error_string);
      }

      return;
    }

    //** Quit if failure */
    if ($response['response']['code'] != '200') {
      return;
    }

    $response = @json_decode($response['body']);

    if (is_object($response->available_features)) {

      $response->available_features = WPI_Functions::objectToArray($response->available_features);

      //** Update the database */
      $wpi_settings = get_option('wpi_options');

      $wpi_settings['available_features'] = WPI_Functions::objectToArray($response->available_features);
      update_option('wpi_options', $wpi_settings);
    } // available_features


    if ($response->features == 'eligible' && $wpi_settings['disable_automatic_feature_update'] != 'true') {

      // Try to create directory if it doesn't exist
      if (!is_dir(WPI_Premium)) {
        @mkdir(WPI_Premium, 0755);
      }

      // If didn't work, we quit
      if (!is_dir(WPI_Premium)) {
        continue;
      }

      // Save code
      if (is_object($response->code)) {
        foreach ($response->code as $code) {

          $filename = $code->filename;
          $php_code = $code->code;
          $version = $code->version;

          //** Check version */

          $default_headers = array(
              'Name' => __('Feature Name', WPI),
              'Version' => __('Version', WPI),
              'Description' => __('Description', WPI)
          );

          $current_file = @get_file_data(WPI_Premium . "/" . $filename, $default_headers, 'plugin');

          if (@version_compare($current_file[Version], $version) == '-1') {
            $this_file = WPI_Premium . "/" . $filename;
            $fh = @fopen($this_file, 'w');
            if ($fh) {
              fwrite($fh, $php_code);
              fclose($fh);

              if ($current_file[Version]) {
                //UD_F::log(sprintf(__('WP-Invoice Premium Feature: %s updated to version %s from %s.', WPI), $code->name, $version, $current_file[Version]));
              } else {
                //UD_F::log(sprintf(__('WP-Invoice Premium Feature: %s updated to version %s.', WPI), $code->name, $version));
              }

              $updated_features[] = $code->name;
            }
          } else {

          }
        }
      }
    }

    // Update settings
    //WPI_Functions::settings_action(true);

    if ($return && $wpi_settings['disable_automatic_feature_update'] == 'true') {
      return __('Update ran successfully but no features were downloaded because the setting is disabled. Enable in the "Developer" tab.', WPI);
    } elseif ($return) {
      return __('Update ran successfully.', WPI);
    }
  }

  /**
   * Check if premium feature is installed or not
   * @param string $slug. Slug of premium feature
   * @return boolean.
   */
  function check_premium($slug) {
    global $wpi_settings;

    if(empty($wpi_settings['installed_features'][$slug]['version'])) {
      return false;
    }

    $file = WPI_Premium . "/" . $slug . ".php";

    $default_headers = array(
      'Name' => __('Name',WPI),
      'Version' => __('Version',WPI),
      'Description' => __('Description',WPI)
    );

    $plugin_data = @get_file_data( $file , $default_headers, 'plugin' );

    if(!is_array($plugin_data) || empty($plugin_data['Version'])) {
      return false;
    }

    return true;
  }

  /**
   * Logs an action
   *
   * @since 3.0
   */
  function log_event($object_id, $attribute, $action, $value, $text = '', $time = false) {
    global $wpdb, $current_user, $blog_id;

    if (!$time) {
      $time = time();
    }
    
     $wpdb->show_errors(); 

    $wpdb->insert($wpdb->base_prefix . 'wpi_object_log', array(
      'object_id' => $object_id,
      'user_id' => $current_user->ID,
      'attribute' => $attribute,
      'action' => $action,
      'value' => $value,
      'text' => $text,
      'time' => $time,
      'blog_id' => $blog_id
    ));
 
      
    if($wpdb->insert_id) {
      return $wpdb->insert_id;
    } else {
      return false;
    }

  }

  /**
   * Detect browser
   *
   * @global bool $is_lynx
   * @global bool $is_gecko
   * @global bool $is_IE
   * @global bool $is_opera
   * @global bool $is_NS4
   * @global bool $is_safari
   * @global bool $is_chrome
   * @global bool $is_iphone
   *
   * @author korotkov@ud
   * @return array
   */
  function browser() {
    global $is_lynx, $is_gecko, $is_IE, $is_opera, $is_NS4, $is_safari, $is_chrome, $is_iphone;

    if ($is_lynx)
      $classes['name'] = 'lynx';
    elseif ($is_gecko)
      $classes['name'] = 'gecko';
    elseif ($is_opera)
      $classes['name'] = 'opera';
    elseif ($is_NS4)
      $classes['name'] = 'ns4';
    elseif ($is_safari)
      $classes['name'] = 'safari';
    elseif ($is_chrome)
      $classes['name'] = 'chrome';

    elseif ($is_IE) {
      $classes['name'] = 'ie';
      if (preg_match('/MSIE ([0-9]+)([a-zA-Z0-9.]+)/', $_SERVER['HTTP_USER_AGENT'], $browser_version))
        $classes['version'] = $browser_version[1];
    } else {
      $classes['name'] = 'unknown';
    }
    if ($is_iphone) {
      $classes['name'] = 'iphone';
    }
    if (stristr($_SERVER['HTTP_USER_AGENT'], "mac")) {
      $classes['sys'] = 'osx';
    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], "linux")) {
      $classes['sys'] = 'linux';
    } elseif (stristr($_SERVER['HTTP_USER_AGENT'], "windows")) {
      $classes['sys'] = 'windows';
    }
    return $classes;
  }

  /**
   * Revalidate all the invoices
   *
   * @author korotkov@ud
   * @global object $wpdb
   */
  function total_revalidate() {
    global $wpdb;

    /** Recalculate all invoices */
    $invoices = $wpdb->get_col("
      SELECT ID
      FROM {$wpdb->posts}
      WHERE post_type = 'wpi_object'
    ");

    foreach ($invoices as $post_id) {
      $invoice_id = wpi_post_id_to_invoice_id($post_id);
      $this_invoice = new WPI_Invoice();
      $this_invoice->load_invoice("id={$invoice_id}");
      $this_invoice->save_invoice();
    }
  }

  /**
   * Returns WP-CRM attributes list
   * 
   * @global object $wp_crm
   * @return array 
   * @author korotkov@ud
   */
  function get_wpi_crm_attributes() {
    /** If WP-CRM not installed */
    if ( !class_exists('WP_CRM_Core') ) return;
    
    global $wp_crm;

    $attributes = array();
    if ( !empty( $wp_crm['data_structure']['attributes'] ) ) {
      foreach( $wp_crm['data_structure']['attributes'] as $slug => $attribute ) {
        if ( !empty( $attribute['wp_invoice'] ) && $attribute['wp_invoice'] == 'true' ) {
          $attributes[ $slug ] = $attribute;
        }
      }
    }

    return $attributes;
  }

  /**
   * WP-CRM custom fields procces filter
   * 
   * @param array $current_fields
   * @param string $name
   * @return array 
   * @author korotkov@ud
   */
  function wpi_crm_custom_fields( $current_fields, $name ) {
    $attributes = self::get_wpi_crm_attributes();
    if ( empty( $attributes ) ) return $current_fields;
    
    foreach( $attributes as $attr_key => $attr_value ) {
      $current_fields['customer_information'][ $attr_key ] = array(
        'type'  => 'text',
        'class' => 'text-input',
        'name'  => $name.'['.$attr_key.']',
        'label' => __( $attr_value['title'], WPI )
      );
    }

    return $current_fields;
  }

}

/**
 * Draw users dropdown list
 * 
 * @global object $wpdb
 * @param string $post_type
 * @param string $select_name
 * @param bool $return_users
 * @return bool|nothing 
 * @author korotkov@ud
 */
function wpi_invoice_users_dropdown($post_type, $select_name, $return_users=false) {
  global $wpdb;

  switch ($post_type) {
    case 'wpi_object':
      $results = $wpdb->get_results($wpdb->prepare("
        SELECT u.ID, pm.meta_value
        FROM {$wpdb->posts} AS p
        JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = p.ID AND pm.meta_key = 'user_email'
        JOIN {$wpdb->users} AS u ON u.user_email = pm.meta_value
        WHERE post_type= %s
        ", $post_type), ARRAY_N);
      break;
  }

  if (empty($results)) {
    return false;
  }

  $users = array();
  foreach ($results as $result) {
    $users[] = $result[0];
  }

  if ($return_users)
    return $users;

  $selected = isset($_GET['recipient']) ? (int) $_GET['recipient'] : 0;

  if (!empty($users)) {
    wp_dropdown_users(
      array(
        'include' => $users,
        'show_option_all' => __('Show all users', WPI),
        'selected' => $selected,
        'name' => $select_name
      )
    );
  }
}

/**
 * Mark invoice as Paid
 * 
 * @param int $invoice_id
 * @param bool $check_balance
 * @return bool
 * @author korotkov@ud
 */
function wp_invoice_mark_as_paid($invoice_id, $check_balance=false) {
  if ($check_balance) {
    if (wpi_is_full_paid_invoice($invoice_id)) {
      $post_id = wpi_invoice_id_to_post_id($invoice_id);
      wp_update_post(
        array(
          'ID' => $post_id,
          'post_status' => 'paid'
        )
      );
      WPI_Functions::log_event($post_id, 'invoice', 'update', '', __('Payment status: Complete', WPI));
      return true;
    } else {
      $post_id = wpi_invoice_id_to_post_id($invoice_id);
      wp_update_post(
        array(
          'ID' => $post_id,
          'post_status' => 'active'
        )
      );
      return true;
    }
  } else {
    $post_id = wpi_invoice_id_to_post_id($invoice_id);
    wp_update_post(
      array(
        'ID' => $post_id,
        'post_status' => 'paid'
      )
    );
    WPI_Functions::log_event($post_id, 'invoice', 'update', '', __('Payment status: Complete', WPI));
    return true;
  }
}

/**
 * Mark invoice as Pending (for PayPal IPN)
 * 
 * @param int $invoice_id
 * @author korotkov@ud
 */
function wp_invoice_mark_as_pending($invoice_id) {
  $post_id = wpi_invoice_id_to_post_id($invoice_id);
  
  wp_update_post(
          array(
              'ID' => $post_id,
              'post_status' => 'pending'
          )
  );
  
	/** Mark invoice as processed by IPN (used for trashing abandoned SPC transactions) */
	update_post_meta($post_id, 'processed_by_ipn', 'true');
  WPI_Functions::log_event($post_id, 'invoice', 'update', '', __('Pending', WPI));
}

/**
 * Determine if invoice is paid full
 * 
 * @global object $wpdb
 * @param int $invoice_id
 * @return bool
 * @author korotkov@ud
 */
function wpi_is_full_paid_invoice($invoice_id) {
  global $wpdb;
  
  $invoice_obj = new WPI_Invoice();
  $invoice_obj->load_invoice("id={$invoice_id}");
  
  $object_id = wpi_invoice_id_to_post_id($invoice_id);
  $payment_history = $wpdb->get_results("SELECT * FROM {$wpdb->base_prefix}wpi_object_log WHERE object_id = '{$object_id}' AND action = 'add_payment'", ARRAY_A);
  $paid_amount = 0;
  
  foreach ($payment_history as $payment) {
    $paid_amount += abs($payment['value']);
  }
  
  return $paid_amount >= ( $invoice_obj->data['subtotal'] - $invoice_obj->data['total_discount'] );
}

/**
 * Formates amount
 * 
 * @param number $amount
 * @return string 
 */
function wp_invoice_currency_format($amount) {
  if ($amount) {
    return number_format($amount, 2, '.', ',');
  } else {
    return $amount;
  }
}

/**
 * Emails user after payment is done
 * 
 * @param array $invoice
 * @author korotkov@ud
 * 
 * @todo Refactor message text to be loaded from editable templates from settings.
 */
function wp_invoice_send_email_receipt($invoice) {
  global $wpi_settings;

  $post_id = wpi_invoice_id_to_post_id($invoice['invoice_id']);

  $name = stripslashes($wpi_settings['business_name']);
  $from = stripslashes($wpi_settings['email_address']);

  $permalink = get_invoice_permalink($invoice['invoice_id']);

  $headers = "From: {$name} <{$from}>\r\n";

  $message = sprintf(
    __("Dear %1s,\n%2s has received your payment for the invoice.\n\nYou can overview invoice status and payment history by clicking this link:\n%3s\n\nThank you very much for your patronage.\n\nBest regards,\n%4s (%5s)", WPI),
    wpi_get_user_display_name($invoice),
    $wpi_settings['business_name'],
    $permalink,
    $name,
    $from
  );

  $subject = sprintf(__("Invoice #%s has been paid", WPI), $invoice['invoice_id']);

  $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
  $subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

  if (wp_mail($invoice['user_data']['user_email'], $subject, $message, $headers)) {
    WPI_Functions::log_event($post_id, 'invoice', 'emailed', '', __('Receipt eMailed', WPI));
  }

  return $message;
}

/**
 * Emails merchant after payment is done
 *
 * @global array $wpi_settings
 * @param array $invoice
 * @author korotkov@UD
 * 
 * @todo Refactor message text to be loaded from editable templates from settings.
 */
function wp_invoice_send_me_notification($invoice) {
  global $wpi_settings;

  $to           = stripslashes( get_option('admin_email') );
  $site         = stripslashes($wpi_settings['business_name']);
  $permalink    = get_invoice_permalink($invoice['invoice_id']);
  $total        = $invoice['subtotal'] - $invoice['total_discount'] + $invoice['total_tax'];
  $display_name = wpi_get_user_display_name($invoice);

  $message = sprintf(
    __("%1s has paid invoice #%2s.\n\n%3s\nTotal payments: %4s %5s of %6s %7s.\n\nYou can overview invoice status and payment history by clicking this link:\n%8s\n\nUser information:\n\nID: %9s\nName: %10s\nEmail: %11s\n\n--------------------\n%12s", WPI),
    $display_name,
    $invoice['invoice_id'],
    $invoice['post_title'],
    $invoice['default_currency_code'],
    $invoice['total_payments'],
    $invoice['default_currency_code'],
    $total,
    $permalink,
    $invoice['user_data']['ID'],
    $display_name,
    $invoice['user_data']['user_email'],
    $site
  );

  $subject = sprintf(__("Invoice #%s has been paid", WPI), $invoice['invoice_id']);

  $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
  $subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

  wp_mail($to, $subject, $message);
}


/**
 * Sends notification to invoice creator
 * 
 * @global array $wpi_settings
 * @param array $invoice
 * @author korotkov@UD
 * 
 * @todo Refactor message text to be loaded from editable templates from settings.
 */
function wp_invoice_send_creator_notification($invoice) {
  global $wpi_settings;

  $creator      = get_userdata( $invoice['post_author'] );
  $to           = stripslashes( $creator->user_email );
  $site         = stripslashes($wpi_settings['business_name']);
  $permalink    = get_invoice_permalink($invoice['invoice_id']);
  $total        = $invoice['subtotal']-$invoice['total_discount']+$invoice['total_tax'];
  $display_name = wpi_get_user_display_name($invoice);

  $message = sprintf(
    __("%1s has paid invoice #%2s.\n\n%3s\nTotal payments: %4s %5s of %6s %7s.\n\nYou can overview invoice status and payment history by clicking this link:\n%8s\n\nUser information:\n\nID: %9s\nName: %10s\nEmail: %11s\n\n--------------------\n%12s", WPI),
    $display_name,
    $invoice['invoice_id'],
    $invoice['post_title'],
    $invoice['default_currency_code'],
    $invoice['total_payments'],
    $invoice['default_currency_code'],
    $total,
    $permalink,
    $invoice['user_data']['ID'],
    $display_name,
    $invoice['user_data']['user_email'],
    $site
  );

  $subject = sprintf(__("Invoice #%s has been paid", WPI), $invoice['invoice_id']);

  $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
  $subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

  wp_mail($to, $subject, $message);
}


/**
 * Sends required notifications
 * @global array $wpi_settings
 * @param array $invoice
 * @author korotkov@UD
 */
function send_notification( $invoice ) {
  global $wpi_settings;

  if ( (!empty($wpi_settings['send_thank_you_email']) && $wpi_settings['send_thank_you_email'] == 'true') ||
       (!empty($wpi_settings['cc_thank_you_email']) && $wpi_settings['cc_thank_you_email'] == 'true') ||
       (!empty($wpi_settings['send_invoice_creator_email']) && $wpi_settings['send_invoice_creator_email'] == 'true' ) ) {

    $paid_invoice = new WPI_Invoice();
    $paid_invoice->load_invoice("id={$invoice['invoice_id']}");

    /** Email client */
    if (!empty($wpi_settings['send_thank_you_email']) && $wpi_settings['send_thank_you_email'] == 'true') {
      wp_invoice_send_email_receipt($paid_invoice->data);
    }

    /** Email site admin */
    if (!empty($wpi_settings['cc_thank_you_email']) && $wpi_settings['cc_thank_you_email'] == 'true') {
      wp_invoice_send_me_notification($paid_invoice->data);
    }

    /** Email invoice creator */
    if ( !empty( $wpi_settings['send_invoice_creator_email'] ) && $wpi_settings['send_invoice_creator_email'] == 'true' ){
      wp_invoice_send_creator_notification($paid_invoice->data);
    }

  }

}

/**
 * Returns display_name from invoice if exists or from userdata
 * 
 * @param type $invoice
 * @return type 
 * @author korotkov@ud
 */
function wpi_get_user_display_name( $invoice ) {
  /** If display name exists, return it */
  if ( !empty( $invoice['user_data']['display_name'] ) ) {
    return $invoice['user_data']['display_name'];
  }
  
  /** Get current user data and return it */
  $user = get_userdata( $invoice['user_data']['ID'] );
  return $user->display_name;
}

/**
 * This function checks to see if a plugin is installed
 * @param string $slug The class name of the plugin
 * @return bool Whether or not its installed
 * @since 3.0
 */
function wpi_feature_installed($slug) {
  global $wpi_settings;
  if (is_array($wpi_settings['installed_features'][$slug]) && !$wpi_settings['installed_features'][$slug]['disabled']) {
    return true;
  }
  return false;
}

/**
 * Shows business information on front-end
 */
function wp_invoice_show_business_information() {
  $core = WPI_Core::getInstance();
  $business_info['name'] = $core->Settings->options['business_name'];
  $business_info['address'] = $core->Settings->options['business_address'];
  $business_info['phone'] = $core->Settings->options['business_phone'];
  ?>
  <div id="invoice_business_info" class="clearfix">
    <p class="invoice_page_subheading"><strong>Bill From:</strong></p>
    <p class="wp_invoice_bi wp_invoice_business_name"><?php echo $business_info['name']; ?></p>
    <p class="wp_invoice_bi wp_invoice_business_address"><?php echo $business_info['address']; ?></p>
    <p class="wp_invoice_bi wp_invoice_business_phone"><?php echo $business_info['phone']; ?></p>
  </div>
  <?php
}

/**
 * Returns due date in format
 * 
 * @param type $invoice
 * @return type 
 * @author korotkov@ud
 */
function get_due_date( $invoice ) {
  
  if ( !empty( $invoice['due_date_year'] ) && !empty( $invoice['due_date_month'] ) && !empty( $invoice['due_date_day'] ) ) {
    return date( get_option('date_format'), strtotime( $invoice['due_date_day'].'-'.$invoice['due_date_month'].'-'.$invoice['due_date_year'] ) );
  }
  
  return false;
  
}

/**
 * Find a full diff between two arrays.
 * 
 * @param array $array1
 * @param array $array2
 * @return array 
 * @author korotkov@ud
 */
function wpi_multi_array_diff($array1, $array2) {
    $ret = array();
    foreach ($array1 as $k => $v) {
        if (!isset($array2[$k])) $ret[$k] = $v;
        else if (is_array($v) && is_array($array2[$k])) {
          $u = wpi_multi_array_diff($v, $array2[$k]);
          if ( !empty($u) ) {
            $ret[$k] = $u;
          }
        } else if ( $v != $array2[$k] ) {
          $ret[$k] = $v;
        }
    }
    return $ret;
}

/**
 * @author korotkov@ud
 * @param array $data
 * <b>Example:</b><br>
 * <pre>
 * array(
 *    'venue' => 'wpi_authorize',
 *    'amount' => '100.00',
 *    'payer_email' => 'john.smith@gmail.com',
 *    'payer_first_name' => 'John',
 *    'payer_last_name' => 'Smith',
 *    'cc_number' => '411111111111111',
 *    'cc_expiration' => '0412',
 *    'cc_code' => '356',
 *    'items' => array(
 *      array(
 *        'name' => 'Name 1',
 *        'description' => 'Item 1',
 *        'quantity' => 1,
 *        'price' => '10.00'
 *      ),
 *      array(
 *        'name' => 'Name 2',
 *        'description' => 'Item 2',
 *        'quantity' => 2,
 *        'price' => '10.00'
 *      )
 *    )
 * )
 * </pre>
 * @return array
 */
function wpi_process_transaction($data) {
  $wpa = new WPI_Payment_Api();
  return $wpa->process_transaction($data);
}