<?php
/**
 * Database schema, upgrades and data access for banners.
 *
 * @package SmartBanner
 */

defined( 'ABSPATH' ) || exit;

/**
 * Current database schema version.
 */
const SPB_DB_VERSION = '1.5';

/**
 * Allowed banner positions and their admin labels.
 *
 * @return array<string, string>
 */
function spb_positions() {
	return array(
		'top'          => __( 'Top of Site', 'smart-banner' ),
		'below-header' => __( 'Below Header', 'smart-banner' ),
		'footer'       => __( 'Above Footer', 'smart-banner' ),
	);
}

/**
 * Full name of the banners table.
 *
 * @return string
 */
function spb_table() {
	global $wpdb;
	return $wpdb->prefix . 'spb_banners';
}

/**
 * Whether the banners table exists.
 *
 * @return bool
 */
function spb_table_exists() {
	global $wpdb;
	$table = spb_table();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Schema check.
	return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
}

/**
 * Creates or updates the banners table with dbDelta().
 */
function spb_create_tables() {
	global $wpdb;
	$table           = spb_table();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		title varchar(255) DEFAULT '',
		content text,
		button_text varchar(255) DEFAULT '',
		button_url varchar(500) DEFAULT '',
		background_color varchar(20) DEFAULT '#f7b733',
		text_color varchar(20) DEFAULT '#1a1a1a',
		button_color varchar(20) DEFAULT '#e85d04',
		position varchar(50) DEFAULT 'top',
		sticky tinyint(1) DEFAULT 0,
		sort_order int(11) DEFAULT 0,
		countdown_enabled tinyint(1) DEFAULT 0,
		countdown_date datetime NULL,
		start_date datetime NULL,
		end_date datetime NULL,
		status tinyint(1) DEFAULT 1,
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Activation callback.
 *
 * Fresh installs are marked as up to date so the legacy timezone repair in
 * spb_maybe_upgrade() never runs on data saved by this version.
 */
function spb_activate() {
	$fresh = ! spb_table_exists();

	spb_create_tables();

	if ( $fresh ) {
		update_option( 'spb_db_version', SPB_DB_VERSION );
	}
}

/**
 * Upgrades the schema and repairs legacy data once per schema version.
 */
function spb_maybe_upgrade() {
	if ( get_option( 'spb_db_version' ) === SPB_DB_VERSION ) {
		return;
	}

	global $wpdb;
	$table = spb_table();
	$fresh = ! spb_table_exists();

	// dbDelta() creates the table or adds any missing columns.
	spb_create_tables();

	if ( ! $fresh ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time data repair.
		foreach ( array( 'start_date', 'end_date', 'countdown_date' ) as $col ) {
			// Repair 1: empty-string or zero dates become real NULLs (strict-mode safe).
			$wpdb->query( $wpdb->prepare( "UPDATE %i SET %i = NULL WHERE %i = '' OR %i = '0000-00-00 00:00:00'", $table, $col, $col, $col ) );
		}

		// Repair 2: versions before schema 1.5 stored dates in site time; shift them to UTC once.
		$offset_seconds = (int) ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		if ( 0 !== $offset_seconds ) {
			foreach ( array( 'start_date', 'end_date', 'countdown_date' ) as $col ) {
				$wpdb->query( $wpdb->prepare( 'UPDATE %i SET %i = DATE_SUB(%i, INTERVAL %d SECOND) WHERE %i IS NOT NULL', $table, $col, $col, $offset_seconds, $col ) );
			}
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	update_option( 'spb_db_version', SPB_DB_VERSION );
}
add_action( 'admin_init', 'spb_maybe_upgrade' );

/**
 * All banners, in display order.
 *
 * @return object[]
 */
function spb_get_banners() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, admin only.
	return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY sort_order ASC, id ASC', spb_table() ) );
}

/**
 * A single banner by ID.
 *
 * @param int $id Banner ID.
 * @return object|null
 */
function spb_get_banner( $id ) {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, admin only.
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', spb_table(), $id ) );
}

/**
 * Banners that are active and inside their schedule right now (UTC).
 *
 * Dismissed banners are filtered later, so the result is cacheable.
 *
 * @return object[]
 */
function spb_get_active_banners() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	if ( ! spb_table_exists() ) {
		$cache = array();
		return $cache;
	}

	global $wpdb;
	$now = current_time( 'mysql', true );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table, memoized per request.
	$cache = (array) $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i
			WHERE status = 1
				AND ( start_date IS NULL OR start_date <= %s )
				AND ( end_date IS NULL OR end_date >= %s )
			ORDER BY sort_order ASC, id ASC',
			spb_table(),
			$now,
			$now
		)
	);

	return $cache;
}

/**
 * Inserts or updates a banner.
 *
 * @param array $data Sanitized column values.
 * @param int   $id   Banner ID to update, or 0 to insert.
 * @return int Banner ID, or 0 on failure.
 */
function spb_save_banner( array $data, $id = 0 ) {
	global $wpdb;

	$formats = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d' );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom table writes.
	if ( $id ) {
		$ok = $wpdb->update( spb_table(), $data, array( 'id' => $id ), $formats, array( '%d' ) );
		return false === $ok ? 0 : (int) $id;
	}

	$data['sort_order'] = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(sort_order) FROM %i', spb_table() ) ) + 1;
	$formats[]          = '%d';

	return $wpdb->insert( spb_table(), $data, $formats ) ? (int) $wpdb->insert_id : 0;
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

/**
 * Deletes a banner.
 *
 * @param int $id Banner ID.
 */
function spb_delete_banner( $id ) {
	global $wpdb;
	$wpdb->delete( spb_table(), array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

/**
 * Toggles a banner between active and paused.
 *
 * @param int $id Banner ID.
 */
function spb_toggle_banner( $id ) {
	$banner = spb_get_banner( $id );

	if ( $banner ) {
		global $wpdb;
		$wpdb->update( spb_table(), array( 'status' => $banner->status ? 0 : 1 ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

/**
 * Saves a new display order.
 *
 * @param int[] $ids Banner IDs in the desired order.
 */
function spb_reorder_banners( array $ids ) {
	global $wpdb;

	foreach ( array_values( $ids ) as $i => $id ) {
		$wpdb->update( spb_table(), array( 'sort_order' => $i ), array( 'id' => $id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

/**
 * Converts a datetime-local value (site timezone) to a UTC MySQL datetime.
 *
 * @param string $raw Value like "2026-06-17T17:46".
 * @return string|null
 */
function spb_local_input_to_utc( $raw ) {
	$raw = trim( (string) $raw );

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $raw ) ) {
		return null;
	}

	$normalized = str_replace( 'T', ' ', $raw );
	if ( 16 === strlen( $normalized ) ) {
		$normalized .= ':00';
	}

	$utc = get_gmt_from_date( $normalized );

	return $utc ? $utc : null;
}

/**
 * Converts a stored UTC datetime to a datetime-local value in site time.
 *
 * @param string|null $utc UTC MySQL datetime.
 * @return string
 */
function spb_utc_to_local_input( $utc ) {
	if ( empty( $utc ) ) {
		return '';
	}

	return (string) get_date_from_gmt( $utc, 'Y-m-d\TH:i' );
}
