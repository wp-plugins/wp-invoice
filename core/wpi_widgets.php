<?php

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
    extract($args);
    $title = apply_filters('widget_title', $instance['title']);
    $message = $instance['message'];
    $button_text = $instance['button_text'];
    echo $before_widget;

    if ($title)
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

}

// class FooWidget

/**
  Invoice History
 */
class InvoiceHistoryWidget extends WP_Widget {

  /** constructor */
  function InvoiceHistoryWidget() {
    $widget_ops = array('classname' => 'widget_invoice_history', 'description' => __('User&#8217;s Paid and Pending Invoices'));
    parent::WP_Widget('invoice_history', __('Invoice History'), $widget_ops);
  }

  /** @see WP_Widget::widget */
  function widget($args, $instance) {
    extract($args);
    global $current_user;

    if (!$current_user->ID)
      return;

    $title = apply_filters('widget_title', $instance['title']);
    $message = $instance['message'];
    $button_text = !empty($instance['button_text']) ? $instance['button_text'] : __('Submit');
    ?>
      <?php echo $before_widget; ?>
      <?php if ($title)
        echo $before_title . $title . $after_title; ?>
    <div class="wpi_widget_invoice_history">
      <!-- <?php //$invoice_array = WPI_Functions::get_user_quotes("user_id={$current_user->ID}");
      if (!empty($invoice_array) && is_array($invoice_array)) { ?>
          <b class="wpi_sidebar_title">Quotes</b>
          <ul class="wpi_invoice_history_list wpi_quotes_list">
        <?php foreach ($invoice_array as $invoice) {
          if ($invoice['reporting']['status'] == 'balance_due') { ?>
                          <li><a href="<?php echo get_invoice_permalink($invoice['invoice_id']); ?>"><?php echo $invoice['subject']; ?></a></li>
          <?php }
        } ?>
          </ul>
      <?php } ?> -->

      <?php
      //$invoice_array = WPI_Functions::get_user_invoices("user_id={$current_user->ID}&status=balance_due");
      /* if(is_array($invoice_array)) {
        ?>
        <b class="wpi_sidebar_title">Due Invoice(s)</b>
        <ul class="wpi_invoice_history_list wpi_due_invoices">
        <?php
        foreach($invoice_array as $invoice) {
        if($invoice['reporting']['status'] == 'balance_due') {
        ?>
        <li><a href="<?php echo get_invoice_permalink($invoice['invoice_id']); ?>"><?php echo $invoice['subject']; ?></a></li>
        <?php
        }
        }
        ?>
        </ul>
        <?php
        } */
      ?>

      <?php
      $invoice_array = WPI_Functions::get_user_invoices("user_email={$current_user->user_email}&status=active");

      if (!empty($invoice_array) && is_array($invoice_array)) {
        ?>
        <b class="wpi_sidebar_title"><?php _e("Active Invoice(s)"); ?></b>
        <ul class="wpi_invoice_history_list wpi_active_invoices">
          <?php
          foreach ($invoice_array as $invoice) {
            ?>
            <li><a href="<?php echo get_invoice_permalink($invoice->data['invoice_id']); ?>"><?php echo $invoice->data['post_title']; ?></a></li>
          <?php
        }
        ?>
        </ul>
        <?php
      }
      ?>

    <?php
    $invoice_array = WPI_Functions::get_user_invoices("user_email={$current_user->user_email}&status=paid");
    if (!empty($invoice_array) && is_array($invoice_array)) {
      ?>
        <b class="wpi_sidebar_title"><?php _e("Paid Invoice(s)"); ?></b>
        <ul class="wpi_invoice_history_list wpi_active_invoices">
          <?php
          foreach ($invoice_array as $invoice) {
            ?>
            <li><a href="<?php echo get_invoice_permalink($invoice->data['invoice_id']); ?>"><?php echo $invoice->data['post_title']; ?></a></li>
          <?php
        }
        ?>
        </ul>
      <?php
    }
    ?>

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
    $title = !empty($instance['title']) ? esc_attr($instance['title']) : '';
    $message = !empty($instance['message']) ? esc_attr($instance['message']) : '';
    $button_text = !empty($instance['button_text']) ? esc_attr($instance['button_text']) : '';
    ?>
    <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
    <p><label for="<?php echo $this->get_field_id('message'); ?>"><?php _e('Message:'); ?> <textarea class="widefat" id="<?php echo $this->get_field_id('message'); ?>" name="<?php echo $this->get_field_name('message'); ?>" type="text"><?php echo $message; ?></textarea></label></p>
    <?php
  }

}

// class FooWidget
?>