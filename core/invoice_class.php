<?php

/*
	Gets an invoice
*/
class wp_invoice_get {
	
	var $invoice_id; 
	var $data;
	var $error;
	
	/*
		Load invoice variables
	*/
	function wp_invoice_get($invoice_id) {
		global $wpdb, $user_ID;
		$this->invoice_id = $invoice_id;
		
		
		$row_obj = $wpdb->get_row("SELECT * FROM ".WP_Invoice::tablename('main')."  WHERE invoice_num = '$invoice_id'");
 
		if(!$row_obj)
			return false;
			
			
		foreach($row_obj as $key => $value) {		
			$this->data->$key = $value;
		}
		
		// Convert itemized list into array
		$this->data->itemized = unserialize(urldecode($this->data->itemized));
 		
		
		// Get meta
		$meta_obj = $wpdb->get_results("SELECT meta_key, meta_value FROM ".WP_Invoice::tablename('meta')."  WHERE invoice_id = '$invoice_id'");
		foreach($meta_obj as $meta_row) {
			$meta_key = $meta_row->meta_key;
			$meta_value = $meta_row->meta_value;
			
			// Remove wp_invoice_ from old meta values
			$meta_key = str_replace("wp_invoice_", "", $meta_key);
			
			$this->data->$meta_key = $meta_value;
		}		
		
		// Get user information
		$this->data->user_class = get_userdata($this->data->user_id);
 
		// Get callsign
		
			$first_name = $this->data->user_class->first_name;
			$last_name = $this->data->user_class->last_name;
			$company_name = $this->data->user_class->company_name;
			$user_email = $this->data->user_class->user_email;

			if(!empty($company_name) && (empty($first_name) || empty($last_name))) $this->data->user_class->callsign = $company_name; 
			elseif(empty ($company_name) && (empty($first_name) || empty($last_name))) $this->data->user_class->callsign = $user_email; 
			else $this->data->user_class->callsign = $first_name . " " . $last_name;
			
		// Create shorthands
		$this->data->display_id = $this->data->wp_invoice_custom_invoice_id;
		
		if(!strpos($this->data->amount,'.')) 
			$amount = $this->data->amount . ".00"; 
		else 
			$amount = $this->data->amount;
		
		$this->data->display_amount = wp_invoice_currency_symbol($this->data->wp_invoice_currency_code) .wp_invoice_currency_format($amount);
				
		$this->data->hash = md5($invoice_id);
 
		// Get paid date
		$paid_date = $wpdb->get_var("SELECT time_stamp FROM  ".WP_Invoice::tablename('log')." WHERE action_type = 'paid' AND invoice_id = '".$invoice_id."' ORDER BY time_stamp DESC LIMIT 0, 1");
		if($paid_date) $this->data->paid_date = wp_invoice_Date::convert($paid_date, 'Y-m-d H', get_option('date_format'));
				
 
		// Determine if invoice has been paid
		if($this->data->paid != true)
			$this->data->is_paid = false;
		
		// Load Invoice History
		if($raw_history = $wpdb->get_results("SELECT * FROM ".WP_Invoice::tablename('log')." WHERE invoice_id = '$invoice_id' ORDER BY time_stamp DESC"))
			$this->data->log = $raw_history;
		else
			$this->data->log = false;
		
		// Recurring start date

			if($this->data->subscription_start_month && $this->data->subscription_start_year && $this->data->subscription_start_day) {
				$this->data->startDate = $this->data->subscription_start_year . "-" . $this->data->subscription_start_month . "-" . $this->data->subscription_start_day;
			} else {
				$this->data->startDate = date("Y-m-d");
			}

		
		// Dynamic Variables
		$this->data->pay_link = wp_invoice_build_invoice_link($invoice_id);
 		
		
		
		return apply_filters('wp_invoice_load_invoice',$this->data);
		
 
	}
	






}