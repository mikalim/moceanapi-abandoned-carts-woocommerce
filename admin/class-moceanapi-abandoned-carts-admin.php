<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    MoceanAPI Abandoned Carts
 * @subpackage MoceanAPI Abandoned Carts/admin
 * @author     Micro Ocean Technologies
 */

if (!class_exists('MoceanSMS_carts')) {
    require_once plugin_dir_path( __DIR__ ) . 'lib/MoceanSMS.php';
}
if (!class_exists('Moceanapi_Carts_Logger')) {
    require_once plugin_dir_path( __DIR__ ) . 'includes/class-moceanapi-abandoned-carts-carts-logger.php';
}

class MoceanAPI_Admin{

	//Log
	protected $log;
	private $_handles;
	private $log_directory;
	/**
	 * The ID of this plugin.
	 *
	 *  
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 *  
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 *  
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    	      The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, Moceanapi_Carts_Logger $log = null){
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		//log
		$upload_dir          = wp_upload_dir();
		$this->log_directory = $upload_dir['basedir'] . '/moceanapi-abandoned-carts-woocommerce-logs/';
		wp_mkdir_p( $this->log_directory );

		if ( $log === null ) {
			$log = new Moceanapi_Carts_Logger();
		}
		$this->log = $log;
	}
	/**
	 * Register the stylesheets for the admin area.
	 *
	 *  
	 */
	public function enqueue_styles(){
		global $moceanapi_abandoned_carts_admin_menu_page;
		$screen = get_current_screen();
		
		//Do not continue if we are not on MoceanAPI plugin page
		if( !is_object($screen) ){
			return;
		}

		if($screen->id == $moceanapi_abandoned_carts_admin_menu_page || $screen->id == 'plugins'){
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/moceanapi-abandoned-carts-admin.css', array('wp-color-picker'), $this->version, 'all' );
		}
	}

	/**
	 * Register the javascripts for the admin area.
	 *
  	 */
	public function enqueue_scripts(){
		global $moceanapi_abandoned_carts_admin_menu_page;
		$screen = get_current_screen();
		
		//Do not continue if we are not on MoceanAPI plugin page
		if(!is_object($screen) || $screen->id != $moceanapi_abandoned_carts_admin_menu_page){
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/moceanapi-abandoned-carts-admin.js', array( 'wp-color-picker', 'jquery' ), $this->version, false );
	}
	
	/**
	 * Register the menu under WooCommerce admin menu.
	 *
	 *  
	 */
	function moceanapi_abandoned_carts_menu(){
		global $moceanapi_abandoned_carts_admin_menu_page;
		if(class_exists('WooCommerce')){
			$moceanapi_abandoned_carts_admin_menu_page = add_submenu_page( 'woocommerce', MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME, __('MoceanAPI Abandoned carts', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN), 'list_users', MOCEANAPI_ABANDONED_CARTS, array($this,'display_page'));
		}else{
			$moceanapi_abandoned_carts_admin_menu_page = add_menu_page( MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME, __('MoceanAPI Abandoned carts', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN), 'list_users', MOCEANAPI_ABANDONED_CARTS, array($this,'display_page'), 'dashicons-archive' );
		}
	}

	/**
	 * Adds newly abandoned cart count to the menu
	 *
	 */
	function menu_abandoned_count(){
		global $wpdb, $submenu;
		$cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
		
		if ( isset( $submenu['woocommerce'] ) ) { //If WooCommerce Menu exists
			$time = $this->get_time_intervals();
			// Retrieve from database rows that have not been e-mailed and are older than 60 minutes
			$order_count = $wpdb->get_var( //Counting newly abandoned carts
				$wpdb->prepare(
					"SELECT COUNT(id)
					FROM $cart_table
					WHERE
					cart_contents != '' AND
					time < %s AND 
					time > %s ",
					$time['cart_abandoned'],
					$time['old_cart']
				)
			);
			
			foreach ( $submenu['woocommerce'] as $key => $menu_item ) { //Go through all Sumenu sections of WooCommerce and look for MoceanAPI Abandoned carts
				if ( 0 === strpos( $menu_item[0], __('MoceanAPI Abandoned carts', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN))) {
					$submenu['woocommerce'][$key][0] .= ' <span class="new-abandoned update-plugins count-' . $order_count . '">' .  $order_count .'</span>';
				}
			}
		}
	}

	/**
	 * Adds Screen options tab
	 *
	 */
	function register_admin_screen_options_tab(){
		global $moceanapi_abandoned_carts_admin_menu_page;
		$screen = get_current_screen();

		// Check if we are on MoceanAPI page
		if(!is_object($screen) || $screen->id != $moceanapi_abandoned_carts_admin_menu_page){
			return;

		}else{
			//Outputing how many rows we would like to see on the page
			$option = 'per_page';
			$args = array(
				'label' => __('Carts per page: ', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
				'default' => 10,
				'option' => 'moceanapi_abandoned_carts_carts_per_page'
			);
			add_screen_option( $option, $args );
		}
	}

	/**
	 * Saves settings displayed under Screen options 
	 */
	function save_page_options(){
		if ( isset( $_POST['wp_screen_options'] ) && is_array( $_POST['wp_screen_options'] ) ) {
			check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

			global $moceanapi_abandoned_carts_admin_menu_page;
			$screen = get_current_screen();

			//Do not continue if we are not on MoceanAPI Pro plugin page
			if(!is_object($screen) || $screen->id != $moceanapi_abandoned_carts_admin_menu_page){
				return;
			}

			$user = wp_get_current_user();
	        if ( ! $user ) {
	            return;
	        }

	        $option = $_POST['wp_screen_options']['option'];
	        $value  = $_POST['wp_screen_options']['value'];
	 
	        if ( sanitize_key( $option ) != $option ) {
	            return;
	        }

	        update_user_meta( $user->ID, $option, $value );
	    }
	}
	
	/**
	 * Display the abandoned carts and settings under admin page
	 *
	 */
	function display_page(){
		global $pagenow;
		
		if ( !current_user_can( 'list_users' )){
			wp_die( __( 'You do not have sufficient permissions to access this page.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ) );
		}
		
		//Prepare Table of elements
		require_once plugin_dir_path( __FILE__ ) . 'class-moceanapi-abandoned-carts-admin-table.php';
		$table = new MoceanAPI_Table();
		$table->prepare_items();
		
		//Output table contents
		$message = '';
		session_start();
		if ('delete' === $table->current_action()) {
			if(is_array($_REQUEST['id'])){ //If deleting multiple lines from table
				$deleted_row_count = sanitize_text_field(count($_REQUEST['id']));
			}
			else{ //If a single row is deleted
				$deleted_row_count = 1;
			}
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf(
				/* translators: %d - Item count */
				__('Items deleted: %d', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), esc_html($deleted_row_count)
			) . '</p></div>';
		}elseif('send_email' === $table->current_action()){
			if(is_array($_REQUEST['id'])){ //If deleting multiple lines from table
				$email_sent_row_count = sanitize_text_field(count($_REQUEST['id']));
			}
			else{ //If a single row is deleted
				$email_sent_row_count = 1;
			}
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf(
				/* translators: %d - Item count */
				__('Selected for send email: %d <br> Email sent: %d', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), esc_html($email_sent_row_count), $_SESSION["count_sent_email"]
			) . '</p></div>';
		}elseif('send_sms' === $table->current_action()){
			if(is_array($_REQUEST['id'])){ //If deleting multiple lines from table
				$sms_sent_row_count = sanitize_text_field(count($_REQUEST['id']));
			}
			else{ //If a single row is deleted
				$sms_sent_row_count = 1;
			}
			$message = '<div class="updated below-h2" id="message"><p>' . sprintf(
				/* translators: %d - Item count */
				__('Selected for send sms: %d <br> SMS sent: %d', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), esc_html($sms_sent_row_count), $_SESSION["count_sent_sms"]
			) . '</p></div>';
			// if(!isset($_SESSION["count_sent_sms"])){
			// 	echo "no session";
			// }else{
			// 	echo " have session";
			// }
			
		}
		unset($_SESSION["count_sent_sms"]);
		unset($_SESSION["count_sent_email"]);


		$cart_status = 'all';
        if (isset($_GET['cart-status'])){
            $cart_status = $_GET['cart-status'];
        }?>

		<div id="moceanapi-abandoned-carts-page-wrapper" class="wrap<?php if(get_option('moceanapi_abandoned_carts_hide_images')) {echo " moceanapi-abandoned-carts-without-thumbnails";}?>">
			<h1><?php echo MOCEANAPI_ABANDONED_CARTS_ABREVIATION; ?></h1>

			<?php if ( isset ( $_GET['tab'] ) ){
				$this->create_admin_tabs($_GET['tab']);
			}else{
				$this->create_admin_tabs('carts');
			}

			if ( $pagenow == 'admin.php' && $_GET['page'] == MOCEANAPI_ABANDONED_CARTS ){
				if (isset($_GET['tab'])){
					$tab = $_GET['tab'];
				}else{
					$tab = 'carts';
				}

				if($tab == 'settings'): //Settings tab output ?>
				<form method="post" action="options.php">
						<?php
							settings_fields( 'moceanapi-abandoned-carts-settings' );
							do_settings_sections( 'moceanapi-abandoned-carts-settings' );
							$lift_email_on = esc_attr( get_option('moceanapi_abandoned_carts_lift_email') );
							$hide_images_on = esc_attr( get_option('moceanapi_abandoned_carts_hide_images') );
							$exclude_ghost_carts = esc_attr( get_option('moceanapi_abandoned_carts_exclude_ghost_carts') );
							$default_email_content = get_option('moceanapi_abandoned_carts_email_content');
							if(empty($default_email_content)){
								$default_email_content = "Dear admin, you having new recoverable abandoned cart. Please take action as soon as possible.";
							}
							$blog_name = get_option( 'blogname' );
							$default_bulk_email_content = get_option('moceanapi_abandoned_carts_bulk_email_content');
							if(empty($default_bulk_email_content)){
								$default_bulk_email_content = "Dear customer, We noticed that you left something in your cart at " . $blog_name . ". Please don't forget to order your favorite.";
							}
							$d_email = get_option( 'admin_email' );
							$default_email = get_option('moceanapi_abandoned_carts_notification_email');
							if(empty($default_email)){
								$default_email = $d_email;
							}
						?>
						<table id="moceanapi-abandoned-carts-settings-table" class="form-table">
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_notification_email"><?php echo __('Send notifications about abandoned carts to this email:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi_abandoned_carts_notification_email" type="email" name="moceanapi_abandoned_carts_notification_email" value="<?php echo $default_email; ?>" <?php echo $this->disable_field(); ?> />
									<p><small><?php echo sprintf(
										/* translators: %s - Email address */
										__('By default, notifications will be sent to WordPress admin email - %s.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN), get_option( 'admin_email' )); ?>
									</small></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_email_content"><?php echo __('Email Content:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<textarea name="moceanapi_abandoned_carts_email_content" cols="80" rows="5" ><?php echo $default_email_content; ?> </textarea>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_bulk_email_content"><?php echo __('Bulk Email Content:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<textarea name="moceanapi_abandoned_carts_bulk_email_content" cols="80" rows="5" ><?php echo $default_bulk_email_content; ?> </textarea>
									<p><small><?php echo sprintf(
										__('This is the bulk email template send to remind customers for abandoned carts. ')); ?>
									</small></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_notification_frequency[hours]"><?php echo __('How often to check and send emails about newly abandoned carts?', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<!-- Using selected() instead -->
									<?php $options = get_option( 'moceanapi_abandoned_carts_notification_frequency' );
										if(!$options){
											$options = array('hours' => 60);
										}
									?>
									 <select id="moceanapi_abandoned_carts_notification_frequency[hours]" name='moceanapi_abandoned_carts_notification_frequency[hours]' <?php echo $this->disable_field(); ?>>
										<option value='10' <?php selected( $options['hours'], 10 ); ?>><?php echo 		__('Every 10 minutes', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='20' <?php selected( $options['hours'], 20 ); ?>><?php echo 		__('Every 20 minutes', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='30' <?php selected( $options['hours'], 30 ); ?>><?php echo 		__('Every 30 minutes', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='60' <?php selected( $options['hours'], 60 ); ?>><?php echo 		__('Every hour', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='120' <?php selected( $options['hours'], 120 ); ?>><?php echo 	__('Every 2 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='180' <?php selected( $options['hours'], 180 ); ?>><?php echo 	__('Every 3 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='240' <?php selected( $options['hours'], 240 ); ?>><?php echo 	__('Every 4 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='300' <?php selected( $options['hours'], 300 ); ?>><?php echo 	__('Every 5 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='360' <?php selected( $options['hours'], 360 ); ?>><?php echo 	__('Every 6 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='720' <?php selected( $options['hours'], 720 ); ?>><?php echo 	__('Twice a day', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='1440' <?php selected( $options['hours'], 1440 ); ?>><?php echo 	__('Once a day', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='2880' <?php selected( $options['hours'], 2880 ); ?>><?php echo 	__('Once every 2 days', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='0' <?php selected( $options['hours'], 0 ); ?>><?php echo 		__('Disable notifications', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi-abandoned-carts-lift-email"><?php echo __('Lift email field:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi-abandoned-carts-lift-email" class="moceanapi-abandoned-carts-checkbox" type="checkbox" name="moceanapi_abandoned_carts_lift_email" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $lift_email_on, false ); ?> />
									<p><small>
										<?php if($lift_email_on){
											echo __('Please test the checkout after enabling this, as sometimes it can cause <br/>issues or not raise the field if you have a custom checkout.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN);
										}else{
											echo __('You could increase the chances of capturing abandoned carts by <br/>moving the email field to the top of your checkout form.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN);
										}?>
										</small>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi-abandoned-carts-hide-images"><?php echo __('Display abandoned cart contents in a list:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi-abandoned-carts-hide-images" class="moceanapi-abandoned-carts-checkbox" type="checkbox" name="moceanapi_abandoned_carts_hide_images" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $hide_images_on, false ); ?> />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi-abandoned-carts-exclude-ghost-carts"><?php echo __('Exclude ghost carts:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi-abandoned-carts-exclude-ghost-carts" class="moceanapi-abandoned-carts-checkbox" type="checkbox" name="moceanapi_abandoned_carts_exclude_ghost_carts" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exclude_ghost_carts, false ); ?> />
								</td>
							</tr>
						</table>
						
						<?php
						if(current_user_can( 'manage_options' )){
							submit_button(__('Save settings', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN));
						}?>
					</form>
				
				<?php elseif($tab == 'smssettings'):?>
					<p class="moceanapi_abandoned_carts_balance">
						<?php 
							echo __('MoceanAPI Account Credit Balance :  ' . $this->smsAbandonedCartGetCredit()); 
						?>
					</p>
					<form method="post" action="options.php">

					<?php
					settings_fields( 'moceanapi-abandoned-carts-smssettings' );
					do_settings_sections( 'moceanapi-abandoned-carts-smssettings' );
					$default_sms_content = get_option('moceanapi_abandoned_carts_content');
					if(empty($default_sms_content)){
						$default_sms_content = "Dear admin, you having new recoverable abandoned cart. Please take action as soon as possible.";
					}
					$default_bulk_sms_content = get_option('moceanapi_abandoned_carts_bulk_content');
					$blog_name = get_option( 'blogname' );
					if(empty($default_bulk_sms_content)){
						$default_bulk_sms_content = "Dear customer, We noticed that you left something in your cart at " . $blog_name . ". Please don't forget to order your favorite.";
					}
					$current_user_id = get_current_user_id();
					$phone = get_user_meta($current_user_id,'billing_phone',true);
					?>

					<table id="moceanapi-abandoned-carts-smssettings-table" class="form-table">
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_key"><?php echo __('MoceanAPI Key:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi_abandoned_carts_key" type="text" name="moceanapi_abandoned_carts_key" value="<?php echo esc_attr( get_option('moceanapi_abandoned_carts_key') ); ?>" <?php echo $this->disable_field(); ?> />
									<p><small><?php echo sprintf(
										__('Your MoceanSMS API Key', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)); ?>
									</small></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_secret"><?php echo __('MoceanAPI Secret:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi_abandoned_carts_secret" type="password" name="moceanapi_abandoned_carts_secret" value="<?php echo esc_attr( get_option('moceanapi_abandoned_carts_secret') ); ?>" <?php echo $this->disable_field(); ?> />
									<p><small><?php echo sprintf(
										__('Your MoceanSMS API Secret', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)); ?>
									</small></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_from"><?php echo __('Message From', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi_abandoned_carts_from" type="text" name="moceanapi_abandoned_carts_from" value="<?php echo esc_attr( get_option('moceanapi_abandoned_carts_from') ); ?>" <?php echo $this->disable_field(); ?> />
									<p><small><?php echo sprintf(
										__('Sender of the SMS when the message is received at a mobile phone', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)); ?>
									</small></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_notification_sms"><?php echo __('Phone number:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi_abandoned_carts_notification_sms" type="text" name="moceanapi_abandoned_carts_notification_sms" placeholder="<?php echo $phone ?>" value="<?php echo esc_attr( get_option('moceanapi_abandoned_carts_notification_sms') ); ?>" <?php echo $this->disable_field(); ?> />
									<p><small><?php echo sprintf(
										/* translators: %s - admin phone number */
										__('By default, notifications will be sent to WordPress admin phone number - %s <br>Please provide country code when enter phonr numbers. E.g. 60123456789', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN), $phone) ?>
									</small></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_content"><?php echo __('SMS Content', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<textarea name="moceanapi_abandoned_carts_content" cols="80" rows="5" ><?php echo $default_sms_content  ?> </textarea>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_bulk_content"><?php echo __('Bulk SMS Content', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<textarea name="moceanapi_abandoned_carts_bulk_content" cols="80" rows="5" ><?php echo $default_bulk_sms_content  ?> </textarea>
									<p><small><?php echo sprintf(
										__('This is the bulk sms template send to remind customers for abandoned carts. ')); ?>
									</small></p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_sms_notification_frequency[hours]"><?php echo __('How often to check and send sms about newly abandoned carts?', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<!-- Using selected() instead -->
									<?php $options = get_option( 'moceanapi_abandoned_carts_sms_notification_frequency' );
										if(!$options){
											$options = array('hours' => 60);
										}
									?>
									 <select id="moceanapi_abandoned_carts_sms_notification_frequency[hours]" name='moceanapi_abandoned_carts_sms_notification_frequency[hours]' <?php echo $this->disable_field(); ?>>
										<option value='10' <?php selected( $options['hours'], 10 ); ?>><?php echo 		__('Every 10 minutes', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='20' <?php selected( $options['hours'], 20 ); ?>><?php echo 		__('Every 20 minutes', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='30' <?php selected( $options['hours'], 30 ); ?>><?php echo 		__('Every 30 minutes', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='60' <?php selected( $options['hours'], 60 ); ?>><?php echo 		__('Every hour', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='120' <?php selected( $options['hours'], 120 ); ?>><?php echo 	__('Every 2 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='180' <?php selected( $options['hours'], 180 ); ?>><?php echo 	__('Every 3 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='240' <?php selected( $options['hours'], 240 ); ?>><?php echo 	__('Every 4 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='300' <?php selected( $options['hours'], 300 ); ?>><?php echo 	__('Every 5 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='360' <?php selected( $options['hours'], 360 ); ?>><?php echo 	__('Every 6 hours', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='720' <?php selected( $options['hours'], 720 ); ?>><?php echo 	__('Twice a day', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='1440' <?php selected( $options['hours'], 1440 ); ?>><?php echo 	__('Once a day', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='2880' <?php selected( $options['hours'], 2880 ); ?>><?php echo 	__('Once every 2 days', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
										<option value='0' <?php selected( $options['hours'], 0 ); ?>><?php echo 		__('Disable notifications', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi_abandoned_carts_download_log"><?php echo __('Export Log:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
								<a href="admin.php?page=moceanapi_abandoned_carts&tab=download_log&file=MoceanAPI_Abandon_Carts" class="button button-secondary" target="_blank">Export</a>	
													
								</td>
							</tr>
					</table>
					<?php
						if(current_user_can( 'manage_options' )){
							submit_button(__('Save settings', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN));
						}?>
					</form>

				<?php elseif($tab == 'exit_intent'): //Exit intent output ?>
					<p class="moceanapi-abandoned-carts-description"><?php echo __('With the help of Exit Intent you can capture even more abandoned carts by displaying a message including an e-mail field that the customer can fill to save his shopping cart. You can even offer to send a discount code.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></p>
					<p class="moceanapi-abandoned-carts-description"><?php echo __('Please note that the Exit Intent will only be showed to unregistered users once per hour after they have added an item to their cart and try to leave your shop.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></p>
					<form method="post" action="options.php">
						<?php
							settings_fields( 'moceanapi-abandoned-carts-settings-exit-intent' );
							do_settings_sections( 'moceanapi-abandoned-carts-settings-exit-intent' );
							$exit_intent_on = esc_attr( get_option('moceanapi_abandoned_carts_exit_intent_status'));
							$test_mode_on = esc_attr( get_option('moceanapi_abandoned_carts_exit_intent_test_mode'));
							$exit_intent_type = esc_attr( get_option('moceanapi_abandoned_carts_exit_intent_type'));
							$main_color = esc_attr( get_option('moceanapi_abandoned_carts_exit_intent_main_color'));
							$inverse_color = esc_attr( get_option('moceanapi_abandoned_carts_exit_intent_inverse_color'));
							$main_image = esc_attr( get_option('moceanapi_abandoned_carts_exit_intent_image'));
						?>
						
						<table id="moceanapi-abandoned-carts-exit-intent-table" class="form-table">
							<tr>
								<th scope="row">
									<label for="moceanapi-abandoned-carts-exit-intent-status"><?php echo __('Enable Exit Intent:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi-abandoned-carts-exit-intent-status" class="moceanapi-abandoned-carts-checkbox" type="checkbox" name="moceanapi_abandoned_carts_exit_intent_status" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $exit_intent_on, false ); ?> />
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="moceanapi-abandoned-carts-exit-intent-test-mode"><?php echo __('Enable test mode:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
								</th>
								<td>
									<input id="moceanapi-abandoned-carts-exit-intent-test-mode" class="moceanapi-abandoned-carts-checkbox" type="checkbox" name="moceanapi_abandoned_carts_exit_intent_test_mode" value="1" <?php echo $this->disable_field(); ?> <?php echo checked( 1, $test_mode_on, false ); ?> />
									<p><small>
										<?php if($test_mode_on){
										echo __('Now go to your store and add a product to your shopping cart. Please note that only <br/>users with Admin rights will be able to see the Exit Intent and appearance limits <br/>have been removed - it will be shown each time you try to leave your shop.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN);
										}?>
										</small>
									</p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<?php echo __('Exit Intent colors:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?>
								</th>
								<td>
									<div class="moceanapi-abandoned-carts-exit-intent-colors">
										<label for="moceanapi-abandoned-carts-exit-intent-main-color"><?php echo __('Main:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
										<input id="moceanapi-abandoned-carts-exit-intent-main-color" type="text" name="moceanapi_abandoned_carts_exit_intent_main_color" class="moceanapi-abandoned-carts-exit-intent-color-picker" value="<?php echo $main_color; ?>" <?php echo $this->disable_field(); ?> />
									</div>
									<div class="moceanapi-abandoned-carts-exit-intent-colors">
										<label for="moceanapi-abandoned-carts-exit-intent-inverse-color"><?php echo __('Inverse:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?></label>
										<input id="moceanapi-abandoned-carts-exit-intent-inverse-color" type="text" name="moceanapi_abandoned_carts_exit_intent_inverse_color" class="moceanapi-abandoned-carts-exit-intent-color-picker" value="<?php echo $inverse_color; ?>" <?php echo $this->disable_field(); ?> />
									</div>
									<p class="clear"><small>
										<?php echo __('If you leave the Inverse color empty, it will automatically use the inverse color of <br/>the main color you have picked. Clear both colors to use the default colors.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN);
										?>
										</small>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<?php echo __('Exit Intent image:', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?>
								</th>
								<td>
									<?php
									if(!did_action('wp_enqueue_media')){
										wp_enqueue_media();
									}
									$image = wp_get_attachment_image_src( $main_image ); ?>
									<div id="moceanapi-abandoned-carts-exit-intent-image-container">
										<p href="#" id="moceanapi-abandoned-carts-upload-image">
											<?php if($image):?>
												<img src="<?php echo $image[0]; ?>" />
											<?php else: ?>
												<input type="button" value="<?php echo __('Add custom image', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?>" class="button" <?php echo $this->disable_field(); ?>/>
											<?php endif; ?>
										</p>
										<a href="#" id="moceanapi-abandoned-carts-remove-image" <?php if(!$image){echo 'style="display:none"';}?>></a>
									</div>
									<?php if(!$image):?>
										<p class="clear">
											<small>
												<?php echo __('Recommended size: 1024 x 600 px.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?>
											</small>
										</p>
									<?php endif;?>
									<input id="moceanapi_abandoned_carts_exit_intent_image" type="hidden" name="moceanapi_abandoned_carts_exit_intent_image" value="<?php if($main_image){echo $main_image;}?>" <?php echo $this->disable_field(); ?>>
								</td>
							</tr>
						</table>
						<?php
						if(current_user_can( 'manage_options' )){
							submit_button(__('Save settings', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN));
						}?>
					</form>

				
				<?php elseif($tab == 'download_log'): //Exit intent output 
						if ( isset( $_GET['file'] ) ) {
							$logFile = $this->log_directory . sanitize_text_field($_GET['file']) . '.log';
							if ( file_exists( $logFile ) ) {
								header( 'Content-Description: File Transfer' );
								header( 'Content-Type: text/plain' );
								header( 'Content-Disposition: attachment; filename="' . basename( $logFile ) . '"' );
								header( 'Expires: 0' );
								header( 'Cache-Control: must-revalidate' );
								header( 'Pragma: public' );
								header( 'Content-Length: ' . filesize( $logFile ) );
								ob_clean();
								flush();
								echo file_get_contents( esc_attr($logFile) );
							}
						}
						exit;
				?>
				
				<?php else: //Table output ?>
					<?php echo $message; 
					if ($this->get_cart_count( 'all' ) == 0): //If no abandoned carts, then output this note ?>
						<p>
							<?php echo __( 'Looks like you do not have any saved Abandoned carts yet.<br/>But do not worry, as soon as someone fills the <strong>Email</strong> or <strong>Phone number</strong> fields of your WooCommerce Checkout form and abandons the cart, it will automatically appear here.', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN); ?>
						</p>
					<?php else: ?>
						<form id="moceanapi-abandoned-carts-table" method="GET">
							<?php $this->display_cart_statuses( $cart_status, $tab);?>
							<input type="hidden" name="page" value="<?php echo esc_html($_REQUEST['page']) ?>"/>
							<?php $table->display(); ?>
						</form>
					<?php endif; ?>
				<?php endif;
			}?>
		</div>
	<?php
	}

	/**
	 * Method creates tabs on plugin page
	 *
	 * @param    $current    Currently open tab - string
	 */
	function create_admin_tabs( $current = 'carts' ){
		$tabs = array(
			'carts' => __('Abandoned carts', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
			'settings' => __('Email Settings', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
			'smssettings' => __('SMS Settings', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
			'exit_intent' => __('Exit Intent', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
		);
		echo '<h2 class="nav-tab-wrapper">';
		$icon_image = NULL;
		
		foreach( $tabs as $tab => $name ){
			if($tab == 'settings' || $tab == 'smssettings'){
				$icon_class = 'dashicons-admin-generic';
				$icon_image = '';
			}
			elseif($tab == 'exit_intent'){
				//$icon_image = '';
				$icon_class = 'moceanapi-abandoned-carts-exit-intent-icon';
				$icon_image = "<img src='data:image/svg+xml;base64," . $this->exit_intent_svg_icon($current) . "' alt=''  />";
			}
			else{
				$icon_class = 'moceanapi-abandoned-carts-logo';
				$icon_image = "<img src='data:image/svg+xml;base64," . $this->moceanapi_abandoned_carts_svg_icon($current) . "' alt=''  />";
			}
			
			$class = ( $tab == $current ) ? ' nav-tab-active' : ''; //if the tab is open, an additional class, nav-tab-active, is added
			echo "<a class='nav-tab$class' href='?page=". MOCEANAPI_ABANDONED_CARTS ."&tab=$tab'><span class='moceanapi-abandoned-carts-tab-icon dashicons $icon_class' >$icon_image</span><span class='moceanapi-abandoned-carts-tab-name'>$name</span></a>";
		}
		echo '</h2>';
	}

	/**
	 * Method adds additional intervals to default Wordpress cron intervals (hourly, twicedaily, daily). Interval provided in minutes
	 *
  	 */
	function additional_cron_intervals($intervals){
		$intervals['moceanapi_abandoned_carts_notification_sendout_interval'] = array( //Defining cron Interval for sending out email notifications about abandoned carts
			'interval' =>  MOCEANAPI_ABANDONED_CARTS_EMAIL_INTERVAL* 60,
			'display' => 'Every '. MOCEANAPI_ABANDONED_CARTS_EMAIL_INTERVAL .' minutes'
		);
		$intervals['moceanapi_abandoned_carts_remove_empty_carts_interval'] = array( //Defining cron Interval for removing abandoned carts that do not have products
			'interval' => 12 * 60 * 60,
			'display' => 'Twice a day'
		);
		$intervals['moceanapi_abandoned_carts_sms_notification_sendout_interval'] = array( //Defining cron Interval for sending out sms notifications about abandoned carts
			'interval' =>  MOCEANAPI_ABANDONED_CARTS_SMS_INTERVAL* 60,
			'display' => 'Every '. MOCEANAPI_ABANDONED_CARTS_SMS_INTERVAL .' minutes'
		);
		return $intervals;
	}

	/**
	 * FOR EMAIL
	 * Method resets Wordpress cron function after user sets other notification frequency
	 * wp_schedule_event() Schedules a hook which will be executed by the WordPress actions core on a specific interval, specified by you. 
	 * The action will trigger when someone visits your WordPress site, if the scheduled time has passed.
	 *
	 */
	function notification_sendout_interval_update(){
		$user_settings_notification_frequency = get_option('moceanapi_abandoned_carts_notification_frequency');
		if(intval($user_settings_notification_frequency['hours']) == 0){ //If Email notifications have been disabled, we disable cron job
			wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_notification_sendout_hook' );
		}else{
			if (wp_next_scheduled ( 'moceanapi_abandoned_carts_notification_sendout_hook' )) {
				wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_notification_sendout_hook' );
			}
			wp_schedule_event(time(), 'moceanapi_abandoned_carts_notification_sendout_interval', 'moceanapi_abandoned_carts_notification_sendout_hook');
		}
	}

	/**
	 * Method shows warnings if any of the WP Cron events required for MailChimp or ActiveCampaign are not scheduled (either sending notifications or pushing carts) or if the WP Cron has been disabled
	 *
	 */
	function display_wp_cron_warnings(){
		global $pagenow;

		//Checking if we are on open plugin page
		if ($pagenow == 'admin.php' && $_GET['page'] == MOCEANAPI_ABANDONED_CARTS){
			//Checking if WP Cron hooks are scheduled
			$missing_hooks = array();
			$user_settings_notification_frequency = get_option('moceanapi_abandoned_carts_notification_frequency');

			if(wp_next_scheduled('moceanapi_abandoned_carts_notification_sendout_hook') === false && intval($user_settings_notification_frequency['hours']) != 0){ //If we havent scheduled email notifications and notifications have not been disabled
				$missing_hooks[] = 'moceanapi_abandoned_carts_notification_sendout_hook';
			}
			if (!empty($missing_hooks)) { //If we have hooks that are not scheduled
				$hooks = '';
				$current = 1;
				$total = count($missing_hooks);
				foreach($missing_hooks as $missing_hook){
					$hooks .= $missing_hook;
					if ($current != $total){
						$hooks .= ', ';
					}
					$current++;
				}
				?>
				<div id="moceanapi-abandoned-carts-cron-schedules" class="moceanapi-abandoned-carts-notification warning notice updated">
					<p class="left-part">
						<?php
							echo sprintf(
								/* translators: %s - Cron event name */
								_n("It seems that WP Cron event <strong>%s</strong> required for plugin automation is not scheduled.", "It seems that WP Cron events <strong>%s</strong> required for plugin automations are not scheduled.", $total, MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), $hooks); ?> <?php echo sprintf(
								/* translators: %s - Plugin name */
								__("Please try disabling and enabling %s plugin. If this notice does not go away after that, please get in touch with your hosting provider and make sure to enable WP Cron. Without it you will not be able to receive automated email notifications about newly abandoned shopping carts.", MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), MOCEANAPI_ABANDONED_CARTS_ABREVIATION ); ?>
					</p>
				</div>
				<?php 
			}

			//Checking if WP Cron is enabled
			if(defined('DISABLE_WP_CRON')){
				if(DISABLE_WP_CRON == true){ ?>
					<div id="moceanapi-abandoned-carts-cron-schedules" class="moceanapi-abandoned-carts-notification warning notice updated">
						<p class="left-part"><?php echo __("WP Cron has been disabled. Several WordPress core features, such as checking for updates or sending notifications utilize this function. Please enable it or contact your system administrator to help you with this.", MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ); ?></p>
					</div>
				<?php
				}
			}
		}
	}

	/**
	 * Method for sending out e-mail notification in order to notify about new abandoned carts
	 *
   	 */
	function send_email(){
		global $wpdb;
		$cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
		$time = $this->get_time_intervals();
		$public = new MoceanAPI_Public(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
		$where_sentence = $this->get_where_sentence( 'recoverable' );
		$to = get_option( 'admin_email' );
		$template = get_option('moceanapi_abandoned_carts_email_content');
		if(empty($template)){
			$template = "Dear admin, you having new recoverable abandoned cart. Please take action as soon as possible.";
		}

		//Retrieve from database rows that have not been e-mailed and are older than 60 minutes
		$cart_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id)
				FROM $cart_table
				WHERE mail_sent = %d
				$where_sentence AND
				cart_contents != '' AND
				time < %s",
				0,
				$time['cart_abandoned']
			)
		);
		if ($cart_count){ //If we have new rows in the database
			$user_settings_email = get_option('moceanapi_abandoned_carts_notification_email'); //Retrieving email address if the user has entered one
			if(!empty($user_settings_email)){
				$to = $user_settings_email;
			}
			
			$sender = 'WordPress@' . preg_replace('#^www\.#', '', strtolower($_SERVER['SERVER_NAME']));
			$from = "From: WordPress <" . apply_filters( 'moceanapi_abandoned_carts_from_email', $sender ) . ">";
			$blog_name = get_option( 'blogname' );
			$admin_link = get_admin_url() .'admin.php?page='. MOCEANAPI_ABANDONED_CARTS;
			$subject = '['.$blog_name.'] '. _n('New abandoned cart saved', 'New abandoned carts saved', $cart_count, MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN);
			$message = sprintf(
				/* translators: %1$d - Abandoned cart count, %2$s - Plugin name, %3$s - Link, %4$s - Link */
				_n('%5$s <br><br> Great! You have saved %1$d new recoverable abandoned cart using %2$s. <br/>View them here: <a href="%3$s">%4$s</a>', '%5$s <br><br> Congratulations, you have saved %1$d new recoverable abandoned carts using %2$s. <br/>View them here: <a href="%3$s">%4$s</a>', $cart_count, MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN), esc_html($cart_count), MOCEANAPI_ABANDONED_CARTS_ABREVIATION, esc_html($admin_link), esc_html($admin_link), $template);
			$headers 	= "$from\n" . "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"\n";

			//Sending out e-mail
			$email_sent = wp_mail( esc_html($to), esc_html($subject), $message, $headers );
			
			//Update mail_sent status to true with mail_status = 0 and are older than 60 minutes
			if($email_sent){
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE $cart_table
						SET mail_sent = %d
						WHERE mail_sent = %d 
						$where_sentence AND
						cart_contents != '' AND
						time < %s",
						1,
						0,
						$time['cart_abandoned']
					)
				);
				return $email_sent;
			}
		}
	}


	
	/**
	 * Method calculates if time has passed since the given time period (In days)
	 *
	 * @return   Boolean
	 * @param    $option    Option from WordPress database
	 * @param    $days      Number of days
	 */
	function days_have_passed( $option, $days ){
		$last_time = esc_attr(get_option($option)); //Get time value from the database
		$last_time = strtotime($last_time); //Convert time from text to Unix timestamp
		
		$date = date_create(current_time( 'mysql', false ));
		$current_time = strtotime(date_format($date, 'Y-m-d H:i:s'));
		$days = $days; //Defines the time interval that should be checked against in days
		
		if($last_time < $current_time - $days * 24 * 60 * 60 ){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Method checks the current plugin version with the one saved in database
	 */
	function check_current_plugin_version(){
		$plugin = new MoceanAPI();
		$current_version = $plugin->get_version();
		
		if ($current_version == get_option('moceanapi_abandoned_carts_version_number')){ //If database version is equal to plugin version. Not updating database
			return;
		}else{ //Versions are different and we must update the database
			update_option('moceanapi_abandoned_carts_version_number', $current_version);
			activate_moceanapi_abandoned_carts(); //Function that updates the database
			return;
		}
	}

	/**
	 * Checks if we have to disable input field or not because of the users access right to save data
	 *
	 * @param    $options    Options
	 */
	function disable_field( $options = array() ){
		if($options){
			if($options['forced'] == true){
				return 'disabled=""';
			}
		}
		elseif(!current_user_can( 'manage_options' )){
			return 'disabled=""';
		}
	}

	/**
	 * Returns the count of total captured abandoned carts
	 *
	 * @return 	 number
	 */
	function total_moceanapi_abandoned_carts_recoverable_cart_count(){
		if ( false === ( $captured_abandoned_cart_count = get_transient( 'moceanapi_abandoned_carts_recoverable_cart_count' ))){ //If value is not cached or has expired
			$captured_abandoned_cart_count = get_option('moceanapi_abandoned_carts_recoverable_cart_count');
			set_transient( 'moceanapi_abandoned_carts_recoverable_cart_count', $captured_abandoned_cart_count, 60 * 10 ); //Temporary cache will expire in 10 minutes
		}
		
		return $captured_abandoned_cart_count;
	}

	/**
	 * Sets the path to language folder for internationalization
	 *
	 */
	function moceanapi_abandoned_carts_text_domain(){
		return load_plugin_textdomain( MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN, false, basename( plugin_dir_path( __DIR__ ) ) . '/languages' );
	}

	/**
	 * Method removes empty abandoned carts that do not have any products and are older than 1 day
	 *
  	 */
	function delete_empty_carts(){
		global $wpdb;
		$cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
		$time = $this->get_time_intervals();
		$public = new MoceanAPI_Public(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
		$where_sentence = $this->get_where_sentence( 'ghost' );

		//Deleting ghost rows from database first
		$ghost_row_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE cart_contents = ''
				$where_sentence AND
				time < %s",
				$time['day']
			)
		);
		$public->decrease_ghost_cart_count( $ghost_row_count );

		//Deleting rest of the abandoned carts without products
		$rest_count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $cart_table
				WHERE cart_contents = '' AND
				time < %s",
				$time['day']
			)
		);
		$public->decrease_recoverable_cart_count( $rest_count );
	}

	/**
	 * Method to clear cart data from row
	 *
  	 */
	function clear_cart_data(){
		global $wpdb;
		$cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
		
		//If a new Order is added from the WooCommerce admin panel, we must check if WooCommerce session is set. Otherwise we would get a Fatal error.
		if(isset(WC()->session)){
			$public = new MoceanAPI_Public(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
			$cart = $public->read_cart();

			if(isset($cart['session_id'])){
				//Cleaning Cart data
				$wpdb->prepare('%s',
					$wpdb->update(
						$cart_table,
						array(
							'cart_contents'	=>	'',
							'cart_total'	=>	0,
							'currency'		=>	sanitize_text_field( $cart['cart_currency'] ),
							'time'			=>	sanitize_text_field( $cart['current_time'] )
						),
						array('session_id' => $cart['session_id']),
						array('%s', '%s'),
						array('%s')
					)
				);
			}
		}
	}

	/**
	 * Reseting abandoned cart data in case if a registered user has an existing abandoned cart and updates his data on his Account page
	 *
	 */
	public function reset_abandoned_cart(){
		if(!is_user_logged_in()){ //Exit in case the user is not logged in
			return;
		}

		global $wpdb;
		$user_id = 0;
		$public = new MoceanAPI_Public(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);

		if (!empty($_POST['user_id']) && is_numeric($_POST['user_id']) ) { //In case the user's data is updated from WordPress admin dashboard "Edit profile page"
			$user_id = $_POST['user_id'];

		}elseif(!empty($_POST['action'])){ //This check is to prevent profile update to be fired after a new Order is created since no "action" is provided and the user's ID remians 0 and we exit resetting of the abandoned cart
			$user_id = get_current_user_id();
		}

		if(!$user_id){ //Exit in case we do not have user's ID value
			return;
		}
		
		if($public->cart_saved($user_id)){ //If we have saved an abandoned cart for the user - go ahead and reset it
			$cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
			$updated_rows = $wpdb->prepare('%s',
				$wpdb->update(
					$cart_table,
					array(
						'name'			=>	'',
						'surname'		=>	'',
						'email'			=>	'',
						'phone'			=>	'',
						'location'		=>	'',
						'cart_contents'	=>	'',
						'cart_total'	=>	'',
						'currency'		=>	'',
						'time'			=>	'',
						'other_fields'	=>	''
					),
					array('session_id' => $user_id),
					array(),
					array('%s')
				)
			);
		}
	}

	/**
	 * Method returns different expressions depending on the amount of captured carts
	 *
	 * @return 	 String
	 */
	function get_expressions(){
		if($this->total_moceanapi_abandoned_carts_recoverable_cart_count() <= 10){
			$expressions = array(
				'exclamation' => __('Congrats!', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
			);
		}elseif($this->total_moceanapi_abandoned_carts_recoverable_cart_count() <= 30){
			$expressions = array(
				'exclamation' => __('Awesome!', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
			);
		}elseif($this->total_moceanapi_abandoned_carts_recoverable_cart_count() <= 100){
			$expressions = array(
				'exclamation' => __('Amazing!', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
			);
		}elseif($this->total_moceanapi_abandoned_carts_recoverable_cart_count() <= 300){
			$expressions = array(
				'exclamation' => __('Incredible!', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
			);
		}elseif($this->total_moceanapi_abandoned_carts_recoverable_cart_count() <= 500){
			$expressions = array(
				'exclamation' => __('Crazy!', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
			);
		}elseif($this->total_moceanapi_abandoned_carts_recoverable_cart_count() <= 1000){
			$expressions = array(
				'exclamation' => __('Fantastic!', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
			);
		}else{
			$expressions = array(
				'exclamation' => __('Insane!', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
			);
		}

		return $expressions;
	}

	/**
	 * Method returns Exit Intent icon as SVG code
	 *
	 * @return 	 String
	 * @param    $current    Current active tab - string
	 */
	public function exit_intent_svg_icon( $current ){
		$color = '#555';
		if($current == 'exit_intent'){
			$color = '#000';
		}
		return base64_encode('<svg height="18px" style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 61.75 63.11"><path d="M26.32,6.24A6.24,6.24,0,1,1,20.07,0a6.24,6.24,0,0,1,6.24,6.24h0Z"/><path d="M55.43,39.26C48.88,43.09,45,37.47,42,32.07c-0.13-.52-5.27-10.44-7.77-14.79,4.89-1.56,9.35-.13,12.86,4.79,2.85,4,9.53.16,6.64-3.88C46.94,8.67,36.8,6.3,26.66,12.32c-0.42.25-2.33,1.3-2.76,1.56-6.31,3.75-12.17,3-16.54-3.1-2.86-4-9.54-.16-6.65,3.89,5.59,7.82,13.43,10.8,21.67,8.27,2.59,4.45,5,9,7.41,13.54-3.49,1.79-10,5.39-11.71,8.71C16,49.32,14,53.53,12,57.7c-2.17,4.48,4.8,7.73,7,3.27,1.92-4,6.28-12.22,6.53-12.43,3.48-3,12.25-7.18,12.44-7.28,5.35,6.79,12.81,10.52,21.75,5.3,4.71-2.75.45-10.07-4.27-7.31h0Z"/></svg>
		');
    }

    /**
	 * Method returns MoceanAPI icon as SVG code
	 *
	 * @return 	 String
	 * @param    $current    Current active tab - string
	 */
	public function moceanapi_abandoned_carts_svg_icon( $current ){
		$color = '#555';
		if($current == 'carts'){
			$color = '#000';
		}
		return base64_encode('<svg height="18px" style="fill: '. $color .';" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26.34 29.48"><path d="M7.65,24c-2.43,0-3.54-1.51-3.54-2.91V3.44C3.77,3.34,3,3.15,2.48,3L.9,2.59A1.28,1.28,0,0,1,0,1.15,1.32,1.32,0,0,1,1.34,0a1.52,1.52,0,0,1,.42.06l.68.2c1.38.41,2.89.85,3.25,1A1.72,1.72,0,0,1,6.79,2.8V5.16L24.67,7.53a1.75,1.75,0,0,1,1.67,2v6.1a3.45,3.45,0,0,1-3.59,3.62h-16v1.68c0,.14,0,.47,1.07.47H21.13a1.32,1.32,0,0,1,1.29,1.38,1.35,1.35,0,0,1-.25.79,1.18,1.18,0,0,1-1,.5Zm-.86-7.5,15.76,0c.41,0,1.11,0,1.11-1.45V10c-3-.41-13.49-1.69-16.87-2.11Z"/><path d="M21.78,29.48a4,4,0,1,1,4-4A4,4,0,0,1,21.78,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.35,1.35,0,0,0,21.78,24.11ZM10.14,29.48a4,4,0,1,1,4-4A4,4,0,0,1,10.14,29.48Zm0-5.37a1.35,1.35,0,1,0,1.34,1.34A1.34,1.34,0,0,0,10.14,24.11Z"/><path d="M18.61,18.91a1.34,1.34,0,0,1-1.34-1.34v-9a1.34,1.34,0,1,1,2.67,0v9A1.34,1.34,0,0,1,18.61,18.91Z"/><path d="M12.05,18.87a1.32,1.32,0,0,1-1.34-1.29v-10a1.34,1.34,0,0,1,2.68,0v10A1.32,1.32,0,0,1,12.05,18.87Z"/></svg>
		');
    }

    /**
	 * Method tries to move the email field higher in the checkout form
	 *
	 * @return 	 Array
	 * @param 	 $fields    Checkout form fields
	 */ 
	public function lift_checkout_email_field( $fields ) {
		$lift_email_on = esc_attr( get_option('moceanapi_abandoned_carts_lift_email'));
		if($lift_email_on){ //Changing the priority and moving the email higher
			$fields['billing_email']['priority'] = 5;
		}
		return $fields;
	}

	/**
	 * Method prepares and returns an array of different time intervals used for calulating time substractions
	 *
	 * @return 	 Array
	 */
	public function get_time_intervals(){
		//Calculating time intervals
		$datetime = current_time( 'mysql' );
		return array(
			'cart_abandoned' 	=> date( 'Y-m-d H:i:s', strtotime( '-' . MOCEANAPI_ABANDONED_CARTS_STILL_SHOPPING . ' minutes', strtotime( $datetime ) ) ),
			'old_cart' 			=> date( 'Y-m-d H:i:s', strtotime( '-' . MOCEANAPI_ABANDONED_CARTS_NEW_NOTICE . ' minutes', strtotime( $datetime ) ) ),
			'day' 				=> date( 'Y-m-d H:i:s', strtotime( '-1 day', strtotime( $datetime ) ) )
		);
	}

	/**
     * Method counts carts in the selected category
     *
     *  
     * @return   number
     */
    function get_cart_count( $cart_status ){
        global $wpdb;
        $cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
        $total_items = 0;
        $where_sentence = $this->get_where_sentence($cart_status);

        $total_items = $wpdb->get_var("
            SELECT COUNT(id)
            FROM $cart_table
            WHERE cart_contents != ''
            $where_sentence
        ");

        return $total_items;
    }

    /**
     * Method displays available cart type filters
     *
     *  
     * @return   string
     * @param 	 $cart_status    Currently filtered cart status
     * @param 	 $tab    		 Currently open tab
     */
    function display_cart_statuses( $cart_status, $tab ){
    	$exclude = false;
    	$divider = ' | ';
    	if(get_option('moceanapi_abandoned_carts_exclude_ghost_carts' )){
			$exclude = true;
		}
    	$cart_types = array(
			'all' 			=> __('All', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
			'action'		=> __('Action Taken',MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
    		'recoverable' 	=> __('Recoverable', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN),
    		'ghost' 		=> __('Ghost', MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN)
    	);
    	$total_items = count($cart_types);
    	if(count($cart_types) <= 3 && $exclude){ //Do not output the filter if we are excluding Ghost carts and we have only 3 cart types
    		return;
    	}
    	echo '<ul id="moceanapi-abandoned-carts-cart-statuses" class="subsubsub">';
    	$counter = 0;
    	foreach( $cart_types as $key => $type ){
    		$counter++;
    		if($counter == $total_items){
    			$divider = '';
    		}
    		$class = ( $key == $cart_status ) ? 'current' : '';
    		$count = $this->get_cart_count($key);
    		if (!($key == 'ghost' && $exclude)){ //If we are not processing Ghost carts and they have not been excluded
	    		echo "<li><a href='?page=". MOCEANAPI_ABANDONED_CARTS ."&tab=$tab&cart-status=$key' title='$type' class='$class'>$type <span class='count'>($count)</span></a></li>$divider";
	    	}elseif($key == 'action'){
				// echo ; 
			}
    	}
    	echo '</ul>';
    }

    /**
     * Method for creating SQL query depending on different post types
     *
     *  
     * @return   string
     */
    function get_where_sentence( $cart_status ){
		$where_sentence = '';

		if($cart_status == 'recoverable'){
			$where_sentence = "AND ( (email != '' OR phone != '' ) AND (bulk_email_sent = '0' AND bulk_sms_sent = '0') )";

		}elseif($cart_status == 'ghost'){
			$where_sentence = "AND ((email IS NULL OR email = '') AND (phone IS NULL OR phone = ''))";

		}elseif(get_option('moceanapi_abandoned_carts_exclude_ghost_carts')){ //In case Ghost carts have been excluded	
			$where_sentence = "AND (email != '' OR phone != '')";
		
		}elseif($cart_status == 'action'){
			$where_sentence = "AND (bulk_email_sent != '0' OR bulk_sms_sent != '0')";
		}

		return $where_sentence;
    }

    /**
	 * Handling abandoned carts in case of a new order is placed
	 *
	 * @param    Integer    $order_id - ID of the order created by WooCommerce
	 */
	function handle_order( $order_id ){
		$public = new MoceanAPI_Public(MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG, MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER);
		$public->update_logged_customer_id(); //In case a user chooses to create an account during checkout process, the session id changes to a new one so we must update it
		$this->clear_cart_data(); //Clearing abandoned cart after it has been synced
	}

	/**
	* This is used to get MOCEANAPI_ABANDONED_CARTS account credit
	 */
	public function smsAbandonedCartGetCredit() {	

		$api_key    = get_option('moceanapi_abandoned_carts_key');
		$api_secret = get_option('moceanapi_abandoned_carts_secret');

		//get balance
		$moceansms_rest = new MoceanSMS_carts( $api_key, $api_secret );
		$rest_response  = $moceansms_rest->accountBalance();
			
		$rest_response = json_decode($rest_response);
	    if($rest_response->{'status'} == 0){
			return $rest_response->{'value'};
	    }
	
	}

	/**
	 * FOR SMS
	 * Method resets Wordpress cron function after user sets other notification frequency
	 * wp_schedule_event() Schedules a hook which will be executed by the WordPress actions core on a specific interval, specified by you. 
	 * The action will trigger when someone visits your WordPress site, if the scheduled time has passed.
	 *
   	 */
	function sms_notification_sendout_interval_update(){
		$user_settings_sms_notification_frequency = get_option('moceanapi_abandoned_carts_sms_notification_frequency');
		if(intval($user_settings_sms_notification_frequency['hours']) == 0){ //If SMS notifications have been disabled, we disable cron job
			wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_sms_auto_sendout_hook' );
		}else{
			if (wp_next_scheduled ( 'moceanapi_abandoned_carts_sms_auto_sendout_hook' )) {
				wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_sms_auto_sendout_hook' );
			}
			wp_schedule_event(time(), 'moceanapi_abandoned_carts_sms_notification_sendout_interval', 'moceanapi_abandoned_carts_sms_auto_sendout_hook');
		}
	}

	/**
	 * Method shows warnings if any of the WP Cron events required for MailChimp or ActiveCampaign are not scheduled (either sending notifications or pushing carts) or if the WP Cron has been disabled
	 *
   	 */
	function display_wp_cron_warnings_sms(){
		global $pagenow;

		//Checking if we are on open plugin page
		if ($pagenow == 'admin.php' && $_GET['page'] == MOCEANAPI_ABANDONED_CARTS){
			//Checking if WP Cron hooks are scheduled
			$missing_hooks = array();
			$user_settings_sms_notification_frequency = get_option('moceanapi_abandoned_carts_sms_notification_frequency');

			if(wp_next_scheduled('moceanapi_abandoned_carts_sms_auto_sendout_hook') === false && intval($user_settings_sms_notification_frequency['hours']) != 0){ //If we havent scheduled email notifications and notifications have not been disabled
				$missing_hooks[] = 'moceanapi_abandoned_carts_sms_auto_sendout_hook';
			}
			if (!empty($missing_hooks)) { //If we have hooks that are not scheduled
				$hooks = '';
				$current = 1;
				$total = count($missing_hooks);
				foreach($missing_hooks as $missing_hook){
					$hooks .= $missing_hook;
					if ($current != $total){
						$hooks .= ', ';
					}
					$current++;
				}
				?>
				<div id="moceanapi-abandoned-carts-cron-schedules" class="moceanapi-abandoned-carts-notification warning notice updated">
					<p class="left-part">
						<?php
							echo sprintf(
								/* translators: %s - Cron event name */
								_n("It seems that WP Cron event <strong>%s</strong> required for plugin automation is not scheduled.", "It seems that WP Cron events <strong>%s</strong> required for plugin automations are not scheduled.", $total, MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), $hooks); ?> <?php echo sprintf(
								/* translators: %s - Plugin name */
								__("Please try disabling and enabling %s plugin. If this notice does not go away after that, please get in touch with your hosting provider and make sure to enable WP Cron. Without it you will not be able to receive automated email notifications about newly abandoned shopping carts.", MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ), MOCEANAPI_ABANDONED_CARTS_ABREVIATION ); ?>
					</p>
				</div>
				<?php 
			}

			//Checking if WP Cron is enabled
			if(defined('DISABLE_WP_CRON')){
				if(DISABLE_WP_CRON == true){ ?>
					<div id="moceanapi-abandoned-carts-cron-schedules" class="moceanapi-abandoned-carts-notification warning notice updated">
						<p class="left-part"><?php echo __("WP Cron has been disabled. Several WordPress core features, such as checking for updates or sending notifications utilize this function. Please enable it or contact your system administrator to help you with this.", MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN ); ?></p>
					</div>
				<?php
				}
			}
		}
	}

	/**
	* This is used to SEND auto SMS to admin
	 */
	function smsAbandonedCartSendMessage() {
		global $wpdb;
		$cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
		$time = $this->get_time_intervals();
		$where_sentence = $this->get_where_sentence( 'recoverable' );
		$current_user_id = get_current_user_id();
		$authorize = $this->smsAbandonedCartGetCredit();

		$api_key    = get_option('moceanapi_abandoned_carts_key');
		$api_secret = get_option('moceanapi_abandoned_carts_secret');
		$sms_from 	= get_option('moceanapi_abandoned_carts_from');
		$sms_to 	= get_user_meta($current_user_id,'billing_phone',true);
		$sms_msg	= get_option('moceanapi_abandoned_carts_content');
		if(empty($sms_msg)){
			$sms_msg = "Dear admin, you having new recoverable abandoned cart. Please take action as soon as possible.";
		}
		$inputted_phone = get_option('moceanapi_abandoned_carts_notification_sms');//Retrive inputted phone number
		$country	= get_user_meta($current_user_id,'billing_country',true);
		if(empty($country)){
			$country = 'MY';
		}

		//Retrieve from database rows that have been e-mailed and havent send sms
		$sms_cart_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(id)
				FROM $cart_table
				WHERE mail_sent = %d
				$where_sentence AND
				cart_contents != '' AND
				sms_sent = %d AND
				bulk_email_sent = %d AND
				bulk_sms_sent = %d" ,
				1,
				0,
				0,
				0			
			)
		);

		if($authorize){
			if($sms_cart_count){ //If we have new rows in the database
				if(!empty($inputted_phone)){ //If user not input any phone number then use admin number.
					$sms_to = $inputted_phone;
				}

				//Check country code
				$admin_phone_no = $this->check_and_get_phone_number( $sms_to, $country );
				if ( $admin_phone_no !== false ) {
					$this->log->add( 'MoceanAPI_Abandon_Carts', 'Admin\'s billing phone number (' . $sms_to . ') in country (' . $country . ') converted to ' . $admin_phone_no );
				}else {
					$admin_phone_no = $sms_to;
				}

				//send SMS
				$moceansms_rest = new MoceanSMS_carts( $api_key, $api_secret );
				$rest_response  = $moceansms_rest->sendSMS( $sms_from, $admin_phone_no, $sms_msg);

				if($rest_response === false) {
					throw new Exception('curl error: ' . curl_error($rest_request));
					$this->log->add( 'MoceanAPI_Abandon_Carts', 'Failed sent SMS: ' . $e->getMessage() );
				}else{
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE $cart_table
							SET sms_sent = %d
							WHERE sms_sent = %d 
							$where_sentence AND
							cart_contents != '' AND
							bulk_email_sent = %d AND
							bulk_sms_sent = %d ",
							1,
							0,
							0,
							0
						)
					);	
					$this->log->add( 'MoceanAPI_Abandon_Carts', 'SMS response from SMS gateway: ' . $rest_response );
					return $rest_response;
				}
			}
		}else{
			$this->log->add( 'MoceanAPI_Abandon_Carts', 'Authorization failed!');
		} 
	}

	/**
	* This is used to check the country code of phone number
	 */
	function check_and_get_phone_number( $phone_number, $country ) {
		$check_phone_number_request_url = 'https://dashboard.moceanapi.com/public/mobileChecking?mobile_number={{phone}}&country_code={{country}}';
		$response                       = wp_remote_get( str_replace(
			array( '{{phone}}', '{{country}}' ),
			array( $phone_number, $country ),
			$check_phone_number_request_url
		) );

		if ( is_array( $response ) ) {
			$customer_phone_no = wp_remote_retrieve_body( $response );
			if ( ctype_digit( $customer_phone_no ) ) {
				return $customer_phone_no;
			}

			$this->log->add( 'MoceanAPI_Abandon_Carts', 'check number api err response:' . $customer_phone_no );

			return false;
		}

		$this->log->add( 'MoceanAPI_Abandon_Carts', 'check number api timeout, continue send without formatting' );

		return false;
	}

}
