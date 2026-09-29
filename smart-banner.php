<?php
/**
 * Plugin Name:       Smart Banner
 * Plugin URI:        https://github.com/lawrancebabu/smart-banner
 * Description:       Stackable site-wide announcement banners with scheduling, live countdowns, drag-to-reorder and remembered dismissals.
 * Version:           2.2.0
 * Author:            Lawrance Babu Gain
 * Author URI:        https://github.com/lawrancebabu
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Text Domain:       smart-banner
 * Domain Path:       /languages
 *
 * @package SmartBanner
 */

defined( 'ABSPATH' ) || exit;

define( 'SPB_VERSION', '2.2.0' );
define( 'SPB_PATH', plugin_dir_path( __FILE__ ) );
define( 'SPB_URL', plugin_dir_url( __FILE__ ) );

require_once SPB_PATH . 'includes/database.php';
require_once SPB_PATH . 'includes/cache.php';
require_once SPB_PATH . 'includes/frontend.php';

if ( is_admin() ) {
	require_once SPB_PATH . 'admin/admin-menu.php';
	add_action( 'admin_enqueue_scripts', 'spb_admin_assets' );
}

register_activation_hook( __FILE__, 'spb_activate' );
add_action( 'init', 'spb_load_textdomain' );

/**
 * Loads translations from the plugin's /languages folder.
 */
function spb_load_textdomain() {
	load_plugin_textdomain( 'smart-banner', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Enqueues admin assets on the plugin's own screens only.
 *
 * @param string $hook Current admin page hook suffix.
 */
function spb_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'spb-' ) ) {
		return;
	}

	wp_enqueue_style( 'spb-admin', SPB_URL . 'assets/css/admin.css', array(), SPB_VERSION );
	wp_enqueue_script( 'spb-admin', SPB_URL . 'assets/js/admin.js', array( 'jquery' ), SPB_VERSION, true );
	wp_localize_script(
		'spb-admin',
		'SmartBannerAdmin',
		array(
			'previewTitle'  => __( 'Banner Title', 'smart-banner' ),
			'unsaved'       => __( 'You have unsaved changes.', 'smart-banner' ),
			'confirmDelete' => __( 'Delete this banner permanently?', 'smart-banner' ),
		)
	);
}
