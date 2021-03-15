<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines how Abandoned cart Table should be displayed
 *
 * @package    MoceanAPI Abandoned Carts
 * @subpackage MoceanAPI Abandoned Carts/admin
 * @author     Micro Ocean Technologies
 */
 
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if (!class_exists('MoceanSMS_carts')) {
    require_once plugin_dir_path( __DIR__ ) . 'lib/MoceanSMS.php';
}
if (!class_exists('Moceanapi_Carts_Logger')) {
    require_once plugin_dir_path( __DIR__ ) . 'includes/class-moceanapi-abandoned-carts-carts-logger.php';
}

class MoceanAPI_Table extends WP_List_Table{

    protected $log;
   /**
    * Constructor, we override the parent to pass our own arguments
    * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    *
    *  
    */
    function __construct(Moceanapi_Carts_Logger $log = null){
        global $status, $page;

        parent::__construct(array(
            'singular' => 'id',
            'plural' => 'ids',
        ));
        //lOG
        if ( $log === null ) {
			$log = new Moceanapi_Carts_Logger();
		}
        $this->log = $log;
    }
	
	/**
     * This method return columns to display in table
     *
     *  
     * @return   array
     */
	function get_columns(){
        return $columns = array(
            'cb'                => 		'<input type="checkbox" />',
            'id'                =>		__('ID', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'name'              =>		__('Name, Surname', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'email'             =>		__('Email', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'phone'			    =>		__('Phone', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'location'          =>      __('Location', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'cart_contents'     =>		__('Cart contents', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'cart_total'        =>		__('Cart total', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'time'              =>		__('Time', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'bulk_email_sent'   =>      __('Email Sent', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'bulk_sms_sent'     =>      __('SMS Sent', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
        );
	}
	
	/**
     * This method return columns that may be used to sort table
     * all strings in array - is column names
     * True on name column means that its default sort
     *
     *  
     * @return   array
     */
	public function get_sortable_columns(){
		return $sortable = array(
			'id'                =>      array('id', true),
            'name'              =>      array('name', true),
            'email'             =>      array('email', true),
            'phone'             =>      array('phone', true),
            'cart_total'        =>      array('cart_total', true),
            'time'              =>      array('time', true),
            'bulk_email_sent'   =>      array('bulk_email_sent', true),
            'bulk_sms_sent'   =>      array('bulk_sms_sent', true)
		);
	}
	
	/**
     * This is a default column renderer
     *
     *  
     * @return   HTML
     * @param    $item - row (key, value array)
     * @param    $column_name - string (key)
     */
    function column_default( $item, $column_name ){
        return $item[$column_name];
    }
	
	/**
     * Rendering Name column
     *
     *  
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_name( $item ){
        $cart_status = 'all';
        if (isset($_GET['cart-status'])){
            $cart_status = sanitize_text_field($_GET['cart-status']);
        }

        $actions = array(
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s&cart-status='. esc_html($cart_status) .'">%s</a>', esc_html($_REQUEST['page']), esc_html($item['id']), __('Delete', 'MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN')),
        );

        $name_array = array();

        if(!empty($item['name'])){
            $name_array[] = $item['name'];
        }

        if(!empty($item['surname'])){
            $name_array[] = $item['surname'];
        }

        $name = implode(' ', $name_array);

        if(get_user_by('id', $item['session_id'])){ //If the user is registered, add link to his profile page
            $name = '<a href="' . add_query_arg( 'user_id', $item['session_id'], self_admin_url( 'user-edit.php')) . '" title="' . __( 'View user profile', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ) . '">'. $name .'</a>';
        }

        return sprintf('<svg class="moceanapi-abandoned-carts-customer-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 450 506"><path d="M225,0A123,123,0,1,0,348,123,123.14,123.14,0,0,0,225,0Z"/><path d="M393,352.2C356,314.67,307,294,255,294H195c-52,0-101,20.67-138,58.2A196.75,196.75,0,0,0,0,491a15,15,0,0,0,15,15H435a15,15,0,0,0,15-15A196.75,196.75,0,0,0,393,352.2Z"/></svg>%s %s',
            $name,
            $this->row_actions($actions)
        );
    }
	
	/**
     * Rendering Email column
     *
     *  
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_email( $item ){
        return sprintf('<a href="mailto:%1$s" title="">%1$s</a>',
            esc_html($item['email'])
        );
    }

    /**
     * Rendering Location column
     *
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_location( $item ){
        if(is_serialized($item['location'])){ 
            $location_data = @unserialize($item['location']);
            $country = $location_data['country'];
            $city = $location_data['city'];
            $postcode = $location_data['postcode'];

        }else{ 
            $parts = explode(',', $item['location']); //Splits the Location field into parts where there are commas
            if (count($parts) > 1) {
                $country = $parts[0];
                $city = trim($parts[1]); //Trim removes white space before and after the string
            }
            else{
                $country = $parts[0];
                $city = '';
            }

            $postcode = '';
            if(is_serialized($item['other_fields'])){
                $other_fields = @unserialize($item['other_fields']);
                if(isset($other_fields['moceanapi_abandoned_carts_billing_postcode'])){
                    $postcode = $other_fields['moceanapi_abandoned_carts_billing_postcode'];
                }
            }
        }

        if($country && class_exists('WooCommerce')){ //In case WooCommerce is active and we have Country data, we can add abbreviation to it with a full country name
            $country = '<abbr class="moceanapi-abandoned-carts-country" title="' . WC()->countries->countries[ $country ] . '">' . esc_html($country) . '</abbr>';
        }

        $location = $country;
        if(!empty($city)){
             $location .= ', ' . $city;
        }
        if(!empty($postcode)){
             $location .= ', ' . $postcode;
        }

        return sprintf('%s',
            $location
        );
    }
	
	/**
     * Rendering Cart Contents column
     *
     *  
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_cart_contents( $item ){
        if(!is_serialized($item['cart_contents'])){
            return;
        }

        $product_array = @unserialize($item['cart_contents']); //Retrieving array from database column cart_contents
        $output = '';
        
        if($product_array){
            if(get_option('moceanapi_abandoned_carts_hide_images')){ //Outputing Cart contents as a list
                $output = '<ul class="wlcfc-product-list">';
                foreach($product_array as $product){
                    if(is_array($product)){
                        if(isset($product['product_title'])){
                            $product_title = esc_html($product['product_title']);
                            $quantity = " (". $product['quantity'] .")"; //Enclose product quantity in brackets
                            $edit_product_link = get_edit_post_link( $product['product_id'], '&' ); //Get product link by product ID
                            $price = '';
                            if($product['product_variation_price']){
                                $price = ', ' . $product['product_variation_price'] . ' ' . esc_html($item['currency']);
                            }
                            if($edit_product_link){ //If link exists (meaning the product hasn't been deleted)
                                $output .= '<li><a href="'. $edit_product_link .'" title="'. $product_title .'" target="_blank">'. $product_title . $price . $quantity .'</a></li>';
                            }else{
                                $output .= '<li>'. $product_title . $price . $quantity .'</li>';
                            }
                        }
                    }
                }
                $output .= '</ul>';

            }else{ //Displaying cart contents with thumbnails
                foreach($product_array as $product){
                    if(is_array($product)){
                        if(isset($product['product_title'])){
                            //Checking product image
                            if(!empty($product['product_variation_id'])){ //In case of a variable product
                                $image = get_the_post_thumbnail_url($product['product_variation_id'], 'thumbnail');
                                if(empty($image)){ //If variation didn't have an image set
                                    $image = get_the_post_thumbnail_url($product['product_id'], 'thumbnail');
                                }
                            }else{ //In case of a simple product
                                 $image = get_the_post_thumbnail_url($product['product_id'], 'thumbnail');
                            }

                            if(empty($image) && class_exists('WooCommerce')){ //In case WooCommerce is active and product has no image, output default WooCommerce image
                                $image = wc_placeholder_img_src('thumbnail');
                            }

                            $product_title = esc_html($product['product_title']);
                            $quantity = " (". $product['quantity'] .")"; //Enclose product quantity in brackets
                            $edit_product_link = get_edit_post_link( $product['product_id'], '&' ); //Get product link by product ID
                            $price = '';
                            if($product['product_variation_price']){
                                $price = ', ' . $product['product_variation_price'] . ' ' . esc_html($item['currency']);
                            }
                            if($edit_product_link){ //If link exists (meaning the product hasn't been deleted)
                                $output .= '<div class="moceanapi-abandoned-carts-abandoned-product"><span class="tooltiptext">'. $product_title . $price . $quantity .'</span><a href="'. $edit_product_link .'" title="'. $product_title .'" target="_blank"><img src="'. $image .'" title="'. $product_title .'" alt ="'. $product_title .'" /></a></div>';
                            }else{
                                $output .= '<div class="moceanapi-abandoned-carts-abandoned-product"><span class="tooltiptext">'. $product_title . $price . $quantity .'</span><img src="'. $image .'" title="'. $product_title .'" alt ="'. $product_title .'" /></div>';
                            }
                        }
                    }
                }
            }
        }
        return sprintf('%s', $output );
    }
	
	/**
     * Rendering Cart Total column
     *
     *  
     * @return   HTML
     * @param    $item - row (key, value array)
     */
    function column_cart_total( $item ){
        return sprintf('%0.2f %s',
            esc_html($item['cart_total']),
            esc_html($item['currency'])
        );
    }
	
	/**
     * Render Time column 
     *
     *  
     * @return   HTML
     * @param    $item - row (key, value array)
     */
	function column_time( $item ){
		$time = new DateTime($item['time']);
		$date_iso = $time->format('c');
        $date_title = $time->format('M d, Y H:i:s');
        $utc_time = $time->format('U');

        if($utc_time > strtotime( '-1 day', current_time( 'timestamp' ))){ //In case the abandoned cart is newly captued
             $friendly_time = sprintf( 
                /* translators: %1$s - Time, e.g. 1 minute, 5 hours */
                __( '%1$s ago', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ),
                human_time_diff( $utc_time,
                current_time( 'timestamp' ))
            );
        }else{ //In case the abandoned cart is older tahn 24 hours
            $friendly_time = $time->format('M d, Y');
        }

		return sprintf( '<svg class="moceanapi-abandoned-carts-time-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 31.18 31.18"><path d="M15.59,31.18A15.59,15.59,0,1,1,31.18,15.59,15.6,15.6,0,0,1,15.59,31.18Zm0-27.34A11.75,11.75,0,1,0,27.34,15.59,11.76,11.76,0,0,0,15.59,3.84Z"/><path d="M20.39,20.06c-1.16-.55-6-3-6.36-3.19s-.46-.76-.46-1.18V7.79a1.75,1.75,0,1,1,3.5,0v6.88s4,2.06,4.8,2.52a1.6,1.6,0,0,1,.69,2.16A1.63,1.63,0,0,1,20.39,20.06Z"/></svg><time datetime="%s" title="%s">%s</time>', esc_html($date_iso), esc_html($date_title), esc_html($friendly_time));
	}

	/**
     * Rendering checkbox column
     *
     *  
     * @return   HTML
     * @param    $item - row (key, value array)
     */
	function column_cb( $item ){
		return sprintf(
			'<input type="checkbox" name="id[]" value="%s" />',
			esc_html($item['id'])
		);
	}
	
	/**
     * Return array of bulk actions if we have any
     *
     *  
     * @return   array
     */
	 function get_bulk_actions(){
        $actions = array(
            'delete' => __('Delete', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'send_sms' => __('Send SMS', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
            'send_email' => __('Send Email', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
        );
        return $actions;
    }

    /**
     * This method processes bulk actions
     *
     *  
     */
    function process_bulk_action(){
        global $wpdb;
        $cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
        session_start();
        if(!isset($_SESSION["count_sent_sms"]))
        {
            $_SESSION["count_sent_sms"] = 0;
        }
        if(!isset($_SESSION["count_sent_email"]))
        {
            $_SESSION["count_sent_email"] = 0;
        }

        if ('delete' === $this->current_action()) {

            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();

            if (!empty($ids)){
                if(!is_array($ids)){ //Bulk abandoned cart deletion
                    $ids[] = $ids;
                }
                foreach ($ids as $key => $id){
                    $selected_id = sanitize_text_field($id); 
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE FROM $cart_table
                            WHERE id = %d",
                            esc_html(intval($selected_id))
                        )
                    );
                }
            }
        }
        elseif('send_sms' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();

            if (!empty($ids)){ //If any selection
                if(!is_array($ids)){ //Bulk abandoned cart send sms
                    $ids[] = $ids;
                }
                foreach ($ids as $key => $id){
                    $selected_id = sanitize_text_field($id);
                    $sent_bulk_sms_successful = $this->send_bulk_sms($selected_id);
                    if($sent_bulk_sms_successful){
                        $_SESSION["count_sent_sms"] = $_SESSION["count_sent_sms"] + 1;
                    }
                }
            }
        }
        elseif('send_email' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (!empty($ids)){ //If any selection
                if(!is_array($ids)){ //Bulk abandoned cart send email
                    $ids[] = $ids; 
                }
                foreach ($ids as $key => $id){
                    $selected_id = sanitize_text_field($id);
                    $sent_bulk_email_successfult = $this->send_bulk_email($selected_id);
                    if($sent_bulk_email_successfult){
                        $_SESSION["count_sent_email"] = $_SESSION["count_sent_email"] + 1;
                    }
                }
            }
        }

    }

	/**
     * Method that renders the table
     */
	function prepare_items(){
        global $wpdb;
        $cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;

        $cart_status = 'all';
        if (isset($_GET['cart-status'])){
            $cart_status = sanitize_text_field($_GET['cart-status']);
        }

        $screen = get_current_screen();
        $user = get_current_user_id();
        $option = $screen->get_option('per_page', 'option');
        $per_page = get_user_meta($user, $option, true);

        //How much records will be shown per page, if the user has not saved any custom values under Screen options, then default amount of 10 rows will be shown
        if ( empty ( $per_page ) || $per_page < 1 ) {
            $per_page = $screen->get_option( 'per_page', 'default' );
        }

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable); // here we configure table headers, defined in our methods
        $this->process_bulk_action(); // process bulk action if any
        $admin = new MoceanAPI_Admin(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
        $total_items = $admin->get_cart_count(esc_html($cart_status));

        // prepare query params, as usual current page, order by and order direction
        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'time';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        // configure pagination
        $this->set_pagination_args(array(
            'total_items' => $total_items, // total items defined above
            'per_page' => $per_page, // per page constant defined at top of method
            'total_pages' => ceil($total_items / $per_page) // calculate pages count
        ));

        $where_sentence = $admin->get_where_sentence(esc_html($cart_status));
        $this->items = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $cart_table
            WHERE cart_contents != ''
            $where_sentence
            ORDER BY $orderby $order
            LIMIT %d OFFSET %d",
        $per_page, $paged * $per_page), ARRAY_A);
    }

    /**
     * Method that send email to customer
     */
    function send_bulk_email($customer_id){
        global $wpdb;
        $admin = new MoceanAPI_Admin(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
        $cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
        $template = get_option('moceanapi_abandoned_carts_bulk_email_content');	
        $blog_name = get_option( 'blogname' );
        if(empty($template)){
            $template = "Dear customer, We noticed that you left something in your cart at " . $blog_name . ". Please don't forget to order your favorite.";
        }

        //Retrieve email from database rows that haven't took action = in recoverable
        $customer_email = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT email
				FROM $cart_table
				WHERE id = %d AND
                cart_contents != '' ",
                intval($customer_id)
			)
        );

        if (!empty($customer_email)){ //If we have email in the database
			
			$sender = 'WordPress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
			$from = "From: WordPress <" . apply_filters( 'moceanapi_abandoned_carts_from_email', $sender ) . ">";
			$blog_name = get_option( 'blogname' );
			$subject = '['.$blog_name.'] '. "Oops! Your Cart is Waiting! ";
			$headers 	= "$from\n" . "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";

			//Sending out e-mail
            $bulk_email_sent = wp_mail( esc_html($customer_email), esc_html($subject), $template, $headers );

            //Update bulk_email_sent times
            if($bulk_email_sent){
                $wpdb->query(
                    $wpdb->prepare(
                        "UPDATE $cart_table
                        SET bulk_email_sent = bulk_email_sent + 1
                        WHERE id = %d AND
                        cart_contents != '' ",
                        intval($customer_id)
                    )
                );
                return $bulk_email_sent;
            }
		}  
    }

    /**
     * Method that send sms to customer
     */
    function send_bulk_sms($customer_id){
        global $wpdb;
        $admin = new MoceanAPI_Admin(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
        $cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
        $authorize = $admin->smsAbandonedCartGetCredit();

        $api_key    = get_option('moceanapi_abandoned_carts_key');
		$api_secret = get_option('moceanapi_abandoned_carts_secret');
		$sms_from 	= get_option('moceanapi_abandoned_carts_from');
        $sms_msg	= get_option('moceanapi_abandoned_carts_bulk_content');
        $blog_name = get_option( 'blogname' );
		if(empty($sms_msg)){
			$sms_msg = "Dear customer, We noticed that you left something in your cart at " . $blog_name . ". Please don't forget to order your favorite.";
		}
        
        //Retrieve from database rows that haven't took action = not in 'action' 
		$customer_phone = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT phone
				FROM $cart_table
				WHERE id = %d AND
                cart_contents != '' ",
                intval($customer_id)		
			)
        );

        $location = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT location
				FROM $cart_table
				WHERE id = %d AND
                cart_contents != '' ",
                intval($customer_id)		
			)
        );
        $location_data = unserialize($location);
        $country = $location_data['country'];
        if($authorize){
            if (!empty($customer_phone)){
                //Check country code
                $cus_phone_no = $admin->check_and_get_phone_number( $customer_phone, $country );
                if ( $cus_phone_no !== false ) {
                    $this->log->add( 'MoceanAPI_Abandon_Carts', 'Customer\'s billing phone number (' . $customer_phone . ') in country (' . $country . ') converted to ' . $cus_phone_no );
                }else {
                    $cus_phone_no = $customer_phone;
                }

                //send sms
                $moceansms_rest = new MoceanSMS_carts( $api_key, $api_secret );
                $rest_response  = $moceansms_rest->sendSMS( $sms_from, $cus_phone_no, $sms_msg );

                if($rest_response === false) {
                    throw new Exception('curl error: ' . curl_error($rest_request));
                    $this->log->add( 'MoceanAPI_Abandon_Carts', 'Failed sent SMS: ' . $e->getMessage() );

                }else{
                    //Update bulk_sms_sent times
                    $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $cart_table
                            SET bulk_sms_sent = bulk_sms_sent + 1
                            WHERE id = %d AND
                            cart_contents != '' ",
                            intval($customer_id)
                        )
                    ); 
                    $this->log->add( 'MoceanAPI_Abandon_Carts', 'SMS response from SMS gateway: ' . $rest_response );
                    return $rest_response;                
                }                   
            }else{
                $this->log->add( 'MoceanAPI_Abandon_Carts', 'Failed sent SMS: Missing customer phone!');
            }
        }else{
            $this->log->add( 'MoceanAPI_Abandon_Carts', 'Authorization failed!');
        }   
        
    }
}
?>