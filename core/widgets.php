<?php
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



/**
 Lookup Widget
 */
class InvoiceLookupWidget extends WP_Widget {
    /** constructor */
    function InvoiceLookupWidget() {
        parent::WP_Widget(false, $name = 'Invoice Lookup');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        $message = $instance['message'];
        $button_text = $instance['button_text'];
		echo $before_widget; 
		
		if ( $title )
			echo $before_title . $title . $after_title; 
		
		wp_invoice_lookup("message=$message&button=$button_text");
		echo $after_widget; 

    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        $message = esc_attr($instance['message']);
        $button_text = esc_attr($instance['button_text']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('message'); ?>"><?php _e('Message:'); ?> <textarea class="widefat" id="<?php echo $this->get_field_id('message'); ?>" name="<?php echo $this->get_field_name('message'); ?>" type="text"><?php echo $message; ?></textarea></label></p>
            <p><label for="<?php echo $this->get_field_id('button_text'); ?>"><?php _e('Button Text:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('button_text'); ?>" name="<?php echo $this->get_field_name('button_text'); ?>" type="text" value="<?php echo $button_text; ?>" /></label></p>
        <?php 
    }

} // class FooWidget

/**
	Invoice History
 */
class InvoiceHistoryWidget extends WP_Widget {
    /** constructor */
    function InvoiceHistoryWidget() {
		$widget_ops = array('classname' => 'widget_invoice_history', 'description' => __( 'User&#8217;s Paid and Pending Invoices') );
        parent::WP_Widget('invoice_history', __('Invoice History'), $widget_ops);
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {
        extract( $args );
		global $current_user;
		
		// Only show up when wpi_user_id is globally set - means somebody is logged in / followed hash link
		if(!isset($current_user->ID))
			return;
		
		$unpaid_invoice_array = wp_invoice_get_user_invoices("user_id={$current_user->ID}&status=unpaid");
		$paid_invoice_array = wp_invoice_get_user_invoices("user_id={$current_user->ID}&status=paid");
		
		if(!$unpaid_invoice_array && !$paid_invoice_array)
			return;
		
		
        $title = apply_filters('widget_title', $instance['title']);
        $message = $instance['message'];
        $button_text = $instance['button_text'];
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title;  ?>
	 
				<div class="wpi_widget_invoice_history">
				
				<?php if($unpaid_invoice_array) { ?>
				<b class="wpi_sidebar_title">Due Invoice(s)</b>
				<ul class="wpi_invoice_history_list wpi_due_invoices">
				<?php foreach($unpaid_invoice_array as $invoice) { ?>				
					<li>
						<span class="wpi_invoice_link"><a href="<?php echo wp_invoice_build_invoice_link($invoice->invoice_num); ?>"><?php echo $invoice->subject; ?></a></span>
						<span class="wpi_invoice_due"><?php echo wp_invoice_currency_symbol(wp_invoice_determine_currency($invoice->invoice_num)) . wp_invoice_currency_format($invoice->amount); ?></span>						
					</li>
				<?php  } ?>
				</ul>
				<?php } ?>
				
				<?php 
				if($paid_invoice_array) { ?>
								
				<b class="wpi_sidebar_title">Paid Invoice(s)</b>
				<ul class="wpi_invoice_history_list wpi_paid_invoices">
					<?php foreach($paid_invoice_array as $invoice) {  ?>
						<span class="wpi_invoice_link"><a href="<?php echo wp_invoice_build_invoice_link($invoice->invoice_num); ?>"><?php echo $invoice->subject; ?></a></span>
						<span class="wpi_invoice_due"><?php echo wp_invoice_currency_symbol(wp_invoice_determine_currency($invoice->invoice_num)) . wp_invoice_currency_format($invoice->amount); ?></span>						
					<?php  } ?>
				</ul>
				<?php } ?>		
				
			 
				</div>
						
               <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
        return $new_instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        $message = esc_attr($instance['message']);
        $button_text = esc_attr($instance['button_text']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
            <p><label for="<?php echo $this->get_field_id('message'); ?>"><?php _e('Message:'); ?> <textarea class="widefat" id="<?php echo $this->get_field_id('message'); ?>" name="<?php echo $this->get_field_name('message'); ?>" type="text"><?php echo $message; ?></textarea></label></p>
         <?php 
    }

} // class FooWidget

?>