<?php
/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package    MoceanAPI Abandoned Carts
 * @subpackage MoceanAPI Abandoned Carts/includes
 * @author     Micro Ocean Technologies
 */
class MoceanAPI_Deactivator{

	/**
	 * Deactivation function
	 *
	 *  
	 */
	public static function deactivate() {
		//Deactivating Wordpress cron job functions and stop sending out e-mails
		wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_notification_sendout_hook' );
		wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_sms_auto_sendout_hook' );
		wp_clear_scheduled_hook( 'moceanapi_abandoned_carts_remove_empty_carts_hook' );
		delete_transient( 'moceanapi_abandoned_carts_recoverable_cart_count' );
	}
}