<?php
/**
 * Uninstall handler for Smart Banner.
 *
 * Deleting the plugin removes its banners table and options.
 *
 * @package SmartBanner
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Removing the plugin's own table.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'spb_banners' ) );

delete_option( 'spb_db_version' );
