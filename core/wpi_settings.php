<?php
// Load all WP-Invoice settings from get_option( 'wpi_options' )
// InitOptions are default settings, and loaded if wpi_options is not set. 
// All these settings are also stored in a global variable ($wpi_settings) for easy access

class WPI_Settings {

  var $Core;
  var $data;
  
  /*
   * Constructor
   */
  function WPI_Settings(&$Core) {
    $this->Core = $Core;
    $this->LoadOptions();
  }
  
  /*
   * 
   */
  function InitOptions() {
    global $wp_invoice_debug;
    
    if(isset($Core) && $Core) 
      $this->options['version'] = $this->Core->version;
      //$this->options[time_difference]                = WPI_Functions::time_difference(); /** Calculate time difference between server and user based on WP settings */
      
      /* Invoice Types */
      $this->options['types'] = array(
        'invoice' => array('label' => 'Invoice'),
        'recurring' => array('label' => 'Recurring')
      );
      
      $this->options['debug'] = $wp_invoice_debug;
      
      if($wp_invoice_debug) {
        $this->options['developer_mode'] = 'true';
      }
      
      // Localization Labels
      $this->options['custom_label_tax'] = "Tax";
      
      // WP-Invoice Lookup
      $this->options['lookup_text'] = "Pay Your Invoice";
      $this->options['lookup_submit'] = "Lookup";
      
      // Frontend Customization
      $this->options['use_custom_templates'] = "false";
      $this->options['state_selection'] = "Dropdown";
      
      $this->options['email_address'] = get_bloginfo('admin_email');
      $this->options['business_name']  = get_bloginfo('blogname');
      $this->options['business_address']  = '';
      $this->options['business_phone']  = '';
      
      $this->options['user_level']  = 8;
      
      $this->options['web_invoice_page'] = '';
      $this->options['where_to_display'] = 'overwrite';
      
      /* Advanced Settings */
      $this->options['allow_deposits'] = 'true';
        
      /* Payment */
      $this->options['client_change_payment_method'] = 'false';
        
        // Basic Settings
        $this->options['replace_page_title_with_subject'] = 'true';
        $this->options['using_godaddy'] = 'no';
        $this->options['use_wp_users'] = 'true';
        $this->options['first_time_setup_ran'] = 'false';
        $this->options['increment_invoice_id'] = 'false';
        $this->options['do_not_load_theme_specific_css'] = 'false';
        $this->options['cc_thank_you_email']  = 'false';
        $this->options['send_invoice_creator_email']  = 'false';
        $this->options['replace_page_heading_with_subject']  = 'false';
        $this->options['hide_page_title']  = 'false';
        $this->options['terms_acceptance_required'] = 'false';
        
        $this->options['use_css'] = 'yes';
        $this->options['force_https'] = 'false';
        $this->options['send_thank_you_email'] = 'no';
        $this->options['show_recurring_billing'] = 'true';
        $this->options['global_tax'] = '0';
        
        $this->options['user_meta']['required']['first_name'] = 'First Name';
        $this->options['user_meta']['required']['last_name'] = 'Last Name';
        $this->options['user_meta']['custom']['company_name'] = 'Company Name';
        $this->options['user_meta']['custom']['phonenumber'] = 'Phone Number';
        $this->options['user_meta']['custom']['streetaddress'] = 'Street Address';
        $this->options['user_meta']['custom']['city'] = 'City';
        $this->options['user_meta']['custom']['state'] = 'State';
        $this->options['user_meta']['custom']['zip'] = 'ZIP';
        
        // Invoice statuses. Filter: wpi_invoice_statuses
        $this->options['invoice_statuses']['active'] = "Active";
        $this->options['invoice_statuses']['archive'] = "Archived";
        $this->options['invoice_statuses']['trash'] = "Trashed";
        $this->options['invoice_statuses']['paid'] = "Paid";
        
        $this->options['countries']['US'] = "United States";
        $this->options['countries']['AL'] = "Albania";
        $this->options['countries']['DZ'] = "Algeria";
        $this->options['countries']['AD'] = "Andorra";
        $this->options['countries']['AO'] = "Angola";
        $this->options['countries']['AI'] = "Anguilla";
        $this->options['countries']['AG'] = "Antigua and Barbuda";
        $this->options['countries']['AR'] = "Argentina";
        $this->options['countries']['AM'] = "Armenia";
        $this->options['countries']['AW'] = "Aruba";
        $this->options['countries']['AU'] = "Australia";
        $this->options['countries']['AT'] = "Austria";
        $this->options['countries']['AZ'] = "Azerbaijan Republic";
        $this->options['countries']['BS'] = "Bahamas";
        $this->options['countries']['BH'] = "Bahrain";
        $this->options['countries']['BB'] = "Barbados";
        $this->options['countries']['BE'] = "Belgium";
        $this->options['countries']['BZ'] = "Belize";
        $this->options['countries']['BJ'] = "Benin";
        $this->options['countries']['BM'] = "Bermuda";
        $this->options['countries']['BT'] = "Bhutan";
        $this->options['countries']['BO'] = "Bolivia";
        $this->options['countries']['BA'] = "Bosnia and Herzegovina";
        $this->options['countries']['BW'] = "Botswana";
        $this->options['countries']['BR'] = "Brazil";
        $this->options['countries']['VG'] = "British Virgin Islands";
        $this->options['countries']['BN'] = "Brunei";
        $this->options['countries']['BG'] = "Bulgaria";
        $this->options['countries']['BF'] = "Burkina Faso";
        $this->options['countries']['BI'] = "Burundi";
        $this->options['countries']['KH'] = "Cambodia";
        $this->options['countries']['CA'] = "Canada";
        $this->options['countries']['CV'] = "Cape Verde";
        $this->options['countries']['KY'] = "Cayman Islands";
        $this->options['countries']['TD'] = "Chad";
        $this->options['countries']['CL'] = "Chile";
        $this->options['countries']['C2'] = "China";
        $this->options['countries']['CO'] = "Colombia";
        $this->options['countries']['KM'] = "Comoros";
        $this->options['countries']['CK'] = "Cook Islands";
        $this->options['countries']['CR'] = "Costa Rica";
        $this->options['countries']['HR'] = "Croatia";
        $this->options['countries']['CY'] = "Cyprus";
        $this->options['countries']['CZ'] = "Czech Republic";
        $this->options['countries']['CD'] = "Democratic Republic of the Congo";
        $this->options['countries']['DK'] = "Denmark";
        $this->options['countries']['DJ'] = "Djibouti";
        $this->options['countries']['DM'] = "Dominica";
        $this->options['countries']['DO'] = "Dominican Republic";
        $this->options['countries']['EC'] = "Ecuador";
        $this->options['countries']['SV'] = "El Salvador";
        $this->options['countries']['ER'] = "Eritrea";
        $this->options['countries']['EE'] = "Estonia";
        $this->options['countries']['ET'] = "Ethiopia";
        $this->options['countries']['FK'] = "Falkland Islands";
        $this->options['countries']['FO'] = "Faroe Islands";
        $this->options['countries']['FM'] = "Federated States of Micronesia";
        $this->options['countries']['FJ'] = "Fiji";
        $this->options['countries']['FI'] = "Finland";
        $this->options['countries']['FR'] = "France";
        $this->options['countries']['GF'] = "French Guiana";
        $this->options['countries']['PF'] = "French Polynesia";
        $this->options['countries']['GA'] = "Gabon Republic";
        $this->options['countries']['GM'] = "Gambia";
        $this->options['countries']['DE'] = "Germany";
        $this->options['countries']['GI'] = "Gibraltar";
        $this->options['countries']['GR'] = "Greece";
        $this->options['countries']['GL'] = "Greenland";
        $this->options['countries']['GD'] = "Grenada";
        $this->options['countries']['GP'] = "Guadeloupe";
        $this->options['countries']['GT'] = "Guatemala";
        $this->options['countries']['GN'] = "Guinea";
        $this->options['countries']['GW'] = "Guinea Bissau";
        $this->options['countries']['GY'] = "Guyana";
        $this->options['countries']['HN'] = "Honduras";
        $this->options['countries']['HK'] = "Hong Kong";
        $this->options['countries']['HU'] = "Hungary";
        $this->options['countries']['IS'] = "Iceland";
        $this->options['countries']['IN'] = "India";
        $this->options['countries']['ID'] = "Indonesia";
        $this->options['countries']['IE'] = "Ireland";
        $this->options['countries']['IL'] = "Israel";
        $this->options['countries']['IT'] = "Italy";
        $this->options['countries']['JM'] = "Jamaica";
        $this->options['countries']['JP'] = "Japan";
        $this->options['countries']['JO'] = "Jordan";
        $this->options['countries']['KZ'] = "Kazakhstan";
        $this->options['countries']['KE'] = "Kenya";
        $this->options['countries']['KI'] = "Kiribati";
        $this->options['countries']['KW'] = "Kuwait";
        $this->options['countries']['KG'] = "Kyrgyzstan";
        $this->options['countries']['LA'] = "Laos";
        $this->options['countries']['LV'] = "Latvia";
        $this->options['countries']['LS'] = "Lesotho";
        $this->options['countries']['LI'] = "Liechtenstein";
        $this->options['countries']['LT'] = "Lithuania";
        $this->options['countries']['LU'] = "Luxembourg";
        $this->options['countries']['MG'] = "Madagascar";
        $this->options['countries']['MW'] = "Malawi";
        $this->options['countries']['MY'] = "Malaysia";
        $this->options['countries']['MV'] = "Maldives";
        $this->options['countries']['ML'] = "Mali";
        $this->options['countries']['MT'] = "Malta";
        $this->options['countries']['MH'] = "Marshall Islands";
        $this->options['countries']['MQ'] = "Martinique";
        $this->options['countries']['MR'] = "Mauritania";
        $this->options['countries']['MU'] = "Mauritius";
        $this->options['countries']['YT'] = "Mayotte";
        $this->options['countries']['MX'] = "Mexico";
        $this->options['countries']['MN'] = "Mongolia";
        $this->options['countries']['MS'] = "Montserrat";
        $this->options['countries']['MA'] = "Morocco";
        $this->options['countries']['MZ'] = "Mozambique";
        $this->options['countries']['NA'] = "Namibia";
        $this->options['countries']['NR'] = "Nauru";
        $this->options['countries']['NP'] = "Nepal";
        $this->options['countries']['NL'] = "Netherlands";
        $this->options['countries']['AN'] = "Netherlands Antilles";
        $this->options['countries']['NC'] = "New Caledonia";
        $this->options['countries']['NZ'] = "New Zealand";
        $this->options['countries']['NI'] = "Nicaragua";
        $this->options['countries']['NE'] = "Niger";
        $this->options['countries']['NU'] = "Niue";
        $this->options['countries']['NF'] = "Norfolk Island";
        $this->options['countries']['NO'] = "Norway";
        $this->options['countries']['OM'] = "Oman";
        $this->options['countries']['PW'] = "Palau";
        $this->options['countries']['PA'] = "Panama";
        $this->options['countries']['PG'] = "Papua New Guinea";
        $this->options['countries']['PE'] = "Peru";
        $this->options['countries']['PH'] = "Philippines";
        $this->options['countries']['PN'] = "Pitcairn Islands";
        $this->options['countries']['PL'] = "Poland";
        $this->options['countries']['PT'] = "Portugal";
        $this->options['countries']['QA'] = "Qatar";
        $this->options['countries']['CG'] = "Republic of the Congo";
        $this->options['countries']['RE'] = "Reunion";
        $this->options['countries']['RO'] = "Romania";
        $this->options['countries']['RU'] = "Russia";
        $this->options['countries']['RW'] = "Rwanda";
        $this->options['countries']['VC'] = "Saint Vincent and the Grenadines";
        $this->options['countries']['WS'] = "Samoa";
        $this->options['countries']['SM'] = "San Marino";
        $this->options['countries']['ST'] = "Sao Tome and Principe";
        $this->options['countries']['SA'] = "Saudi Arabia";
        $this->options['countries']['SN'] = "Senegal";
        $this->options['countries']['SC'] = "Seychelles";
        $this->options['countries']['SL'] = "Sierra Leone";
        $this->options['countries']['SG'] = "Singapore";
        $this->options['countries']['SK'] = "Slovakia";
        $this->options['countries']['SI'] = "Slovenia";
        $this->options['countries']['SB'] = "Solomon Islands";
        $this->options['countries']['SO'] = "Somalia";
        $this->options['countries']['ZA'] = "South Africa";
        $this->options['countries']['KR'] = "South Korea";
        $this->options['countries']['ES'] = "Spain";
        $this->options['countries']['LK'] = "Sri Lanka";
        $this->options['countries']['SH'] = "St. Helena";
        $this->options['countries']['KN'] = "St. Kitts and Nevis";
        $this->options['countries']['LC'] = "St. Lucia";
        $this->options['countries']['PM'] = "St. Pierre and Miquelon";
        $this->options['countries']['SR'] = "Suriname";
        $this->options['countries']['SJ'] = "Svalbard and Jan Mayen Islands";
        $this->options['countries']['SZ'] = "Swaziland";
        $this->options['countries']['SE'] = "Sweden";
        $this->options['countries']['CH'] = "Switzerland";
        $this->options['countries']['TW'] = "Taiwan";
        $this->options['countries']['TJ'] = "Tajikistan";
        $this->options['countries']['TZ'] = "Tanzania";
        $this->options['countries']['TH'] = "Thailand";
        $this->options['countries']['TG'] = "Togo";
        $this->options['countries']['TO'] = "Tonga";
        $this->options['countries']['TT'] = "Trinidad and Tobago";
        $this->options['countries']['TN'] = "Tunisia";
        $this->options['countries']['TR'] = "Turkey";
        $this->options['countries']['TM'] = "Turkmenistan";
        $this->options['countries']['TC'] = "Turks and Caicos Islands";
        $this->options['countries']['TV'] = "Tuvalu";
        $this->options['countries']['UG'] = "Uganda";
        $this->options['countries']['UA'] = "Ukraine";
        $this->options['countries']['AE'] = "United Arab Emirates";
        $this->options['countries']['GB'] = "United Kingdom";
        $this->options['countries']['UY'] = "Uruguay";
        $this->options['countries']['VU'] = "Vanuatu";
        $this->options['countries']['VA'] = "Vatican City State";
        $this->options['countries']['VE'] = "Venezuela";
        $this->options['countries']['VN'] = "Vietnam";
        $this->options['countries']['WF'] = "Wallis and Futuna Islands";
        $this->options['countries']['YE'] = "Yemen";
        $this->options['countries']['ZM'] = "Zambia";
        
        $this->options['states']['AL'] = "Alabama";
        $this->options['states']['AK'] = "Alaska";
        $this->options['states']['AS'] = "American Samoa";
        $this->options['states']['AZ'] = "Arizona";
        $this->options['states']['AR'] = "Arkansas";
        $this->options['states']['CA'] = "California";
        $this->options['states']['CO'] = "Colorado";
        $this->options['states']['CT'] = "Connecticut";
        $this->options['states']['DE'] = "Delaware";
        $this->options['states']['DC'] = "District of Columbia";
        $this->options['states']['FM'] = "Federated States of Micronesia";
        $this->options['states']['FL'] = "Florida";
        $this->options['states']['GA'] = "Georgia";
        $this->options['states']['GU'] = "Guam";
        $this->options['states']['HI'] = "Hawaii";
        $this->options['states']['ID'] = "Idaho";
        $this->options['states']['IL'] = "Illinois";
        $this->options['states']['IN'] = "Indiana";
        $this->options['states']['IA'] = "Iowa";
        $this->options['states']['KS'] = "Kansas";
        $this->options['states']['KY'] = "Kentucky";
        $this->options['states']['LA'] = "Louisiana";
        $this->options['states']['ME'] = "Maine";
        $this->options['states']['MH'] = "Marshall Islands";
        $this->options['states']['MD'] = "Maryland";
        $this->options['states']['MA'] = "Massachusetts";
        $this->options['states']['MI'] = "Michigan";
        $this->options['states']['MN'] = "Minnesota";
        $this->options['states']['MS'] = "Mississippi";
        $this->options['states']['MO'] = "Missouri";
        $this->options['states']['MT'] = "Montana";
        $this->options['states']['NE'] = "Nebraska";
        $this->options['states']['NV'] = "Nevada";
        $this->options['states']['NH'] = "New Hampshire";
        $this->options['states']['NJ'] = "New Jersey";
        $this->options['states']['NM'] = "New Mexico";
        $this->options['states']['NY'] = "New York";
        $this->options['states']['NC'] = "North Carolina";
        $this->options['states']['ND'] = "North Dakota";
        $this->options['states']['MP'] = "Northern Mariana Islands";
        $this->options['states']['OH'] = "Ohio";
        $this->options['states']['OK'] = "Oklahoma";
        $this->options['states']['OR'] = "Oregon";
        $this->options['states']['PW'] = "Palau";
        $this->options['states']['PA'] = "Pennsylvania";
        $this->options['states']['PR'] = "Puerto Rico";
        $this->options['states']['RI'] = "Rhode Island";
        $this->options['states']['SC'] = "South Carolina";
        $this->options['states']['SD'] = "South Dakota";
        $this->options['states']['TN'] = "Tennessee";
        $this->options['states']['TX'] = "Texas";
        $this->options['states']['UT'] = "Utah";
        $this->options['states']['VT'] = "Vermont";
        $this->options['states']['VI'] = "Virgin Islands";
        $this->options['states']['VA'] = "Virginia";
        $this->options['states']['WA'] = "Washington";
        $this->options['states']['WV'] = "West Virginia";
        $this->options['states']['WI'] = "Wisconsin";
        $this->options['states']['WY'] = "Wyoming";
        $this->options['states']['AB'] = "Alberta";
        $this->options['states']['BC'] = "British Columbia";
        $this->options['states']['MB'] = "Manitoba";
        $this->options['states']['NB'] = "New Brunswick";
        $this->options['states']['NF'] = "Newfoundland";
        $this->options['states']['NW'] = "Northwest Territory";
        $this->options['states']['NS'] = "Nova Scotia";
        $this->options['states']['ON'] = "Ontario";
        $this->options['states']['PE'] = "Prince Edward Island";
        $this->options['states']['QU'] = "Quebec";
        $this->options['states']['SK'] = "Saskatchewan";
        $this->options['states']['YT'] = "Yukon Territory";

        $this->options['currency']['types']['AUD'] = "Australian Dollars";
        $this->options['currency']['types']['CAD'] = "Canadian Dollars";
        $this->options['currency']['types']['EUR'] = "Euros";
        $this->options['currency']['types']['GBP'] = "Pounds Sterling";
        $this->options['currency']['types']['JPY'] = "Yen";
        $this->options['currency']['types']['USD'] = "U.S. Dollars";
        $this->options['currency']['types']['NZD'] = "New Zealand Dollar";
        $this->options['currency']['types']['CHF'] = "Swiss Franc";
        $this->options['currency']['types']['HKD'] = "Hong Kong Dollar";
        $this->options['currency']['types']['SGD'] = "Singapore Dollar";
        $this->options['currency']['types']['SEK'] = "Swedish Krona";
        $this->options['currency']['types']['DKK'] = "Danish Krone";
        $this->options['currency']['types']['PLN'] = "Polish Zloty";
        $this->options['currency']['types']['NOK'] = "Norwegian Krone";
        $this->options['currency']['types']['HUF'] = "Hungarian Forint";
        $this->options['currency']['types']['CZK'] = "Czech Koruna";
        $this->options['currency']['types']['ILS'] = "Israeli Shekel";
        $this->options['currency']['types']['MXN'] = "Mexican Peso";
        
        $this->options['currency']['symbol']['USD'] = "$";
        $this->options['currency']['symbol']['CAD'] = "$";
        $this->options['currency']['symbol']['EUR'] = "&#8364;";
        $this->options['currency']['symbol']['GBP'] = "&pound;";
        $this->options['currency']['symbol']['JPY'] = "&yen;";
        
        $this->options['currency']['default_currency_code'] = 'USD';
        
        // Favorite Countries are now stored in CSV format (Nov 25 09 - Potanin)
        //$this->options['globals']['favorite_countries'] = 'US,CA,RU';
        //$this->options['globals']['favorite_states']  = 'AL,AK,AS,AZ,AR,CA,CO,CT,DE,DC,FM,FL,GA,GU,HI,ID,IL,IN,IA,KS,KY,LA,ME,MH,MD,MA,MI,MN,MS,MO,MT,NE,NV,NH,NJ,NM,NY,NC,ND,MP,OH,OK,OR,PW,PA,PR,RI,SC,SD,TN,TX,UT,VT,VI,VA,WA,WV,WI,WY,AB,BC,MB,NB,NF,NW,NS,ON,PE,QU,SK,YT';
        $this->options['globals']['client_change_payment_method'] = 'true';
        $this->options['globals']['show_business_address'] = 'false';
        $this->options['globals']['show_quantities'] = 'false';
        
        //Mail - Notification
        $this->options['notification'][1]['name']    = "New Invoice";
        $this->options['notification'][1]['subject'] = "[New Invoice] %subject%";
        $this->options['notification'][1]['content'] = "Dear %recipient%, \n\n".get_bloginfo() . " has sent you a %recurring% invoice in the amount of %amount%. \n\n%description% \n\nYou may pay, view and print the invoice online by visiting the following link: \n%link% \n\nBest regards, \n".get_bloginfo() . " (".get_bloginfo('admin_email') . ")";
        $this->options['notification'][2]['name']    = "Reminder";
        $this->options['notification'][2]['subject'] = "[Reminder] %subject%";
        $this->options['notification'][2]['content'] = "Dear %recipient%, \n\n".get_bloginfo() . " has sent you a reminder for the %recurring% invoice in the amount of %amount%. \n\n%description% \n\nYou may pay, view and print the invoice online by visiting the following link: \n%link%. \n\nBest regards, \n".get_bloginfo() . " (".get_bloginfo('admin_email') . ")";
        $this->options['notification'][3]['name']    = 'Send Receipt';
        $this->options['notification'][3]['subject'] = "[Payment Received] %subject%";
        $this->options['notification'][3]['content'] = "Dear %recipient%, \n\n".get_bloginfo() . " has received your payment for the %recurring% invoice in the amount of %amount%. \n\nThank you very much for your patronage. \n\nBest regards, \n".get_bloginfo() . " (".get_bloginfo('admin_email') . ")";

    }

    function SaveSettings($new_settings) {
        global $wpi_settings;
        
        /* Set 'first_time_setup_ran' as 'true' to avoid loading First Time Setup Page in future */
        $new_settings['first_time_setup_ran'] = 'true';
        
        $this->options = WPI_Functions::array_merge_recursive_distinct($this->options, $new_settings);
        
        // Copy template files from plugin folder to active theme/template
        if(isset($new_settings['install_use_custom_templates']) &&
          isset($new_settings['use_custom_templates']) &&
          $new_settings['install_use_custom_templates'] == 'yes' && 
          $new_settings['use_custom_templates'] == 'yes') {
            WPI_Functions::install_templates();
        }
        
        /* Process Special Settings */
        /* Predefined Services */
        $this->options['predefined_services'] = ( isset($new_settings['predefined_services']) ? $new_settings['predefined_services'] : false );
        /* E-Mail Templates */
        if(isset($new_settings['notification'])) {
          $this->options['notification'] = $new_settings['notification'];
        }
        
        /* Process Special Settings */
        
        // fix checkboxes
        foreach($this->options['billing'] as $key => $value) {
            if(!isset($new_settings['billing'][$key]['allow'])) unset($this->options['billing'][$key]['allow']);
        }
        
        $checkbox_array = array('increment_invoice_id', 'send_thank_you_email', 'cc_thank_you_email', 'force_https', 'show_recurring_billing', 'send_invoice_creator_email');
        foreach($checkbox_array as $checkbox_name) {
            if(!isset($new_settings[$checkbox_name])) unset($this->options[$checkbox_name]);
        }
        
        /*// Does it really need here? Maxim Peshkov.
        $globals_checkbox_array = array('show_business_address','show_quantities');
        foreach($globals_checkbox_array as $checkbox_name) {
          if(!isset($new_settings['globals'][$checkbox_name])) unset($this->options['globals'][$checkbox_name]);
        }
        */
        
        $this->CommitUpdates();
        
        // Update global variable
        $wpi_settings = WPI_Functions::array_merge_recursive_distinct($wpi_settings, $this->options);
        /* Fix Predefined Services */
        $wpi_settings['predefined_services'] = $this->options['predefined_services'];
        /* Fix E-Mail Templates */
        $wpi_settings['notification'] = $this->options['notification'];
        wpi_gateway_base::sync_billing_objects();
    }
    
    function LoadOptions() {
        // Options concept taken from Theme My Login (http://webdesign.jaedub.com)
        $this->InitOptions();
        $storedoptions = get_option( 'wpi_options' );
        
        if ( $storedoptions && is_array( $storedoptions ) ) {
            foreach ( $storedoptions as $key => $value ) {
                $this->options[$key] = $value;
            }
        } else update_option( 'wpi_options', $this->options);
        
        // Depreciatd - Apply filters
        // $this->options['billing'] = apply_filters('wpi_billing_method', $this->options['billing']);
    }
    
    function GetOption( $key ) {
      if ( array_key_exists( $key, $this->options ) ) {
        return $this->options[$key];
      } else return null;
    }
    
    function setOption( $key, $value, $group = false) {
      global $wpi_settings;
      
      if(isset($this)) {
        $this->options[$key] = $value;
      } else {
        //** Handle option settings when not handled as object */
        
        if(!$value) {
          if($group) {
            unset($wpi_settings[$group][$key]);
          } else {
            unset($wpi_settings[$key]);
          }
        } else {
          if($group) {
            $wpi_settings[$group][$key] = $value;
          } else {
            $wpi_settings[$key] = $value;
          }
        }
        
        $settings = $wpi_settings;
        
        /* This element of array contain objects and should not be stored in DB */
        if(isset($settings['installed_gateways'])) {
          unset($settings['installed_gateways']);
        }
        
        if(update_option( 'wpi_options', $settings)) {
          return true;
        } 
      }
      
      
    }
    
    function CommitUpdates() {
        $oldvalue = get_option( 'wpi_options' );
				
        if( $oldvalue == $this->options )
            return false;
        else 
            return update_option( 'wpi_options', $this->options );
    }
    
    function ConvertPre20Options() {
        global $wpdb;
        
        // Take all old wp_invoice options and convert them put them into a single option
        // DOESN"T WORK WITH BILLING OPTIONS SINCE THEY ARE NOW HELD IN MULTIMENSIONAL ARRAY
        $load_all_options = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE 'wp_invoice%'");
        
        if(is_array($load_all_options)) {
            $counter = 0;
            while(list($key,$entry) = each($load_all_options)) {
                $this->setOption(str_replace("wp_invoice_", "", $entry->option_name), $entry->option_value);
                delete_option($entry->option_name);
                $counter++;
            }
            echo "$counter old options found, converted into new format, and deleted.";
            $this->SaveOptions;
        }
        
    }
}
