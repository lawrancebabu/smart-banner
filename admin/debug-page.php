<?php
/**
 * Diagnostics admin screen. Read-only: it never changes banner data.
 *
 * @package SmartBanner
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

global $wpdb;

$spb_table   = spb_table();
$spb_status  = function ( $ok ) {
	return $ok
		? '<span class="dashicons dashicons-yes-alt" style="color:#16a34a"></span> '
		: '<span class="dashicons dashicons-warning" style="color:#dc2626"></span> ';
};
$spb_allowed = array(
	'span' => array(
		'class' => true,
		'style' => true,
	),
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Smart Banner Diagnostics', 'smart-banner' ); ?></h1>
	<p><?php esc_html_e( 'Checks the database, schedule and front-end output to explain why a banner is or is not showing.', 'smart-banner' ); ?></p>

	<h2><?php esc_html_e( '1. Database', 'smart-banner' ); ?></h2>
	<?php
	$spb_exists = spb_table_exists();
	echo wp_kses( $spb_status( $spb_exists ), $spb_allowed );
	/* translators: %s: database table name. */
	echo esc_html( sprintf( $spb_exists ? __( 'Table %s exists.', 'smart-banner' ) : __( 'Table %s is missing. Deactivate and reactivate the plugin.', 'smart-banner' ), $spb_table ) );
	echo '<br>';
	/* translators: 1: stored schema version, 2: expected schema version. */
	echo esc_html( sprintf( __( 'Schema version: %1$s (expected %2$s)', 'smart-banner' ), (string) get_option( 'spb_db_version', '-' ), SPB_DB_VERSION ) );
	?>

	<?php if ( $spb_exists ) : ?>
		<h2><?php esc_html_e( '2. Banners and schedule', 'smart-banner' ); ?></h2>
		<p>
			<?php
			/* translators: %s: current UTC time. */
			echo esc_html( sprintf( __( 'Now (UTC): %s', 'smart-banner' ), current_time( 'mysql', true ) ) );
			echo '<br>';
			/* translators: 1: current site time, 2: site timezone. */
			echo esc_html( sprintf( __( 'Now (site time): %1$s, %2$s', 'smart-banner' ), current_time( 'mysql' ), wp_timezone_string() ) );
			?>
		</p>
		<?php
		$spb_all       = spb_get_banners();
		$spb_active    = wp_list_pluck( spb_get_active_banners(), 'id' );
		$spb_dismissed = spb_get_dismissed_ids();
		?>
		<table class="widefat striped" style="max-width:900px">
			<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Title', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Status', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Start (site time)', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'End (site time)', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Showing now?', 'smart-banner' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! $spb_all ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No banners yet.', 'smart-banner' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $spb_all as $spb_b ) : ?>
					<?php
					$spb_live = in_array( $spb_b->id, $spb_active, true );
					if ( ! $spb_b->status ) {
						$spb_reason = __( 'No: paused', 'smart-banner' );
					} elseif ( ! $spb_live ) {
						$spb_reason = __( 'No: outside schedule', 'smart-banner' );
					} elseif ( in_array( (int) $spb_b->id, $spb_dismissed, true ) ) {
						$spb_reason = __( 'Yes (dismissed in your browser)', 'smart-banner' );
					} else {
						$spb_reason = __( 'Yes', 'smart-banner' );
					}
					?>
					<tr>
						<td><?php echo esc_html( (string) $spb_b->id ); ?></td>
						<td><?php echo esc_html( $spb_b->title ); ?></td>
						<td><?php $spb_b->status ? esc_html_e( 'Active', 'smart-banner' ) : esc_html_e( 'Paused', 'smart-banner' ); ?></td>
						<td><?php echo esc_html( $spb_b->start_date ? get_date_from_gmt( $spb_b->start_date, 'Y-m-d H:i' ) : '-' ); ?></td>
						<td><?php echo esc_html( $spb_b->end_date ? get_date_from_gmt( $spb_b->end_date, 'Y-m-d H:i' ) : '-' ); ?></td>
						<td><?php echo wp_kses( $spb_status( $spb_live ), $spb_allowed ) . esc_html( $spb_reason ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h2><?php esc_html_e( '3. Front-end output', 'smart-banner' ); ?></h2>
	<?php
	$spb_resp = wp_remote_get(
		add_query_arg( 'spb_cache_bust', time(), home_url( '/' ) ),
		array(
			'timeout' => 15,
			'headers' => array( 'Cache-Control' => 'no-cache' ),
		)
	);

	if ( is_wp_error( $spb_resp ) ) {
		echo wp_kses( $spb_status( false ), $spb_allowed );
		/* translators: %s: error message. */
		echo esc_html( sprintf( __( 'Could not fetch the homepage: %s', 'smart-banner' ), $spb_resp->get_error_message() ) );
	} else {
		$spb_body   = wp_remote_retrieve_body( $spb_resp );
		$spb_loaded = false !== strpos( $spb_body, 'assets/js/frontend.js' );
		/* translators: %d: HTTP status code. */
		echo esc_html( sprintf( __( 'Homepage HTTP status: %d', 'smart-banner' ), (int) wp_remote_retrieve_response_code( $spb_resp ) ) ) . '<br>';
		echo wp_kses( $spb_status( $spb_loaded || ! $spb_active ), $spb_allowed );
		if ( $spb_loaded ) {
			esc_html_e( 'Banner script is present on a cache-busted homepage request.', 'smart-banner' );
		} elseif ( ! $spb_active ) {
			esc_html_e( 'No banner is scheduled right now, so no script is expected.', 'smart-banner' );
		} else {
			esc_html_e( 'A banner is active but the script is missing. Check that the theme calls wp_head() and wp_footer().', 'smart-banner' );
		}

		$spb_headers = wp_remote_retrieve_headers( $spb_resp );
		$spb_lines   = array();
		foreach ( array( 'x-cache', 'cf-cache-status', 'age', 'cache-control', 'x-proxy-cache', 'x-sg-cache', 'server' ) as $spb_h ) {
			if ( isset( $spb_headers[ $spb_h ] ) ) {
				$spb_lines[] = $spb_h . ': ' . ( is_array( $spb_headers[ $spb_h ] ) ? implode( ', ', $spb_headers[ $spb_h ] ) : $spb_headers[ $spb_h ] );
			}
		}
		if ( $spb_lines ) {
			echo '<p>' . esc_html__( 'Cache-related response headers:', 'smart-banner' ) . '</p><pre style="background:#f6f7f7;padding:10px;max-width:900px;">' . esc_html( implode( "\n", $spb_lines ) ) . '</pre>';
		}
	}
	?>

	<h2><?php esc_html_e( '4. Possible conflicts', 'smart-banner' ); ?></h2>
	<?php
	$spb_suspects = array_filter(
		(array) get_option( 'active_plugins', array() ),
		function ( $p ) {
			return (bool) preg_match( '/cache|minif|optimi|speed|autoptimize|rocket|litespeed|w3-total|fastest|hummingbird/i', $p );
		}
	);

	if ( $spb_suspects ) {
		echo wp_kses( $spb_status( false ), $spb_allowed );
		esc_html_e( 'Caching or optimization plugins detected. Purge their cache after changing banners:', 'smart-banner' );
		echo '<ul style="list-style:disc;margin-left:20px">';
		foreach ( $spb_suspects as $spb_p ) {
			echo '<li><code>' . esc_html( $spb_p ) . '</code></li>';
		}
		echo '</ul>';
	} else {
		echo wp_kses( $spb_status( true ), $spb_allowed );
		esc_html_e( 'No common caching or minification plugins detected.', 'smart-banner' );
	}
	?>
</div>
