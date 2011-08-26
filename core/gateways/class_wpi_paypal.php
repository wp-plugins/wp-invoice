<?php
/**
Name: WP-Invoice PayPal Gateway
Class: wpi_paypal
Internal Slug: wpi_paypal
JS Slug: wpi_paypal
Version: 1.0
Description: Provides the PayPal for payment options
*/

class wpi_paypal extends wpi_gateway_base {

  var $options = array(
    'name' => 'PayPal',
    'allow' => true,
    'default_option' => '',
    'settings' => array(
      'paypal_address' => array(
        'label' => "PayPal Username",
        'value' => ''
      ),
      'test_mode' => array(
        'label' => "Use in Test Mode",
        'description' => "Use PayPal SandBox for test mode",
        'type' => 'select',
        'value' => 'https://www.paypal.com/cgi-bin/webscr',
        'data' => array(
          'https://www.paypal.com/cgi-bin/webscr' => "No",
          'https://www.sandbox.paypal.com/cgi-bin/webscr' => "Yes"
        )
      ),
      'button_url' => array(
        'label' => "PayPal Button URL",
        'value' => "https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif"
      ),
      'ipn' => array(
        'label' => "PayPal IPN URL",
        'type' => "readonly",
        'description' => "Once IPN is integrated, sellers can automate their back office so they don’t have to wait for payments to come in to trigger order fulfillment. Setup this URL into your PayPal Merchant Account Settings."
      )
    )
  );
  
  function __construct() {
    parent::__construct();
    $this->options['settings']['ipn']['value'] = urlencode( get_bloginfo('url').'/wp-admin/admin-ajax.php?action=wpi_gateway_server_callback&type=wpi_paypal' );
  }
  
  /**
   * Handler for PayPal IPN queries
   * @author Anton Korotkov
   * Full callback URL: http://domain/wp-admin/admin-ajax.php?action=wpi_gateway_server_callback&type=wpi_paypal
   */
  function server_callback(){
    
    if ( empty( $_POST ) ) die('Direct access not allowed');
    
    $invoice = new WPI_Invoice();
    $invoice->load_invoice("id={$_POST['invoice']}");

    if ( $this->_ipn_verified( $invoice ) ) {

      switch( $_POST['payment_status'] ) {

        case 'Pending':

          wp_invoice_mark_as_pending( $_POST['invoice'] );

          break;

        case 'Completed':
          // Add payment amount
          $event_note = WPI_Functions::currency_format(abs($_POST['mc_gross']), $_POST['invoice'])." paid via PayPal";
          $event_amount = (float)$_POST['mc_gross'];
          $event_type   = 'add_payment';

          // Log balance changes
          $invoice->add_entry("attribute=balance&note=$event_note&amount=$event_amount&type=$event_type");
          // Log client IP
          $success = "Successfully processed by {$_SERVER['REMOTE_ADDR']}";
          $invoice->add_entry("attribute=invoice&note=$success&type=update");
          // Log payer email
          $payer_email = "PayPal Payer email: {$_POST['payer_email']}";
          $invoice->add_entry("attribute=invoice&note=$payer_email&type=update");
          $invoice->save_invoice();
          // ... and mark invoice as paid
          wp_invoice_mark_as_paid( $_POST['invoice'], $check = true );
          break;

        default: break;

      }

    }
    
  }
  
  /**
   * Verify IPN and returns TRUE or FALSE
   * @author Anton Korotkov
   **/
  private function _ipn_verified( $invoice ) {

    $request = $invoice->data['billing']['wpi_paypal']['settings']['test_mode']['value'].'?cmd=_notify-validate';

    foreach ( $_POST as $key => $value ) {
      $value = urlencode( stripslashes( $value ) );
      $request .= "&$key=$value";
    }

    return strstr( file_get_contents( $request ), 'VERIFIED' ) ? TRUE : FALSE;

  }
    
}