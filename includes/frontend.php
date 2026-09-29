<?php
/**
 * Front-end rendering.
 *
 * Banner markup is rendered in PHP (escaped), passed to a small footer script
 * and inserted at the right place in the theme's DOM (top of body, after the
 * header or before the footer), which works with any theme.
 *
 * @package SmartBanner
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'spb_enqueue_frontend' );

/**
 * IDs of banners the visitor dismissed (from the spb_dismissed cookie).
 *
 * @return int[]
 */
function spb_get_dismissed_ids() {
	if ( empty( $_COOKIE['spb_dismissed'] ) ) {
		return array();
	}

	$ids = json_decode( sanitize_text_field( wp_unslash( $_COOKIE['spb_dismissed'] ) ), true );

	return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
}

/**
 * Renders one banner.
 *
 * @param object $banner Banner row.
 * @return string
 */
function spb_render_banner_markup( $banner ) {
	$id      = (int) $banner->id;
	$bg      = sanitize_hex_color( (string) $banner->background_color );
	$txt     = sanitize_hex_color( (string) $banner->text_color );
	$btn_clr = sanitize_hex_color( (string) $banner->button_color );
	$has_cta = '' !== trim( (string) $banner->button_text );
	$has_cd  = ! empty( $banner->countdown_enabled ) && ! empty( $banner->countdown_date );

	$html  = '<div class="spb-banner" id="spb-banner-' . $id . '" data-id="' . $id . '" style="background:' . esc_attr( $bg ? $bg : '#f7b733' ) . ';color:' . esc_attr( $txt ? $txt : '#1a1a1a' ) . ';">';
	$html .= '<div class="spb-banner-inner"><div class="spb-banner-text">';

	if ( '' !== (string) $banner->title ) {
		$html .= '<div class="spb-banner-title">' . esc_html( $banner->title ) . '</div>';
	}
	if ( '' !== (string) $banner->content ) {
		$html .= '<div class="spb-banner-content">' . esc_html( $banner->content ) . '</div>';
	}
	$html .= '</div>';

	if ( $has_cd ) {
		$units = array(
			'd' => __( 'Days', 'smart-banner' ),
			'h' => __( 'Hours', 'smart-banner' ),
			'm' => __( 'Minutes', 'smart-banner' ),
			's' => __( 'Seconds', 'smart-banner' ),
		);

		$html .= '<div class="spb-countdown" data-target="' . esc_attr( (string) ( strtotime( $banner->countdown_date . ' UTC' ) * 1000 ) ) . '" role="timer">';
		foreach ( $units as $unit => $label ) {
			$html .= '<div class="spb-countdown-unit"><span class="spb-cd-num" data-unit="' . esc_attr( $unit ) . '">00</span><span class="spb-cd-label">' . esc_html( $label ) . '</span></div>';
		}
		$html .= '</div>';
	}

	if ( $has_cta ) {
		$html .= '<a href="' . esc_url( $banner->button_url ) . '" class="spb-banner-btn" style="background:' . esc_attr( $btn_clr ? $btn_clr : '#e85d04' ) . ';" target="_blank" rel="noopener">' . esc_html( $banner->button_text ) . '</a>';
	}

	$html .= '<button class="spb-close" type="button" data-spb-dismiss="' . $id . '" aria-label="' . esc_attr__( 'Dismiss banner', 'smart-banner' ) . '">&#215;</button>';
	$html .= '</div></div>';

	return $html;
}

/**
 * Builds the markup for each position group.
 *
 * @return array{markup: array<string, string>, sticky: bool}
 */
function spb_build_payload() {
	$dismissed = spb_get_dismissed_ids();
	$groups    = array_fill_keys( array_keys( spb_positions() ), '' );
	$sticky    = false;

	foreach ( spb_get_active_banners() as $banner ) {
		if ( in_array( (int) $banner->id, $dismissed, true ) ) {
			continue;
		}

		$pos             = isset( $groups[ $banner->position ] ) ? $banner->position : 'top';
		$groups[ $pos ] .= spb_render_banner_markup( $banner );

		if ( 'top' === $pos && ! empty( $banner->sticky ) ) {
			$sticky = true;
		}
	}

	return array(
		'markup' => array_filter( $groups ),
		'sticky' => $sticky,
	);
}

/**
 * Enqueues the front-end assets when there is at least one banner to show.
 */
function spb_enqueue_frontend() {
	if ( is_admin() ) {
		return;
	}

	$payload = spb_build_payload();

	if ( empty( $payload['markup'] ) ) {
		return;
	}

	wp_enqueue_style( 'spb-frontend', SPB_URL . 'assets/css/frontend.css', array(), SPB_VERSION );
	wp_enqueue_script( 'spb-frontend', SPB_URL . 'assets/js/frontend.js', array(), SPB_VERSION, true );
	wp_localize_script( 'spb-frontend', 'SmartBanner', $payload );
}
