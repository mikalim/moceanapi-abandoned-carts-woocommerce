<?php

/**
 * Fired when the plugin is uninstalled.
 *
 *  
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' )){
	exit;
}

//Deletes database table and all it's data on uninstall
global $wpdb;
$cart_table = $wpdb->prefix . "moceanapi_abandoned_carts";

$wpdb->query( "DROP TABLE IF EXISTS $cart_table" );

//Removing Custom options created with the plugin
delete_option( 'moceanapi_abandoned_carts_bulk_content' );
delete_option( 'moceanapi_abandoned_carts_key' );
delete_option( 'moceanapi_abandoned_carts_secret' );
delete_option( 'moceanapi_abandoned_carts_from' );
delete_option( 'moceanapi_abandoned_carts_notification_sms' );
delete_option( 'moceanapi_abandoned_carts_content' );
delete_option( 'moceanapi_abandoned_carts_sms_notification_frequency' );
delete_option( 'moceanapi_abandoned_carts_bulk_email_content' );
delete_option( 'moceanapi_abandoned_carts_notification_email' );
delete_option( 'moceanapi_abandoned_carts_email_content' );
delete_option( 'moceanapi_abandoned_carts_notification_frequency' );
delete_option( 'moceanapi_abandoned_carts_exclude_ghost_carts' );
delete_option( 'moceanapi_abandoned_carts_version_number' );
delete_option( 'moceanapi_abandoned_carts_recoverable_cart_count' );
delete_option( 'moceanapi_abandoned_carts_ghost_cart_count' );
delete_option( 'moceanapi_abandoned_carts_times_review_declined' );
delete_option( 'moceanapi_abandoned_carts_exit_intent_status' );
delete_option( 'moceanapi_abandoned_carts_exit_intent_test_mode' );
delete_option( 'moceanapi_abandoned_carts_exit_intent_type' );
delete_option( 'moceanapi_abandoned_carts_exit_intent_main_color' );
delete_option( 'moceanapi_abandoned_carts_exit_intent_inverse_color' );
delete_option( 'moceanapi_abandoned_carts_exit_intent_image' );
delete_option( 'moceanapi_abandoned_carts_lift_email' );
delete_option( 'moceanapi_abandoned_carts_hide_images' );
delete_option( 'moceanapi_abandoned_carts_carts_per_page' );
delete_option( 'moceanapi_abandoned_carts_transferred_table' ); 

delete_metadata( 'user', 0, 'moceanapi_abandoned_carts_carts_per_page', '', true ); //Removes moceanapi_abandoned_carts_carts_per_page from wp_usermeta table on plugin uninstall