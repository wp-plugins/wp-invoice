<?php
/**
    Used for global functions
*/
/**
    Returns an invoice object as an array.
*/
function get_invoice($args) {
    if(is_numeric($args)) {
        $invoice_id = $args;
    } else {
        extract(wp_parse_args($args, $defaults), EXTR_SKIP);
        $defaults = array ('invoice_id' => '', 'return_class' => false);
    }
    $invoice = new WPI_Invoice();
    $invoice->load_invoice("id=$invoice_id");
    
    if(!empty($invoice->error) && $invoice->error) {
      return "Invoice $invoice_id not found.";
    }
    
    if(!empty($return_class) && $return_class) {
      return $invoice;
    }
    
    return $invoice->data;
}

    function wpi_log_event($event) {
      WPI_Functions::log($event);
    }
    
/**
    Converted to WP 2.0
    Archives an invoice, or multiple invoices.
*/
    function wpi_archive_invoice($invoice_id) {
        global $wpdb;
        // Check to see if array is passed or single.
        if(is_array($invoice_id))
        {
            $counter=0;
            foreach ($invoice_id as $single_invoice_id) {
                $this_invoice = new WPI_Invoice();
                $this_invoice->load_invoice("id=$single_invoice_id");
                $this_invoice->set("status=archive");
                $this_invoice->add_entry("Archived.");
                if($this_invoice->save_invoice())
                    $counter++;
            }
            return __("$counter  invoice(s) archived.", WP_INVOICE_TRANS_DOMAIN);
        } else {
            $this_invoice = new WPI_Invoice();
            $this_invoice->load_invoice("id=$invoice_id");
            $this_invoice->set("status=archive");
            $this_invoice->add_entry("Archived.");
            if($this_invoice->save_invoice())
                return __('Successfully archived.', WP_INVOICE_TRANS_DOMAIN);
        }
    }
 
/**
    Invoice lookup function
    If return is passed as true, function is returned.
*/
    function wp_invoice_lookup($args = '') {
        global $wpi_settings;
        $defaults = array (
            'message' => 'Enter Invoice ID',
            'button' => 'Lookup',
            'return' => false
        );
        extract(wp_parse_args($args, $defaults), EXTR_SKIP);
         ob_start();
        if(WPI_Functions::wpi_use_custom_template('invoice_lookup.php'))
            include($wpi_settings['frontend_template_path'] . 'invoice_lookup.php');
        else
            include($wpi_settings['default_template_path'] . 'invoice_lookup.php');
        $result .= ob_get_contents();
        ob_end_clean();
        if($return)
            return $result;
        echo $result;
    }
/**
    TO keep wpi naming structure
*/
    function wpi_invoice_lookup($args = '') {
        return wp_invoice_lookup($args);
    }
?>