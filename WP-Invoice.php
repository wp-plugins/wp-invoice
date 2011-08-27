<?php
/**
Plugin Name: Web Invoicing and Billing
Plugin URI: http://twincitiestech.com/services/wp-invoice/
Description: Send itemized web-invoices directly to your clients.  Credit card payments may be accepted via Authorize.net, MerchantPlus NaviGate, or PayPal account. Recurring billing is also available via Authorize.net's ARB. Visit <a href="admin.php?page=wpi_page_settings">WP-Invoice Settings Page</a> to setup.
Author: UsabilityDynamics.com
Version: 3.02
Author URI: http://UsabilityDynamics.com/

Copyright 2011  Usability Dynamics, Inc.   (email : andy.potanin@UsabilityDynamics.com)

*/


/**
  Created by UsabilityDynamics.com
  (website: UsabilityDynamics.com       email : support@UsabilityDynamics.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; version 3 of the License.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

 
/** Path for Includes */
define('WPI_Path', WP_PLUGIN_DIR . '/wp-invoice');

/** Path for front-end links */
define('WPI_URL', WP_PLUGIN_URL . '/wp-invoice');

define("WP_INVOICE_VERSION_NUM", "3.02");
define("WP_INVOICE_TRANS_DOMAIN", "wp-invoice");
 
/** Directory paths */
define('WPI_Premium', WP_PLUGIN_DIR . '/wp-invoice/core/premium');
define('WPI_Gateways_Path', WP_PLUGIN_DIR . '/wp-invoice/core/gateways');
define('WPI_Gateways_URL', WPI_URL."/core/gateways");

// Always include everything below here
require_once("wpi_legacy.php");
require_once("core/wpi_functions.php");
require_once("core/wpi_settings.php");
require_once("core/wpi_invoice.php");
require_once("core/wpi_shorthand_functions.php");
require_once("core/wpi_gateway_base.php");
require_once("core/wpi_ui.php");
require_once("core/wpi_ajax.php");
require_once("core/wpi_widgets.php");
require_once("core/template.php");
require_once("core/wpi_payment_api.php");

 /** Loads all the metaboxes for the crm page */
include_once WPI_Path . '/core/ui/wpi_metaboxes.php';

// Set to true to display debugging messages throughout the UI
$wp_invoice_debug = true;

if (!class_exists('WPI_Core')) {
  class WPI_Core {
    static private $instance = NULL;
    public $User;
    public $Settings;
    public $Functions;
    public $UI;
    public $Ajax;
    public $version = WP_INVOICE_VERSION_NUM;
    public $uri;
    public $the_path;
    public $frontend_path;
    public $page_link;
    public $links;
    public $ui_path;
    public $current_user;
    
    /**
     * Singleton.
     *
     * @since 3.0
     *
     */
    static function getInstance() {
      if (is_null(self::$instance)) {
        self::$instance = new WPI_Core();
      }
      return self::$instance;
    }
    
    /**
     * Constructor
     *
     * @since 3.0
     *
     */
    function __construct() {
      global $wpdb, $wp_version, $user_ID, $wpi_settings, $wp_invoice_debug, $wpi_notification;
      
      // Load settings class and options
      $this->Settings = new WPI_Settings($this);
      $this->options = $this->Settings->options;
      
      // Load settings into global variable
      $wpi_settings = $this->options;
      
      // Load other classes
      $this->UI = new WPI_UI($this);
      $this->Functions = new WPI_Functions($this);
      $this->Ajax = new WPI_Ajax($this);
      
      // Set basic variables
      $this->plugin_basename = plugin_basename(__FILE__);
      $this->path = dirname(__FILE__);
      $this->file = basename(__FILE__);
      $this->directory = basename($this->path);
      $this->uri = WP_PLUGIN_URL . "/" . $this->directory;
      $this->the_path = WP_PLUGIN_URL . "/" . basename(dirname(__FILE__));
      $this->frontend_path = ($wpi_settings['force_https'] == 'true' ? str_replace('http://', 'https://', $this->the_path) : $this->the_path);
      
      //require_once('../'.$this->path.'/mailpress/MailPress.php');
      // Path to template files.  By default all UI files are in wp-invoice\core\ui.
      // This checks if there is a "wp-invoice" folder in the template directory
      $this->ui_path = ($this->options['use_custom_templates'] == "true" && is_dir(STYLESHEETPATH . "/wp-invoice") ? STYLESHEETPATH . "/wp-invoice" : $this->path . "/core/ui/");
      
      // Set additional dynamic settings
      $wpi_settings['frontend_path'] = $this->frontend_path;
      $wpi_settings['total_invoice_count'] = $wpdb->get_var("SELECT COUNT(*) FROM ". $wpdb->posts ." WHERE post_type = 'wpi_object' AND post_title != ''");
      $wpi_settings['links']['overview_page'] = 'admin.php?page=wpi_main';
      $wpi_settings['links']['settings_page'] = 'admin.php?page=wpi_page_settings';
      $wpi_settings['links']['manage_invoice'] = 'admin.php?page=wpi_page_manage_invoice';
      $wpi_settings['admin']['ui_path'] = ($this->options['use_custom_templates'] == "true" && is_dir(STYLESHEETPATH . "/wp-invoice") ? STYLESHEETPATH . "/wp-invoice" : $this->path . "/core/ui/");
      
      // Load Payment gateways
      $this->Functions->load_gateways();
      
      // Load the rest at the init level
      add_action('init', array($this, 'init'), 0);
    }
    
    /**
     * Loaded at 'init' action
     *
     * @since 3.0
     *
     */
    function init() {
      global $wpdb, $wp_version, $user_ID, $wpi_settings, $wp_invoice_debug, $wpi_notification;
      
      // Action for premium features to use to hook in before init
      do_action('wpi_pre_init');
      
      // Load premium features
      $this->Functions->load_premium();
      
      add_action('admin_head', array($this, 'admin_head'));
      
      do_action('wpi_premium_loaded');
      
      /* After Premium Features are loaded we update invoices types */
      $wpi_settings['types'] = apply_filters('wpi_object_types', $wpi_settings['types']);
      
      // Run Everytime
      $this->Functions->register_post_type();
      
      add_action("admin_enqueue_scripts", array('WPI_UI', "admin_enqueue_scripts"));
      add_action("admin_enqueue_scripts", array('WPI_UI', "admin_print_styles"));
      
      add_action('template_redirect', array($this, 'template_redirect'), 0);
      add_action('profile_update', array('WPI_Functions', 'save_update_profile'), 10, 2);
      add_action('edit_user_profile', array($this->UI, 'display_user_profile_fields'));
      add_action('show_user_profile', array($this->UI, 'display_user_profile_fields'));
      
      add_action('admin_menu', array('WPI_UI', 'admin_menu'));
      add_action('admin_init', array($this, 'admin_init'));
      
      add_action('wp_ajax_wpi_list_table', create_function('', ' die(WPI_Ajax::wpi_list_table());'));
      add_action('wp_ajax_wpi_get_user_date', create_function('', ' die(WPI_Ajax::get_user_date($_REQUEST["user_email"]));'));
      
      add_action('wp_ajax_wpi_ajax_check_plugin_updates', create_function('', ' die(WPI_Ajax::check_plugin_updates());'));
       
      add_action('wp_ajax_wpi_update_user_option', array('WPI_Ajax', 'update_user_option'));
      add_action('wp_ajax_wpi_update_wpi_option', array('WPI_Ajax', 'update_wpi_option'));
      add_action('wp_ajax_wpi_process_manual_event', array('WPI_Ajax', 'process_manual_event'));
      add_action('wp_ajax_wpi_get_notification_email', array('WPI_Ajax', 'get_notification_email'));
      add_action('wp_ajax_wpi_save_invoice', array('WPI_Ajax', 'save_invoice'));
      add_action('wp_ajax_wpi_get_status', array($this->Ajax, 'show_invoice_status'));
      add_action('wp_ajax_wpi_get_charges', array($this->Ajax, 'show_invoice_charges'));
      add_action('wp_ajax_wpi_payment_select', array('WPI_Ajax', 'payment_select'));
      add_action('wp_ajax_nopriv_wpi_payment_select', array('WPI_Ajax', 'payment_select'));
      add_action('wp_ajax_wpi_send_notification', array('WPI_Ajax', 'send_notification'));
      
      add_action('wp_ajax_wpi_import_legacy', array('WPI_Ajax', 'import_legacy_data'));
      
      add_action('wp_ajax_wpi_total_revalidate', array('WPI_Ajax', 'revalidate'));

      /** Add our actions for our payment handlers */
      add_action('wp_ajax_nopriv_wpi_gateway_process_payment', array('WPI_Gateway_Base', 'process_payment'));
      add_action('wp_ajax_wpi_gateway_process_payment', array('WPI_Gateway_Base', 'process_payment'));
      add_action('wp_ajax_nopriv_wpi_front_change_payment_form_ajax', array('WPI_Gateway_Base', 'change_payment_form_ajax'));
      add_action('wp_ajax_wpi_front_change_payment_form_ajax', array('WPI_Gateway_Base', 'change_payment_form_ajax'));
      /** Server Callback functionality */
      add_action('wp_ajax_nopriv_wpi_gateway_server_callback', array('WPI_Gateway_Base', 'server_callback'));
      add_action('wp_ajax_wpi_gateway_server_callback', array('WPI_Gateway_Base', 'server_callback'));

      //add_action('admin_print_styles', array('WPI_UI', 'admin_print_styles'));
      add_action('wpi_integrate_crm_user_panel', array('WPI_UI', 'crm_user_panel'));

      /** If we are in debug mode, lets add these actions */
      if($wpi_settings['debug']){
        add_action('wp_ajax_wpi_debug_get_invoice', array('WPI_Ajax', 'debug_get_invoice'));
      }

      add_action('the_post', array('WPI_Functions', 'the_post'));
      // Filters
      add_filter("plugin_action_links_{$this->plugin_basename}", array($this->Functions, 'set_plugin_page_settings_link'));
      add_filter("screen_settings", array($this->Functions, 'wpi_screen_options'), 10, 2);
      
      add_shortcode('wp-invoice-lookup', 'wp_invoice_lookup');

      // Load invoice lookup widget
      add_action('widgets_init', create_function('', 'return register_widget("InvoiceLookupWidget");'));

      // load user's invoice history widget
      add_action('widgets_init', create_function('', 'return register_widget("InvoiceHistoryWidget");'));

      // Find out if a wpi directory exists in template folder and use that, if not, use default template
      $wpi_settings['frontend_template_path'] = $this->Functions->template_path();
      $wpi_settings['default_template_path'] = $this->path . '/core/template/';

      // has to be set here, WPI_Core is loaded too early
      $this->current_user = $user_ID;
      $this->user_preferences['manage_page'] = get_user_meta($user_ID, 'wp_invoice_ui_manage_page');
      $this->user_preferences['main'] = get_user_meta($user_ID, 'wp_invoice_ui_main');

      if (version_compare($wp_version, '2.6', '<')) { // Using old WordPress
        load_plugin_textdomain(WP_INVOICE_TRANS_DOMAIN, PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/languages');
      }
      else {
        load_plugin_textdomain(WP_INVOICE_TRANS_DOMAIN, PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . '/languages', dirname(plugin_basename(__FILE__)) . '/languages');
      }

      if (!get_user_option("screen_layout_admin_page_wpi_invoice_edit")) {
        update_user_option($user_ID, 'screen_layout_admin_page_wpi_invoice_edit', 2, true);
      }

      if (!get_user_option("wpi_blank_item_rows")) {
        update_user_option($user_ID, 'wpi_blank_item_rows', 2, true);
      }

      if (!get_user_option("wpi_ui_display_global_tax")) {
        update_user_option($user_ID, 'wpi_ui_display_global_tax', 'true', true);
      }

      if (!get_user_option("wpi_ui_display_itemized_tax")) {
        update_user_option($user_ID, 'wpi_ui_display_itemized_tax', 'false', true);
      }

      if (!get_user_option("wpi_ui_payment_method_options")) {
        update_user_option($user_ID, 'wpi_ui_payment_method_options', 'true', true);
      }

      if (!get_user_option("wpi_ui_currency_options")) {
        update_user_option($user_ID, 'wpi_ui_currency_options', 'true', true);
      }

      wp_register_script('jquery.ui.custom.wp-invoice', WPI_URL . "/core/js/jquery-ui-1.8.13.custom.min.js", array('jquery'));
      wp_register_script('jquery.bind', WPI_URL . "/core/js/jquery.bind.js", array('jquery'));
      wp_register_script('ui.datepicker', WPI_URL . "/core/js/ui.datepicker.js", array('jquery'));
      wp_register_script('jquery.autocomplete', WPI_URL . "/core/js/jquery.autocomplete.pack.js", array('jquery'));
      wp_register_script('jquery.maskedinput', WPI_URL . "/core/js/jquery.maskedinput.js", array('jquery'));
      wp_register_script('jquery.form', WPI_URL . "/core/js/jquery.form.js", array('jquery'));
      wp_register_script('jquery.validate', 'https://ajax.aspnetcdn.com/ajax/jquery.validate/1.8.1/jquery.validate.min.js', array('jquery'));
      wp_register_script('jquery.cookie', WPI_URL . "/core/js/jquery.cookie.js", array('jquery'));
      wp_register_script('jquery.formatCurrency', WPI_URL . "/core/js/jquery.formatCurrency.js", array('jquery'));
      wp_register_script('jquery.impromptu', WPI_URL . "/core/js/jquery-impromptu.1.7.js", array('jquery'));
      wp_register_script('jquery.delegate', WPI_URL . "/core/js/jquery.delegate-1.1.min.js", array('jquery'));
      wp_register_script('jquery.field', WPI_URL . "/core/js/jquery.field.min.js", array('jquery'));
      wp_register_script('wpi-gateways', WPI_Gateways_URL . '/js/wpi_gateways.js.php', array('jquery'));
      wp_register_script('jquery.field', WPI_URL . "/third-party/dataTables/jquery.dataTables.js", array('jquery'));
      wp_register_script('jsapi', 'https://www.google.com/jsapi');
      wp_register_script('jquery-data-tables', WPI_URL . "/third-party/dataTables/jquery.dataTables.min.js", array('jquery'));
      
      wp_register_style('jquery-data-tables', WPI_URL . "/core/css/wpi-data-tables.css");
      
      //** Masure dependancies are identified in case this script is included in other pages */      
      wp_register_script('wp-invoice-events', WPI_URL . "/core/js/wpi-events.js", array(
        'jquery', 
        'jquery.formatCurrency', 
        'jquery.ui.custom.wp-invoice'
        ));
        
      wp_register_script('wp-invoice-functions', WPI_URL . "/core/js/wpi-functions.js",  array('wp-invoice-events'));      
      
      wp_register_script('jquery-ui-sortable', '/wp-includes/js/jquery/ui.sortable.js', array('jquery'));
      //wp_register_script('jquery.json', WPI_URL . '/core/js/jquery.json-2.2.min.js', array('jquery'));

      // Find and register theme-specific style if a custom wp_properties.css does not exist in theme
      if(!$this->Functions->is_true($wpi_settings['do_not_load_theme_specific_css']) && $this->Functions->has_theme_specific_stylesheet()) {
        wp_register_style('wpi-theme-specific', WPI_URL . "/core/template/theme-specific/".get_option('template').".css",  array(),WP_INVOICE_VERSION_NUM);
      }

      if($this->Functions->is_true($wpi_settings['use_css'])) {
        wp_register_style('wpi-default-style', WPI_URL . "/core/template/wpi-default-style.css", array(),WP_INVOICE_VERSION_NUM);
      }
    }
    
    /**
     * Pre-Header functions
     *
     * Loads after admin_enqueue_scripts, admin_print_styles, and admin_head.
     * Loads before: favorite_actions, screen_meta
     *
     * @check if wpi_pre_header_ action is being used for anything, it doesn't seem to be the right placement because 'admin_head' is fired off after headers have been sent
     *
     * @since 3.0
     */
    function admin_head() {
      global $current_screen;
      if ( $current_screen->id == 'invoice_page_wpi_page_manage_invoice' ) {
        //echo '<style>#screen-options-link-wrap{display:none !important;}</style>';
      }
      
      do_action("wpi_pre_header_{$current_screen->id}", $current_screen->id);
      //do_action("wpi_print_styles");

      // Load contextual help for all pages
      add_filter('contextual_help', array('WPI_UI', 'contextual_help'));

    }

    /**
     * Perform back-end administrative functions
     *
     *
     * @since 3.0
     */
    function admin_init() {
      global $wpi_settings;
      /*if (isset($_REQUEST['wpi_do'])) {
        $this->Functions->DoFunction($_REQUEST['wpi_do']);
      }*/
      
      if ( !empty( $_REQUEST['wpi_settings'] ) && is_array($_REQUEST['wpi_settings']) ) {
        $this->Settings->SaveSettings($_REQUEST['wpi_settings']);
      }
      
      add_filter("manage_{$wpi_settings['pages']['main']}_columns", array( 'WPI_UI', 'overview_columns' ), 10, 3 );
      
      /* Add metaboxes */
      if(isset($wpi_settings['pages']) && is_array($wpi_settings['pages'])) {
        $this->add_metaboxes($wpi_settings['pages']);
      }
    }
    
    /*
     * Add metaboxes to WPI pages
     * 
     */
    function add_metaboxes($screens) {
      foreach($screens as $screen) {
        if(!class_exists($screen)) {
          continue;
        }
        
        
        $location_prefixes = array('side_', 'normal_', 'advanced_');
        
        foreach(get_class_methods($screen) as $box) {
          // Set context and priority if specified for box
          $context = 'normal';
          
          if(strpos($box, "side_") === 0) {
            $context = 'side';
          }
          if(strpos($box, "advanced_") === 0) {
            $context = 'advanced';
          }
          
          // Get name from slug
          $label = WPI_Functions::slug_to_label(str_replace($location_prefixes, '', $box));

          add_meta_box( $box, $label , array($screen,$box), $screen, $context, 'high');
        }
      }
    }
    
    /**
     * @author Anton Korotkov
     * @TODO It make sense to add some option inyo settings to be able to set which types are viewable.
     * @return array 
     */
    function viewable_types() {
      return array( 'paid', 'active', 'pending' );
    }
    
    /**
     * Handles validation when somebody is attempting to view an invoice.  
     * If validation is passsed, we add the necessary
     * filters to display the invoice header and page content;
     * Global $invoice_id variable set by WPI_Functions::validate_page_hash();
     */
    function template_redirect() {
      global $wpdb, $invoice_id, $wpi_user_id, $wpi_settings, $wpi_invoice_object, $post;
      
      //** Alwys load styles without checking if given page has an invoice */
      wp_enqueue_style('wpi-theme-specific');
      wp_enqueue_style('wpi-default-style');
      
      /* Determine if the current page is invoice's page */
      if ($wpi_settings['web_invoice_page'] != $post->ID) {
        return;
      }
      
      // If invoice_id is passed, run validate_page_hash  to make sure this is the right page and invoice_id exists
      if (isset($_GET['invoice_id'])) {
        
        if (WPI_Functions::validate_page_hash(mysql_escape_string($_GET['invoice_id']))) {
          
          /** load global invoice object */
          $post_id = wpi_invoice_id_to_post_id($invoice_id);
          
          $wpi_invoice_object = new WPI_Invoice();
          $wpi_invoice_object->load_invoice("id=$post_id");
          $wpi_invoice_object->data;
          
          add_filter('viewable_invoice_types', array( $this, 'viewable_types' ));
          
          //* Determine if current invoice object is "viewable" */
          if(!in_array($wpi_invoice_object->data['post_status'], apply_filters('viewable_invoice_types', array('active')))) {
            return;
          }
          
          // Load front end scripts
          wp_enqueue_script('jquery.validate');
          wp_enqueue_script('wpi-gateways');
          wp_enqueue_script('jquery.maskedinput');
          wp_enqueue_script('wpi-frontend-scripts');

          //** Apply Filters to the invoice description */
          add_action('wpi_description', 'wpautop');
          add_action('wpi_description', 'wptexturize');
          add_action('wpi_description', 'shortcode_unautop');
          add_action('wpi_description', 'convert_chars');
          add_action('wpi_description', 'capital_P_dangit');
          
          // Declare the variable that will hold our AJAX url for JavaScript purposes
          wp_localize_script('jquery', 'wpi_ajax', array( 'url' => admin_url( 'admin-ajax.php' ) ) );
          
          add_action('wp_head', array('WPI_UI', 'frontend_header'));
          
          if ($wpi_settings['replace_page_title_with_subject'] == 'true' || $wpi_settings['hide_page_title'] == 'true') {
            add_action('wp_title', array('WPI_UI', 'wp_title'), 0, 3);
          }
          
          if ($wpi_settings['replace_page_heading_with_subject'] == 'true' || $wpi_settings['hide_page_title'] == 'true') {
            add_action('the_title', array('WPI_UI', 'the_title'), 0, 2);
          }
          
          add_action('the_content', array('WPI_UI', 'the_content'));
          
          if ( $wpi_settings['where_to_display'] == 'replace_tag' ) {
            add_shortcode('wp-invoice', array('WPI_UI', 'the_content_shortcode'));
          }
        
        } else {
          /* Show 404 when invoice doesn't exist */
          $not_found = get_query_template('404');
          require_once $not_found;
          die();
        }
        
      }
      
      // Fixed WordPress filters if page is being opened in HTTPS mode
      if (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {
        if(function_exists('force_ssl')) {
          add_filter('option_siteurl', 'force_ssl');
          add_filter('option_home', 'force_ssl');
          add_filter('option_url', 'force_ssl');
          add_filter('option_wpurl', 'force_ssl');
          add_filter('option_stylesheet_url', 'force_ssl');
          add_filter('option_template_url', 'force_ssl');
          add_filter('script_loader_src', 'force_ssl');
        }
      }
      
      // Lookup functionality
      if(isset($_POST['wp_invoice_lookup_input'])) {
        header("location:" . get_invoice_permalink($_POST['wp_invoice_lookup_input']));
        //exit;
      }
    }
  }
}

// Run hooks when plugin is activated or deactivated
register_activation_hook(__FILE__, array("WPI_Functions", 'Activate'));

register_deactivation_hook(__FILE__, array("WPI_Functions", 'Deactivate'));

// Load plugin after_setup_theme
add_action("after_setup_theme", array('WPI_Core', 'getInstance'));

