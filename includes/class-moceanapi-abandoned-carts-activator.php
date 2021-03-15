<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package    MoceanAPI Abandoned Carts
 * @subpackage MoceanAPI Abandoned Carts/includes
 * @author     Micro Ocean Technologies
 */
 
class MoceanAPI_Activator{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 */
	public static function activate() {
		
		
		/**
		* Creating table
		*/
		global $wpdb;
		
		$cart_table = $wpdb->prefix . MOCEANAPI_ABANDONED_CARTS_TABLE_NAME;
		$old_cart_table = $wpdb->prefix . "captured_wc_fields";
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $cart_table (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(60),
			surname VARCHAR(60),
			email VARCHAR(100),
			phone VARCHAR(20),
			location VARCHAR(100),
			cart_contents LONGTEXT,
			cart_total DECIMAL(10,2),
			currency VARCHAR(10),
			time DATETIME DEFAULT '0000-00-00 00:00:00',
			session_id VARCHAR(60),
			mail_sent TINYINT NOT NULL DEFAULT 0,
			sms_sent TINYINT NOT NULL DEFAULT 0,
			bulk_email_sent TINYINT NOT NULL DEFAULT 0,
			bulk_sms_sent TINYINT NOT NULL DEFAULT 0,
			other_fields LONGTEXT,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		/**
		* Resets table Auto increment index to 1
		*/
		$sql ="ALTER TABLE $cart_table AUTO_INCREMENT = 1";
		dbDelta( $sql );

		/**
		 * Handling cart transfer from the old captured_wc_fields table to new one
		 *
		 */
		function moceanapi_abandoned_carts_transfer_carts( $wpdb, $cart_table, $old_cart_table ){
		    if(!moceanapi_abandoned_carts_old_table_exists( $wpdb, $old_cart_table )){ //If old table no longer exists, exit
		    	return;
		    }
		    if(!get_option('moceanapi_abandoned_carts_transferred_table')){ //If we have not yet transfered carts to the new table
		    	$old_carts = $wpdb->get_results( //Selecting all rows that are not empty
	    			"SELECT * FROM $old_cart_table
	    			WHERE cart_contents != ''
	    			"
		    	);

		    	if($old_carts){ //If we have carts
		    		$imported_cart_count = 0;
		    		$batch_count = 0; //Keeps count of current batch of data to insert
		    		$batches = array(); //Array containing the batches of import since SQL is having troubles importing too many rows at once
					$abandoned_cart_data = array();
					$placeholders = array();

					foreach($old_carts as $key => $cart){ // Looping through abandoned carts to create the arrays
						$batch_count++;

						array_push(
							$abandoned_cart_data,
							sanitize_text_field( $cart->id ),
							sanitize_text_field( $cart->name ),
							sanitize_text_field( $cart->surname ),
							sanitize_email( $cart->email ),
							sanitize_text_field( $cart->phone ),
							sanitize_text_field( $cart->location ),
							sanitize_text_field( $cart->cart_contents ),
							sanitize_text_field( $cart->cart_total ),
							sanitize_text_field( $cart->currency ),
							sanitize_text_field( $cart->time ),
							sanitize_text_field( $cart->session_id ),
							sanitize_text_field( $cart->mail_sent ),
							sanitize_text_field( $cart->sms_sent ),
							sanitize_text_field( $cart->bulk_email_sent ),
							sanitize_text_field( $cart->bulk_sms_sent ),
							sanitize_text_field( $cart->other_fields )
						);
						$placeholders[] = "( %d, %s, %s, %s, %s, %s, %s, %0.2f, %s, %s, %s, %d, %d, %d, %d, %s )";

						if($batch_count >= 100){ //If we get a full batch, add it to the array and start preparing a new one
							$batches[] = array(
								'data'			=>	$abandoned_cart_data,
								'placeholders'	=>	$placeholders
							);
							$batch_count = 0;
							$abandoned_cart_data = array();
							$placeholders = array();
						}
					}

					//In case something is left at the end of the loop, we add it to the batches so we do not loose any abandoned carts during the import process
					if($abandoned_cart_data){
						$batches[] = array(
							'data'			=>	$abandoned_cart_data,
							'placeholders'	=>	$placeholders
						);
					}
					
					foreach ($batches as $key => $batch) { //Looping through the batches and importing the carts
						$query = "INSERT INTO ". $cart_table ." (id, name, surname, email, phone, location, cart_contents, cart_total, currency, time, session_id, mail_sent, sms_sent, bulk_email_sent, bulk_sms_sent, other_fields) VALUES ";
						$query .= implode(', ', $batch['placeholders']);
						$count = $wpdb->query( $wpdb->prepare("$query ", $batch['data']));
						$imported_cart_count = $imported_cart_count + $count;
					}
		    	}

		    	update_option('moceanapi_abandoned_carts_transferred_table', true); //Making sure the user is not allowed to transfer carts more than once
		    	$wpdb->query( "DROP TABLE IF EXISTS $old_cart_table" ); //Removing old table from the database
		    }
		}

		/**
		 * Determine if we have old MoceanAPI cart table still present
		 *
		 * @return 	 Boolean
		 */
		function moceanapi_abandoned_carts_old_table_exists( $wpdb, $old_cart_table ){
			$exists = false;
			$table_exists = $wpdb->query(
				"SHOW TABLES LIKE '{$old_cart_table}'"
			);
			if($table_exists){ //In case table exists
				$exists = true;
			}
			return $exists;
		}

		moceanapi_abandoned_carts_transfer_carts( $wpdb, $cart_table, $old_cart_table );

		//Registering email notification frequency
		if ( get_option('moceanapi_abandoned_carts_notification_frequency') !== false ) {
			// The option already exists, so we do not do nothing
		}else{
			// The option hasn't been added yet
			add_option('moceanapi_abandoned_carts_notification_frequency', array('hours' => 60));
		}

		//Registering sms notification frequency
		if ( get_option('moceanapi_abandoned_carts_sms_notification_frequency') !== false ) {
			// The option already exists, so we do not do nothing
		}else{
			// The option hasn't been added yet
			add_option('moceanapi_abandoned_carts_sms_notification_frequency', array('hours' => 60));
		}

		//Setting default Exit Intent type if it has not been previously set
		add_option('moceanapi_abandoned_carts_exit_intent_type', 1);

		if (! wp_next_scheduled ( 'moceanapi_abandoned_carts_remove_empty_carts_hook' )) {
			wp_schedule_event(time(), 'moceanapi_abandoned_carts_remove_empty_carts_interval', 'moceanapi_abandoned_carts_remove_empty_carts_hook');
		}

		// if (get_option( 'wclcfc_review_submitted' )){
		// 	update_option( 'moceanapi_abandoned_carts_review_submitted', get_option( 'wclcfc_review_submitted' ));
		// }
		delete_option( 'wclcfc_review_submitted' );
		if (get_option( 'wclcfc_version_number' )){
			update_option( 'moceanapi_abandoned_carts_version_number', get_option( 'wclcfc_version_number' ));
		}
		delete_option( 'wclcfc_version_number' );
		if (get_option( 'wclcfc_captured_abandoned_cart_count' )){
			update_option( 'moceanapi_abandoned_carts_captured_abandoned_cart_count', get_option( 'wclcfc_captured_abandoned_cart_count' ));
		}
		delete_option( 'wclcfc_captured_abandoned_cart_count' );
		if (get_option( 'wclcfc_times_review_declined' )){
			update_option( 'moceanapi_abandoned_carts_times_review_declined', get_option( 'wclcfc_times_review_declined' ));
		}
		delete_option( 'wclcfc_times_review_declined' );
		if (get_option( 'wclcfc_exit_intent_status' )){
			update_option( 'moceanapi_abandoned_carts_exit_intent_status', get_option( 'wclcfc_exit_intent_status' ));
		}
		delete_option( 'wclcfc_exit_intent_status' );
		if (get_option( 'wclcfc_exit_intent_test_mode' )){
			update_option( 'moceanapi_abandoned_carts_exit_intent_test_mode', get_option( 'wclcfc_exit_intent_test_mode' ));
		}
		delete_option( 'wclcfc_exit_intent_test_mode' );
		if (get_option( 'wclcfc_exit_intent_type' )){
			update_option( 'moceanapi_abandoned_carts_exit_intent_type', get_option( 'wclcfc_exit_intent_type' ));
		}
		delete_option( 'wclcfc_exit_intent_type' );
		if (get_option( 'wclcfc_exit_intent_main_color' )){
			update_option( 'moceanapi_abandoned_carts_exit_intent_main_color', get_option( 'wclcfc_exit_intent_main_color' ));
		}
		delete_option( 'wclcfc_exit_intent_main_color' );
		if (get_option( 'wclcfc_exit_intent_inverse_color' )){
			update_option( 'moceanapi_abandoned_carts_exit_intent_inverse_color', get_option( 'wclcfc_exit_intent_inverse_color' ));
		}
		delete_option( 'wclcfc_exit_intent_inverse_color' );

		if (get_option( 'moceanapi_abandoned_carts_captured_abandoned_cart_count' )){
			update_option( 'moceanapi_abandoned_carts_recoverable_cart_count', get_option( 'moceanapi_abandoned_carts_captured_abandoned_cart_count' ));
		}
		delete_option( 'moceanapi_abandoned_carts_captured_abandoned_cart_count' );

		/**
		 * Starting WordPress cron function in order to send out e-mails on a set interval
		 *
		 */
		$user_settings_notification_frequency = get_option('moceanapi_abandoned_carts_notification_frequency');
		if(intval($user_settings_notification_frequency['hours']) == 0){ //If Email notifications have been disabled, we disable cron job
			wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_notification_sendout_hook' );
		}else{
			if (! wp_next_scheduled ( 'moceanapi_abandoned_carts_notification_sendout_hook' )) {
				wp_schedule_event(time(), 'moceanapi_abandoned_carts_notification_sendout_interval', 'moceanapi_abandoned_carts_notification_sendout_hook');
			}
		}

		/**
		 * Starting WordPress cron function in order to send out sms on a set interval
		 *
		 */
		$user_settings_sms_notification_frequency = get_option('moceanapi_abandoned_carts_sms_notification_frequency');
		if(intval($user_settings_sms_notification_frequency['hours']) == 0){ //If SMS notifications have been disabled, we disable cron job
			wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_sms_auto_sendout_hook' );
		}else{
			if (! wp_next_scheduled ( 'moceanapi_abandoned_carts_sms_auto_sendout_hook' )) {
				wp_schedule_event(time(), 'moceanapi_abandoned_carts_sms_notification_sendout_interval', 'moceanapi_abandoned_carts_sms_auto_sendout_hook');
			}
		}
	}
}