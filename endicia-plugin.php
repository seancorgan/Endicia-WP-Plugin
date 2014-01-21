<?php ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
/**
 * Plugin Name: Endicia Shipping Labels
 * Description: A Wordpress Plugin to generate shipping labels on form submission.
 * Version: 1.0
 * Author: Sean Corgan
 * Author URI:  http://www.seancorgan.com
 */
require_once('endicia.class.php');

class Endicia_Plugin {

		// Endicia Info 
		public $RequesterID;
		public $AccountID; 
		public $PassPhrase;

		// Shipping To 
		public $ToName;
		public $ToCompany; 
		public $ToAddress1; 
		public $ToCity; 
		public $ToState; 
		public $ToPostalCode; 
		public $ToZIP4; 
		public $ToPhone;

		// Shipping From 
		public $shipping_option; 
		public $payment_type;  
		public $from_email; 
		public $from_fname; 
		public $from_lname; 
		public $ReturnAddress1; 
		public $FromCity; 
		public $FromState;
		public $FromPostalCode; 
		public $FromZIP4 = "0004";  

	function __construct() {
		register_activation_hook( __FILE__, array( $this, 'sc_rewrite_flush') );
		add_action( 'init', array( $this, 'sc_register_phone_tracking') );

		add_filter('manage_edit-phonetracking_columns', array($this, 'sc_add_pt_column_headers'));

		add_filter('manage_phonetracking_posts_custom_column', array($this, 'sc_add_pt_columns'), 10, 2);

		add_action( 'admin_menu', array( $this, 'sc_plugin_menu') );

		add_shortcode( 'endicia_form', array( $this, 'endicia_shortcode_form') );

		if(is_admin()): 
			$this->check_for_settings(); 
		endif; 

		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));


		add_action( 'wp_ajax_endicia_post_form', array($this, 'endicia_post_form') );
		add_action( 'wp_ajax_nopriv_endicia_post_form', array($this, 'endicia_post_form') );
	}

	/**
	 * Adds Column Header names
	 * @return array column names 
	 */
	function sc_add_pt_column_headers() { 
		$new_columns['id'] = __('ID');
		 $new_columns['title'] = "Name";
		 $new_columns['email'] = "Email";
		 $new_columns['shipping_option'] = "Shipping Option"; 
		 $new_columns['payment_type'] = "Payment Type"; 
		 $new_columns['address1'] = "Address1"; 
		 $new_columns['city'] = "City"; 
		 $new_columns['state'] = "State"; 
		 $new_columns['zip'] = "Zip"; 

		return $new_columns; 
	}

	/**
	 * Adds Custom Column data 
	 * @return [type] [description]
	 */
	function sc_add_pt_columns($column, $post_id) { 
		
		switch ( $column ) {
	        case 'id' :
	           echo $post_id; 
	        break; 

	        case 'email' :
	            echo get_post_meta( $post_id , 'email' , true ); 
	        break;

	        case 'shipping_option' :
	            echo get_post_meta( $post_id , 'shipping_option' , true ); 
	        break;

	        case 'payment_type' :
	            echo get_post_meta( $post_id , 'payment_type' , true ); 
	        break;

	        case 'address1' :
	            echo get_post_meta( $post_id , 'address1' , true ); 
	        break;

	        case 'city' :
	            echo get_post_meta( $post_id , 'city' , true ); 
	        break;

	        case 'state' :
	            echo get_post_meta( $post_id , 'state' , true ); 
	        break;

	        case 'zip' :
	            echo get_post_meta( $post_id , 'zip' , true ); 
	        break;

   		}
	}

	/**
	* Enques javascript and styles 
	*/ 
	function enqueue_scripts($hook) {
		wp_enqueue_script( 'parsley', plugins_url( '/js/parsley.js', __FILE__ ), array('jquery') );

		wp_enqueue_script( 'ajax-script', plugins_url( '/js/scripts.js', __FILE__ ), array('jquery') );

		wp_enqueue_style( 'sc-plugin-styles', plugins_url( '/css/style.css', __FILE__)); 

		wp_localize_script( 'ajax-script', 'ajax_object',
	            array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
	}

	function set_from_email($email) { 
		$foo = filter_var($email, FILTER_VALIDATE_EMAIL);  

		if(filter_var(!$email, FILTER_VALIDATE_EMAIL)) { 
			throw new Exception('Not a valid Email Address');
		} else { 
			$this->from_email = $email; 
		}
	}

	function set_ReturnAddress1($ReturnAddress1) { 
		if(empty($ReturnAddress1)) { 
			throw new Exception('To Address must be set');
		} else { 
			$this->ReturnAddress1 = $ReturnAddress1; 
		}
	}

	function set_from_fname($from_fname) { 
		if(empty($from_fname)) { 
			throw new Exception('First name cannot be empty');
		} else { 
			$this->from_fname = $from_fname; 
		}
	}

	function set_from_lname($from_lname) { 
		if(empty($from_lname)) { 
			throw new Exception('Last name cannot be empty');
		} else { 
			$this->from_lname = $from_lname; 
		}
	}

	function set_FromCity($FromCity) { 
		if(empty($FromCity)) { 
			throw new Exception('From City cannot be empty');
		} else { 
			$this->FromCity = $FromCity; 
		}
	}

	function set_FromState($FromState) { 
		if(empty($FromState)) { 
			throw new Exception('From State cannot be empty');
		} else { 
			$this->FromState = $FromState; 
		}
	}

	function set_FromPostalCode($FromPostalCode) { 
		if(empty($FromPostalCode)) { 
			throw new Exception('From Postal Code cannot be empty');
		} else { 
			$this->FromPostalCode = $FromPostalCode; 
		}
	}

	function set_payment_type($payment_type) { 
		if(empty($payment_type)) { 
			throw new Exception('From Postal Code cannot be empty');
		} else { 
			$this->payment_type = $payment_type; 
		}
	}

	function set_shipping_option($shipping_option) { 
		if(empty($shipping_option)) { 
			throw new Exception('From Postal Code cannot be empty');
		} else { 
			$this->shipping_option = $shipping_option; 
		}
	}

	/**
	 * Ajax for the Phone Form. 
	 * @todo   Add security like nonce 
	 * @return JSON 
	 */
	function endicia_post_form() {  
		try { 
			 $this->set_payment_type($_POST['formData']['payment_type']);
			 $this->set_shipping_option($_POST['formData']['shipping_option']);
		     $this->set_from_fname($_POST['formData']['first_name']);
			 $this->set_from_lname($_POST['formData']['last_name']);
		 	 $this->set_from_email($_POST['formData']['email']); 
			 $this->set_ReturnAddress1($_POST['formData']['address1']);
			 $this->set_FromCity($_POST['formData']['city']); 
			 $this->set_FromState($_POST['formData']['state']);
			 $this->set_FromPostalCode($_POST['formData']['zip']);

			 $post_id = $this->add_phone_tracking_post(); 

		} catch (Exception $e) {
			return json_encode(array('status' => 'error', 'message' => $e->getMessage())); 
		}

		if($this->shipping_option == 'print') { 
			echo $this->process_label($post_id); 
		}
	}

	function add_phone_tracking_post() { 
			$phone_tracking_post = array(
			  'post_title'    => $this->from_fname.' '.$this->from_lname,
			  'post_status'   => 'publish',
			  'post_author'   => 1, 
			  'post_visibility'   => 'private', 
			  'post_type' => 'Phone Tracking'
			);

			$post_id = wp_insert_post( $phone_tracking_post );

			if(!empty($post_id)) { 
				add_post_meta( $post_id, 'shipping_option', $this->shipping_option );
				add_post_meta( $post_id, 'payment_type', $this->payment_type );
				add_post_meta( $post_id, 'email', $this->from_email );
				add_post_meta( $post_id, 'address1', $this->ReturnAddress1 );
				add_post_meta( $post_id, 'city', $this->FromCity );
				add_post_meta( $post_id, 'state', $this->FromState );
				add_post_meta( $post_id, 'zip', $this->FromPostalCode );
			} else { 
				throw new Exception("Problem adding post", 1);
			}

			return $post_id; 
	}

	/**
	 * Creates shipping label from endicia
	 * @todo   We need to process the label, save the image and tracking number to the database 
	 * @param  array $data Form array of data
	 * @return JSON       Json Response 
	 */
	function process_label($data) { 
		
	}

	/**
	* Register the the custom post type to store our Phone Tracking data
	*/
	function sc_register_phone_tracking() {
	    register_post_type( 'Phone Tracking', array(
	        'public' => false,
	        'publicly_queryable' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'query_var' => true,
	        'rewrite' => array( 'slug' => 'phone-tracking' ),
	        'has_archive' => true,
	        'hierarchical' => false,
	        'menu_position' => null,
	        'supports' => array( 'title', 'editor'),
	        'capability_type' => 'post',
	        'capabilities' => array(),
	        'labels' => array(
	            'name' => __( 'Phone Tracking', 'Phone Tracking' ),
	            'singular_name' => __( 'Phone Tracking', 'Phone Tracking' ),
	            'add_new' => __( 'Add New Phone Tracking', 'Phone Tracking' ),
	            'add_new_item' => __( 'Add New Phone Tracking', 'Phone Tracking' ),
	            'edit_item' => __( 'Edit Phone Tracking', 'Phone Tracking' ),
	            'new_item' => __( 'New Phone Tracking', 'Phone Tracking' ),
	            'all_items' => __( 'All Phone Tracking', 'Phone Tracking' ),
	            'view_item' => __( 'View Phone Tracking', 'Phone Tracking' ),
	            'search_items' => __( 'Search Phone Tracking', 'Phone Tracking' ),
	            'not_found' =>  __( 'No Phone Tracking found', 'Phone Tracking' ),
	            'not_found_in_trash' => __( 'No Phone Tracking found in Trash', 'Phone Tracking' ),
	            'parent_item_colon' => '',
	            'menu_name' => 'Phone Tracking'
	        )
	    ) );
	}
	/**
	 * Places Endicia form on page
	 * @param  array $atts     array of attributes passed in wordpress
	 * @param  [type] $content content in shortcode
	 * @return string returns html form
	 */
	function endicia_shortcode_form ($atts, $content = null) { 
		
		$html = '
			<form class="endicia_form" id="endicia-form" parsley-validate>

				<div class="endicia-left-block">
				<h1>Your Personal Information</h1>
				<input type="email" class="endicia-email" required parsley-trigger="change" name="email" placeholder="Email Address">

				<input type="text" class="endicia-fname" required name="first_name" placeholder="First Name">

				<input type="text" class="endicia-lname" required name="last_name" placeholder="Last Name">

				<input type="text" class="endicia-address1" required  name="address1" placeholder="Address Line 1">

				<input type="text" class="endicia-address2" name="address2" placeholder="Address Line 2" >

				<input type="text" name="city" class="endicia-city" required placeholder="City">

				<select name="state" class="endicia-state" required> 
					<option value="" selected="selected">Select a State</option> 
					<option value="AL">Alabama</option> 
					<option value="AK">Alaska</option> 
					<option value="AZ">Arizona</option> 
					<option value="AR">Arkansas</option> 
					<option value="CA">California</option> 
					<option value="CO">Colorado</option> 
					<option value="CT">Connecticut</option> 
					<option value="DE">Delaware</option> 
					<option value="DC">District Of Columbia</option> 
					<option value="FL">Florida</option> 
					<option value="GA">Georgia</option> 
					<option value="HI">Hawaii</option> 
					<option value="ID">Idaho</option> 
					<option value="IL">Illinois</option> 
					<option value="IN">Indiana</option> 
					<option value="IA">Iowa</option> 
					<option value="KS">Kansas</option> 
					<option value="KY">Kentucky</option> 
					<option value="LA">Louisiana</option> 
					<option value="ME">Maine</option> 
					<option value="MD">Maryland</option> 
					<option value="MA">Massachusetts</option> 
					<option value="MI">Michigan</option> 
					<option value="MN">Minnesota</option> 
					<option value="MS">Mississippi</option> 
					<option value="MO">Missouri</option> 
					<option value="MT">Montana</option> 
					<option value="NE">Nebraska</option> 
					<option value="NV">Nevada</option> 
					<option value="NH">New Hampshire</option> 
					<option value="NJ">New Jersey</option> 
					<option value="NM">New Mexico</option> 
					<option value="NY">New York</option> 
					<option value="NC">North Carolina</option> 
					<option value="ND">North Dakota</option> 
					<option value="OH">Ohio</option> 
					<option value="OK">Oklahoma</option> 
					<option value="OR">Oregon</option> 
					<option value="PA">Pennsylvania</option> 
					<option value="RI">Rhode Island</option> 
					<option value="SC">South Carolina</option> 
					<option value="SD">South Dakota</option> 
					<option value="TN">Tennessee</option> 
					<option value="TX">Texas</option> 
					<option value="UT">Utah</option> 
					<option value="VT">Vermont</option> 
					<option value="VA">Virginia</option> 
					<option value="WA">Washington</option> 
					<option value="WV">West Virginia</option> 
					<option value="WI">Wisconsin</option> 
					<option value="WY">Wyoming</option>
				</select>
				
				<input type="text" name="zip" placeholder="Zip" required class="endicia-zip">

			</div> 

			<div class="endicia-right-block">

				<h1>How do you Want to get Paid</h1>
				
				<div class="endicia-left"> 
					<img src="'.plugins_url( '/img/paypal-icon.png', __FILE__).'"/>
					<input checked="checked" type="radio"  name="payment_type" value="paypal" class="endicia-payment-type"><label class="endicia-paypal-radio">Paypal</label>
				</div> 
				<div class="endicia-left"> 
					<img src="'.plugins_url( '/img/check-icon.png', __FILE__).'"/>
					<input type="radio" name="payment_type" value="check" class="endicia-payment-type"><label class="endicia-check-radio">Check</label>
				</div>

				<div class="endicia-clear"></div>

				<h1>Select a Shipping Option</h1>
				<input checked="checked" type="radio" name="shipping_option" value="box" class="endicia-shipping-box"><label>Send a Prepaid Label & Box</label>
				<span class="endicia-radio-details">the package is a padded envelope, safe, secure and credible.</span>

				<input type="radio" name="shipping_option" value="print" class="endicia-shipping-box"><label>Print my own label & use my own box.</label>
				
				<input type="submit" value="SELL NOW">
			</div>

			</form>'; 
		return $html; 

	}

	/**
	* Should Help Handle permalinks on plugin activation
	*/
	function sc_rewrite_flush() {
    	$this->sc_register_phone_tracking();
    	flush_rewrite_rules();
	}

	/**
	*  Adds Settings page, and fields
	*/
	function sc_plugin_menu() {
		 add_options_page( 'Endicia Settings', 'Endicia Settings', 'manage_options', 'sc-endicia', array($this, 'endicia_plugin_options') );
		 $this->register_endicia_settings(); 
	}

	/**
	*  Settings Page
	*/
	function endicia_plugin_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		?> 
		<div class="wrap">
		<?php screen_icon(); ?> 
		
		<h2>Endicia Settings</h2>
		<form method="post" action="options.php"> 
			<?php settings_fields( 'sc-endicia-settings' ); ?>
			<?php do_settings_sections( 'sc-endicia-settings' ); ?>
			 <table class="form-table">
		        <tr valign="top">
		        <th scope="row">Requester ID</th>
		        <td><input type="text" name="RequesterID" value="<?php echo get_option('RequesterID'); ?>" /></td>
		        </tr>
		         
		        <tr valign="top">
		        <th scope="row">Account ID</th>
		        <td><input type="text" name="AccountID" value="<?php echo get_option('AccountID'); ?>" /></td>
		        </tr>
		        
		        <tr valign="top">
		        <th scope="row">Pass Phrase</th>
		        <td><input type="text" name="PassPhrase" value="<?php echo get_option('PassPhrase'); ?>" /></td>
		        </tr>

				<tr valign="top">
		        <th scope="row">To Name</th>
		        <td><input type="text" name="ToName" value="<?php echo get_option('ToName'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">To Company</th>
		        <td><input type="text" name="ToCompany" value="<?php echo get_option('ToCompany'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">To Address</th>
		        <td><input type="text" name="ToAddress1" value="<?php echo get_option('ToAddress1'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">To City</th>
		        <td><input type="text" name="ToCity" value="<?php echo get_option('ToCity'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">To State</th>
		        <td><input type="text" name="ToState" value="<?php echo get_option('ToState'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">To PostalCode</th>
		        <td><input type="text" name="ToPostalCode" value="<?php echo get_option('ToPostalCode'); ?>" /></td>
		        </tr>

		        <tr valign="top">
		        <th scope="row">To Phone</th>
		        <td><input type="text" name="ToPhone" value="<?php echo get_option('ToPhone'); ?>" /></td>
		        </tr>

		    </table>
			<?php submit_button(); ?>

		</form>

		<?php 
	}

	/**
	*  Registers Plugin Settings
	*/
	function register_endicia_settings() { // whitelist options
	  register_setting( 'sc-endicia-settings', 'RequesterID' );
	  register_setting( 'sc-endicia-settings', 'AccountID' );
	  register_setting( 'sc-endicia-settings', 'PassPhrase' );
	  register_setting( 'sc-endicia-settings', 'ToName' );
	  register_setting( 'sc-endicia-settings', 'ToCompany' );
	  register_setting( 'sc-endicia-settings', 'ToAddress1' );
	  register_setting( 'sc-endicia-settings', 'ToCity' );
	  register_setting( 'sc-endicia-settings', 'ToState' );
	  register_setting( 'sc-endicia-settings', 'ToPostalCode' );
	  register_setting( 'sc-endicia-settings', 'ToZIP4' );
	  register_setting( 'sc-endicia-settings', 'ToPhone' );
	}

	/**
	*  Checks to make sure settings are set, displays error if not, run parent constructor if
	*  they are and sets properties
	* 
	*/
	function check_for_settings() { 
		$RequesterID = get_option('RequesterID');
		$AccountID = get_option('AccountID');
		$PassPhrase = get_option('PassPhrase');
		$ToName = get_option('ToName');
		$ToCompany = get_option('ToCompany');
		$ToAddress1 = get_option('ToAddress1');
		$ToCity = get_option('ToCity');
		$ToState = get_option('ToState');
		$ToPostalCode = get_option('ToPostalCode');
		$ToZIP4 = get_option('ToZIP4');
		$ToPhone = get_option('ToPhone'); 

		if(empty($RequesterID) || 
		   empty($AccountID) || 
		   empty($PassPhrase) ||
		   empty($ToName) ||
		   empty($ToCompany) ||
		   empty($ToAddress1) ||
		   empty($ToCity) ||
		   empty($ToState) ||
		   empty($ToPostalCode) || 
		   empty($ToPhone) 
		   	) { 
			add_action( 'admin_notices', array($this, 'settings_missing_message') );
		} else {  
			$this->RequesterID = $RequesterID;
			$this->AccountID = $AccountID;
			$this->PassPhrase = $PassPhrase;
			$this->ToName = $ToName;
			$this->ToCompany = $ToCompany;
			$this->ToAddress1 = $ToAddress1;
			$this->ToCity = $ToCity;
			$this->ToState = $ToState;
			$this->ToPostalCode = $ToPostalCode;
			$this->ToZIP4 = $ToZIP4;
			$this->ToPhone = $ToPhone;

			$endicia_client = new Endicia($this->RequesterID, $this->AccountID, $this->PassPhrase);  
		}
	}

	/**
	*  Displays Error Message
	*/	
	function settings_missing_message() {
	    ?>
	    <div class="error">
	        <p><?php _e( 'Please make sure you have all settings filled out for Endicia!', 'sc-endicia' ); ?></p>
	    </div>
	    <?php
	}
}

$e = new Endicia_Plugin(); 