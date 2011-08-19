/* Our Rules for this type of form */
var wpi_paypal_rules = { 
  "first_name": {
    required: true
  },
  "last_name": {
    required: true
  }
};

/* Our Messages for this type of form */
var wpi_paypal_messages = { 
  "first_name": {
    required: "First name is required."
  },
  "last_name": {
    required: "Last name is required."
  }
};

/* This function adds to form validation, and returns true or false */
var wpi_paypal_validate_form = function(){
  /* Just return, no extra validation needed */
  return true;
};

/* This function handles the submit event */
var wpi_paypal_submit = function(){
  /* Just go ahead and return true, we want the form to submit */
  return true;
};

function wpi_paypal_init_form() {
  
}