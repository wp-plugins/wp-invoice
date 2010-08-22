<?php 
/**
 * WP-Invoice General Functions
 *
 * Contains all the general functions used by the plugin.
 *
 * @version 2.039
 * @author Andy Potanin <andy.potnain@twincitiestech.com>
 * @package WPI_Functions
 * @subpackage WP-Invoice
 */

class WPI_Functions {

	/**
	 * Check if blog qualifies for premium features
	 *
	 * @since 2.039
	 *
	 */ 
	function feature_check() {
		$blogname = get_bloginfo('url');
		$blogname = urlencode(str_replace(array('http://', 'https://'), '', $blogname));
		
		$check_url = "http://updates.twincitiestech.com/?system=wpi&site=$blogname";
		$response = @wp_remote_get($check_url);
	
		if(!$response)
			return;
					
		// Quite if failture
		if($response[response][code] != '200')
			return;
	
 		$response = json_decode($response[body]);
	
		if($response->features == 'eligible') {
			// Next release
		}
	}

}



setlocale(LC_MONETARY, 'en_US'); 
 

/*
	New function for sending invoices 
*/
	function wp_send_single_invoice($invoice_id, $message = false, $reminder = false) {
		$invoice_class = new wp_invoice_get($invoice_id);
		$invoice = $invoice_class->data;
 
 
 
		$name = get_option("wp_invoice_business_name");
		$from = get_option("wp_invoice_email_address");
 
		if($reminder) {
			$subject = preg_replace_callback('/(%([a-z_]+)%)/', 'wp_invoice_email_apply_variables', get_option('wp_invoice_email_send_reminder_subject'));
			$message = strip_tags(wp_invoice_show_reminder_email($invoice_id));
		} else {
			$subject = $invoice->email_message_subject;
			$message = $invoice->email_message_content;
		}
		
		if(empty($subject))
			$subject = "Web Invoice: {$invoice->subject}";
	
		if(wp_mail($invoice->user_class->user_email, $subject, $message, "From: $name <$from>\r\n")) {
 			wp_invoice_update_invoice_meta($invoice_id, "sent_date", date("Y-m-d", time()));
			wp_invoice_update_log($invoice_id,'contact',"Invoice eMailed to {$invoice->user_class->user_email}"); 
			return true; 
		} else { 
			return false; 
		}
			
	
	
	}
	
	
function wp_invoice_convert_email_to_id($email) {
	global $wpdb;
	
	$user_id = $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}users WHERE user_email = '$email'");

	if($user_id)
		return $user_id;

	return 'create_new_user';

}




function wp_invoice_number_of_invoices() {
	global $wpdb;
	$query = "SELECT COUNT(*) FROM ".WP_Invoice::tablename('main')."";
	$count = $wpdb->get_var($query);
	return $count;
}

function wp_invoice_does_invoice_exist($invoice_id) {
	global $wpdb;
	return $wpdb->get_var("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num = $invoice_id");
}

function wp_invoice_validate_cc_number($cc_number) {
   /* Validate; return value is card type if valid. */
   $false = false;
   $card_type = "";
   $card_regexes = array(
      "/^4\d{12}(\d\d\d){0,1}$/" => "visa",
      "/^5[12345]\d{14}$/"       => "mastercard",
      "/^3[47]\d{13}$/"          => "amex",
      "/^6011\d{12}$/"           => "discover",
      "/^30[012345]\d{11}$/"     => "diners",
      "/^3[68]\d{12}$/"          => "diners",
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

   /*  mod 10 checksum algorithm  */
   $revcode = strrev($cc_number);
   $checksum = 0;

   for ($i = 0; $i < strlen($revcode); $i++) {
       $current_num = intval($revcode[$i]);
       if($i & 1) {  /* Odd  position */
          $current_num *= 2;
       }
       /* Split digits and add. */
           $checksum += $current_num % 10; if
       ($current_num >  9) {
           $checksum += 1;
       }
   }

   if ($checksum % 10 == 0) {
       return $card_type;
   } else {
       return $false;
   }
}
	




function wp_invoice_update_log($invoice_id,$action_type,$value) 
{
	global $wpdb;
	if(isset($invoice_id))
	{
	$time_stamp = date("Y-m-d h-i-s");
	$wpdb->query("INSERT INTO ".WP_Invoice::tablename('log')." 
	(invoice_id , action_type , value, time_stamp)
	VALUES ('$invoice_id', '$action_type', '$value', '$time_stamp');");
	}
}

function wp_invoice_query_log($invoice_id,$action_type) {
	global $wpdb;
	if($results = $wpdb->get_results("SELECT * FROM ".WP_Invoice::tablename('log')." WHERE invoice_id = '$invoice_id' AND action_type = '$action_type' ORDER BY 'time_stamp' DESC")) return $results;
	
}

function wp_invoice_meta($invoice_id,$meta_key)
{
	global $wpdb;
	return $wpdb->get_var("SELECT meta_value FROM `".WP_Invoice::tablename('meta')."` WHERE meta_key = '$meta_key' AND invoice_id = '$invoice_id'");
}

function wp_invoice_update_invoice_meta($invoice_id,$meta_key,$meta_value)
{

	global $wpdb;
	if(empty($meta_value)) {
		// Dlete meta_key if no value is set
		$wpdb->query("DELETE FROM ".WP_Invoice::tablename('meta')." WHERE  invoice_id = '$invoice_id' AND meta_key = '$meta_key'"); 
	}
	else
	{
		// Check if meta key already exists, then we replace it WP_Invoice::tablename('meta')
		if($wpdb->get_var("SELECT meta_key 	FROM `".WP_Invoice::tablename('meta')."` WHERE meta_key = '$meta_key' AND invoice_id = '$invoice_id'"))
		{ $wpdb->query("UPDATE `".WP_Invoice::tablename('meta')."` SET meta_value = '$meta_value' WHERE meta_key = '$meta_key' AND invoice_id = '$invoice_id'"); }
		else
		{ $wpdb->query("INSERT INTO `".WP_Invoice::tablename('meta')."` (invoice_id, meta_key, meta_value) VALUES ('$invoice_id','$meta_key','$meta_value')"); }
	}
}

function wp_invoice_delete_invoice_meta($invoice_id,$meta_key='')
{
	
	
	global $wpdb;
	if(empty($meta_key)) 
	{ $wpdb->query("DELETE FROM `".WP_Invoice::tablename('meta')."` WHERE invoice_id = '$invoice_id' ");}
	else
	{ $wpdb->query("DELETE FROM `".WP_Invoice::tablename('meta')."` WHERE invoice_id = '$invoice_id' AND meta_key = '$meta_key'");}

}


function wp_invoice_delete($invoice_id) {
global $wpdb;

// Check to see if array is passed or single.
if(is_array($invoice_id))
{
	$counter=0;
	foreach ($invoice_id as $single_invoice_id) {
		$counter++;
		$wpdb->query("DELETE FROM ".WP_Invoice::tablename('main')." WHERE invoice_num = '$single_invoice_id'");

		wp_invoice_update_log($single_invoice_id, "deleted", "Deleted on ");
		
		// Get all meta keys for this invoice, then delete them
		
		$all_invoice_meta_values = $wpdb->get_col("SELECT invoice_id FROM ".WP_Invoice::tablename('meta')." WHERE invoice_id = '$single_invoice_id'");

		//print_r($all_invoice_meta_values);
		foreach ($all_invoice_meta_values as $meta_key) {
			wp_invoice_delete_invoice_meta($single_invoice_id);

		}
	}
	return $counter . __(' invoice(s) successfully deleted.', WP_INVOICE_TRANS_DOMAIN);

}
else
{
	// Delete Single
	$wpdb->query("DELETE FROM ".WP_Invoice::tablename('main')." WHERE invoice_num = '$invoice_id'");
	
	// Get all meta keys for this invoice, then delete them
	
	$all_invoice_meta_values = $wpdb->get_col("SELECT invoice_id FROM ".WP_Invoice::tablename('meta')." WHERE invoice_id = '$invoice_id'");

	//print_r($all_invoice_meta_values);
	foreach ($all_invoice_meta_values as $meta_key) {
		wp_invoice_delete_invoice_meta($invoice_id);
	}
		
	// Make log entry
	wp_invoice_update_log($invoice_id, "deleted", "Deleted on ");
	return __('Invoice successfully deleted.', WP_INVOICE_TRANS_DOMAIN);
}
}

function wp_invoice_archive($invoice_id) {
global $wpdb;

// Check to see if array is passed or single.
if(is_array($invoice_id))
{
	$counter=0;
	foreach ($invoice_id as $single_invoice_id) {
	$counter++;
	wp_invoice_update_invoice_meta($single_invoice_id, "archive_status", "archived");
	}
	return __("$counter  invoice(s) archived.", WP_INVOICE_TRANS_DOMAIN);

}
else
{
	wp_invoice_update_invoice_meta($invoice_id, "archive_status", "archived");
	return __('Invoice successfully archived.', WP_INVOICE_TRANS_DOMAIN);
}
}

function wp_invoice_mark_as_unpaid($invoice_id) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		$counter=0;
		foreach ($invoice_id as $single_invoice_id) {
			$counter++;
			wp_invoice_delete_invoice_meta($single_invoice_id,'subscription_started');
			wp_invoice_delete_invoice_meta($single_invoice_id,'paid_status');
			wp_invoice_update_log($single_invoice_id,'paid',"Invoice marked as un-paid");
		}
		return $counter . __(' invoice(s) marked as paid.', WP_INVOICE_TRANS_DOMAIN);

	}
	else
	{
		wp_invoice_delete_invoice_meta($invoice_id,'subscription_started');
		wp_invoice_delete_invoice_meta($invoice_id,'paid_status');
		wp_invoice_update_log($invoice_id,'paid',"Invoice marked as un-paid");
		return $counter .  __(' invoice marked as paid.', WP_INVOICE_TRANS_DOMAIN);
	}
}


function wp_invoice_mark_as_paid($invoice_id) {
	global $wpdb;

	// Check to see if array is passed or single.
	if(is_array($invoice_id))
	{
		$counter=0;
		foreach ($invoice_id as $single_invoice_id) {
		$counter++;
		wp_invoice_update_invoice_meta($single_invoice_id,'paid_status','paid');
		wp_invoice_update_log($single_invoice_id,'paid',"Invoice marked as paid");
		if(get_option('wp_invoice_send_thank_you_email') == 'yes') wp_invoice_send_email_receipt($single_invoice_id);
		}
		if(get_option('wp_invoice_send_thank_you_email') == 'yes') {
		return $counter . __(' invoice(s) marked as paid, and thank you email sent to customer.', WP_INVOICE_TRANS_DOMAIN);
		}
		else{
		return $counter . __(' invoice(s) marked as paid.', WP_INVOICE_TRANS_DOMAIN);
		}
	}
	else
	{
		wp_invoice_update_invoice_meta($invoice_id,'paid_status','paid');
		wp_invoice_update_log($invoice_id,'paid',"Invoice marked as paid");
		if(get_option('wp_invoice_send_thank_you_email') == 'yes') wp_invoice_send_email_receipt($single_invoice_id);

		if(get_option('wp_invoice_send_thank_you_email') == 'yes') {
		return $counter . __(' invoice marked as paid, and thank you email sent to customer.', WP_INVOICE_TRANS_DOMAIN);
		}
		else{
		return $counter .  __(' invoice marked as paid.', WP_INVOICE_TRANS_DOMAIN);
		}}
}

function wp_invoice_unarchive($invoice_id) {
global $wpdb;

// Check to see if array is passed or single.
if(is_array($invoice_id))
{
	$counter=0;
	foreach ($invoice_id as $single_invoice_id) {
	$counter++;
	wp_invoice_delete_invoice_meta($single_invoice_id, "archive_status");
	}
	return $counter . __(' invoice(s) unarchived.', WP_INVOICE_TRANS_DOMAIN);

}
else
{
	wp_invoice_delete_invoice_meta($invoice_id, "archive_status");
	return __('Invoice successfully unarchived', WP_INVOICE_TRANS_DOMAIN);
}
}

function wp_invoice_mark_as_sent($invoice_id) {
global $wpdb;

// Check to see if array is passed or single.
if(is_array($invoice_id))
{
	$counter=0;
	foreach ($invoice_id as $single_invoice_id) {
	$counter++;
	wp_invoice_update_invoice_meta($single_invoice_id, "sent_date", date("Y-m-d", time()));
	wp_invoice_update_log($single_invoice_id,'contact','Invoice Maked as eMailed'); //make sent entry
	
	}
	return $counter .  __(' invoice(s) marked as sent.', WP_INVOICE_TRANS_DOMAIN);

}
else
{
	wp_invoice_update_invoice_meta($invoice_id, "sent_date", date("Y-m-d", time()));
	wp_invoice_update_log($invoice_id,'contact','Invoice Maked as eMailed'); //make sent entry
	
	return __('Invoice market as sent.', WP_INVOICE_TRANS_DOMAIN);
}
}

function wp_invoice_get_invoice_attrib($invoice_id,$attribute) 
{
	global $wpdb;
	$query = "SELECT $attribute FROM ".WP_Invoice::tablename('main')." WHERE invoice_num=".$invoice_id."";
	return $wpdb->get_var($query);
}

function wp_invoice_get_invoice_status($invoice_id,$count='1') 
{
if($invoice_id != '') {
	global $wpdb;
	$query = "SELECT * FROM ".WP_Invoice::tablename('log')."
	WHERE invoice_id = $invoice_id
	ORDER BY time_stamp DESC
	LIMIT 0 , $count";

	$exclude_ips = get_option('wp_invoice_exclude_ips');
	
	if(!empty($exclude_ips))
		$ip_array = explode("\n", $exclude_ips);
		
	$ip_array = (is_array($ip_array) ? $ip_array : false);

 
	
 	
	$status_update = $wpdb->get_results($query);
	
	if(count($status_update) < 1)
		return false;

	foreach ($status_update as $single_status) {
		
		$skip = false;
		
		// Exclude ips
		if($ip_array) {
			foreach($ip_array as $ip) {
 				if(strpos(trim($single_status->value), trim($ip)))  {
				$skip = true;
				continue;
				}
			}
		}
		if($skip)
			continue;
			
		$message .= "<li>" . $single_status->value . " on <span class='wp_invoice_tamp_stamp'>" . $single_status->time_stamp . "</span></li>";
	}

	return $message;
	}
}

function wp_invoice_clear_invoice_status($invoice_id) 
{
	global $wpdb;
	if(isset($invoice_id)) {
	if($wpdb->query("DELETE FROM ".WP_Invoice::tablename('log')." WHERE invoice_id = $invoice_id"))
	return  __('Logs for invoice #', WP_INVOICE_TRANS_DOMAIN) . $invoice_id .  __(' cleared.', WP_INVOICE_TRANS_DOMAIN);
	}
}

function wp_invoice_get_single_invoice_status($invoice_id) 
{
	// in class
	global $wpdb;
	if($status_update = $wpdb->get_row("SELECT * FROM ".WP_Invoice::tablename('log')." WHERE invoice_id = $invoice_id ORDER BY `".WP_Invoice::tablename('log')."`.`time_stamp` DESC LIMIT 0 , 1"))
	return $status_update->value . " - " . wp_invoice_Date::convert($status_update->time_stamp, 'Y-m-d H', 'M d Y');
}


function wp_invoice_currency_format($amount) {
	return number_format($amount, 2, '.', ',');
}

function wp_invoice_paid($invoice_id) {
	global $wpdb;
	//$wpdb->query("UPDATE  ".WP_Invoice::tablename('main')." SET status = 1 WHERE  invoice_num = '$invoice_id'");
	wp_invoice_update_invoice_meta($invoice_id,'paid_status','paid');
 	wp_invoice_update_log($invoice_id,'paid',"Invoice successfully processed by ". $_SERVER['REMOTE_ADDR']);	

}

/*
	Obsolete function
*/
function wp_invoice_recurring($invoice_id) {
	global $wpdb;
	if(wp_invoice_meta($invoice_id,'recurring_billing')) return true;
}

function wp_invoice_recurring_started($invoice_id) {
	global $wpdb;
	if(wp_invoice_meta($invoice_id,'subscription_id')) return true;
}

function wp_invoice_paid_status($invoice_id) {
	//Merged with paid_status in class
	global $wpdb;
	if(!empty($invoice_id) && wp_invoice_meta($invoice_id,'paid_status') || $wpdb->get_var("SELECT status FROM  ".WP_Invoice::tablename('main')." WHERE invoice_num = '$invoice_id'")) return true;
}

function wp_invoice_paid_date($invoice_id) {
	// in invoice class
	global $wpdb;
	return $wpdb->get_var("SELECT time_stamp FROM  ".WP_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$invoice_id."' ORDER BY time_stamp DESC LIMIT 0, 1");
	
}


function wp_invoice_build_invoice_link($invoice_id) {
	// in invoice class
	global $wpdb;
	
	$link_to_page = get_permalink(get_option('wp_invoice_web_invoice_page'));


	$hashed_invoice_id = md5($invoice_id);
	if(get_option("permalink_structure")) { $link = $link_to_page . "?invoice_id=" .$hashed_invoice_id; } 
	else { $link =  $link_to_page . "&invoice_id=" . $hashed_invoice_id; } 

	return $link;
}


function wp_invoice_draw_inputfield($name,$value,$special = '') {
	
	return "<input id='$name' type='text' class='$name input_field' name='$name' value='$value' $special />";
}
function wp_invoice_draw_textarea($name,$value,$special = '') {
	
	return "<textarea id='$name' class='$name' name='$name'  $special>$value</textarea>";
}

function wp_invoice_draw_select($name,$values,$current_value = '') {
	
	$output = "<select id='$name' name='$name' class='$name'>";
	foreach($values as $key => $value) {
	$output .=  "<option style='padding-right: 10px;' value='$key'";
	if($key == $current_value || $value == $current_value) $output .= " selected";	
	$output .= ">".stripslashes($value)."</option>";
	}
	$output .= "</select>";

	return $output;
}

function wp_invoice_send_email_receipt($invoice_id) {
	global $wpdb, $wp_invoice_email_variables;
 	
	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;

	$wp_invoice_email_variables = wp_invoice_email_variables($invoice_id);
	
	$message = wp_invoice_show_receipt_email($invoice_id);


	$name = get_option("wp_invoice_business_name");
	$from = get_option("wp_invoice_email_address");
	
	$headers = "From: {$name} <{$from}>\r\n";
	if (get_option('wp_invoice_cc_thank_you_email') == 'yes') {
		$headers .= "CC: {$from}\r\n";
	}

	$message = wp_invoice_show_receipt_email($invoice_id);
	$subject = preg_replace_callback('/(%([a-z_]+)%)/', 'wp_invoice_email_apply_variables', get_option('wp_invoice_email_send_receipt_subject'));

	if(wp_mail($invoice->user_class->user_email, $subject, $message, $headers))
	{ wp_invoice_update_log($invoice_id,'contact','Receipt eMailed'); }

	return $message;
}

function wp_invoice_format_phone($phone)
{
	$phone = preg_replace("/[^0-9]/", "", $phone);

	if(strlen($phone) == 7)
		return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
	elseif(strlen($phone) == 10)
		return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
	else
		return $phone;
}



function wp_invoice_deactivation($confirm=false) 
{
 
}


function wp_invoice_complete_removal() 
{
echo 'complete removal';
	// Run regular deactivation, but also delete the main table - all invoice data is gone
	global $wpdb;
	wp_invoice_deactivation() ;;
	$wpdb->query("DROP TABLE " . WP_Invoice::tablename('log') .";");
	$wpdb->query("DROP TABLE " . WP_Invoice::tablename('main') .";");
	$wpdb->query("DROP TABLE " . WP_Invoice::tablename('meta') .";");
	
	$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%wp_invoice_%'");
	 
	return __("All settings and databases removed.", WP_INVOICE_TRANS_DOMAIN);
}

function get_invoice_user_id($invoice_id) {
	// in class
	global $wpdb;
	$invoice_info = $wpdb->get_row("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num = '".$invoice_id."'");
	return $invoice_info->user_id;
}

/*
	Used for processing bulk sends

*/
function wp_invoice_send_bulk_email($invoice_array, $reminder = false) {
	
	if(is_array($invoice_array)) {
		$counter=0;
		foreach ($invoice_array as $invoice_id) {
			if(wp_send_single_invoice($invoice_id, false, $reminder))
				$counter++; 
		}
		if($counter)
			return "Successfully sent $counter web invoices(s).";
		else
			return "No invoices were sent.";
	} else {
		if(wp_send_single_invoice($invoice_id, false, $reminder))
			return "Web invoice sent successfully.";
		else
			return "There was a problem sending the invoice.";
	}
}


	
function wp_invoice_array_stripslashes($slash_array = array())
{
	if($slash_array)
	{
		foreach($slash_array as $key=>$value)
		{
			if(is_array($value))
			{
				$slash_array[$key] = wp_invoice_array_stripslashes($value);
			}
			else
			{
				$slash_array[$key] = stripslashes($value);
			}
		}
	}
	return($slash_array);
}
	
function wp_invoice_profile_update() {
	global $wpdb;
	$user_id =  $_REQUEST['user_id'];


	if(isset($_POST['company_name'])) update_usermeta($user_id, 'company_name', $_POST['company_name']);
	if(isset($_POST['streetaddress'])) update_usermeta($user_id, 'streetaddress', $_POST['streetaddress']);
	if(isset($_POST['zip']))  update_usermeta($user_id, 'zip', $_POST['zip']);
	if(isset($_POST['state'])) update_usermeta($user_id, 'state', $_POST['state']);
	if(isset($_POST['city'])) update_usermeta($user_id, 'city', $_POST['city']);
	if(isset($_POST['phonenumber'])) update_usermeta($user_id, 'phonenumber', $_POST['phonenumber']);

}
	
class wp_invoice_Date 
{

	function convert($string, $from_mask, $to_mask='', $return_unix=false)
	{
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
		foreach($all as $type => $chars)
		{
			// get the position of the current character
			if(($pos = strpos($from_mask, $chars)) === false)
				continue;

			// find the value in the original string
			$val = substr($string, $pos, strlen($chars));

			// store it for later processing
			$vals[$type] = $val;
		}

		foreach($vals as $type => $val)
		{
			switch($type)
			{
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
					$year = '20'.$val; // Year 3k bug right here
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
			(int)$hours, (int)$minutes, (int)$seconds, 
			(int)$month, (int)$day, (int)$year);
		
		if($return_unix)
			return $unix_time;

		return date($to_mask, $unix_time);
	}
}


function wp_invoice_fix_billing_meta_array($arr){
    $narr = array();
	$counter = 1;
    while(list($key, $val) = each($arr)){
        if (is_array($val)){
            $val = array_remove_empty($val);
            if (count($val)!=0){
                $narr[$counter] = $val;$counter++;
            }
        }
        else {
            if (trim($val) != ""){
                $narr[$counter] = $val;$counter++;
            }
        }
		
    }
    unset($arr);
    return $narr;
}

function wp_invoice_printYearDropdown($sel='')
{
	$localDate=getdate();
	$minYear = $localDate["year"];
	$maxYear = $minYear + 15;

	  $output =  "<option value=''>--</option>";
	  for($i=$minYear; $i<$maxYear; $i++) {
	    $output .= "<option value='". substr($i, 2, 2) ."'".($sel==(substr($i, 2, 2))?' selected':'').
		">". $i ."</option>";
	  }
	  return($output);
}

function wp_invoice_printMonthDropdown($sel='') {
	$output =  "<option value=''>--</option>";
	$output .=  "<option " . ($sel==1?' selected':'') . " value='01'>01 - Jan</option>";
	$output .=  "<option " . ($sel==2?' selected':'') . "  value='02'>02 - Feb</option>";
	$output .=  "<option " . ($sel==3?' selected':'') . "  value='03'>03 - Mar</option>";
	$output .=  "<option " . ($sel==4?' selected':'') . "  value='04'>04 - Apr</option>";
	$output .=  "<option " . ($sel==5?' selected':'') . "  value='05'>05 - May</option>";
	$output .=  "<option " . ($sel==6?' selected':'') . "  value='06'>06 - Jun</option>";
	$output .=  "<option " . ($sel==7?' selected':'') . "  value='07'>07 - Jul</option>";
	$output .=  "<option " . ($sel==8?' selected':'') . "  value='08'>08 - Aug</option>";
	$output .=  "<option " . ($sel==9?' selected':'') . "  value='09'>09 - Sep</option>";
	$output .=  "<option " . ($sel==10?' selected':'') . "  value='10'>10 - Oct</option>";
	$output .=  "<option " . ($sel==11?' selected':'') . "  value='11'>11 - Nov</option>";
	$output .=  "<option " . ($sel==12?' selected':'') . "  value='12'>12 - Doc</option>";

	return($output);
}

function wp_invoice_state_array($country = false) {

if(!$country)
	$country = 'US';
	
	$uk_counties = array("Aberdeenshire"=>"Aberdeenshire","Angus/Forfarshire"=>"Angus/Forfarshire","Argyllshire"=>"Argyllshire",
"Ayrshire"=>"Ayrshire","Banffshire"=>"Banffshire","Bedfordshire"=>"Bedfordshire","Berkshire"=>"Berkshire",
"Berwickshire"=>"Berwickshire","Blaenau Gwent"=>"Blaenau Gwent","Bridgend"=>"Bridgend",
"Buckinghamshire"=>"Buckinghamshire","Buteshire"=>"Buteshire","Caerphilly"=>"Caerphilly","Caithness"=>"Caithness",
"Cambridgeshire"=>"Cambridgeshire","Cardiff"=>"Cardiff","Carmarthenshire"=>"Carmarthenshire",
"Ceredigion"=>"Ceredigion","Cheshire"=>"Cheshire","Clackmannanshire"=>"Clackmannanshire",
"Conwy"=>"Conwy","Cornwall"=>"Cornwall","Cromartyshire"=>"Cromartyshire","Cumberland"=>"Cumberland",
"Denbighshire"=>"Denbighshire","Derbyshire"=>"Derbyshire","Devon"=>"Devon","Dorset"=>"Dorset",
"Dumfriesshire"=>"Dumfriesshire","Dunbartonshire/Dumbartonshire"=>"Dunbartonshire/Dumbartonshire",
"Durham"=>"Durham","East Lothian/Haddingtonshire"=>"East Lothian/Haddingtonshire","Essex"=>"Essex","Fife"=>"Fife",
"Flintshire"=>"Flintshire","Gloucestershire"=>"Gloucestershire","Gwynedd"=>"Gwynedd","Hampshire"=>"Hampshire",
"Herefordshire"=>"Herefordshire","Hertfordshire"=>"Hertfordshire","Huntingdonshire"=>"Huntingdonshire",
"Inverness-shire"=>"Inverness-shire","Isle of Anglesey"=>"Isle of Anglesey","Kent"=>"Kent",
"Kincardineshire"=>"Kincardineshire","Kinross-shire"=>"Kinross-shire","Kirkcudbrightshire"=>"Kirkcudbrightshire",
"Lanarkshire"=>"Lanarkshire","Lancashire"=>"Lancashire","Leicestershire"=>"Leicestershire",
"Lincolnshire"=>"Lincolnshire","Merthyr Tydfil"=>"Merthyr Tydfil","Middlesex"=>"Middlesex",
"Midlothian/Edinburghshire"=>"Midlothian/Edinburghshire","Monmouthshire"=>"Monmouthshire",
"Morayshire"=>"Morayshire","Nairnshire"=>"Nairnshire","Neath Port Talbot"=>"Neath Port Talbot",
"Newport"=>"Newport","Norfolk"=>"Norfolk","Northamptonshire"=>"Northamptonshire",
"Northumberland"=>"Northumberland","Nottinghamshire"=>"Nottinghamshire","Orkney"=>"Orkney",
"Oxfordshire"=>"Oxfordshire","Peeblesshire"=>"Peeblesshire","Pembrokeshire"=>"Pembrokeshire",
"Perthshire"=>"Perthshire","Powys"=>"Powys","Renfrewshire"=>"Renfrewshire",
"Rhondda Cynon Taff"=>"Rhondda Cynon Taff","Ross-shire"=>"Ross-shire","Roxburghshire"=>"Roxburghshire",
"Rutland"=>"Rutland","Selkirkshire"=>"Selkirkshire","Shetland"=>"Shetland","Shropshire"=>"Shropshire",
"Somerset"=>"Somerset","Staffordshire"=>"Staffordshire","Stirlingshire"=>"Stirlingshire","Suffolk"=>"Suffolk",
"Surrey"=>"Surrey","Sussex"=>"Sussex","Sutherland"=>"Sutherland","Swansea"=>"Swansea","Torfaen"=>"Torfaen",
"Vale of Glamorgan"=>"Vale of Glamorgan","Warwickshire"=>"Warwickshire",
"West Lothian/Linlithgowshire"=>"West Lothian/Linlithgowshire","Westmorland"=>"Westmorland",
"Wigtownshire"=>"Wigtownshire","Wiltshire"=>"Wiltshire",
"Worcestershire"=>"Worcestershire","Wrexham"=>"Wrexham","Yorkshire"=>"Yorkshire");

$us_states = array(
   'AL' => 'Alabama',
   'AK' => 'Alaska',
   'AS' => 'American Samoa',
   'AZ' => 'Arizona',
   'AR' => 'Arkansas',
   'CA' => 'California',
   'CO' => 'Colorado',
   'CT' => 'Connecticut',
   'DE' => 'Delaware',
   'DC' => 'District of Columbia',
   'FM' => 'Federated States of Micronesia',
   'FL' => 'Florida',
   'GA' => 'Georgia',
   'GU' => 'Guam',
   'HI' => 'Hawaii',
   'ID' => 'Idaho',
   'IL' => 'Illinois',
   'IN' => 'Indiana',
   'IA' => 'Iowa',
   'KS' => 'Kansas',
   'KY' => 'Kentucky',
   'LA' => 'Louisiana',
   'ME' => 'Maine',
   'MH' => 'Marshall Islands',
   'MD' => 'Maryland',
   'MA' => 'Massachusetts',
   'MI' => 'Michigan',
   'MN' => 'Minnesota',
   'MS' => 'Mississippi',
   'MO' => 'Missouri',
   'MT' => 'Montana',
   'NE' => 'Nebraska',
   'NV' => 'Nevada',
   'NH' => 'New Hampshire',
   'NJ' => 'New Jersey',
   'NM' => 'New Mexico',
   'NY' => 'New York',
   'NC' => 'North Carolina',
   'ND' => 'North Dakota',
   'MP' => 'Northern Mariana Islands',
   'OH' => 'Ohio',
   'OK' => 'Oklahoma',
   'OR' => 'Oregon',
   'PW' => 'Palau',
   'PA' => 'Pennsylvania',
   'PR' => 'Puerto Rico',
   'RI' => 'Rhode Island',
   'SC' => 'South Carolina',
   'SD' => 'South Dakota',
   'TN' => 'Tennessee',
   'TX' => 'Texas',
   'UT' => 'Utah',
   'VT' => 'Vermont',
   'VI' => 'Virgin Islands',
   'VA' => 'Virginia',
   'WA' => 'Washington',
   'WV' => 'West Virginia',
   'WI' => 'Wisconsin',
   'WY' => 'Wyoming',
   'AB' => 'Alberta',
   'BC' => 'British Columbia',
   'MB' => 'Manitoba',
   'NB' => 'New Brunswick',
   'NF' => 'Newfoundland',
   'NW' => 'Northwest Territory',
   'NS' => 'Nova Scotia',
   'ON' => 'Ontario',
   'PE' => 'Prince Edward Island',
   'QU' => 'Quebec',
   'SK' => 'Saskatchewan',
   'YT' => 'Yukon Territory',
	);

	if($country == 'US')
		return($us_states);
		
	if($country == 'UK')
		return($uk_counties);
}
		
function wp_invoice_country_array() {
	return array("US"=> "United States","AL"=> "Albania","DZ"=> "Algeria","AD"=> "Andorra","AO"=> "Angola","AI"=> "Anguilla","AG"=> "Antigua and Barbuda","AR"=> "Argentina","AM"=> "Armenia","AW"=> "Aruba","AU"=> "Australia","AT"=> "Austria","AZ"=> "Azerbaijan Republic","BS"=> "Bahamas","BH"=> "Bahrain","BB"=> "Barbados","BE"=> "Belgium","BZ"=> "Belize","BJ"=> "Benin","BM"=> "Bermuda","BT"=> "Bhutan","BO"=> "Bolivia","BA"=> "Bosnia and Herzegovina","BW"=> "Botswana","BR"=> "Brazil","VG"=> "British Virgin Islands","BN"=> "Brunei","BG"=> "Bulgaria","BF"=> "Burkina Faso","BI"=> "Burundi","KH"=> "Cambodia","CA"=> "Canada","CV"=> "Cape Verde","KY"=> "Cayman Islands","TD"=> "Chad","CL"=> "Chile","C2"=> "China","CO"=> "Colombia","KM"=> "Comoros","CK"=> "Cook Islands","CR"=> "Costa Rica","HR"=> "Croatia","CY"=> "Cyprus","CZ"=> "Czech Republic","CD"=> "Democratic Republic of the Congo","DK"=> "Denmark","DJ"=> "Djibouti","DM"=> "Dominica","DO"=> "Dominican Republic","EC"=> "Ecuador","SV"=> "El Salvador","ER"=> "Eritrea","EE"=> "Estonia","ET"=> "Ethiopia","FK"=> "Falkland Islands","FO"=> "Faroe Islands","FM"=> "Federated States of Micronesia","FJ"=> "Fiji","FI"=> "Finland","FR"=> "France","GF"=> "French Guiana","PF"=> "French Polynesia","GA"=> "Gabon Republic","GM"=> "Gambia","DE"=> "Germany","GI"=> "Gibraltar","GR"=> "Greece","GL"=> "Greenland","GD"=> "Grenada","GP"=> "Guadeloupe","GT"=> "Guatemala","GN"=> "Guinea","GW"=> "Guinea Bissau","GY"=> "Guyana","HN"=> "Honduras","HK"=> "Hong Kong","HU"=> "Hungary","IS"=> "Iceland","IN"=> "India","ID"=> "Indonesia","IE"=> "Ireland","IL"=> "Israel","IT"=> "Italy","JM"=> "Jamaica","JP"=> "Japan","JO"=> "Jordan","KZ"=> "Kazakhstan","KE"=> "Kenya","KI"=> "Kiribati","KW"=> "Kuwait","KG"=> "Kyrgyzstan","LA"=> "Laos","LV"=> "Latvia","LS"=> "Lesotho","LI"=> "Liechtenstein","LT"=> "Lithuania","LU"=> "Luxembourg","MG"=> "Madagascar","MW"=> "Malawi","MY"=> "Malaysia","MV"=> "Maldives","ML"=> "Mali","MT"=> "Malta","MH"=> "Marshall Islands","MQ"=> "Martinique","MR"=> "Mauritania","MU"=> "Mauritius","YT"=> "Mayotte","MX"=> "Mexico","MN"=> "Mongolia","MS"=> "Montserrat","MA"=> "Morocco","MZ"=> "Mozambique","NA"=> "Namibia","NR"=> "Nauru","NP"=> "Nepal","NL"=> "Netherlands","AN"=> "Netherlands Antilles","NC"=> "New Caledonia","NZ"=> "New Zealand","NI"=> "Nicaragua","NE"=> "Niger","NU"=> "Niue","NF"=> "Norfolk Island","NO"=> "Norway","OM"=> "Oman","PW"=> "Palau","PA"=> "Panama","PG"=> "Papua New Guinea","PE"=> "Peru","PH"=> "Philippines","PN"=> "Pitcairn Islands","PL"=> "Poland","PT"=> "Portugal","QA"=> "Qatar","CG"=> "Republic of the Congo","RE"=> "Reunion","RO"=> "Romania","RU"=> "Russia","RW"=> "Rwanda","VC"=> "Saint Vincent and the Grenadines","WS"=> "Samoa","SM"=> "San Marino","ST"=> "São Tomé and Príncipe","SA"=> "Saudi Arabia","SN"=> "Senegal","SC"=> "Seychelles","SL"=> "Sierra Leone","SG"=> "Singapore","SK"=> "Slovakia","SI"=> "Slovenia","SB"=> "Solomon Islands","SO"=> "Somalia","ZA"=> "South Africa","KR"=> "South Korea","ES"=> "Spain","LK"=> "Sri Lanka","SH"=> "St. Helena","KN"=> "St. Kitts and Nevis","LC"=> "St. Lucia","PM"=> "St. Pierre and Miquelon","SR"=> "Suriname","SJ"=> "Svalbard and Jan Mayen Islands","SZ"=> "Swaziland","SE"=> "Sweden","CH"=> "Switzerland","TW"=> "Taiwan","TJ"=> "Tajikistan","TZ"=> "Tanzania","TH"=> "Thailand","TG"=> "Togo","TO"=> "Tonga","TT"=> "Trinidad and Tobago","TN"=> "Tunisia","TR"=> "Turkey","TM"=> "Turkmenistan","TC"=> "Turks and Caicos Islands","TV"=> "Tuvalu","UG"=> "Uganda","UA"=> "Ukraine","AE"=> "United Arab Emirates","GB"=> "United Kingdom","UY"=> "Uruguay","VU"=> "Vanuatu","VA"=> "Vatican City State","VE"=> "Venezuela","VN"=> "Vietnam","WF"=> "Wallis and Futuna Islands","YE"=> "Yemen","ZM"=> "Zambia");
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

function wp_invoice_go_secure($destination) {
    $reload = 'Location: ' . $destination;
    header($reload);
} 



function wp_invoice_process_cc_transaction($cc_data) {



$errors = array ();
$errors_msg = null;
$_POST['processing_problem'] = '';
unset($stop_transaction);
$invoice_id = preg_replace("/[^0-9]/","", $_POST['invoice_num']); /* this is the real invoice id */


	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;

if(wp_invoice_recurring($invoice_id)) $recurring = true;

// Accomodate Custom Invoice IDs by changing the post value, this is passed to Authorize.net account
$wp_invoice_custom_invoice_id = wp_invoice_meta($invoice_id,'wp_invoice_custom_invoice_id');
// If there is a custom invoice id, we're setting the $_POST['invoice_num'] to the custom id, because that is what's getting passed to authorize.net
if($wp_invoice_custom_invoice_id) { $_POST['invoice_num'] = $wp_invoice_custom_invoice_id; }
 
$wp_users_id = get_invoice_user_id($invoice_id);


if(empty($_POST['first_name'])){$errors [ 'first_name' ] [] = "Please enter your first name.";$stop_transaction = true;}
if(empty($_POST['last_name'])){$errors [ 'last_name' ] [] = "Please enter your last name. ";$stop_transaction = true;}
if(empty($_POST['email_address'])){$errors [ 'email_address' ] [] = "Please provide an email address.";$stop_transaction = true;}
if(empty($_POST['phonenumber'])){$errors [ 'phonenumber' ] [] = "Please enter your phone number.";$stop_transaction = true;}
if(empty($_POST['address'])){$errors [ 'address' ] [] = "Please enter your address.";$stop_transaction = true;}
if(empty($_POST['city'])){$errors [ 'city' ] [] = "Please enter your city.";$stop_transaction = true;}
if(get_option('wp_invoice_fe_state_selection') != 'Hide') {  if(empty($_POST['state'])){$errors [ 'state' ] [] = "Please select your state.";$stop_transaction = true;} }
if(empty($_POST['zip'])){$errors [ 'zip' ] [] = "Please enter your ZIP code.";$stop_transaction = true;}
if(empty($_POST['country'])){$errors [ 'country' ] [] = "Please enter your country.";$stop_transaction = true;}
if(empty($_POST['card_num'])) {	$errors [ 'card_num' ] []  = "Please enter your credit card number.";	$stop_transaction = true;} else { if (!wp_invoice_validate_cc_number($_POST['card_num'])){$errors [ 'card_num' ] [] = "Please enter a valid credit card number."; $stop_transaction = true; } }
if(empty($_POST['exp_month'])){$errors [ 'exp_month' ] [] = "Please enter your credit card's expiration month.";$stop_transaction = true;}
if(empty($_POST['exp_year'])){$errors [ 'exp_year' ] [] = "Please enter your credit card's expiration year.";$stop_transaction = true;}
if(empty($_POST['card_code'])){$errors [ 'card_code' ] [] = "The <b>Security Code</b> is the code on the back of your card.";$stop_transaction = true;}

// Charge Card
if(!$stop_transaction) {

	require_once('gateways/authnet.class.php');
	require_once('gateways/authnetARB.class.php');

	$payment = new WP_Invoice_Authnet(true); 
	$payment->transaction($_POST['card_num']); 
	
	// Billing Info
	$payment->setParameter("x_card_code", $_POST['card_code']);
	$payment->setParameter("x_exp_date ", $_POST['exp_month'] . $_POST['exp_year']);
	$payment->setParameter("x_amount", $invoice->amount);
	if($recurring) $payment->setParameter("x_recurring_billing", true);
	
	// Order Info
	$payment->setParameter("x_description", $invoice->subject);
	$payment->setParameter("x_invoice_num",  $invoice->display_id);
	$payment->setParameter("x_test_request", false);
	$payment->setParameter("x_duplicate_window", 30);
	
	//Customer Info
	$payment->setParameter("x_first_name", $_POST['first_name']);
	$payment->setParameter("x_last_name", $_POST['last_name']);
	$payment->setParameter("x_address", $_POST['address']);
	$payment->setParameter("x_city", $_POST['city']);
	$payment->setParameter("x_state", $_POST['state']);
	$payment->setParameter("x_country", $_POST['country']);
	$payment->setParameter("x_zip", $_POST['zip']);
	$payment->setParameter("x_phone", $_POST['phonenumber']);
	$payment->setParameter("x_email", $_POST['email_address']);
	$payment->setParameter("x_cust_id", "WP User - " . $invoice->user_id);
	$payment->setParameter("x_customer_ip ", $_SERVER['REMOTE_ADDR']);
	
	$payment->process(); 
 
	if($payment->isApproved()) {
	echo "Transaction okay.";

	update_usermeta($wp_users_id,'last_name',$_POST['last_name']);
	update_usermeta($wp_users_id,'last_name',$_POST['last_name']);
	update_usermeta($wp_users_id,'first_name',$_POST['first_name']);
	update_usermeta($wp_users_id,'city',$_POST['city']);
	update_usermeta($wp_users_id,'state',$_POST['state']);
	update_usermeta($wp_users_id,'zip',$_POST['zip']);
	update_usermeta($wp_users_id,'streetaddress',$_POST['address']);
	update_usermeta($wp_users_id,'phonenumber',$_POST['phonenumber']);
	update_usermeta($wp_users_id,'country',$_POST['country']);

	//Mark invoice as paid
	wp_invoice_paid($invoice_id);
	if(get_option('wp_invoice_send_thank_you_email') == 'yes') wp_invoice_send_email_receipt($invoice_id);

	if($recurring) {
   
		$arb = new WP_Invoice_AuthnetARB(); 
		// Customer Info
		$arb->setParameter('customerId', "WP User - " . $invoice->user_id); 
		$arb->setParameter('firstName', $_POST['first_name']); 
		$arb->setParameter('lastName', $_POST['last_name']); 
		$arb->setParameter('address', $_POST['address']); 
		$arb->setParameter('city', $_POST['city']); 
		$arb->setParameter('state', $_POST['state']); 
		$arb->setParameter('zip', $_POST['zip']); 
		$arb->setParameter('country', $_POST['country']); 
		$arb->setParameter('customerEmail', $_POST['email_address']); 
		$arb->setParameter('customerPhoneNumber', $_POST['phonenumber']); 
		
		// Billing Info
		$arb->setParameter('amount', $invoice->amount); 
		$arb->setParameter('cardNumber', $_POST['card_num']); 
		$arb->setParameter('expirationDate', $_POST['exp_month'].$_POST['exp_year']); 
		
		//Subscription Info
		$arb->setParameter('refID',  $invoice->display_id); 
		$arb->setParameter('subscrName', $invoice->subscription_name); 
		$arb->setParameter('interval_length', $invoice->subscription_length); 
		$arb->setParameter('interval_unit', $invoice->subscription_unit); 
		$arb->setParameter('startDate', $invoice->startDate); 
		$arb->setParameter('totalOccurrences', $invoice->subscription_total_occurances); 
		
		// First billing cycle is taken care off with initial payment
		$arb->setParameter('trialOccurrences', '1'); 
		$arb->setParameter('trialAmount', '0.00'); 
		
		$arb->setParameter('orderInvoiceNumber',  $invoice->display_id); 
		$arb->setParameter('orderDescription', $invoice->subject); 
		
		$arb->createAccount();
		
		if ($arb->isSuccessful()) { 
		wp_invoice_update_invoice_meta($invoice_id, 'subscription_id',$arb->getSubscriberID());
		wp_invoice_update_log($invoice_id, 'subscription', ' Authorize.net subscription initiated, Subcription ID - ' . $arb->getSubscriberID());
		}
		
		if($arb->isError()) {
		$errors [ 'processing_problem' ] [] .=  "One-time credit card payment is processed successfully.  However, recurring billing setup failed." . $arb->getResponse(); $stop_transaction = true;;
		wp_invoice_update_log($invoice_id, 'subscription_error', 'Response Code: ' . $arb->getResponseCode() . ' | Subscription error - ' . $arb->getResponse());

		}
		
	}
   

 } else {
$errors [ 'processing_problem' ] [] .= $payment->getResponseText();$stop_transaction = true;

 }
// Uncomment these to troubleshoot.  You will need FireBug to view the response of the AJAX post. 
//echo $arb->xml;
//echo $arb->response;
//echo $arb->getResponse();

//echo $payment->getResponseText();
//echo $payment->getTransactionID();
//echo $payment->getAVSResponse();
//echo $payment->getAuthCode();
}


if ($stop_transaction && is_array($_POST))
{
	foreach ( $_POST as $key => $value )
	{
		if ( array_key_exists ( $key, $errors ) )
		{
			foreach ( $errors [ $key ] as $k => $v )
			{
				$errors_msg .= "error|$key|$v\n";
			}
		}
		else {
			$errors_msg .= "ok|$key\n";
		}
	}
}

		
echo $errors_msg;
}

function wp_invoice_currency_array() {
	$currency_list = array(
	"AUD"=> "Australian Dollars",
	"CAD"=> "Canadian Dollars",
	"EUR"=> "Euros",
	"GBP"=> "Pounds Sterling",
	"JPY"=> "Yen",
	"USD"=> "U.S. Dollars",
	"NZD"=> "New Zealand Dollar",
	"CHF"=> "Swiss Franc",
	"HKD"=> "Hong Kong Dollar",
	"SGD"=> "Singapore Dollar",
	"SEK"=> "Swedish Krona",
	"DKK"=> "Danish Krone",
	"PLN"=> "Polish Zloty",
	"NOK"=> "Norwegian Krone",
	"HUF"=> "Hungarian Forint",
	"CZK"=> "Czech Koruna",
	"ILS"=> "Israeli Shekel",
	"MXN"=> "Mexican Peso");
	
	return $currency_list;
}

function wp_invoice_currency_symbol($currency = "USD" )
{
	$currency_list = array(
	'CAD'=> '$',
	'EUR'=> '&#8364;',
	'GBP'=> '&pound;',
	'JPY'=> '&yen;',
	'USD'=> '$');


foreach($currency_list as $value => $display)
{
    if($currency == $value) { return $display; $success = true; break;}
}
if(!$success) return $currency;
	
	
	
}

function wp_invoice_contextual_help_list($content) {
// Will add help and FAQ here eventually
return $content;
}

function wp_invoice_process_invoice_update($invoice_id) {

	global $wpdb;
	
	
	if($_REQUEST['user_id'] == 'create_new_user') {
		
		$user_info = array();
		$user_info['wp_invoice_first_name'] = $_REQUEST['wp_invoice_first_name'];
		$user_info['wp_invoice_last_name'] = $_REQUEST['wp_invoice_last_name'];
		$user_info['wp_invoice_new_user_username'] = $_REQUEST['wp_invoice_new_user_username'];
		$user_info['wp_invoice_new_user_email_address'] = $_REQUEST['wp_invoice_new_user_email_address'];
		
		$send_notification = ($_REQUEST['wp_invoice_send_new_user_email'] == 'true' ? true : false);

		$user_id = wp_invoice_create_wp_user($user_info, $send_notification);

		
	} else {
		$user_id = $_REQUEST['user_id'];
	}
	
	//Update User Information
	$profileuser = @get_user_to_edit($_POST['user_id']);
	$description = $_REQUEST['description'];
	$subject = $_REQUEST['subject'];
	$amount = $_REQUEST['amount'];
	
	
	
	//Update User Information
	if(!empty($_REQUEST['wp_invoice_first_name'])) update_usermeta($user_id, 'first_name', $_REQUEST['wp_invoice_first_name']);
	if(!empty($_REQUEST['wp_invoice_last_name'])) update_usermeta($user_id, 'last_name', $_REQUEST['wp_invoice_last_name']);
	if(!empty($_REQUEST['wp_invoice_streetaddress'])) update_usermeta($user_id, 'streetaddress', $_REQUEST['wp_invoice_streetaddress']);
	if(!empty($_REQUEST['wp_invoice_company_name'])) update_usermeta($user_id, 'company_name',$_REQUEST['wp_invoice_company_name']);
	if(!empty($_REQUEST['wp_invoice_city'])) update_usermeta($user_id, 'city',$_REQUEST['wp_invoice_city']);
	if(!empty($_REQUEST['wp_invoice_state'])) update_usermeta($user_id, 'state', $_REQUEST['wp_invoice_state']);
	if(!empty($_REQUEST['wp_invoice_zip'])) update_usermeta($user_id, 'zip', $_REQUEST['wp_invoice_zip']);
	//if(!empty($_REQUEST['wp_invoice_localization'])) update_usermeta($user_id, 'wpi_localization', $_REQUEST['wp_invoice_localization']);
	if(!empty($_REQUEST['wp_invoice_country'])) update_usermeta($user_id, 'country', $_REQUEST['wp_invoice_country']);



	
	// Itemized List
	$itemized_list = $_REQUEST['itemized_list'];
	//remove items from itemized list that are missing a title, they are most likely deleted
	if(is_array($itemized_list)) {
		$counter = 1;
		foreach($itemized_list as $itemized_item){
			if(empty($itemized_item[name])) {
				unset($itemized_list[$counter]); 
			}
		$counter++;
		}
	array_values($itemized_list);
	}
	$itemized = urlencode(serialize($itemized_list));

	
	// Check if this is new invoice creation, or an update

	if(wp_invoice_does_invoice_exist($invoice_id)) {
		// Updating Old Invoice

		if(wp_invoice_get_invoice_attrib($invoice_id,'subject') != $subject) { $wpdb->query("UPDATE ".WP_Invoice::tablename('main')." SET subject = '$subject' WHERE invoice_num = $invoice_id"); 			wp_invoice_update_log($invoice_id, 'updated', ' Subject Updated '); $message .= "Subject updated. ";}
		if(wp_invoice_get_invoice_attrib($invoice_id,'description') != $description) { $wpdb->query("UPDATE ".WP_Invoice::tablename('main')." SET description = '$description' WHERE invoice_num = $invoice_id"); 			wp_invoice_update_log($invoice_id, 'updated', ' Description Updated '); $message .= "Description updated. ";}
		if(wp_invoice_get_invoice_attrib($invoice_id,'amount') != $amount) { $wpdb->query("UPDATE ".WP_Invoice::tablename('main')." SET amount = '$amount' WHERE invoice_num = $invoice_id"); 			wp_invoice_update_log($invoice_id, 'updated', ' Amount Updated '); $message .= "Amount updated. ";}
		if(wp_invoice_get_invoice_attrib($invoice_id,'itemized') != $itemized) { $wpdb->query("UPDATE ".WP_Invoice::tablename('main')." SET itemized = '$itemized' WHERE invoice_num = $invoice_id"); 			wp_invoice_update_log($invoice_id, 'updated', ' Itemized List Updated '); $message .= "Itemized List updated. ";}
	}
	else {
		// Create New Invoice

		if($wpdb->insert(WP_Invoice::tablename('main'), array( 'amount' => $amount, 'description' => $description, 'invoice_num' => $invoice_id, 'user_id' => $user_id, 'subject' => $subject, 'itemized' => $itemized))) {
			$message = __("New Invoice saved.", WP_INVOICE_TRANS_DOMAIN);
			wp_invoice_update_log($invoice_id, 'created', ' Created ');;
		} 
		else { 
			$error = true; $message = __("There was a problem saving invoice.  Try deactivating and reactivating plugin.", WP_INVOICE_TRANS_DOMAIN); 
		}
	}
		
	// See if invoice is recurring
	if(!empty($_REQUEST['wp_invoice_subscription_name']) &&	!empty($_REQUEST['wp_invoice_subscription_unit']) && !empty($_REQUEST['wp_invoice_subscription_total_occurances'])) {
		$wp_invoice_recurring_status = true;
		wp_invoice_update_invoice_meta($invoice_id, "recurring_billing", true);
		$message .= __(" Recurring invoice saved.  This invoice may be viewed under <b>Recurring Billing</b>. ", WP_INVOICE_TRANS_DOMAIN);
	}

	$basic_invoice_settings = array(
	"wp_invoice_custom_invoice_id",
 	"wp_invoice_tax",
	"wp_invoice_currency_code",
	"wp_invoice_due_date_day",
	"wp_invoice_due_date_month",
	"wp_invoice_due_date_year");
	
	wp_invoice_process_updates($basic_invoice_settings, 'wp_invoice_update_invoice_meta', $invoice_id);
	
	
	$payment_and_billing_settings_array = array(
	"wp_invoice_payment_method",
	"wp_invoice_client_change_payment_method",
	
	"wp_invoice_paypal_allow",
	"wp_invoice_paypal_address",
	"wp_invoice_fe_paypal_link_url",
	
	"wp_invoice_cc_allow",
	"wp_invoice_gateway_url",
	"wp_invoice_gateway_username",
	"wp_invoice_gateway_tran_key",
	"wp_invoice_gateway_merchant_email",
	"wp_invoice_gateway_delim_data",
	"wp_invoice_gateway_delim_char",
	"wp_invoice_gateway_encap_char",
	"wp_invoice_gateway_MD5Hash",
	"wp_invoice_gateway_test_mode",
	"wp_invoice_gateway_relay_response",
	"wp_invoice_gateway_email_customer",
	"wp_invoice_recurring_gateway_url",
	
 
	
	"wp_invoice_subscription_name",
	"wp_invoice_subscription_unit",
	"wp_invoice_subscription_length",
	"wp_invoice_subscription_start_month",
	"wp_invoice_subscription_start_day",
	"wp_invoice_subscription_start_year",
	"wp_invoice_subscription_total_occurances");

	wp_invoice_process_updates($payment_and_billing_settings_array, 'wp_invoice_update_invoice_meta', $invoice_id);


	
	//If there is a message, append it with the web invoice link
	if($message && $invoice_id) {
	
	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;
	
	$message .= " <a href='".$invoice->pay_link."'>".__("View Web Invoice", WP_INVOICE_TRANS_DOMAIN)."</a>.";
	}
	
	
	if(!$error) return $message;
	if($error) return "An error occured: $message.";
	


}

function wp_invoice_show_message($content,$type="updated fade") {
if($content) echo "<div id=\"message\" class='$type' ><p>".$content."</p></div>";
}


/*
	Throw warnings if any required configuation settings are missing
*/
function wp_invoice_detect_config_erors() {
	global $wpdb;

	if(get_option("wp_invoice_web_invoice_page") == '') { $warning_message .= __('Invoice page not selected. ', WP_INVOICE_TRANS_DOMAIN); }
	if(get_option("wp_invoice_payment_method") == '') { $warning_message .= __('Payment method not set. ', WP_INVOICE_TRANS_DOMAIN); }
	if(get_option("wp_invoice_payment_method") == '' || get_option("wp_invoice_web_invoice_page") == '') {
		$warning_message .= __("Visit ", WP_INVOICE_TRANS_DOMAIN)."<a href='admin.php?page=invoice_settings'>settings page</a>".__(" to configure.", WP_INVOICE_TRANS_DOMAIN);
	}
	
	
	if(!$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('meta')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('main')."';") || !$wpdb->query("SHOW TABLES LIKE '".WP_Invoice::tablename('log')."';")) { 
		$warning_message .= __("The plugin database tables are gone, deactivate and reactivate plugin to re-create them.", WP_INVOICE_TRANS_DOMAIN);
	}

	
	
	return (!empty($warning_message) ? $warning_message : false);
}

function wp_invoice_process_settings() {
	global $wpdb;

	
	$settings_array = array(
	"wp_invoice_business_name",
	"wp_invoice_business_phone",
	"wp_invoice_business_address",
	"wp_invoice_default_currency_code",
	"wp_invoice_using_godaddy",
	"wp_invoice_email_address",
	"wp_invoice_force_https",
	"wp_invoice_where_to_display",
	"wp_invoice_welcome_line",
	"wp_invoice_custom_label_tax",
	"wp_invoice_custom_zip_label",
	
	"wp_invoice_googlecheckout_address",
	"wp_invoice_payment_link",
	"wp_invoice_payment_method",
	
	"wp_invoice_send_thank_you_email",
	"wp_invoice_show_business_address",
	"wp_invoice_show_quantities",
	"wp_invoice_fe_state_selection",
	"wp_invoice_use_css",
	"wp_invoice_use_recurring",
	"wp_invoice_exclude_ips",
	"wp_invoice_user_level",
	"wp_invoice_web_invoice_page",
	"wp_invoice_reminder_message",
	"wp_invoice_cc_thank_you_email",

	"wp_invoice_lookup_text",
	"wp_invoice_lookup_submit",

	"wp_invoice_email_send_invoice_subject",
	"wp_invoice_email_send_invoice_content",
	"wp_invoice_email_send_reminder_subject",
	"wp_invoice_email_send_reminder_content",
	"wp_invoice_email_send_receipt_subject",
	"wp_invoice_email_send_receipt_content");
	
	$payment_and_billing_settings_array = array(
	"wp_invoice_client_change_payment_method",
	
	"wp_invoice_paypal_allow",
	"wp_invoice_paypal_address",
	"wp_invoice_fe_paypal_link_url",
	
	"wp_invoice_cc_allow",
	"wp_invoice_gateway_url",
	"wp_invoice_gateway_username",
	"wp_invoice_gateway_tran_key",
	"wp_invoice_gateway_merchant_email",
	"wp_invoice_gateway_delim_data",
	"wp_invoice_gateway_delim_char",
	"wp_invoice_gateway_encap_char",
	"wp_invoice_gateway_MD5Hash",
	"wp_invoice_gateway_test_mode",
	"wp_invoice_gateway_relay_response",
	"wp_invoice_gateway_email_customer",
	"wp_invoice_recurring_gateway_url",
	
	"wp_invoice_moneybookers_allow",
	"wp_invoice_moneybookers_address",
	"wp_invoice_moneybookers_merchant",
	"wp_invoice_moneybookers_secret",
	"wp_invoice_moneybookers_ip",
	
	"wp_invoice_alertpay_allow",
	"wp_invoice_alertpay_address",
	"wp_invoice_alertpay_merchant",
	"wp_invoice_alertpay_secret",
	"wp_invoice_gateway_email_customer",
	"wp_invoice_alertpay_test_mode");

	wp_invoice_process_updates($payment_and_billing_settings_array);
	wp_invoice_process_updates($settings_array);
	
	if($_REQUEST['wp_invoice_load_original_email_templates']) { wp_invoice_load_email_template_content(); }
	
}

function wp_invoice_is_not_merchant() {
	if(get_option('wp_invoice_gateway_username') == '' || get_option('wp_invoice_gateway_tran_key') == '') return true;
}

function wp_invoice_process_updates($array, $type = "update_option", $invoice_id = '') {
	global $wp_invoice_debug;
 	if($type == "update_option") foreach($array as $item_name) { 
		if($wp_invoice_debug) echo $item_name . " - " . $_POST[$item_name] . "  <br />";
		if(isset($_POST[$item_name])) update_option($item_name, $_POST[$item_name]); 
	}
	
	if($type == "wp_invoice_update_invoice_meta") foreach($array as $item_name) { 
		if($wp_invoice_debug) echo $item_name . " - " . $_POST[$item_name] . "  <br />";
		if(isset($_POST[$item_name])) wp_invoice_update_invoice_meta($invoice_id, $item_name, $_POST[$item_name]); }
}



function wp_invoice_determine_currency($invoice_id) {
	//in class
	if(wp_invoice_meta($invoice_id,'wp_invoice_currency_code') != '')
		{ $currency_code = wp_invoice_meta($invoice_id,'wp_invoice_currency_code'); }
		elseif(get_option('wp_invoice_default_currency_code') != '')
		{ $currency_code = get_option('wp_invoice_default_currency_code'); }
		else { $currency_code = "USD"; }
		return $currency_code;
}

function wp_invoice_md5_to_invoice($md5) {
	global $wpdb, $_wp_invoice_md5_to_invoice_cache;
	if (isset($_wp_invoice_md5_to_invoice_cache[$md5]) && $_wp_invoice_md5_to_invoice_cache[$md5]) {
		return $_wp_invoice_md5_to_invoice_cache[$md5];
	}

	$md5_escaped = mysql_escape_string($md5);
	$all_invoices = $wpdb->get_col("SELECT invoice_num FROM ".WP_Invoice::tablename('main')." WHERE MD5(invoice_num) = '{$md5_escaped}'");
	foreach ($all_invoices as $value) {
		if(md5($value) == $md5) {
			$_wp_invoice_md5_to_invoice_cache[$md5] = $value;
			return $_wp_invoice_md5_to_invoice_cache[$md5];
		}
	}
}

function wp_invoice_create_paypal_itemized_list($itemized_array,$invoice_id) {
	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;

	$tax = $invoice->tax;
	$amount = $invoice->amount;
	$display_id = $invoice->display_id;
	
	$tax_free_sum = 0;
	$counter = 1;
	foreach($itemized_array as $itemized_item) {

		// If we have a negative item, PayPal will not accept, we must group everything into one amount
		if($itemized_item[price] * $itemized_item[quantity] < 0) {
			$tax = 0;
			$output = "
			<input type='hidden' name='item_name' value='Reference Invoice #$display_id' /> \n
			<input type='hidden' name='amount' value='$amount' />\n";

			$single_item = true;
			break;
		}

		$output .= "<input type='hidden' name='item_name_$counter' value='".$itemized_item[name]."' />\n";
		$output .= "<input type='hidden' name='amount_$counter' value='".$itemized_item[price] * $itemized_item[quantity]."' />\n";

		$tax_free_sum = $tax_free_sum + $itemized_item[price] * $itemized_item[quantity];
		$counter++;
	}

	// Add tax onnly by using tax_free_sum (which is the sums of all the individual items * quantities. 
	if(!empty($tax)) {
		$tax_cart = round($tax_free_sum * ($tax / 100),2);
		$output .= "<input type='hidden' name='tax_cart' value='". $tax_cart ."' />\n";	
	}

	if($single_item) $output .= "<input type='hidden' name='cmd' value='_xclick' />\n";	
	if(!$single_item) $output .= "
	<input type='hidden' name='cmd' value='_ext-enter' />
	<input type='hidden' name='redirect_cmd' value='_cart' />\n";	
	return $output;
}






function wp_invoice_accepted_payment($invoice_id = 'global') {
	
	// fix the occasional issue with empty varlue being passed. or if the invoice id doesn't exist, load defaults
	if(empty($invoice_id) || !wp_invoice_does_invoice_exist($invoice_id))
		$invoice_id = "global";

 	if($invoice_id == 'global') {
	
		if(get_option('wp_invoice_paypal_allow') == 'yes') { 
			$payment_array['paypal']['name'] = 'paypal'; 
			$payment_array['paypal']['active'] = true; 
			$payment_array['paypal']['nicename'] = "PayPal"; 
			if(get_option('wp_invoice_payment_method') == 'paypal' || get_option('wp_invoice_payment_method') == 'PayPal') $payment_array['paypal']['default'] = true; 
		}
		
		if(get_option('wp_invoice_cc_allow') == 'yes') { 
			$payment_array['cc']['name'] = 'cc'; 
			$payment_array['cc']['active'] = true; 
			$payment_array['cc']['nicename'] = "Credit Card"; 
			if(get_option('wp_invoice_payment_method') == 'cc' || get_option('wp_invoice_payment_method') == 'Credit Card') $payment_array['cc']['default'] = true; 
		}
 

		return $payment_array;
	} else {
	
		$invoice_class = new wp_invoice_get($invoice_id);
		$invoice_class = $invoice_class->data;
		
 		$payment_array = array();
		
		// PayPal
		if($invoice_class->paypal_allow) {
			$payment_array['paypal']['name'] = 'paypal'; 
			$payment_array['paypal']['active'] = true; 
			$payment_array['paypal']['nicename'] = "PayPal"; 
			if(strtolower($invoice_class->payment_method) == 'paypal')
				$payment_array['paypal']['default'] = true;
		
		}
		
		// Credit Card
		if($invoice_class->cc_allow) {
			$payment_array['cc']['name'] = 'cc'; 
			$payment_array['cc']['active'] = true; 
			$payment_array['cc']['nicename'] = "Credit Card"; 
			if(strtolower($invoice_class->payment_method) == 'cc')
				$payment_array['cc']['default'] = true;
		
		}

		return $payment_array;
	}
}



function wp_invoice_create_wp_user($p, $send_notification = false) {
   
	$username = $p['wp_invoice_new_user_username'];
	if(!$username or wp_invoice_username_taken($username)) {
		$username = wp_invoice_get_user_login_name();
	}   

	$generated_password = wp_generate_password();
	
	$userdata = array(
	 'user_pass' => $generated_password,
	 'user_login' => $username,
	 'user_email' => $p['wp_invoice_new_user_email_address'],
	 'first_name' => $p['wp_invoice_first_name'],
	 'last_name' =>  $p['wp_invoice_last_name']);

	if(empty($p['wp_invoice_new_user_email_address']))
		return false;
		
	$wpuid = wp_insert_user($userdata);

	if($send_notification) {
		wp_new_user_notification($wpuid, $generated_password);	
	}
	
	
	return $wpuid;
}

function wp_invoice_username_taken($username) {
  $user = get_userdatabylogin($username);
  return $user != false;
}

function wp_invoice_get_user_login_name() {
  return 'wp_invoice_'.rand(10000,100000);
}



function wp_invoice_email_variables($invoice_id) {
 	global $wp_invoice_email_variables;

	$invoice_class = new wp_invoice_get($invoice_id);
	$invoice = $invoice_class->data;
	
	
	$wp_invoice_email_variables = array(
		'recipient' => $invoice->user_class->callsign, 
		'business_name' => stripslashes(get_option("wp_invoice_business_name")),
		'recurring' => (wp_invoice_recurring($invoice_id) ? " recurring " : ""),
		'amount' => $invoice->display_amount,
		'link' => $invoice->pay_link,
		'business_email' => get_option("wp_invoice_email_address"),
		'subject' => $invoice->subject
	);

	if($invoice->description) {
		$wp_invoice_email_variables['description'] = $invoice->description .".";
	} else {
		$wp_invoice_email_variables['description'] = "";
	}
	
	return $wp_invoice_email_variables;
}

function wp_invoice_email_apply_variables($matches) {
	global $wp_invoice_email_variables;

	if (isset($wp_invoice_email_variables[$matches[2]])) {
		return $wp_invoice_email_variables[$matches[2]];
	}
	return $matches[2];
}


function wp_invoice_load_email_template_content() {

	
// Send invoice
		add_option('wp_invoice_email_send_invoice_subject','%subject%');
		add_option('wp_invoice_email_send_invoice_content',
"Dear %recipient%, 

%business_name% has sent you a %recurring% web invoice in the amount of %amount%.

%description%

You may pay, view and print the invoice online by visiting the following link: 
%link%

Best regards,
%business_name% ( %business_email% )");

		// Send reminder
		add_option('wp_invoice_email_send_reminder_subject','[Reminder] %subject%');
		add_option('wp_invoice_email_send_reminder_content',
"Dear %recipient%, 

%business_name% has ent you a reminder for the %recurring% web invoice in the amount of %amount%.

%description%

You may pay, view and print the invoice online by visiting the following link: 
%link%.

Best regards,
%business_name% ( %business_email% )");

		// Send receipt
		add_option('wp_invoice_email_send_receipt_subject','Receipt for %subject%');
		add_option('wp_invoice_email_send_receipt_content',
"Dear %recipient%, 

%business_name% has received your payment for the %recurring% web invoice in the amount of %amount%.

Thank you very much for your patronage.

Best regards,
%business_name% ( %business_email% )");
	
}


class load_wp_invoice {

	var $invoice_id;

	
	function create_new($user_id) {
 	
		// this is a new invoice, get defaults
		$this->client_change_payment_method = get_option('wp_invoice_client_change_payment_method');
		$this->payment_method = get_option('wp_invoice_payment_method');

		$this->paypal_allow = get_option('wp_invoice_paypal_allow');
		$this->paypal_address = get_option('wp_invoice_paypal_address');

	 	
		$this->cc_allow = get_option('wp_invoice_cc_allow');		
		$this->gateway_username = get_option('wp_invoice_gateway_username');
		$this->gateway_tran_key = get_option('wp_invoice_gateway_tran_key');
		$this->gateway_url = get_option('wp_invoice_gateway_url');
		$this->recurring_gateway_url = get_option('wp_invoice_recurring_gateway_url');	
		$this->gateway_test_mode = get_option('wp_invoice_gateway_test_mode');
		$this->gateway_delim_char = get_option('wp_invoice_gateway_delim_char');
		$this->gateway_encap_char = get_option('wp_invoice_gateway_encap_char');
		$this->gateway_merchant_email = get_option('wp_invoice_gateway_merchant_email');
		$this->gateway_email_customer = get_option('wp_invoice_gateway_email_customer');
		$this->gateway_MD5Hash = get_option('wp_invoice_gateway_MD5Hash');	

	 
	
		$this->currency_code = get_option("wp_invoice_default_currency_code");
	
			
		// create item rows 
		$this->itemized[1] = "";
		$this->itemized[2] = "";	

		// Check if new user is being created
		if($user_id == 'create_new_user') {
		
				$this->create_new_user = true;
				$this->user_class->create_new_user = true;
		
		} else {
			$user_class = get_userdata($user_id);
		
			if(!$user_class->ID): 
				$this->user_deleted = true;
			else:
				$this->user_class = $user_class;
			endif;	
		}
	
	}
	
	
	function create_from_template($template_invoice_id, $user_id) {
		global $wpdb;
		
 		$invoice_info = $wpdb->get_row("SELECT * FROM ".WP_Invoice::tablename('main')." WHERE invoice_num = '".$template_invoice_id."'");
 		$this->invoice_id = rand(10000000, 90000000);
		$this->amount = $invoice_info->amount;
		$this->subject = $invoice_info->subject;
		$this->description = $invoice_info->description;
		$this->itemized = $invoice_info->itemized;
		$this->itemized = unserialize(urldecode($this->itemized)); 
		
		
		$this->template_copy = true;

		
			// Check if new user is being created
		if($user_id == 'create_new_user') {
		
				$this->create_new_user = true;
				$this->user_class->create_new_user = true;
		
		} else {
			$user_class = get_userdata($user_id);
		
			if(!$user_class->ID): 
				$this->user_deleted = true;
			else:
				$this->user_class = $user_class;
			endif;	
		}
		

		$this->tax = wp_invoice_meta($template_invoice_id,'wp_invoice_tax');
		if($this->tax == '') $this->tax = wp_invoice_meta($template_invoice_id,'tax_value');

		
		$this->currency_code = wp_invoice_meta($template_invoice_id,'wp_invoice_currency_code');
		$this->due_date_day = wp_invoice_meta($template_invoice_id,'wp_invoice_due_date_day');
		$this->due_date_month = wp_invoice_meta($template_invoice_id,'wp_invoice_due_date_month');
		$this->due_date_year = wp_invoice_meta($template_invoice_id,'wp_invoice_due_date_year');

		$this->subscription_name = wp_invoice_meta($template_invoice_id,'wp_invoice_subscription_name');
		$this->subscription_unit = wp_invoice_meta($template_invoice_id,'wp_invoice_subscription_unit');
		$this->subscription_length = wp_invoice_meta($template_invoice_id,'wp_invoice_subscription_length');
		$this->subscription_start_month = wp_invoice_meta($template_invoice_id,'wp_invoice_subscription_start_month');
		$this->subscription_start_day = wp_invoice_meta($template_invoice_id,'wp_invoice_subscription_start_day');
		$this->subscription_start_year = wp_invoice_meta($template_invoice_id,'wp_invoice_subscription_start_year');
		$this->subscription_total_occurances = wp_invoice_meta($template_invoice_id,'wp_invoice_subscription_total_occurances');
		
 
  		
		$this->client_change_payment_method = wp_invoice_meta($template_invoice_id,'wp_invoice_client_change_payment_method');
		$this->payment_method = wp_invoice_meta($template_invoice_id,'wp_invoice_payment_method');

		$this->paypal_allow = wp_invoice_meta($template_invoice_id,'wp_invoice_paypal_allow'); 
		$this->paypal_address = wp_invoice_meta($template_invoice_id,'wp_invoice_paypal_address');
		
		$this->cc_allow = wp_invoice_meta($template_invoice_id,'wp_invoice_cc_allow');  
		$this->gateway_username = wp_invoice_meta($template_invoice_id,'wp_invoice_gateway_username');
		$this->gateway_tran_key = wp_invoice_meta($template_invoice_id,'wp_invoice_gateway_tran_key');
		$this->gateway_url = wp_invoice_meta($template_invoice_id,'wp_invoice_gateway_url');
		$this->recurring_gateway_url = wp_invoice_meta($template_invoice_id,'wp_invoice_recurring_gateway_url');
 
 
		if($this->subscription_name && $this->subscription_unit && $this->subscription_total_occurances)
			$this->is_recurring = true;
		
	
	
	}
	
	
	
	function load_existing($invoice_id = false) {
		global $wpdb;
		
		// If variable is passed, overwrite class variable
		if(isset($invoice_id))
			$this->invoice_id = $invoice_id;
			
		$invoice_class = new wp_invoice_get($this->invoice_id);
		$this->data = $invoice_class->data;

 	}

}

function wp_invoice_get_user_invoices($args = false) {
	global $wpdb;
	
	$defaults = array('user_id' => false, 'status' => 'paid');
	$args = wp_parse_args($args, $defaults);
	extract($args, EXTR_SKIP);
	
	
	
	if(!$user_id)
		return false;
		
	
	if($status == 'paid'):
		$paid_array = $wpdb->get_results("
		SELECT DISTINCT amount, description, invoice_num, subject FROM  ".WP_Invoice::tablename('main')." 
		LEFT JOIN ".WP_Invoice::tablename('meta')."  
		ON ".WP_Invoice::tablename('main').".invoice_num = ".WP_Invoice::tablename('meta').".invoice_id 
		WHERE meta_key = 'paid_status' 
		AND meta_value = 'paid'
		AND user_id = '$user_id'");
				
		
		if(count($paid_array) > 0)
			return $paid_array;
		
		return false;
	endif;
	
	if($status == 'unpaid'):
	
		// can't do a neat query as above because unpaid invoices don't have any special designators
		$unpaid_raw_array = $wpdb->get_results("
		SELECT DISTINCT amount, description, invoice_num, subject FROM ".WP_Invoice::tablename('main')." 
		LEFT JOIN ".WP_Invoice::tablename('meta')."  
		ON ".WP_Invoice::tablename('main').".invoice_num = ".WP_Invoice::tablename('meta').".invoice_id 
		WHERE user_id = '$user_id'");
		
		if(count($unpaid_raw_array) > 0) {
			$unpaid_array = array();
			
			foreach($unpaid_raw_array as $unpaid_invoice) {
			
				if(!wp_invoice_meta($unpaid_invoice->invoice_num, 'paid_status'))
					array_push($unpaid_array, $unpaid_invoice);
			
			
			}	
		
			return $unpaid_array;
		}
		
				
		return false;
		
	endif;
	
	
}
 
 
 
 
 
 
 
 
 
 
 
 
?>