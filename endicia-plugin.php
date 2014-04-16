
<?php
/**
 * Plugin Name: Endicia Shipping Labels
 * Description: A Wordpress Plugin to generate shipping labels on form submission.
 * Version: 0.9.1
 * Author: Sean Corgan
 * Author URI:  http://www.seancorgan.com
 */
require_once('endicia.class.php');

class Endicia_Plugin {

	// Endicia Info 
	public $RequesterID;
	public $AccountID; 
	public $PassPhrase;


	public $error_message; 

	// Shipping To 
	public $ToName;
	public $ToCompany;
	public $ToAddress1;
	public $ToCity;
	public $ToState;
	public $ToPostalCode;
	public $ToZIP4 = "0004";
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

	public $phone; 
	public $carrier;
	public $quote;  

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

		add_action( 'save_post', array($this, 'save_phonetracking' ) );

		add_action( 'init', array($this, 'phone_tracking_taxonomy') );

		add_action( 'restrict_manage_posts', array($this, 'restrict_manage_posts' ) );		
	}

/**
	 * Adds Column Header names
	 * @return array column names 
	 */
	function sc_add_pt_column_headers() { 
		 $new_columns['cb'] = '';
		 $new_columns['id'] = __('ID');
		 $new_columns['title'] = "Name";
		 $new_columns['date'] = "Date Created";
		 $new_columns['email'] = "Email or PayPal ID";
		 $new_columns['shipping_option'] = "Shipping Option"; 
		 $new_columns['payment_type'] = "Payment Type"; 
		 $new_columns['address1'] = "Address1"; 
		 $new_columns['city'] = "City"; 
		 $new_columns['state'] = "State"; 
		 $new_columns['zip'] = "Zip"; 
		 $new_columns['label'] = "Shipping Label";
		 $new_columns['tracking_number'] = "Tracking Number";
		 $new_columns['phone'] = "Phone";
		 $new_columns['carrier'] = "Carrier";
		 $new_columns['quote'] = "Quote";
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

	        case 'date' :
	            echo get_post_meta( $post_id , 'post_date' , true ); 
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

	        case 'tracking_number' :
	            echo get_post_meta( $post_id , 'tracking_number' , true ); 
	        break;

	        case 'phone' :
	            echo get_post_meta( $post_id , 'phone' , true ); 
	        break;


	        case 'carrier' :
	            echo get_post_meta( $post_id , 'carrier' , true ); 
	        break;

	        case 'quote' :
	         		$quote = get_post_meta( $post_id , 'quote' , true );
	         		if(!empty($quote)) { 
	         			echo '$'.$quote; 
	         		} 
	        break;


	        case 'label' :
	             $id = get_post_meta( $post_id , 'label' , true ); 
	             $label = wp_get_attachment_image_src($id, '', true );

	           	 $pos = strpos($label[0],'default.png'); 

	             if($pos === false) { 
		             $icon = plugins_url( '/Endicia-WP-Plugin/img/shipping-icon.png'); 
		             echo '<a target="_blank" href="'.$label[0].'"><img src="'.$icon.'"/></a>';
	             } 
	             
	        break;

   		}
	}

	/**
	* Enques javascript and styles 
	*/ 
	function enqueue_scripts($hook) {
		wp_enqueue_script( 'parsley', plugins_url( '/js/parsley.js', __FILE__ ), array('jquery') );

		wp_enqueue_script( 'placeholder', plugins_url( '/js/placeholder.js', __FILE__ ), array('jquery') );

		wp_enqueue_script( 'ajax-script', plugins_url( '/js/scripts.js', __FILE__ ), array('jquery') );

		wp_enqueue_style( 'sc-plugin-styles', plugins_url( '/css/style.css', __FILE__)); 

		wp_localize_script( 'ajax-script', 'ajax_object',
	            array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
	}
	/**
	 * sets emails
	 * @param string $email email address 
	 */
	function set_from_email($email) { 
		$foo = filter_var($email, FILTER_VALIDATE_EMAIL);  

		if(filter_var(!$email, FILTER_VALIDATE_EMAIL)) { 
			throw new Exception('Not a valid Email Address');
		} else { 
			$this->from_email = $email; 
		}
	}
	/**
	 * Sets return address
	 * @param string $ReturnAddress1 the address to return the package to
	 */
	function set_ReturnAddress1($ReturnAddress1) { 
		if(empty($ReturnAddress1)) { 
			throw new Exception('Endicia To Address must be set');
		} else { 
			$this->ReturnAddress1 = $ReturnAddress1; 
		}
	}
	/**
	 * The sender of the package
	 * @param string $from_fname 
	 */
	function set_from_fname($from_fname) { 
		if(empty($from_fname)) { 
			throw new Exception('Endicia First name cannot be empty');
		} else { 
			$this->from_fname = $from_fname; 
		}
	}
	/**
	 * Sets the from the lname
	 * @param string $from_lname the last name of the sender
	 */
	function set_from_lname($from_lname) { 
		if(empty($from_lname)) { 
			throw new Exception('Endicia Last name cannot be empty');
		} else { 
			$this->from_lname = $from_lname; 
		}
	}
	/**
	 * Sets the from city property 
	 * @param string $FromCity The from city of the sender
	 */
	function set_FromCity($FromCity) { 
		if(empty($FromCity)) { 
			throw new Exception('Endicia From City cannot be empty');
		} else { 
			$this->FromCity = $FromCity; 
		}
	}

	/**
	 * Sets the from state of the sender
	 * @param string $FromState 
	 */
	function set_FromState($FromState) { 
		if(empty($FromState)) { 
			throw new Exception('Endicia From State cannot be empty');
		} else { 
			$this->FromState = $FromState; 
		}
	}

	/**
	 * The p
	 * @param [type] $FromPostalCode [description]
	 */
	function set_FromPostalCode($FromPostalCode) { 
		if(empty($FromPostalCode)) { 
			throw new Exception('Endicia From Postal Code cannot be empty');
		} else { 
			$this->FromPostalCode = $FromPostalCode; 
		}
	}

	function set_payment_type($payment_type) { 
		if(empty($payment_type)) { 
			throw new Exception('Endicia Payment type cannot be empty');
		} else { 
			$this->payment_type = $payment_type; 
		}
	}

	function set_shipping_option($shipping_option) { 
		if(empty($shipping_option)) { 
			throw new Exception('Endicia Shipping Option cannot be empty');
		} else { 
			$this->shipping_option = $shipping_option; 
		}
	}

	function set_RequesterID($RequesterID) { 
		if(empty($RequesterID)) { 
			throw new Exception('Endicia RequesterID Not Set');
		} else { 
			$this->RequesterID = $RequesterID; 
		}
	}

	function set_AccountID($AccountID) { 
		if(empty($AccountID)) { 
			throw new Exception('Endicia AccountID is not Set');
		} else { 
			$this->AccountID = $AccountID; 
		}
	}

	function set_PassPhrase($PassPhrase) { 
		if(empty($PassPhrase)) { 
			throw new Exception('Endicia PassPhrase Not Set');
		} else { 
			$this->PassPhrase = $PassPhrase; 
		}
	}

	function set_ToName($ToName) { 
		if(empty($ToName)) { 
			throw new Exception('Endicia Shipping TO Name Not Set');
		} else { 
			$this->ToName = $ToName; 
		}
	}

	function set_ToCompany($ToCompany) { 
		if(empty($ToCompany)) { 
			throw new Exception('Endicia Company Name not Set');
		} else { 
			$this->ToCompany = $ToCompany; 
		}
	}

	function set_ToAddress1($ToAddress1) { 
		if(empty($ToAddress1)) { 
			throw new Exception('Endicia To Address1 not Set');
		} else { 
			$this->ToAddress1 = $ToAddress1; 
		}
	}

	function set_ToCity($ToCity) { 
		if(empty($ToCity)) { 
			throw new Exception('Endicia To City not Set');
		} else { 
			$this->ToCity = $ToCity; 
		}
	}

	function set_ToState($ToState) { 
		if(empty($ToState)) { 
			throw new Exception('Endicia To State not Set');
		} else { 
			$this->ToState = $ToState; 
		}
	}

	function set_ToPostalCode($ToPostalCode) { 
		if(empty($ToPostalCode)) { 
			throw new Exception('Endicia To Zip not Set');
		} else { 
			$this->ToPostalCode = $ToPostalCode; 
		}
	}

	function set_ToPhone($ToPhone) { 
		if(empty($ToPhone)) { 
			throw new Exception('Endicia To Phone not Set');
		} else { 
			$this->ToPhone = $ToPhone; 
		}
	}

	function set_phone($phone) { 
		if(empty($phone)) { 
			throw new Exception('Phone not set');
		} else { 
			$this->phone = $phone; 
		}
	}

	function set_carrier($carrier) { 
		if(empty($carrier)) { 
			throw new Exception('Carrier Not set');
		} else { 
			$this->carrier = $carrier; 
		}
	}


	function set_quote($quote) { 
		if(empty($quote)) { 
			throw new Exception('Quote value Not set');
		} else { 
			$this->quote = $quote; 
		}
	}


	/**
	 * Ajax for the Phone Form. 
	 * @todo   Add security like nonce 
	 * @return JSON with status and message
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
			$this->set_phone($_POST['formData']['phone']);
			$this->set_carrier($_POST['formData']['carrier']);
			$this->set_quote($_POST['formData']['quote']);

 			$post_id = $this->add_phone_tracking_post(); 

			if($_POST['formData']['shipping_option'] == "print") { 
			 	$this->process_label($post_id); 
			}

		} catch (Exception $e) {
			echo json_encode(array('status' => 'error', 'message' => $e->getMessage())); 
		}
		
		$redirect_link = get_permalink(138).'?id='.$post_id;

		if(!empty($_POST['formData']['email'])) { 
			$this->email_on_success($_POST['formData']['email'], $post_id);
		}

		echo json_encode(array('status' => 'success', 'redirect' => $redirect_link));
		die();  
	}

	
	/**
	 * Send email to client on completing Phone Order
	 * @param  string $email    email of the client
	 * @param  int $order_id id of the order
	 */
	function email_on_success($email, $order_id) { 
		$to = $email; 
		$subject ="Thanks for you're order";

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'To: '.strip_tags($email) . "\r\n";
		$headers .= 'From: CBB <cbb@cbb.com>' . "\r\n";

		$message = '<html><body>';
		$message .= 'Thanks, you\'re order #'.$order_id.' it is processing'; 
		$message .= "</body></html>"; 

		mail($to, $subject, $message, $headers);
	}

	function restrict_manage_posts() {
	global $typenow;
    
	$taxonomy = $typenow;
  
	
	if( $_GET['post_type'] == "phonetracking" ){
		$filters = get_terms('status');  

			echo "<select name='status' id='status' class='postform'>";
			echo "<option value=''>Show All Status'</option>";
			foreach ($filters as $term) { 
				echo '<option value='. $term->slug, $_GET[$tax_slug] == $term->slug ? ' selected="selected"' : '','>' . $term->name .' (' . $term->count .')</option>'; }
			}
			echo "</select>";
	}

	/**
	 * Setups up post data info 
	 */
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
				add_post_meta( $post_id, 'phone', $this->phone );
				add_post_meta( $post_id, 'carrier', $this->carrier );
				add_post_meta( $post_id, 'quote', $this->quote );
				//update_field('field_530126d9a8999', 'Device Not Received', $post_id);
				wp_set_post_terms( $post_id, 17, 'status'); 
		

			} else { 
				throw new Exception("Problem adding post", 1);
			}

			return $post_id; 
	}

	/**
	 * Creates shipping label from endicia and stores it into wordpress 
	 * @param   int The Post ID in Wordpress 
	 */
	function process_label($post_id) { 
		
		$e = new Endicia($this->RequesterID, $this->AccountID, $this->PassPhrase);
		    // See Endicia Documentation for correct values.  Array Keys must match XML node name 
		    $data = array(
		        'MailClass' => 'Priority', 
		        'WeightOz' => 5,
		        'MailpieceShape' => 'Parcel', 
		        'Description' => 'Electronics', 
		        'FromName' => $this->from_fname.' '.$this->from_lname,  
		        'ReturnAddress1' => $this->ReturnAddress1, 
		        'FromCity' => $this->FromCity, 
		        'FromState' => $this->FromState, 
		        'FromPostalCode' => $this->FromPostalCode, 
		        'FromZIP4' => $this->FromZIP4, 
		        'ToName' => $this->ToName,
		        'ToCompany' => $this->ToCompany,
		        'ToAddress1' => $this->ToAddress1,
		        'ToCity' => $this->ToCity,
		        'ToState' => $this->ToState,
		        'ToPostalCode' => $this->ToPostalCode,
		        'ToZIP4' => '0004', 
		        'ToDeliveryPoint' => '00',
		        'ToPhone' => $this->ToPhone 
		    ); 

		    $res = $e->request_shipping_label($data); 

		    // Not a success from Endicia
		    if($res['Status'] != 0) { 
		    	throw new Exception("Problem getting shipping Label", 1);
		    }

		    add_post_meta( $post_id, 'tracking_number', $res['TrackingNumber']);


		    $upload_dir = wp_upload_dir();
		    $data = base64_decode($res['Base64LabelImage']);

		    $dir = $upload_dir['path'] .'/labels/'; 

			if (!file_exists($dir)) {
				  mkdir($dir, 0777, true);
			}

			$file =  $dir. uniqid() . '.png';

			if(!file_put_contents($file, $data)) { 
				throw new Exception("Could not Upload Label", 1);
			} 

		  $wp_filetype = wp_check_filetype(basename($file), null );
		  $attachment = array(
		     'guid' => $upload_dir['url'] . '/' . basename( $file ), 
		     'post_mime_type' => $wp_filetype['type'],
		     'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $file ) ),
		     'post_content' => '',
		     'post_status' => 'inherit'
		  );
		  $attach_id = wp_insert_attachment( $attachment, $file, $post_id );

		  add_post_meta( $post_id, 'label', $attach_id);

		  // you must first include the image.php file
		  // for the function wp_generate_attachment_metadata() to work
		  require_once( ABSPATH . 'wp-admin/includes/image.php' );
		  $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		  wp_update_attachment_metadata( $attach_id, $attach_data );

		  $label = wp_get_attachment_url( $attach_id);
		  session_start(); 
		  $_SESSION['label'] = $label; 
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
	            'name' => __( 'Orders', 'Phone Tracking' ),
	            'singular_name' => __( 'Orders', 'Phone Tracking' ),
	            'add_new' => __( 'Add New Orders', 'Phone Tracking' ),
	            'add_new_item' => __( 'Add New Orders', 'Phone Tracking' ),
	            'edit_item' => __( 'Edit Orders', 'Phone Tracking' ),
	            'new_item' => __( 'New Orders', 'Phone Tracking' ),
	            'all_items' => __( 'All Orders', 'Phone Tracking' ),
	            'view_item' => __( 'View Orders', 'Phone Tracking' ),
	            'search_items' => __( 'Search Orders', 'Phone Tracking' ),
	            'not_found' =>  __( 'No Orders found', 'Phone Tracking' ),
	            'not_found_in_trash' => __( 'No Orders found in Trash', 'Phone Tracking' ),
	            'parent_item_colon' => '',
	            'menu_name' => 'Orders'
	        )
	    ) );
	}


	function phone_tracking_taxonomy() { 
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Status', 'Status' ),
			'singular_name'     => _x( 'Status', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Status' ),
			'all_items'         => __( 'All Status' ),
			'parent_item'       => __( 'Parent Status' ),
			'parent_item_colon' => __( 'Parent Status:' ),
			'edit_item'         => __( 'Edit Status' ),
			'update_item'       => __( 'Update Status' ),
			'add_new_item'      => __( 'Add New Status' ),
			'new_item_name'     => __( 'New Status Name' ),
			'menu_name'         => __( 'Status' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'status' ),
		);

		register_taxonomy( 'status', array( 'phonetracking' ), $args );

	}
	/**
	 * Places Endicia form on page
	 * @param  array $atts     array of attributes passed in wordpress
	 * @param  [type] $content content in shortcode
	 * @return string returns html form
	 */
	function endicia_shortcode_form ($atts, $content = null) { 
		$url = site_url(); 
		if(isset($_GET['id'])) {
			$phone_title = get_the_title($_GET['id']); 
		} 

		$html = '
			<form class="endicia_form" id="endicia-form" parsley-validate novalidate>
				<input type="hidden" name="carrier" value="'.$_GET['carrier'].'" > 
				<input type="hidden" name="phone" value="'.$phone_title.'"> 
				<input type="hidden" name="quote" value="'.$_GET['quote'].'">  
				<div class="endicia-left-block">
				<h1>Your Personal Information</h1>
				<input type="email" class="endicia-email" parsley-required="true" name="email" placeholder="Email Address">

				<input class="endicia-fname" data-validation-minlength="2" name="first_name" placeholder="First Name" parsley-required="true" />

				<input type="text" class="endicia-lname" name="last_name" placeholder="Last Name" parsley-required="true" />

				<input type="text" class="endicia-address1"  name="address1" placeholder="Address Line 1" parsley-required="true" />

				<input type="text" class="endicia-address2" name="address2" placeholder="Address Line 2" >

				<input type="text" name="city" class="endicia-city" placeholder="City" required>

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
				<img class="endicia-loading" src="'.$url.'/wp-includes/js/tinymce/themes/advanced/skins/default/img/progress.gif" style="display:none"/>
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
	* Sets TO properties for shipping 
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
		$ToPhone = get_option('ToPhone'); 

		try {
			$this->set_RequesterID($RequesterID);
			$this->set_AccountID($AccountID);
			$this->set_PassPhrase($PassPhrase);
			$this->set_ToName($ToName);
			$this->set_ToCompany($ToCompany);
			$this->set_ToAddress1($ToAddress1);
			$this->set_ToCity($ToCity);
			$this->set_ToState($ToState);
			$this->set_ToPostalCode($ToPostalCode);
			$this->set_ToPhone($ToPhone);

		} catch (Exception $e) {
			$this->error_message = $e->getMessage(); 
		 	add_action( 'admin_notices', array($this, 'settings_missing_message'));
		}
	}

	/** 
	*  Displays Error Messages
	*/	
	function settings_missing_message() {
	    ?>
	    <div class="error">
	        <p><?php _e($this->error_message); ?></p>
	    </div>
	    <?php
	}

	/**
	 * Checks to see if we have a status update on a phonetracking order.
	 */
	function save_phonetracking() { 
		if ( 'phonetracking' != $_POST['post_type'] ) {
	        return;
	    } 

	    if(!empty($_POST['fields']['field_530126d9a8999'])) { 
	    	$current_field = get_field('field_530126d9a8999', $_POST['post_ID']);
	    	if($current_field != $_POST['fields']['field_530126d9a8999']) { 
	    		 $this->send_status_update($_POST['fields']['field_533894e5e04f2'], $_POST['post_ID'], $_POST['fields']['field_530126d9a8999'], $_POST['fields']['field_53476d2d72037']); 
	    	}
	    }  
	}

	function send_status_update($email, $order_id, $status, $rejected_message){ 
		$to = $email; 
		$subject ="There was a status update on you're order# ".$order_id;

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$headers .= 'To: '.strip_tags($email) . "\r\n";
		$headers .= 'From: CBB <cbb@cbb.com>' . "\r\n";

		$message = '<html><body>';
		
		if($status != "Device Rejected - Awaiting Customer Offer Approval") { 
			$message .= 'Thanks, you\'re order is now '.$status; 
		} else { 
			$message .= "Sorry you're device was rejected";
			$message .= "Rejected Instructions:"; 
			$message .= $rejected_message;  
		}

		$message .= "</body></html>";

		mail($to, $subject, $message, $headers);
	}
}

$e = new Endicia_Plugin(); 
