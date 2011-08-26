<?php
/**
 *
 * Handles all functions
 *
 */
setlocale(LC_MONETARY, 'en_US');

class WPI_Functions {

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
        $r['collected_client_value'][$object['user_email']] = !empty($r['client_value'][$object['user_email']]) ? $r['client_value'][$object['user_email']] : 0 + $object['subtotal'];
        $r['total_paid'][] = $objects[$post_id]['subtotal'];

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
        $r['uncollected_client_value'][$object['user_email']] = !empty($r['uncollected_client_value'][$object['user_email']]) ? $r['uncollected_client_value'][$object['user_email']] : 0 + $object['subtotal'];
        $r['total_unpaid'][] = $objects[$post_id]['subtotal'];
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
      // default user's role - Anton Korotkov
      $userdata['user_login'] = $userdata['user_pass'] = $userdata['user_email'];
      $userdata['role'] = 'subscriber';
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
        WPI_Functions::add_message("Unable to create 'wpi' folder in template folder. ", 'bad');
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
      WPI_Functions::add_message("Unable to open 'wpi' folder in template folder. $original_template_path", 'bad');
      return false;
    }

    if ((intval($files_copied)) != 0) {
      WPI_Functions::add_message("Success, ($files_copied) template file(s) copied.");
      return true;
    } else {
      WPI_Functions::add_message("No template files copied.", 'bad');
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
    //echo $invoice_id;
    //print_r($this_invoice);

    if (is_array($this_invoice->data['log'])) {
      foreach (array_reverse($this_invoice->data['log']) as $event) {

        if (empty($event['text']))
          continue;
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
    Returns the highest custom ID stored in DB.
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

    $custom_id_array = array();

    foreach ($invoices as $invoice) {
      $custom_id_array[] = get_post_meta($invoice->ID, 'invoice_id', true);
    }

    return @max($custom_id_array);
  }

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

    WPI_Functions::log("Schedule created with plugin activation.");

    /* Try to create new schema tables */
    WPI_Functions::create_new_schema_tables();

    /* Get previous activated version */
    $current_version = get_option('wp_invoice_version');

    /* If no version found at all, we do new install */
    if ($current_version == WP_INVOICE_VERSION_NUM) {
      WPI_Functions::log("Plugin activated. No older versions found, installing version " . WP_INVOICE_VERSION_NUM . ".");
    } else if ((int) $current_version < 3) {
      /* Determine if legacy data exist */
      WPI_Legacy::init();
      WPI_Functions::log("Plugin activated.");
    }

    /* Update version */
    update_option('wp_invoice_version', WP_INVOICE_VERSION_NUM);
  }

  function Deactivate() {
    wp_clear_scheduled_hook('wpi_hourly_event');
    wp_clear_scheduled_hook('wpi_update');
    WPI_Functions::log("Plugin deactivated.");
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
        if ($wpi_settings['force_https'] == 'true' && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")) {
          $_SESSION['https'] = 1;
          header("Location: https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
          exit;
        } else {
          if (session_id() != '')
            session_destroy();
        }
      }
      //Added to see how the invoice looks once it is created...
      if ($wpi_settings['force_https'] == 'false') {
        if (session_id() != '')
          session_destroy();
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
      return __('Today', WP_INVOICE_TRANS_DOMAIN);
    } elseif ($days == 1) {
      return($future ? " Tomorrow " : " Yesterday ");
    } elseif ($days > 1 && $days <= 6) {
      return ($future ? " in $days days " : " $days days ago");
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
      $value = implode($locale['mon_decimal_point'], $value);
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
      $message = __("The plugin database tables are gone, deactivate and reactivate plugin to re-create them.", WP_INVOICE_TRANS_DOMAIN);
    }
    WPI_UI::error_message($message);
  }

  function tables_exist() {
    global $wpdb;
    if (!$wpdb->query("SHOW TABLES LIKE '{$wpdb->prefix}wpi_object_log';"))
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
      $message .= __('Invoice page not selected. ', WP_INVOICE_TRANS_DOMAIN);
      $message .= __("Visit ", WP_INVOICE_TRANS_DOMAIN) . "<a href='admin.php?page=wpi_page_settings'>settings page</a>" . __(" to configure.", WP_INVOICE_TRANS_DOMAIN);
    }

    if (!function_exists('curl_exec'))
      $message .= "cURL is not turned on on your server, credit card processing will not work. If you have access to your php.ini file, activate <b>extension=php_curl.dll</b>.";

    WPI_UI::error_message($message);
  }

  /**
    Handles saving and updating
    Can also handle AJAX save/update function
   */
  function save_invoice($invoice, $args = '') {
    //WPI_Functions::qc($_REQUEST[wpi_invoice]);

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

    //$ni->set("show_only_favorite_states={$invoice['meta']['show_only_favorite_states']}");
    //$ni->set("show_only_favorite_countries={$invoice['meta']['show_only_favorite_countries']}");
    //$ni->set("show_quantities={$invoice['meta']['show_quantities']}");
    //$ni->set("show_business_address={$invoice['meta']['show_business_address']}");
    //$ni->set("favorite_states={$invoice['meta']['favorite_states']}");
    //$ni->set("favorite_countries={$invoice['meta']['favorite_countries']}");

    /* Setting the type */
    if ($invoice['recurring']['active'] == 'on' && !empty($invoice['recurring']['cycles'])) {
      $ni->create_schedule("unit={$invoice['recurring']['unit']}&length={$invoice['recurring']['length']}&cycles={$invoice['recurring']['cycles']}&send_invoice_automatically={$invoice['recurring']['send_invoice_automatically']}&start_date[month]={$invoice['recurring']['start_date']['month']}&start_date[day]={$invoice['recurring']['start_date']['day']}&start_date[year]={$invoice['recurring']['start_date']['year']}");
      $ni->set("type=recurring");
      //} elseif ($invoice['quote'] == 'on') {
      //  $ni->set("type=quote");
    } else {
      $ni->set("type=invoice");
    }

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
        'name' => __('Invoices', 'wpi'),
        'singular_name' => __('Invoice', 'wpi'),
        'add_new' => __('Add New', 'wpi'),
        'add_new_item' => __('Add New Invoice', 'wpi'),
        'edit_item' => __('Edit Invoice', 'wpi'),
        'new_item' => __('New Invoice', 'wpi'),
        'view_item' => __('View Invoice', 'wpi'),
        'search_items' => __('Search Invoices', 'wpi'),
        'not_found' => __('No invoices found', 'wpi'),
        'not_found_in_trash' => __('No invoices found in Trash', 'wpi'),
        'parent_item_colon' => ''
    );


    // Register custom post types
    register_post_type('wpi_object', array(
        'labels' => $labels,
        'singular_label' => __('Invoice', 'wpi'),
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

    dbDelta("CREATE TABLE IF NOT EXISTS  {$wpdb->prefix}wpi_object_log  (
      ID mediumint(9) NOT NULL auto_increment,
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

    if (!is_dir(WPI_Gateways_Path))
      return;

    if ($premium_dir = opendir(WPI_Gateways_Path)) {

      if (file_exists(WPI_Gateways_Path . "/index.php"))
        @include_once(WPI_Gateways_Path . "/index.php");

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

          @include_once(WPI_Gateways_Path . "/" . $file);

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
        'Name' => __('Name', 'wpi'),
        'Version' => __('Version', 'wpi'),
        'Description' => __('Description', 'wpi')
    );

    if (!is_dir(WPI_Premium))
      return;

    if ($premium_dir = opendir(WPI_Premium)) {

      if (file_exists(WPI_Premium . "/index.php"))
        @include_once(WPI_Premium . "/index.php");

      while (false !== ($file = readdir($premium_dir))) {
        if ($file == 'index.php')
          continue;

        if (end(explode(".", $file)) == 'php') {

          $plugin_slug = str_replace(array('.php'), '', $file);
          if (substr($plugin_slug, 0, 6) == "class_") {
            $t = split("class_", $plugin_slug);
            $plugin_slug = $t[1];
          }

          $plugin_data = @get_file_data(WPI_Premium . "/" . $file, $default_headers, 'plugin');
          $wpi_settings['installed_features'][$plugin_slug]['name'] = $plugin_data['Name'];
          $wpi_settings['installed_features'][$plugin_slug]['version'] = $plugin_data['Version'];
          $wpi_settings['installed_features'][$plugin_slug]['description'] = $plugin_data['Description'];

          // Check if the plugin is disabled
          if (empty($wpi_settings['installed_features'][$plugin_slug]['disabled'])) {
            $wpi_settings['installed_features'][$plugin_slug]['disabled'] = false;
          }
          if ($wpi_settings['installed_features'][$plugin_slug]['disabled'] != 'true') {

            include_once(WPI_Premium . "/" . $file);

            // Disable plugin if class does not exists - file is empty
            if (!class_exists($plugin_slug))
              unset($wpi_settings['installed_features'][$plugin_slug]);
            else
              $wpi_settings['installed_features'][$plugin_slug]['disabled'] = false;
          } else {
            // Feature not loaded because it is disabled
          }
        }
      }
    }
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

    $check_url = "http://updates.usabilitydynamics.com/?system=$system&site=$blogname&system_version=$wpi_version";
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
        return sprintf(__('An error occured during premium feature check: <b> %s </b>.', WP_INVOICE_TRANS_DOMAIN), $error_string);
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
              'Name' => __('Feature Name', WP_INVOICE_TRANS_DOMAIN),
              'Version' => __('Version', WP_INVOICE_TRANS_DOMAIN),
              'Description' => __('Description', WP_INVOICE_TRANS_DOMAIN)
          );

          $current_file = @get_file_data(WPI_Premium . "/" . $filename, $default_headers, 'plugin');

          if (@version_compare($current_file[Version], $version) == '-1') {
            $this_file = WPI_Premium . "/" . $filename;
            $fh = @fopen($this_file, 'w');
            if ($fh) {
              fwrite($fh, $php_code);
              fclose($fh);

              if ($current_file[Version]) {
                //UD_F::log(sprintf(__('WP-Invoice Premium Feature: %s updated to version %s from %s.', WP_INVOICE_TRANS_DOMAIN), $code->name, $version, $current_file[Version]));
              } else {
                //UD_F::log(sprintf(__('WP-Invoice Premium Feature: %s updated to version %s.', WP_INVOICE_TRANS_DOMAIN), $code->name, $version));
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
      return __('Update ran successfully but no features were downloaded because the setting is disabled. Enable in the "Developer" tab.', 'wpi');
    } elseif ($return) {
      return __('Update ran successfully.', 'wpi');
    }
  }

  /**
   * Logs an action
   *
   * @since 3.0
   */
  function log_event($object_id, $attribute, $action, $value, $text = '', $time = false) {
    global $wpdb, $current_user;

    if (!$time)
      $time = time();

    $wpdb->insert($wpdb->prefix . 'wpi_object_log', array(
        'object_id' => $object_id,
        'user_id' => $current_user->ID,
        'attribute' => $attribute,
        'action' => $action,
        'value' => $value,
        'text' => $text,
        'time' => $time
    ));
  }

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

  function total_revalidate() {
    global $wpdb;

    // Recalculate all invoices
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

}

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
    case 'wpi_payment':
      /* @TODO */
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
    wp_dropdown_users(array(
        'include' => $users,
        'show_option_all' => 'Show all users',
        'selected' => $selected,
        'name' => $select_name
            )
    );
  }
}

function wp_invoice_validate_cc_number($cc_number) {
  /** Validate; return value is card type if valid. */
  $false = false;
  $card_type = "";
  $card_regexes = array(
      "/^4\d{12}(\d\d\d){0,1}$/" => "visa",
      "/^5[12345]\d{14}$/" => "mastercard",
      "/^3[47]\d{13}$/" => "amex",
      "/^6011\d{12}$/" => "discover",
      "/^30[012345]\d{11}$/" => "diners",
      "/^3[68]\d{12}$/" => "diners",
  );

  foreach ($card_regexes as $regex => $type) {
    if (preg_match($regex, $cc_number)) {
      $card_type = $type;
      break;
    }
  }

  if (!$card_type) {
    return $false;
  }

  /**  mod 10 checksum algorithm  */
  $revcode = strrev($cc_number);
  $checksum = 0;

  for ($i = 0; $i < strlen($revcode); $i++) {
    $current_num = intval($revcode[$i]);
    if ($i & 1) { /** Odd  position */
      $current_num *= 2;
    }
    /** Split digits and add. */
    $checksum += $current_num % 10;
    if
    ($current_num > 9) {
      $checksum += 1;
    }
  }

  if ($checksum % 10 == 0) {
    return $card_type;
  } else {
    return $false;
  }
}

/**
 * Mark invoice as Paid
 * @param int $invoice_id
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
      WPI_Functions::log_event($post_id, 'invoice', 'update', '', 'Payment status: Complete');
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
    WPI_Functions::log_event($post_id, 'invoice', 'update', '', 'Payment status: Complete');
    return true;
  }
}

/**
 * Mark invoice as Pending (for PayPal IPN)
 * @param int $invoice_id
 */
function wp_invoice_mark_as_pending($invoice_id) {
  $post_id = wpi_invoice_id_to_post_id($invoice_id);
  wp_update_post(
          array(
              'ID' => $post_id,
              'post_status' => 'pending'
          )
  );
  WPI_Functions::log_event($post_id, 'invoice', 'update', '', 'Pending');
}

function wp_invoice_mark_as_active($invoice_id, $reason='Activated') {
  $post_id = wpi_invoice_id_to_post_id($invoice_id);
  wp_update_post(
          array(
              'ID' => $post_id,
              'post_status' => 'active'
          )
  );
  WPI_Functions::log_event($post_id, 'invoice', 'update', '', $reason);
}

function wpi_is_full_paid_invoice($invoice_id) {
  global $wpdb;
  $invoice_obj = new WPI_Invoice();
  $invoice_obj->load_invoice("id={$invoice_id}");
  $object_id = wpi_invoice_id_to_post_id($invoice_id);
  $payment_history = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wpi_object_log WHERE object_id = '{$object_id}' AND action = 'add_payment'", ARRAY_A);
  $paid_amount = 0;
  foreach ($payment_history as $payment) {
    $paid_amount += abs($payment['value']);
  }
  return $paid_amount >= $invoice_obj->data['subtotal'];
}

function wp_invoice_currency_format($amount) {

  if ($amount) {
    return number_format($amount, 2, '.', ',');
  } else {
    return $amount;
  }
}

/**
 * Emails user after payment is done
 */
function wp_invoice_send_email_receipt($invoice) {
  global $wpi_settings;

  $post_id = wpi_invoice_id_to_post_id($invoice['invoice_id']);


  $name = stripslashes($wpi_settings['business_name']);
  $from = stripslashes($wpi_settings['email_address']);

  $permalink = get_invoice_permalink($invoice['invoice_id']);

  $headers = "From: {$name} <{$from}>\r\n";

  $message = "Dear {$invoice['user_data']['display_name']}, 
{$wpi_settings['business_name']} has received your payment for the invoice.
      
You can overview invoice status and payment history by clicking this link:
{$permalink}

Thank you very much for your patronage.

Best regards,
$name ($from)";

  $subject = "Invoice #{$invoice['invoice_id']} has been paid";

  $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
  $subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

  if (wp_mail($invoice['user_data']['user_email'], $subject, $message, $headers)) {
    WPI_Functions::log_event($post_id, 'invoice', 'emailed', '', 'Receipt eMailed');
  }

  return $message;
}

/**
 * Emails merchant after payment is done
 */
function wp_invoice_send_me_notification($invoice) {
  global $wpi_settings;

  $to = stripslashes($wpi_settings['email_address']);
  $site = stripslashes($wpi_settings['business_name']);

  $permalink = get_invoice_permalink($invoice['invoice_id']);

  $message = "{$invoice['user_data']['display_name']} has paid invoice #{$invoice['invoice_id']}.

{$invoice['post_title']}
Total payments: {$invoice['default_currency_code']} {$invoice['total_payments']} of {$invoice['default_currency_code']} {$invoice['subtotal']}.
  
You can overview invoice status and payment history by clicking this link:
{$permalink}

User information:

ID: {$invoice['user_data']['ID']}
Name: {$invoice['user_data']['display_name']}
Email: {$invoice['user_data']['user_email']}

--------------------
{$site}";

  $subject = "Invoice #{$invoice['invoice_id']} has been paid";

  $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
  $subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');

  wp_mail($to, $subject, $message);
}

class wp_invoice_Date {

  function convert($string, $from_mask, $to_mask='', $return_unix=false) {
    // define the valid values that we will use to check
    // value => length
    $all = array(
        's' => 'ss',
        'i' => 'ii',
        'H' => 'HH',
        'y' => 'yy',
        'Y' => 'YYYY',
        'm' => 'mm',
        'd' => 'dd'
    );

    // this will give us a mask with full length fields
    $from_mask = str_replace(array_keys($all), $all, $from_mask);

    $vals = array();
    foreach ($all as $type => $chars) {
      // get the position of the current character
      if (($pos = strpos($from_mask, $chars)) === false)
        continue;

      // find the value in the original string
      $val = substr($string, $pos, strlen($chars));

      // store it for later processing
      $vals[$type] = $val;
    }

    foreach ($vals as $type => $val) {
      switch ($type) {
        case 's' :
          $seconds = $val;
          break;
        case 'i' :
          $minutes = $val;
          break;
        case 'H':
          $hours = $val;
          break;
        case 'y':
          $year = '20' . $val; // Year 3k bug right here
          break;
        case 'Y':
          $year = $val;
          break;
        case 'm':
          $month = $val;
          break;
        case 'd':
          $day = $val;
          break;
      }
    }

    $unix_time = mktime(
            (int) $hours, (int) $minutes, (int) $seconds, (int) $month, (int) $day, (int) $year);

    if ($return_unix)
      return $unix_time;

    return date($to_mask, $unix_time);
  }

}

function wp_invoice_fix_billing_meta_array($arr) {
  $narr = array();
  $counter = 1;
  while (list($key, $val) = each($arr)) {
    if (is_array($val)) {
      $val = array_remove_empty($val);
      if (count($val) != 0) {
        $narr[$counter] = $val;
        $counter++;
      }
    } else {
      if (trim($val) != "") {
        $narr[$counter] = $val;
        $counter++;
      }
    }
  }
  unset($arr);
  return $narr;
}

function wp_invoice_month_array() {
  return array(
      "01" => __("Jan", WP_INVOICE_TRANS_DOMAIN),
      "02" => __("Feb", WP_INVOICE_TRANS_DOMAIN),
      "03" => __("Mar", WP_INVOICE_TRANS_DOMAIN),
      "04" => __("Apr", WP_INVOICE_TRANS_DOMAIN),
      "05" => __("May", WP_INVOICE_TRANS_DOMAIN),
      "06" => __("Jun", WP_INVOICE_TRANS_DOMAIN),
      "07" => __("Jul", WP_INVOICE_TRANS_DOMAIN),
      "08" => __("Aug", WP_INVOICE_TRANS_DOMAIN),
      "09" => __("Sep", WP_INVOICE_TRANS_DOMAIN),
      "10" => __("Oct", WP_INVOICE_TRANS_DOMAIN),
      "11" => __("Nov", WP_INVOICE_TRANS_DOMAIN),
      "12" => __("Dec", WP_INVOICE_TRANS_DOMAIN));
}

function wp_invoice_show_message($content, $type="updated fade") {
  if ($content)
    echo "<div id=\"message\" class='$type' ><p>" . $content . "</p></div>";
}

function wp_invoice_accepted_payment($invoice_id = 'global') {

  if ($invoice_id == 'global') {

    if (get_option('wp_invoice_paypal_allow') == 'yes') {
      $payment_array['paypal']['name'] = 'paypal';
      $payment_array['paypal']['active'] = true;
      $payment_array['paypal']['nicename'] = "PayPal";
      if (get_option('wp_invoice_payment_method') == 'paypal' || get_option('wp_invoice_payment_method') == 'PayPal')
        $payment_array['paypal']['default'] = true;
    }

    if (get_option('wp_invoice_cc_allow') == 'yes') {
      $payment_array['cc']['name'] = 'cc';
      $payment_array['cc']['active'] = true;
      $payment_array['cc']['nicename'] = "Credit Card";
      if (get_option('wp_invoice_payment_method') == 'cc' || get_option('wp_invoice_payment_method') == 'Credit Card')
        $payment_array['cc']['default'] = true;
    }

    return $payment_array;
  } else {


    $invoice_info = new WPI_Core_GetInfo($invoice_id);
    $payment_array = array();
    if ($this->Core->Invoice->data['wp_invoice_payment_method'] != '') {
      $custom_default_payment = true;
    } else {
      $custom_default_payment = false;
    }

    if ($this->Core->Invoice->data['wp_invoice_paypal_allow'] == 'yes') {
      $payment_array['paypal']['name'] = 'paypal';
      $payment_array['paypal']['active'] = true;
      $payment_array['paypal']['nicename'] = "PayPal";


      if ($custom_default_payment && $this->Core->Invoice->data['wp_invoice_payment_method'] == 'paypal' || $this->Core->Invoice->data['wp_invoice_payment_method'] == 'PayPal')
        $payment_array['paypal']['default'] = true;
      if (!$custom_default_payment && empty($payment_array['paypal']['default']) && get_option('wp_invoice_payment_method') == 'paypal') {
        $payment_array['paypal']['default'] = true;
      }
    }

    if ($this->Core->Invoice->data['wp_invoice_cc_allow'] == 'yes') {
      $payment_array['cc']['name'] = 'cc';
      $payment_array['cc']['active'] = true;
      $payment_array['cc']['nicename'] = "Credit Card";
      if ($custom_default_payment && $this->Core->Invoice->data['wp_invoice_payment_method'] == 'cc' || $this->Core->Invoice->data['wp_invoice_payment_method'] == 'Credit Card')
        $payment_array['cc']['default'] = true;
      if (!$custom_default_payment && empty($payment_array['cc']['default']) && get_option('wp_invoice_payment_method') == 'cc')
        $payment_array['cc']['default'] = true;
    }

    return $payment_array;
  }
}

function wp_invoice_create_wp_user($p) {

  $username = $p['wp_invoice_new_user_username'];
  if (!$username or wp_invoice_username_taken($username)) {
    $username = wp_invoice_get_user_login_name();
  }

  $userdata = array(
      'user_pass' => wp_generate_password(),
      'user_login' => $username,
      'user_email' => $p['wp_invoice_new_user_email_address'],
      'first_name' => $p['wp_invoice_first_name'],
      'last_name' => $p['wp_invoice_last_name']);

  $wpuid = wp_insert_user($userdata);

  return $wpuid;
}

function wp_invoice_username_taken($username) {
  $user = get_userdatabylogin($username);
  return $user != false;
}

function wp_invoice_get_user_login_name() {
  return 'wp_invoice_' . rand(10000, 100000);
}

function wp_invoice_lipsum($quantity) {
  $words = split(' ', 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Etiam at purus. Etiam placerat orci ut neque. Mauris convallis, diam in egestas cursus, massa leo consequat pede, vitae aliquet tellus turpis in orci. Sed ac est. Aliquam ipsum libero, adipiscing eu, scelerisque id, dictum vitae, est. Nunc at nisl. Etiam fringilla, leo non venenatis euismod, turpis velit convallis nibh, a mollis metus felis non tortor. Nunc tincidunt. Quisque hendrerit, dolor vel consequat varius, dui odio faucibus est, sed lobortis ligula velit non lorem. Praesent non nibh sagittis erat rutrum placerat. Quisque posuere. Fusce tortor sem, laoreet sed, consectetuer et, tempor eu, nisi. Etiam leo ipsum, bibendum quis, suscipit nec, ultricies eget, nisi. Integer condimentum arcu sed purus. Fusce non enim. Etiam eros dolor, bibendum vitae, tempor ac, molestie nec, purus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Phasellus ultricies. Proin aliquam dui. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.');
  for ($i = 0; $i < $quantity; $i++) {
    $ipsum = $ipsum . ' ' . strtolower($words[rand(0, count($words) - 2)]);
  }
  return $ipsum;
}

if (!defined('print_var')) {

  function print_var($var) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
  }

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
 * @author Anton Korotkov
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