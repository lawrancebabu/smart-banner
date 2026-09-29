<?php
/**
 * Cache purging after banner changes.
 *
 * @package SmartBanner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Purges common caching layers so banner changes appear immediately.
 *
 * Each integration only runs when the matching plugin or host API is present.
 * Other code can hook into the `spb_cache_purged` action to add more.
 */
function spb_purge_known_caches() {
	// Object cache.
	wp_cache_flush();

	// WP Rocket.
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}

	// LiteSpeed Cache.
	if ( defined( 'LSCWP_V' ) ) {
		do_action( 'litespeed_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party hook.
	}

	// W3 Total Cache.
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}

	// WP Super Cache.
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}

	// SiteGround Optimizer.
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
	}

	// WP Engine.
	if ( class_exists( 'WpeCommon' ) ) {
		if ( method_exists( 'WpeCommon', 'purge_memcached' ) ) {
			WpeCommon::purge_memcached();
		}
		if ( method_exists( 'WpeCommon', 'purge_varnish_cache' ) ) {
			WpeCommon::purge_varnish_cache();
		}
	}

	/**
	 * Fires after Smart Banner purged the caches it knows about.
	 *
	 * Use it to purge a host or CDN cache that is not covered above.
	 */
	do_action( 'spb_cache_purged' );
}
