<?php

/**
 * Plugin Name: MoceanAPI Abandoned Carts
 * Description: MoceanAPI SMS Notification for WooCommerce Abandoned Carts. 
 * Version: 1.1.0
 * Text Domain: moceanapi-abandoned-carts-woocommerce
 * Author: Micro Ocean Technologies
 * Author URI: https://moceanapi.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Retrieve email notification frequency from database
$user_settings_notification_frequency = get_option('moceanapi_abandoned_carts_notification_frequency'); //Retrieving notification frequency
if($user_settings_notification_frequency == '' || $user_settings_notification_frequency == NULL){
	$frequency = 60; //Default 60 minutes
}else{
	$frequency = intval($user_settings_notification_frequency['hours']);
}

//Retrieve sms notification frequency from database
$user_settings_sms_notification_frequency = get_option('moceanapi_abandoned_carts_sms_notification_frequency'); //Retrieving notification frequency
if($user_settings_sms_notification_frequency == '' || $user_settings_sms_notification_frequency == NULL){
	$sms_frequency = 60; //Default 60 minutes
}else{
	$sms_frequency = intval($user_settings_sms_notification_frequency['hours']);
}

//Defining constants
if (!defined('MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER')) define( 'MOCEANAPI_ABANDONED_CARTS_VERSION_NUMBER', '1.1.0' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME')) define( 'MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME', 'MoceanAPI Abandoned Carts' );
if (!defined('MOCEANAPI_ABANDONED_CARTS')) define( 'MOCEANAPI_ABANDONED_CARTS', 'moceanapi_abandoned_carts' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG')) define( 'MOCEANAPI_ABANDONED_CARTS_PLUGIN_NAME_SLUG', 'moceanapi_abandoned_carts' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_BASENAME')) define( 'MOCEANAPI_ABANDONED_CARTS_BASENAME', plugin_basename( __FILE__ ) );
if (!defined('MOCEANAPI_ABANDONED_CARTS_TABLE_NAME')) define( 'MOCEANAPI_ABANDONED_CARTS_TABLE_NAME', 'moceanapi_abandoned_carts' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_LICENSE_SERVER_URL')) define('MOCEANAPI_ABANDONED_CARTS_LICENSE_SERVER_URL', 'https://www.moceanapi.com' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_REVIEW_LINK')) define('MOCEANAPI_ABANDONED_CARTS_REVIEW_LINK', '' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN')) define( 'MOCEANAPI_ABANDONED_CARTS_TEXT_DOMAIN', 'moceanapi-abandoned-carts-woocommerce' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_ABREVIATION')) define( 'MOCEANAPI_ABANDONED_CARTS_ABREVIATION', 'MoceanAPI Abandoned Carts' );
if (!defined('MOCEANAPI_ABANDONED_CARTS_EMAIL_INTERVAL')) define( 'MOCEANAPI_ABANDONED_CARTS_EMAIL_INTERVAL', $frequency ); //In minutes. Defines the interval at which e-mailing function is fired
if (!defined('MOCEANAPI_ABANDONED_CARTS_SMS_INTERVAL')) define( 'MOCEANAPI_ABANDONED_CARTS_SMS_INTERVAL', $sms_frequency );
if (!defined('MOCEANAPI_ABANDONED_CARTS_STILL_SHOPPING')) define( 'MOCEANAPI_ABANDONED_CARTS_STILL_SHOPPING', 60 ); //In minutes. Defines the time period after which an e-mail notice will be sent to admin and the cart is presumed abandoned
if (!defined('MOCEANAPI_ABANDONED_CARTS_NEW_NOTICE')) define( 'MOCEANAPI_ABANDONED_CARTS_NEW_NOTICE', 240 ); //Defining time in minutes how long New status is shown in the table


//Registering custom options
register_setting( 'moceanapi-abandoned-carts-settings', 'moceanapi_abandoned_carts_bulk_email_content' );
register_setting( 'moceanapi-abandoned-carts-settings', 'moceanapi_abandoned_carts_notification_email' );
register_setting( 'moceanapi-abandoned-carts-settings', 'moceanapi_abandoned_carts_email_content' );
register_setting( 'moceanapi-abandoned-carts-settings', 'moceanapi_abandoned_carts_notification_frequency' );
register_setting( 'moceanapi-abandoned-carts-settings', 'moceanapi_abandoned_carts_lift_email' );
register_setting( 'moceanapi-abandoned-carts-settings', 'moceanapi_abandoned_carts_hide_images' );
register_setting( 'moceanapi-abandoned-carts-settings', 'moceanapi_abandoned_carts_exclude_ghost_carts' );
register_setting( 'moceanapi-abandoned-carts-smssettings', 'moceanapi_abandoned_carts_bulk_content' );
register_setting( 'moceanapi-abandoned-carts-smssettings', 'moceanapi_abandoned_carts_key' );
register_setting( 'moceanapi-abandoned-carts-smssettings', 'moceanapi_abandoned_carts_secret' );
register_setting( 'moceanapi-abandoned-carts-smssettings', 'moceanapi_abandoned_carts_from' );
register_setting( 'moceanapi-abandoned-carts-smssettings', 'moceanapi_abandoned_carts_notification_sms' );
register_setting( 'moceanapi-abandoned-carts-smssettings', 'moceanapi_abandoned_carts_content' );
register_setting( 'moceanapi-abandoned-carts-smssettings', 'moceanapi_abandoned_carts_sms_notification_frequency' );
register_setting( 'moceanapi-abandoned-carts-settings-declined', 'moceanapi_abandoned_carts_times_review_declined' );
register_setting( 'moceanapi-abandoned-carts-settings-exit-intent', 'moceanapi_abandoned_carts_exit_intent_status' );
register_setting( 'moceanapi-abandoned-carts-settings-exit-intent', 'moceanapi_abandoned_carts_exit_intent_test_mode' );
register_setting( 'moceanapi-abandoned-carts-settings-exit-intent', 'moceanapi_abandoned_carts_exit_intent_type' );
register_setting( 'moceanapi-abandoned-carts-settings-exit-intent', 'moceanapi_abandoned_carts_exit_intent_main_color' );
register_setting( 'moceanapi-abandoned-carts-settings-exit-intent', 'moceanapi_abandoned_carts_exit_intent_inverse_color' );
register_setting( 'moceanapi-abandoned-carts-settings-exit-intent', 'moceanapi_abandoned_carts_exit_intent_image' );

/**
 * The code that runs during plugin activation.
 *
 *  
 */
function activate_moceanapi_abandoned_carts(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-moceanapi-abandoned-carts-activator.php';
	MoceanAPI_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 *  
 */
function deactivate_moceanapi_abandoned_carts(){
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-moceanapi-abandoned-carts-deactivator.php';
	MoceanAPI_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_moceanapi_abandoned_carts' );
register_deactivation_hook( __FILE__, 'deactivate_moceanapi_abandoned_carts' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 *
 *  
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-moceanapi-abandoned-carts.php';

/**
 * Begins execution of the plugin.
 *
 *  
 */
function run_moceanapi_abandoned_carts(){
	$plugin = new MoceanAPI();
	$plugin->run();
}
run_moceanapi_abandoned_carts();
