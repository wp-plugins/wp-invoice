/**
 * Handle user events and interact with GA
 * 
 * @author korotkov@UD
 * @uses Google Analytics object '_gaq'
 */

//** Create 'wpi' object if it doesn't exist yet */
var wpi = wpi || {};

//** Create 'ga' object which contains objects and methods for interacting with Google Analytics */
wpi.ga = {
  
  //** Object with properties and methods for Event Tracking */
  tracking: {
    
    //** Available Event categories and actions */
    event : {
      category : {
        invoices : 'Invoice',
        spc : 'Single Page Checkout'
      },
      action : {
        pay : 'Pay',
        view : 'View'
      }
    },
    
    //** Getter for Event Category */
    get_event_cat : function( category ) {
      return typeof this.event.category[category] == 'string' ? this.event.category[category] : 'Unknown Category';
    },
    
    //** Getter for Event Action */
    get_event_act : function( action ) {
      return typeof this.event.action[action] == 'string' ? this.event.action[action] : 'Unknown Action';
    },
    
    //** Initialize and run push events mentioned in 'options' if they exist */
    init : function ( options ) {
      for ( var i in options ) {
        if ( options[i] == 'true' && typeof this[i] == 'function' ) this[i]();
      }
    },
    
    //** Add Event for '#cc_pay_button' click */
    attempting_pay_invoice : function () {
      jQuery('#cc_pay_button').live('click', function(){
        _gaq.push(['_trackEvent', wpi.ga.tracking.get_event_cat('invoices'), wpi.ga.tracking.get_event_act('pay'), invoice_title?invoice_title:'Unknown Label', parseInt(invoice_amount)]);
      });
    },
    
    //** Event of invoice viewing */
    view_invoice : function () {
      _gaq.push(['_trackEvent', wpi.ga.tracking.get_event_cat('invoices'), wpi.ga.tracking.get_event_act('view'), invoice_title?invoice_title:'Unknown Label']);
    }
  }
}