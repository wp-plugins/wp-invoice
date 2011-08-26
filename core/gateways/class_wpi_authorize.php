<?php

/**
  Name: WP-Invoice Authorize.net Compatible Gateways
  Class: wpi_authorize
  Internal Slug: wpi_authorize
  JS Slug: wpi_authorize
  Version: 1.0
  Description: Uses Authorize.net compatible gateways to accept payments
 */
class wpi_authorize extends wpi_gateway_base {

  var $options = array(
      'name' => 'Credit Card',
      'public_name' => 'Credit Card',
      'allow' => '',
      'default_option' => '',
      'settings' => array(
          'gateway_username' => array(
              'label' => "Gateway Username",
              'value' => '',
              'description' => "Your credit card processor will provide you with a gateway username."
          ),
          'gateway_tran_key' => array(
              'label' => "Gateway Transaction Key",
              'value' => "",
              'description' => "You will be able to generate this in your credit card processor's control panel."
          ),
          'gateway_url' => array(
              'label' => "Gateway URL",
              'value' => "",
              'description' => "This is the URL provided to you by your credit card processing company.",
              'special' => array(
                  'MerchantPlus' => 'https://gateway.merchantplus.com/cgi-bin/PAWebClient.cgi',
                  'Authorize.Net' => 'https://secure.authorize.net/gateway/transact.dll',
                  'Authorize.Net Developer' => 'https://test.authorize.net/gateway/transact.dll',
              )
          ),
          'recurring_gateway_url' => array(
              'label' => "Recurring Billing Gateway URL",
              'value' => "",
              'description' => "Recurring billing gateway URL is most likely different from the Gateway URL, and will almost always be with Authorize.net. Be advised - test credit card numbers will be declined even when in test mode.",
              'special' => array(
                  'Authorize.net ARB' => 'https://api.authorize.net/xml/v1/request.api',
                  'Authorize.Net ARB Testing' => 'https://apitest.authorize.net/xml/v1/request.api'
              )
          ),
          'gateway_test_mode' => array(
              'label' => "Test / Live Mode",
              'type' => "select",
              'data' => array(
                  "TRUE" => "Test - Do Not Process Transactions",
                  "FALSE" => "Live - Process Transactions"
              )
          ),
          'gateway_delim_char' => array(
              'label' => "Delimiter Character",
              'value' => "",
              'description' => "Get this from your credit card processor. If the transactions are not going through, this character is most likely wrong."
          ),
          'gateway_encap_char' => array(
              'label' => "Encapsulation Character",
              'value' => "",
              'description' => "Authorize.net default is blank. Otherwise, get this from your credit card processor. If the transactions are going through, but getting strange responses, this character is most likely wrong."
          ),
          'gateway_email_customer' => array(
              'label' => "Email Customer (on success)",
              'type' => "select",
              'value' => '',
              'data' => array(
                  "TRUE" => "Yes",
                  "FALSE" => "No"
              )
          ),
          'gateway_merchant_email' => array(
              'label' => "Merchant Email",
              'value' => "",
              'description' => "Email address to which the merchant’s copy of the customer confirmation email should be sent. If a value is submitted, an email will be sent to this address as well as the address(es) configured in the Merchant Interface."
          ),
          'gateway_header_email_receipt' => array(
              'label' => "Customer Receipt Email Header",
              'value' => ""
          ),
          'gateway_MD5Hash' => array(
              'label' => "Security: MD5 Hash",
              'value' => ""
          ),
          'gateway_delim_data' => array(
              'label' => "Delim Data",
              'type' => "select",
              'value' => '',
              'data' => array(
                  "TRUE" => "Yes",
                  "FALSE" => "No"
              )
          ),
          'silent_post_url' => array(
              'label' => "Silent Post URL",
              'type' => "readonly",
              'value' => "",
              'description' => "Silent Post responses are returned in real-time, meaning as soon as the transaction processes the Silent Post is sent to your specified URL. Go to https://account.authorize.net -> Settings -> Silent Post URL and copy this URL to input field."
          )
      )
  );

  function __construct() {
    parent::__construct();
    $this->options['settings']['silent_post_url']['value'] = urlencode(get_bloginfo('url') . '/wp-admin/admin-ajax.php?action=wpi_gateway_server_callback&type=wpi_authorize');
  }

  function silent_post_url() {
    return "1";
  }

  function process_payment($data=null) {
    global $invoice, $wpi_settings;

    // Require our external libraries
    require_once('authorize.net/authnet.class.php');
    require_once('authorize.net/authnetARB.class.php');

    // Pull in the CCard data from the request, and other variables we'll use
    // If data passed then use it. Otherwise use data from request.
    // It used to make available to do payment processes by WPI_Payment_Api
    $cc_data = is_null($data) ? $_REQUEST['cc_data'] : $data;
    $invoice_id = $invoice['invoice_id'];
    $wp_users_id = $invoice['user_data']['ID'];
    $post_id = wpi_invoice_id_to_post_id($invoice_id);

    // Recurring
    $recurring = $invoice['type'] == 'recurring' ? true : false;

    // Response
    $response = array(
        'success' => false,
        'error' => false,
        'data' => null
    );

    // Invoice custom id which is sending to authorize.net
    $cc_data['invoice_id'] = $invoice_id;

    $invoice_obj = new WPI_Invoice();
    $invoice_obj->load_invoice("id={$invoice['invoice_id']}");

    if ($invoice['deposit_amount'] > 0) {
      $amount = (float) $cc_data['amount'];
      if (((float) $cc_data['amount']) > $invoice['net']) {
        $amount = $invoice['net'];
      }
      if (((float) $cc_data['amount']) < $invoice['deposit_amount']) {
        $amount = $invoice['deposit_amount'];
      }
    } else {
      $amount = $invoice['net'];
    }

    // We assume that all data is good to go, considering we are valadating with JavaScript
    $payment = new WP_Invoice_Authnet();
    $payment->transaction($cc_data['card_num']);

    // Billing Info
    $payment->setParameter("x_card_code", $cc_data['card_code']);
    $payment->setParameter("x_exp_date ", $cc_data['exp_month'] . $cc_data['exp_year']);
    $payment->setParameter("x_amount", $amount);
    $payment->setParameter("x_currency_code", $cc_data['currency_code']);
    
    if ($recurring) {
      $payment->setParameter("x_recurring_billing", true);
    }

    // Order Info
    $payment->setParameter("x_description", $invoice['post_title']);
    $payment->setParameter("x_invoice_id", $invoice['invoice_id']);
    $payment->setParameter("x_duplicate_window", 30);

    // Customer Info
    $payment->setParameter("x_first_name", $cc_data['first_name']);
    $payment->setParameter("x_last_name", $cc_data['last_name']);
    $payment->setParameter("x_address", $cc_data['address']);
    $payment->setParameter("x_city", $cc_data['city']);
    $payment->setParameter("x_state", $cc_data['state']);
    $payment->setParameter("x_country", $cc_data['country']);
    $payment->setParameter("x_zip", $cc_data['zip']);
    $payment->setParameter("x_phone", $cc_data['phonenumber']);
    $payment->setParameter("x_email", $cc_data['email_address']);
    $payment->setParameter("x_cust_id", "WP User - " . $wp_users_id);
    $payment->setParameter("x_customer_ip ", $_SERVER['REMOTE_ADDR']);

    // Process
    $payment->process();

    // Process results
    if ($payment->isApproved()) {
      update_user_meta($wp_users_id, 'last_name', $cc_data['last_name']);
      update_user_meta($wp_users_id, 'last_name', $cc_data['last_name']);
      update_user_meta($wp_users_id, 'first_name', $cc_data['first_name']);
      update_user_meta($wp_users_id, 'city', $cc_data['city']);
      update_user_meta($wp_users_id, 'state', $cc_data['state']);
      update_user_meta($wp_users_id, 'zip', $cc_data['zip']);
      update_user_meta($wp_users_id, 'streetaddress', $cc_data['address']);
      update_user_meta($wp_users_id, 'phonenumber', $cc_data['phonenumber']);
      update_user_meta($wp_users_id, 'country', $cc_data['country']);

      // Add payment amount
      $event_note = WPI_Functions::currency_format($amount, $invoice['invoice_id']) . " paid via Authorize.net";
      $event_amount = $amount;
      $event_type = 'add_payment';

      $event_note = urlencode($event_note);
      // Log balance changes
      $invoice_obj->add_entry("attribute=balance&note=$event_note&amount=$event_amount&type=$event_type");
      // Log client IP
      $success = "Successfully processed by {$_SERVER['REMOTE_ADDR']}";
      $invoice_obj->add_entry("attribute=invoice&note=$success&type=update");
      // Log payer email
      $payer_email = "Authorize.net Payer email: {$cc_data['email_address']}";
      $invoice_obj->add_entry("attribute=invoice&note=$payer_email&type=update");

      $invoice_obj->save_invoice();
      //Mark invoice as paid
      wp_invoice_mark_as_paid($invoice_id, $check = true);

      if (!empty($wpi_settings['send_thank_you_email']) && $wpi_settings['send_thank_you_email'] == 'true') {
        wp_invoice_send_email_receipt($invoice);
      }

      if (!empty($wpi_settings['cc_thank_you_email']) && $wpi_settings['cc_thank_you_email'] == 'true') {
        $paid_invoice = new WPI_Invoice();
        $paid_invoice->load_invoice("id={$invoice['invoice_id']}");
        wp_invoice_send_me_notification($paid_invoice->data);
      }

      $data['messages'][] = $payment->getResponseText();
      $response['success'] = true;
      $response['error'] = false;

      if ($recurring) {
        $arb = new WP_Invoice_AuthnetARB($invoice);
        // Customer Info
        $arb->setParameter('customerId', "WP User - " . $invoice['user_data']['ID']);
        $arb->setParameter('firstName', $cc_data['first_name']);
        $arb->setParameter('lastName', $cc_data['last_name']);
        $arb->setParameter('address', $cc_data['address']);
        $arb->setParameter('city', $cc_data['city']);
        $arb->setParameter('state', $cc_data['state']);
        $arb->setParameter('zip', $cc_data['zip']);
        $arb->setParameter('country', $cc_data['country']);
        $arb->setParameter('customerEmail', $cc_data['email_address']);
        $arb->setParameter('customerPhoneNumber', $cc_data['phonenumber']);

        // Billing Info
        $arb->setParameter('amount', $invoice['net']);
        $arb->setParameter('cardNumber', $cc_data['card_num']);
        $arb->setParameter('expirationDate', $cc_data['exp_month'] . $cc_data['exp_year']);

        //Subscription Info
        $arb->setParameter('refID', $invoice['invoice_id']);
        $arb->setParameter('subscrName', $invoice['post_title']);

        $arb->setParameter('interval_length', $invoice['recurring']['length']);
        $arb->setParameter('interval_unit', $invoice['recurring']['unit']);

        // format: yyyy-mm-dd
        if ($invoice['recurring']['send_invoice_automatically'] == 'on') {
          $arb->setParameter('startDate', date("Y-m-d", time()));
        } else {
          $arb->setParameter('startDate', $invoice['recurring']['start_date']['year'] . '-' . $invoice['recurring']['start_date']['month'] . '-' . $invoice['recurring']['start_date']['day']);
        }

        $arb->setParameter('totalOccurrences', $invoice['recurring']['cycles']);

        $arb->setParameter('trialOccurrences', 1);
        $arb->setParameter('trialAmount', '0.00');

        $arb->setParameter('orderInvoiceNumber', $invoice['invoice_id']);
        $arb->setParameter('orderDescription', $invoice['post_title']);

        $arb->createAccount();

        if ($arb->isSuccessful()) {
          update_post_meta($post_id, 'subscription_id', $arb->getSubscriberID());
          WPI_Functions::log_event($post_id, 'invoice', 'update', '', 'Subscription initiated, Subcription ID - ' . $arb->getSubscriberID());
          $data['messages'][] = "Recurring Billing Subscription initiated";
          $response['success'] = true;
          $response['error'] = false;
        }

        if ($arb->isError()) {
          $data['messages'][] = 'One-time credit card payment is processed successfully. However, recurring billing setup failed. ' . $arb->getResponse();
          $response['success'] = false;
          $response['error'] = true;
          WPI_Functions::log_event($post_id, 'invoice', 'update', '', 'Response Code: ' . $arb->getResponseCode() . ' | Subscription error - ' . $arb->getResponse());
        }
      }
    } else {
      $response['success'] = false;
      $response['error'] = true;
      $data['messages'][] = $payment->getResponseText();
    }

    $response['data'] = $data;

    // Uncomment these to troubleshoot.  You will need FireBug to view the response of the AJAX post.
    //echo $arb->xml;
    //echo $arb->response;
    //echo $arb->getResponse();
    //print_r( $payment->getResults() );
    //echo $payment->getResponseText();
    //echo $payment->getTransactionID();
    //echo $payment->getAVSResponse();
    //echo $payment->getAuthCode();

    die(json_encode($response));
  }

  function server_callback() {
    $arb = false;
    $fields = array();

    foreach ($_REQUEST as $name => $value) {
      $fields[$name] = $value;
      if ($name == 'x_subscription_id') {
        $arb = true;
      }
    }

    // Handle recurring billing payments
    if ($arb == true && $fields['x_response_code'] == 1) {

      $paynum = $fields['x_subscription_paynum'];
      $subscription_id = $fields['x_subscription_id'];
      $amount = $fields['x_amount'];
      $invoice_id = wpi_post_id_to_invoice_id(wpi_subscription_id_to_post_id($subscription_id));

      $invoice_obj = new WPI_Invoice();
      $invoice_obj->load_invoice("id=$invoice_id");

      // Add payment amount
      $event_note = WPI_Functions::currency_format(abs($amount), $invoice_id) . ". ARB payment $paynum of {$invoice_obj->data['recurring']['cycles']}";
      $event_amount = $amount;
      $event_type = 'add_payment';

      $invoice_obj->add_entry("attribute=balance&note=$event_note&amount=$event_amount&type=$event_type");

      // Complete subscription if last payment done
      if ($invoice_obj->data['recurring']['cycles'] <= $paynum) {
        WPI_Functions::log_event(wpi_invoice_id_to_post_id($invoice_id), 'invoice', 'update', '', 'Subscription completely paid');
        wp_invoice_mark_as_paid($invoice_id);
      }

      $invoice_obj->save_invoice();
    }
  }

}