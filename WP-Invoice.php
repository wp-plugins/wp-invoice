<?php
/*
Plugin Name: Web Invoicing and Billing
Plugin URI: http://twincitiestech.com/services/wp-invoice/
Description: Send itemized web-invoices directly to your clients.  Credit card payments may be accepted via Authorize.net, MerchantPlus NaviGate, or PayPal account. Recurring billing is also available via Authorize.net's ARB. Visit <a href="admin.php?page=invoice_settings">WP-Invoice Settings Page</a> to setup.
Author: TwinCitiesTech.com
Version: 2.038
Author URI: http://twincitiestech.com/

Copyright 2009  TwinCitiesTech.com Inc.   (email : andy.potanin@twincitiestech.com)
*/

/*
	Created by TwinCitiesTech.com
	(website: twincitiestech.com       email : support@twincitiestech.com)

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

if(is_admin()) {
/*
	ini_set('display_errors', 1);
	ini_set('log_errors', 1);
	error_reporting(E_ALL);
*/
}

define("WP_INVOICE_VERSION_NUM", "2.038");
define("WP_INVOICE_TRANS_DOMAIN", "wp-invoice");
define("WP_INVOICE_UI_PATH", ABSPATH . "/wp-content/plugins/wp-invoice/core/ui/");

require_once("core/functions.php");
require_once("core/display.php");
require_once("core/frontend.php");
require_once("core/widgets.php");
require_once("core/invoice_class.php");

$wp_invoice_debug = false;
$WP_Invoice = new WP_Invoice();	

class WP_Invoice {

	var $Invoice;
	var $wp_invoice_user_level = 8;
	var $uri;
	var $the_path;
	var $frontend_path;
	
	function the_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		return $path;
	}
	
	function frontend_path() {
		$path =	WP_PLUGIN_URL."/".basename(dirname(__FILE__));
		if(get_option('wp_invoice_force_https') == 'true') $path = str_replace('http://','https://',$path);
		return $path;
	}
		
	function WP_Invoice() {
		
		$version = get_option('wp_invoice_version');

		$this->path = dirname(__FILE__);
		$this->file = basename(__FILE__);
		$this->directory = basename($this->path);
		$this->uri = WP_PLUGIN_URL."/".$this->directory;
		$this->the_path = $this->the_path();

		$this->frontend_path = $this->frontend_path();
				
		add_action('init',  array($this, 'init'),0);
		add_action('template_redirect',  array($this, 'print_frontend_scripts')); 
		add_action('template_redirect',  array($this, 'template_redirect'),0);
		add_action('profile_update','wp_invoice_profile_update');
		add_action('edit_user_profile', 'wp_invoice_user_profile_fields');
		add_action('show_user_profile', 'wp_invoice_user_profile_fields');
		add_action('admin_menu', array($this, 'wp_invoice_add_pages'));
		add_action('wp_head', 'wp_invoice_frontend_header'); 
		add_action('wp', array($this, 'api'));
		add_action('admin_init', array($this, 'admin_init'));

		add_action('contextual_help', 'wp_invoice_contextual_help_list');
		add_filter('favorite_actions', array(&$this, 'favorites'));
		
		register_activation_hook(__FILE__, array(&$this, 'install'));
		register_deactivation_hook(__FILE__, "wp_invoice_deactivation");
		add_shortcode('wp-invoice-lookup', 'wp_invoice_lookup');

		// Only run the content script if we are not using the replace_tag method.  We want to avoid running the function twice
		if(get_option('wp_invoice_where_to_display') != 'replace_tag') { add_filter('the_content', 'wp_invoice_the_content');  } else { add_shortcode('wp-invoice', 'wp_invoice_the_content'); 	}
		
		$this->SetUserAccess(get_option('wp_invoice_user_level'));

		// Load invoice lookup widget
		add_action('widgets_init', create_function('', 'return register_widget("InvoiceLookupWidget");'));
		// load user's invoice history widget
		add_action('widgets_init', create_function('', 'return register_widget("InvoiceHistoryWidget");'));

		
	}
	
	function template_redirect() {
		if(isset($_POST['wp_invoice_lookup_input'])) { 
			header("location:" . wp_invoice_build_invoice_link($_POST['wp_invoice_lookup_input']));
			exit;
		}
	}
		
	function SetUserAccess($level = 8) {
	
 		if(empty($level))
			$level = 8;
			
		$this->wp_invoice_user_level = $level;
	}

	function tablename ($table) {
		global $table_prefix;
		return $table_prefix.'invoice_'.$table;
	}


 
	function wp_invoice_add_pages() {
		global $_wp_last_object_menu;
		/*
		toplevel_page_web-invoice_page_overview
		web-invoice_page_new_invoice
		web-invoice_page_recurring_billing
		web-invoice_page_invoice_settings
		*/
		
			
		//necessary to insert the page link correctly into admin menu
		$_wp_last_object_menu++;
		
		$menus['main'] = add_menu_page('Web Invoice System', 'Web Invoice',  $this->wp_invoice_user_level,'web-invoice_page_overview', array(&$this,'invoice_overview'),$this->uri."/core/images/wp_invoice.png", $_wp_last_object_menu);
		$menus['web-invoice_page_overview'] = add_submenu_page( 'web-invoice_page_overview', "Overview", "Overview", $this->wp_invoice_user_level, 'web-invoice_page_overview', array(&$this,'invoice_overview'));
		$menus['manage_invoice'] = add_submenu_page( 'web-invoice_page_overview', "Manage Invoice", "New Invoice", $this->wp_invoice_user_level, 'new_invoice', array(&$this,'new_invoice'));
		//$menus['reports'] = add_submenu_page( 'web-invoice_page_overview', "Reports", "Reports", $this->wp_invoice_user_level, 'reports_page', array(&$this,'reports_page'));
		
		if($_REQUEST['wp_invoice_use_recurring'] != 'no' && get_option('wp_invoice_use_recurring') == 'yes' || $_REQUEST[wp_invoice_use_recurring] == 'yes')
			$menus['recurring_page']= add_submenu_page( 'web-invoice_page_overview', "Recurring Billing", "Recurring Billing", $this->wp_invoice_user_level, 'recurring_billing', array(&$this,'recurring'));
			
		$menus['settings_page'] = add_submenu_page( 'web-invoice_page_overview', "Settings", "Settings", $this->wp_invoice_user_level, 'invoice_settings', array(&$this,'settings_page'));
		
		foreach($menus as $name => $menu) {
			add_action("admin_print_scripts-$menu", array($this, 'admin_print_scripts'));
			//add_action("admin_print_styles-$menu", array($this, 'admin_print_styles'));
		}
		
		// Ability to toggle columns on overview page
		$overview_columns  = array(
				'cb' => '<input type="checkbox" />',
				'subject' => __('Subject'),
				'balance' => __('Balance'),
				'user' => __('User'),
				'user_email' => __('User Email'),
				'wp_username' => __('Username'),
				'company_name' => __('Company Name'),
				'status' => __('Status'),
				'date_sent' => __('Date Sent'),
				'due_date' => __('Due Date'),
				'invoice_id' => __('Invoice ID')
 			);

		// Ability to toggle columns on recurring page
		$recurring_columns  = array(
				'cb' => '<input type="checkbox" />',
				'subject' => __('Subject'),
				'balance' => __('Balance'),
				'user' => __('User'),
				'user_email' => __('User Email'),
				'wp_username' => __('Username'),
				'company_name' => __('Company Name'),
				'status' => __('Status'),
				'date_sent' => __('Date Sent'),
				'date_started' => __('Date Started'),
				//'end_date' => __('End Date'),
				'total_cycles' => __('Billing Cycles'),
				'invoice_id' => __('Invoice ID')
 			);
			
			
		register_column_headers("toplevel_page_web-invoice_page_overview", $overview_columns);	
		register_column_headers("web-invoice_page_recurring_billing", $recurring_columns);	

		add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);		
		
		//Metaboxes
		add_meta_box('wp_invoice_metabox_invoice_details', __('Invoice Details',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_invoice_details', 'web-invoice_page_new_invoice', 'normal', 'high');
		
		if(get_option('wp_invoice_use_recurring') == 'yes')
			add_meta_box('wp_invoice_metabox_recurring_billing', __('Recurring Options',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_recurring_billing', 'web-invoice_page_new_invoice', 'side', 'high');
		
		add_meta_box('wp_invoice_metabox_billing', __('Billing Settings',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_billing', 'web-invoice_page_new_invoice', 'normal', 'core');
		add_meta_box('wp_invoice_metabox_publish', __('Publish',WP_INVOICE_TRANS_DOMAIN), 'wp_invoice_metabox_publish', 'web-invoice_page_new_invoice', 'side', 'high');

 	}


/*
	Add columns to invoice editing page
*/
	function on_screen_layout_columns($columns, $screen) {
		if ($screen == 'web-invoice_page_new_invoice') {
			$columns['web-invoice_page_new_invoice'] = 2;
		}
		return $columns;
	}	
	 
	 
	function admin_print_scripts() {
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');

		wp_enqueue_script('jquery.autocomplete',$this->uri."/core/js/jquery.autocomplete.pack.js", array('jquery'));
		wp_enqueue_script('jquery.cookie',$this->uri."/core/js/jquery.cookie.js", array('jquery'));
		wp_enqueue_script('jquery.livequery',$this->uri."/core/js/jquery.livequery.js", array('jquery'));
		wp_enqueue_script('jquery.formatCurrency',$this->uri."/core/js/jquery.formatCurrency.js", array('jquery'));
		wp_enqueue_script('jquery.idTabs',$this->uri."/core/js/jquery.idTabs.min.js", array('jquery'));
		wp_enqueue_script('jquery.impromptu',$this->uri."/core/js/jquery-impromptu.1.7.js", array('jquery'));
		wp_enqueue_script('jquery.field',$this->uri."/core/js/jquery.field.min.js", array('jquery'));
		wp_enqueue_script('jquery.calculation',$this->uri."/core/js/jquery.calculation.min.js", array('jquery'));
		wp_enqueue_script('jquery.tablesorter',$this->uri."/core/js/jquery.tablesorter.min.js", array('jquery'));
		wp_enqueue_script('jquery.autogrow-textarea',$this->uri."/core/js/jquery.autogrow-textarea.js", array('jquery') );
		wp_enqueue_script('wp-invoice',$this->uri."/core/js/wp-invoice-2.0.js", array('jquery') );		

		wp_enqueue_script('google-api', 'http://www.google.com/jsapi');
		
   		wp_enqueue_style('wp_invoice_css', $this->uri . "/core/css/wp_admin-2.0.css");
		wp_print_styles();

		?>

	<?php
	}
	
	function print_frontend_scripts() {
		if(get_option('wp_invoice_web_invoice_page') != '' && is_page(get_option('wp_invoice_web_invoice_page'))) {
			wp_enqueue_script('jquery.maskedinput',$this->frontend_path."/core/js/jquery.maskedinput.js", array('jquery'));
			wp_enqueue_script('jquery.form',$this->frontend_path."/core/js/jquery.form.js", array('jquery') );
		}

	}
	
	function reports_page() {
		global $wpdb;
	
	
		include 'core/ui/ui_reports_page.php';
	}
	
/*
	Displays either the user selection form or an existing invoice
*/
	function new_invoice() {
		//wpi_qc($_REQUEST);
		$invoice_id = (!empty($_REQUEST['invoice_id']) ? $_REQUEST['invoice_id'] : false);
		
		if($_REQUEST['wp_invoice_action'] != 'save_and_preview' && ($invoice_id || isset($_REQUEST['wp_invoice_userlookup']))) {
			wp_invoice_options_manageInvoice();
		} elseif($_REQUEST['wp_invoice_action'] =='save_and_preview') {
			wp_invoice_process_invoice_update($invoice_id);
			wp_invoice_saved_preview($invoice_id);
			
		} else {
			wp_invoice_draw_user_selection_form();
		}
	}	
	
	function favorites ($actions) {
		$key = 'admin.php?page=new_invoice';
		$actions[$key] = array('New Invoice',$this->wp_invoice_user_level);
		return $actions;
	}
	
	function recurring() {
		global $wpdb;
	
		if(isset($_REQUEST['delete_single_invoice']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_invoice_delete_' . $_REQUEST['delete_single_invoice']))
			$messages[] = wp_invoice_delete($_REQUEST['delete_single_invoice']);
			
		if(isset($_REQUEST['wp_invoice_action'])) {
			$action = $_REQUEST['wp_invoice_action'];
			$invoice_array = $_REQUEST['multiple_invoices'];
			
			switch($action) {
 
				case 'archive_invoice':				
					$messages[] = wp_invoice_archive($invoice_array);
				break;		
				
				case 'send_invoice':
					$messages[] = wp_invoice_send_bulk_email($invoice_array);
				break;								
				
				case 'unrachive_invoice':
					$messages[] = wp_invoice_unarchive($invoice_array);
				break;		
				
				case 'mark_as_sent':
					$messages[] = wp_invoice_mark_as_sent($invoice_array);
				break;		
		
				case 'mark_as_paid':
					$messages[] = wp_invoice_mark_as_paid($invoice_array);
				break;		
		
				case 'mark_as_unpaid':
					$messages[] = wp_invoice_mark_as_unpaid($invoice_array);
				break;		
			}
		}
		
			
		if($_REQUEST[action] == 'post_save_and_preview') {
			$invoice_id = $_REQUEST['invoice_id'];
 			if($_REQUEST['wp_invoice_action'] == 'Email to Client') {
				wp_invoice_update_invoice_meta($invoice_id, 'email_message_content', $_REQUEST['wp_invoice_email_message_content']);
				wp_invoice_update_invoice_meta($invoice_id, 'email_message_subject', $_REQUEST['wp_invoice_email_message_subject']);
				$messages[] = (wp_send_single_invoice($invoice_id) ? "Message sent successfully.": "Error sending message." );
			}			
			
			if($_REQUEST['wp_invoice_action'] == 'Save for Later') {			
				// Do nothing, invoice was already by visiting the save_and_preview page
			}		
		}

		
		include WP_INVOICE_UI_PATH . 'ui_overview_recurring_page.php';
	}
	
	function api() {
		if(get_option('wp_invoice_web_invoice_page') != '' && is_page(get_option('wp_invoice_web_invoice_page'))) {
			if((get_option('wp_invoice_moneybookers_merchant') == 'True') && isset($_POST['mb_transaction_id']) && isset($_POST['status'])) {
				require_once("core/gateways/moneybookers.class.php");
				$moneybookers_obj = new WP_Invoice_Moneybookers($_POST['transaction_id']);
				$moneybookers_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			} else if((get_option('wp_invoice_alertpay_merchant') == 'True') && isset($_POST['ap_itemname']) && isset($_POST['ap_securitycode'])) {
				require_once("core/gateways/alertpay.class.php");
				$alertpay_obj = new WP_Invoice_AlertPay($_POST['ap_itemname']);
				$alertpay_obj->processRequest($_SERVER['REMOTE_ADDR'], $_POST);
			}
		}
	}
	
	function invoice_overview() {
		global $wpdb;
		
  
		// Save firt-time-setup configuration
		if(isset($_POST['wp_invoice_web_invoice_page'])) update_option('wp_invoice_web_invoice_page', $_POST['wp_invoice_web_invoice_page']);
		if(isset($_POST['wp_invoice_use_recurring'])) update_option('wp_invoice_use_recurring', $_POST['wp_invoice_use_recurring']);
		if(isset($_POST['wp_invoice_where_to_display'])) update_option('wp_invoice_where_to_display', $_POST['wp_invoice_where_to_display']);
 
		if(isset($_REQUEST['delete_single_invoice']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wp_invoice_delete_' . $_REQUEST['delete_single_invoice']))
			$messages[] = wp_invoice_delete($_REQUEST['delete_single_invoice']);
			
		if(isset($_REQUEST['wp_invoice_action'])) {
			$action = $_REQUEST['wp_invoice_action'];
			$invoice_array = $_REQUEST['multiple_invoices'];
			
			switch($action) {
 
				case 'archive_invoice':				
					$messages[] = wp_invoice_archive($invoice_array);
				break;					
				
				case 'send_invoice':				
					$messages[] = wp_invoice_send_bulk_email($invoice_array);
				break;		
				
				case 'unrachive_invoice':
					$messages[] = wp_invoice_unarchive($invoice_array);
				break;		
				
				case 'mark_as_sent':
					$messages[] = wp_invoice_mark_as_sent($invoice_array);
				break;		
		
				case 'mark_as_paid':
					$messages[] = wp_invoice_mark_as_paid($invoice_array);
				break;		
		
				case 'mark_as_unpaid':
					$messages[] = wp_invoice_mark_as_unpaid($invoice_array);
				break;		
			}
		}
		
			
		if($_REQUEST[action] == 'post_save_and_preview') {
			$invoice_id = $_REQUEST['invoice_id'];
 			if($_REQUEST['wp_invoice_action'] == 'Email to Client') {
				wp_invoice_update_invoice_meta($invoice_id, 'email_message_content', $_REQUEST['wp_invoice_email_message_content']);
				wp_invoice_update_invoice_meta($invoice_id, 'email_message_subject', $_REQUEST['wp_invoice_email_message_subject']);
				$messages[] = (wp_send_single_invoice($invoice_id) ? "Message sent successfully.": "Error sending message." );
			}			
			
			if($_REQUEST['wp_invoice_action'] == 'Save for Later') {			
				// Do nothing, invoice was already by visiting the save_and_preview page
			}		
		}
		
		
  
 		$wp_invoice_web_invoice_page = get_option("wp_invoice_web_invoice_page");
 
  
		if(!$wp_invoice_web_invoice_page) {
			include WP_INVOICE_UI_PATH . 'first_time_setup.php';
		} else {		
			include WP_INVOICE_UI_PATH . 'ui_overview_page.php';
		}
		
 	}
	
	function settings_page() {
 		
		if(wp_verify_nonce($_REQUEST['_wpnonce'], 'wpi_update_settings'))				
			wp_invoice_process_settings();
		
		wp_invoice_show_settings();
	}
	
	function admin_init() {
	
		
		// Admin Redirections. Has to go here to load before headers
		if( $_REQUEST['wp_invoice_action'] == __('Continue Editing', WP_INVOICE_TRANS_DOMAIN)) {		
			wp_redirect(admin_url("admin.php?page=new_invoice&wp_invoice_action=doInvoice&invoice_id={$_REQUEST['invoice_id']}"));
			die();
		}
	
	
	
	}

	function init() {
		global $wpdb, $wp_version;

	
	
		
		
		if (version_compare($wp_version, '2.6', '<')) // Using old WordPress
        	load_plugin_textdomain(WP_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages');
        else
        	load_plugin_textdomain(WP_INVOICE_TRANS_DOMAIN, PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/languages', dirname(plugin_basename(__FILE__)).'/languages');

						
			// Make sure proper MD5 is being passed (32 chars), and strip of everything but numbers and letters
			if(isset($_GET['invoice_id']) && strlen($_GET['invoice_id']) != 32) unset($_GET['invoice_id']); 
			$_GET['invoice_id'] = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['invoice_id']);
			
			if(!empty($_GET['invoice_id'])) {
			
				$md5_invoice_id = $_GET['invoice_id'];

				// Convert MD5 hash into Actual Invoice ID
				$all_invoices = $wpdb->get_col("SELECT invoice_num FROM ".WP_Invoice::tablename('main')." ");
				foreach ($all_invoices as $value) { if(md5($value) == $md5_invoice_id) {$invoice_id = $value;} }		
						
				//Check if invoice exists, SSL enforcement is setp, and we are not currently browing HTTPS,  then reload page into HTTPS 
				if(!function_exists('wp_https_redirect')) {
					if(wp_invoice_does_invoice_exist($invoice_id) && get_option('wp_invoice_force_https') == 'true' && $_SERVER['HTTPS'] != "on") {  header("Location: https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']); exit;}
				}
				
			}
			
			if(isset($_POST['wp_invoice_id_hash'])) {
			
				$md5_invoice_id = $_POST['wp_invoice_id_hash'];

				// Convert MD5 hash into Actual Invoice ID
				$all_invoices = $wpdb->get_col("SELECT invoice_num FROM ".WP_Invoice::tablename('main')." ");
				foreach ($all_invoices as $value) { if(md5($value) == $md5_invoice_id) {$invoice_id = $value;} }
				
				//Check to see if this is a credit card transaction, if so process
				if(wp_invoice_does_invoice_exist($invoice_id)) { wp_invoice_process_cc_transaction($_POST); exit; }
				}				

		
		if(empty($_GET['invoice_id'])) unset($_GET['invoice_id']);
		}

		
		function install() {
			
			global $wpdb;

			//change old table name to new one
			if($wpdb->get_var("SHOW TABLES LIKE 'wp_invoice'")) {
			global $table_prefix;
			$sql_update = "RENAME TABLE ".$table_prefix."invoice TO ". WP_Invoice::tablename('main')."";
			$wpdb->query($sql_update);
			}
			
			
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

				
		if($wpdb->get_var("SHOW TABLES LIKE '". WP_Invoice::tablename('main') ."'") != WP_Invoice::tablename('main')) {
			$sql_main = "CREATE TABLE ". WP_Invoice::tablename('main') ." (
				  id int(11) NOT NULL auto_increment,
				  amount double default '0',
				  description text NOT NULL,
				  invoice_num varchar(45) NOT NULL default '',
				  user_id varchar(20) NOT NULL default '',
				  subject text NOT NULL,
				  itemized text NOT NULL,
				  status int(11) NOT NULL,
				  PRIMARY KEY  (id),
				  UNIQUE KEY invoice_num (invoice_num));";
			dbDelta($sql_main);
		}

		if($wpdb->get_var("SHOW TABLES LIKE '". WP_Invoice::tablename('log') ."'") != WP_Invoice::tablename('log')) {
				$sql_log = "CREATE TABLE " . WP_Invoice::tablename('log') . " (
				  id bigint(20) NOT NULL auto_increment,
				  invoice_id int(11) NOT NULL default '0',
				  action_type varchar(255) NOT NULL,
				  value longtext NOT NULL,
				  time_stamp timestamp NOT NULL,
				  PRIMARY KEY  (id));";
				dbDelta($sql_log);
				}
			

		if($wpdb->get_var("SHOW TABLES LIKE '". WP_Invoice::tablename('meta') ."'") != WP_Invoice::tablename('meta')) {
				$sql_meta= "CREATE TABLE " . WP_Invoice::tablename('meta') . "(
				meta_id bigint(20) NOT NULL AUTO_INCREMENT,
				PRIMARY KEY  (meta_id),
				invoice_id bigint(20) NOT NULL default '0',
				meta_key varchar(255) default NULL,
				meta_value longtext);";
				dbDelta($sql_meta);
				}
			
	
	
			
			// Fix Paid Statuses  from Old Version where they were kept in main table
		$all_invoices = $wpdb->get_results("SELECT invoice_num FROM ".WP_Invoice::tablename('main')." WHERE status ='1'");
		if(!empty($all_invoices)) {
			foreach ($all_invoices as $invoice) {
				wp_invoice_update_invoice_meta($invoice->invoice_num,'paid_status','paid');
			}
		}

		// Fix old phone_number and street_address to be without the dash
		$all_users_with_meta = $wpdb->get_col("SELECT DISTINCT user_id FROM $wpdb->usermeta");
		if(!empty($all_users_with_meta)) {
			foreach ($all_users_with_meta as $user) {
				if(get_usermeta($user, 'street_address')) { update_usermeta($user, 'streetaddress',get_usermeta($user, 'street_address')); delete_usermeta($user, 'street_address',''); }
				if(get_usermeta($user, 'phone_number')) { update_usermeta($user, 'phonenumber',get_usermeta($user, 'phone_number')); delete_usermeta($user, 'phone_number',''); }
				if(get_usermeta($user, 'country')) { update_usermeta($user, 'country',get_usermeta($user, 'country')); }
				
			}
		}

		// Help with updating
		if(get_option('wp_invoice_paypal_address','') != '') update_option('wp_invoice_paypal_allow', 'yes');
		if(get_option('wp_invoice_gateway_username','') != '') update_option('wp_invoice_cc_allow', 'yes');
	
		// Localization Labels
		add_option('wp_invoice_custom_label_tax', "Tax");
		add_option('wp_invoice_custom_zip_label', "Zip Code");
		
		// WP-Invoice Lookup
		add_option('wp_invoice_lookup_text', "Pay Your Invoice");
		add_option('wp_invoice_lookup_submit', "Lookup");
		
		add_option('wp_invoice_using_godaddy', "no");

		
		// Frontend Customization
		add_option('wp_invoice_fe_paypal_link_url', "https://www.paypal.com/en_US/i/btn/btn_paynow_LG.gif");
		add_option('wp_invoice_fe_state_selection', "Dropdown");
	
		add_option('wp_invoice_version', WP_INVOICE_VERSION_NUM);
		add_option('wp_invoice_email_address',get_bloginfo('admin_email'));
		add_option('wp_invoice_business_name', get_bloginfo('blogname'));
		add_option('wp_invoice_business_address', '');
		add_option('wp_invoice_show_business_address', 'no');
		add_option('wp_invoice_payment_method','paypal');
		add_option('wp_invoice_user_level','level_8');
		add_option('wp_invoice_web_invoice_page','');
		add_option('wp_invoice_where_to_display','overwrite');
		add_option('wp_invoice_paypal_allow','yes');
		add_option('wp_invoice_moneybookers_address','');
		add_option('wp_invoice_googlecheckout_address','');
		add_option('wp_invoice_default_currency_code','USD');
		add_option('wp_invoice_reminder_message','This is a reminder.');
		
		add_option('wp_invoice_show_quantities','Hide');
		add_option('wp_invoice_use_css','yes');
		add_option('wp_invoice_force_https','false');
		add_option('wp_invoice_send_thank_you_email','no');
		
		add_option('wp_invoice_use_recurring','yes');
		
		//Authorize.net Gateway  Settings
		add_option('wp_invoice_client_change_payment_method','yes');
		add_option('wp_invoice_gateway_username','');
		add_option('wp_invoice_gateway_tran_key','');
		add_option('wp_invoice_gateway_delim_char',',');
		add_option('wp_invoice_gateway_encap_char','');
		add_option('wp_invoice_gateway_merchant_email',get_bloginfo('admin_email'));
		add_option('wp_invoice_recurring_gateway_url','https://api.authorize.net/xml/v1/request.api');
		add_option('wp_invoice_gateway_url','https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi');
		add_option('wp_invoice_gateway_MD5Hash','');
		
		add_option('wp_invoice_gateway_test_mode','FALSE');
		add_option('wp_invoice_gateway_delim_data','TRUE');
		add_option('wp_invoice_gateway_relay_response','FALSE');
		add_option('wp_invoice_gateway_email_customer','FALSE');

		wp_invoice_load_email_template_content();
	


	}

}
 


