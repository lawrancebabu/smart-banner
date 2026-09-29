<?php
/**
 * Admin menu and request handling.
 *
 * All state-changing actions are handled on the page's load-* hook, before
 * any output, and finish with a redirect (Post/Redirect/Get).
 *
 * @package SmartBanner
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'spb_admin_menu' );

/**
 * Registers the Smart Banner menu and its load handler.
 */
function spb_admin_menu() {
	$hook = add_menu_page(
		__( 'Smart Banner', 'smart-banner' ),
		__( 'Smart Banner', 'smart-banner' ),
		'manage_options',
		'spb-banners',
		'spb_banner_page',
		'dashicons-megaphone',
		30
	);

	add_submenu_page(
		'spb-banners',
		__( 'Banners', 'smart-banner' ),
		__( 'Banners', 'smart-banner' ),
		'manage_options',
		'spb-banners',
		'spb_banner_page'
	);

	add_submenu_page(
		'spb-banners',
		__( 'Diagnostics', 'smart-banner' ),
		__( 'Diagnostics', 'smart-banner' ),
		'manage_options',
		'spb-debug',
		'spb_debug_page'
	);

	add_action( 'load-' . $hook, 'spb_handle_admin_actions' );
}

/**
 * Renders the banners screen.
 */
function spb_banner_page() {
	include SPB_PATH . 'admin/banners-page.php';
}

/**
 * Renders the diagnostics screen.
 */
function spb_debug_page() {
	include SPB_PATH . 'admin/debug-page.php';
}

/**
 * Redirects back to the banners screen with a notice code.
 *
 * @param string $notice Notice code.
 * @param array  $args   Extra query args.
 */
function spb_redirect_with_notice( $notice, array $args = array() ) {
	$args['page']       = 'spb-banners';
	$args['spb_notice'] = $notice;

	wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
	exit;
}

/**
 * Handles create, update, delete, toggle, reorder and cache purge requests.
 */
function spb_handle_admin_actions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Each branch verifies its own nonce.
	if ( isset( $_GET['delete'] ) ) {
		$id = absint( $_GET['delete'] );
		check_admin_referer( 'spb_delete_' . $id );
		spb_delete_banner( $id );
		spb_purge_known_caches();
		spb_redirect_with_notice( 'deleted' );
	}

	if ( isset( $_GET['toggle'] ) ) {
		$id = absint( $_GET['toggle'] );
		check_admin_referer( 'spb_toggle_' . $id );
		spb_toggle_banner( $id );
		spb_purge_known_caches();
		spb_redirect_with_notice( 'toggled' );
	}

	if ( isset( $_GET['purge_cache'] ) ) {
		check_admin_referer( 'spb_purge_cache' );
		spb_purge_known_caches();
		spb_redirect_with_notice( 'purged' );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( isset( $_POST['spb_reorder'] ) ) {
		check_admin_referer( 'spb_reorder' );
		$order = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';
		spb_reorder_banners( array_filter( array_map( 'absint', explode( ',', $order ) ) ) );
		spb_purge_known_caches();
		spb_redirect_with_notice( 'reordered' );
	}

	if ( isset( $_POST['save_banner'] ) ) {
		check_admin_referer( 'spb_save_banner' );

		$id   = isset( $_POST['banner_id'] ) ? absint( $_POST['banner_id'] ) : 0;
		$data = spb_sanitize_banner_input( wp_unslash( $_POST ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized field by field in spb_sanitize_banner_input().

		if ( $id && ! spb_get_banner( $id ) ) {
			$id = 0;
		}

		$saved_id = spb_save_banner( $data, $id );
		spb_purge_known_caches();

		if ( ! $saved_id ) {
			spb_redirect_with_notice( 'error' );
		}

		spb_redirect_with_notice( $id ? 'updated' : 'created', $id ? array( 'edit' => $saved_id ) : array() );
	}
}

/**
 * Sanitizes the banner form submission into column values.
 *
 * @param array $input Unslashed $_POST data.
 * @return array
 */
function spb_sanitize_banner_input( array $input ) {
	$cd_enabled = empty( $input['countdown_enabled'] ) ? 0 : 1;
	$position   = sanitize_key( $input['position'] ?? 'top' );

	return array(
		'title'             => sanitize_text_field( $input['title'] ?? '' ),
		'content'           => sanitize_textarea_field( $input['content'] ?? '' ),
		'button_text'       => sanitize_text_field( $input['button_text'] ?? '' ),
		'button_url'        => esc_url_raw( $input['button_url'] ?? '' ),
		'background_color'  => sanitize_hex_color( $input['background_color'] ?? '' ),
		'text_color'        => sanitize_hex_color( $input['text_color'] ?? '' ),
		'button_color'      => sanitize_hex_color( $input['button_color'] ?? '' ),
		'position'          => array_key_exists( $position, spb_positions() ) ? $position : 'top',
		'sticky'            => empty( $input['sticky'] ) ? 0 : 1,
		'countdown_enabled' => $cd_enabled,
		'countdown_date'    => $cd_enabled ? spb_local_input_to_utc( $input['countdown_date'] ?? '' ) : null,
		'start_date'        => spb_local_input_to_utc( $input['start_date'] ?? '' ),
		'end_date'          => spb_local_input_to_utc( $input['end_date'] ?? '' ),
		'status'            => empty( $input['status'] ) ? 0 : 1,
	);
}
