<?php
/**
 * Banners admin screen (view). Requests are handled in spb_handle_admin_actions().
 *
 * @package SmartBanner
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view parameters.
$spb_edit_id     = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
$spb_notice_code = isset( $_GET['spb_notice'] ) ? sanitize_key( $_GET['spb_notice'] ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$spb_edit       = $spb_edit_id ? spb_get_banner( $spb_edit_id ) : null;
$spb_is_editing = (bool) $spb_edit;
$spb_banners    = spb_get_banners();
$spb_total      = count( $spb_banners );
$spb_active     = count(
	array_filter(
		$spb_banners,
		function ( $b ) {
			return 1 === (int) $b->status;
		}
	)
);
$spb_positions  = spb_positions();
$spb_icons      = array(
	'top'          => 'top',
	'below-header' => 'mid',
	'footer'       => 'bot',
);

$spb_notices = array(
	'created'   => __( 'Banner created.', 'smart-banner' ),
	'updated'   => __( 'Banner updated.', 'smart-banner' ),
	'deleted'   => __( 'Banner deleted.', 'smart-banner' ),
	'toggled'   => __( 'Banner status changed.', 'smart-banner' ),
	'reordered' => __( 'Banner order saved.', 'smart-banner' ),
	'purged'    => __( 'Cache purge triggered across all detected caching layers.', 'smart-banner' ),
	'error'     => __( 'The banner could not be saved.', 'smart-banner' ),
);

$spb_value = function ( $field, $fallback = '' ) use ( $spb_edit ) {
	return ( $spb_edit && isset( $spb_edit->$field ) && '' !== (string) $spb_edit->$field ) ? $spb_edit->$field : $fallback;
};
?>
<div class="spb-wrap">

	<div class="spb-page-header" style="justify-content:space-between;">
		<div style="display:flex;align-items:center;gap:12px;">
			<span class="dashicons dashicons-megaphone"></span>
			<div>
				<h1><?php esc_html_e( 'Smart Banner', 'smart-banner' ); ?></h1>
				<p><?php esc_html_e( 'Stack multiple banners with live countdowns, scheduling and smart dismiss.', 'smart-banner' ); ?></p>
			</div>
		</div>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=spb-banners&purge_cache=1' ), 'spb_purge_cache' ) ); ?>" class="spb-btn spb-btn-ghost" style="white-space:nowrap;">
			<span class="dashicons dashicons-update" style="font-size:14px;width:14px;height:14px;"></span>
			<?php esc_html_e( 'Purge Cache', 'smart-banner' ); ?>
		</a>
	</div>

	<?php if ( isset( $spb_notices[ $spb_notice_code ] ) ) : ?>
		<?php $spb_type = 'error' === $spb_notice_code ? 'error' : 'success'; ?>
		<div class="spb-notice <?php echo esc_attr( $spb_type ); ?>" role="status">
			<span class="dashicons dashicons-<?php echo esc_attr( 'success' === $spb_type ? 'yes-alt' : 'warning' ); ?>"></span>
			<?php echo esc_html( $spb_notices[ $spb_notice_code ] ); ?>
		</div>
	<?php endif; ?>

	<div class="spb-stats">
		<div class="spb-stat">
			<div class="spb-stat-val"><?php echo esc_html( number_format_i18n( $spb_total ) ); ?></div>
			<div class="spb-stat-label"><?php esc_html_e( 'Total', 'smart-banner' ); ?></div>
		</div>
		<div class="spb-stat">
			<div class="spb-stat-val" style="color:var(--spb-success)"><?php echo esc_html( number_format_i18n( $spb_active ) ); ?></div>
			<div class="spb-stat-label"><?php esc_html_e( 'Active', 'smart-banner' ); ?></div>
		</div>
		<div class="spb-stat">
			<div class="spb-stat-val" style="color:var(--spb-muted)"><?php echo esc_html( number_format_i18n( $spb_total - $spb_active ) ); ?></div>
			<div class="spb-stat-label"><?php esc_html_e( 'Inactive', 'smart-banner' ); ?></div>
		</div>
	</div>

	<div class="spb-layout">

		<div class="spb-card">
			<div class="spb-card-header">
				<span class="dashicons dashicons-<?php echo esc_attr( $spb_is_editing ? 'edit' : 'plus-alt' ); ?>"></span>
				<h2><?php $spb_is_editing ? esc_html_e( 'Edit Banner', 'smart-banner' ) : esc_html_e( 'Create Banner', 'smart-banner' ); ?></h2>
			</div>
			<div class="spb-card-body">

				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=spb-banners' . ( $spb_is_editing ? '&edit=' . $spb_edit_id : '' ) ) ); ?>">
					<?php wp_nonce_field( 'spb_save_banner' ); ?>
					<input type="hidden" name="banner_id" value="<?php echo esc_attr( $spb_is_editing ? $spb_edit_id : 0 ); ?>">

					<div class="spb-section-label"><?php esc_html_e( 'Content', 'smart-banner' ); ?></div>

					<div class="spb-field">
						<label for="spb-title"><?php esc_html_e( 'Banner Title', 'smart-banner' ); ?></label>
						<input id="spb-title" type="text" name="title" value="<?php echo esc_attr( $spb_value( 'title' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. DEAL OF THE WEEK: 20% OFF everything', 'smart-banner' ); ?>">
					</div>

					<div class="spb-field">
						<label for="spb-content"><?php esc_html_e( 'Sub-message', 'smart-banner' ); ?></label>
						<textarea id="spb-content" name="content" rows="2" placeholder="<?php esc_attr_e( 'Optional secondary text shown beside the title', 'smart-banner' ); ?>"><?php echo esc_textarea( $spb_value( 'content' ) ); ?></textarea>
					</div>

					<div class="spb-section-label"><?php esc_html_e( 'Call-to-Action', 'smart-banner' ); ?></div>

					<div class="spb-field-row">
						<div class="spb-field">
							<label for="spb-btn-text"><?php esc_html_e( 'Button Label', 'smart-banner' ); ?></label>
							<input id="spb-btn-text" type="text" name="button_text" value="<?php echo esc_attr( $spb_value( 'button_text' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. Shop Now', 'smart-banner' ); ?>">
						</div>
						<div class="spb-field">
							<label for="spb-btn-url"><?php esc_html_e( 'Button URL', 'smart-banner' ); ?></label>
							<input id="spb-btn-url" type="url" name="button_url" value="<?php echo esc_url( $spb_value( 'button_url' ) ); ?>" placeholder="https://">
						</div>
					</div>

					<div class="spb-section-label"><?php esc_html_e( 'Colors', 'smart-banner' ); ?></div>

					<div class="spb-color-row">
						<div class="spb-color-item">
							<label for="spb-bg-color"><?php esc_html_e( 'Background', 'smart-banner' ); ?></label>
							<input id="spb-bg-color" type="color" name="background_color" value="<?php echo esc_attr( $spb_value( 'background_color', '#f7b733' ) ); ?>">
						</div>
						<div class="spb-color-item">
							<label for="spb-text-color"><?php esc_html_e( 'Text', 'smart-banner' ); ?></label>
							<input id="spb-text-color" type="color" name="text_color" value="<?php echo esc_attr( $spb_value( 'text_color', '#1a1a1a' ) ); ?>">
						</div>
						<div class="spb-color-item">
							<label for="spb-button-color"><?php esc_html_e( 'Button', 'smart-banner' ); ?></label>
							<input id="spb-button-color" type="color" name="button_color" value="<?php echo esc_attr( $spb_value( 'button_color', '#e85d04' ) ); ?>">
						</div>
					</div>

					<div class="spb-section-label" style="margin-top:20px;"><?php esc_html_e( 'Countdown Timer', 'smart-banner' ); ?></div>

					<div class="spb-toggle-row">
						<div class="spb-toggle-label">
							<?php esc_html_e( 'Enable countdown', 'smart-banner' ); ?>
							<small><?php esc_html_e( 'Shows Days / Hours / Minutes / Seconds until deadline', 'smart-banner' ); ?></small>
						</div>
						<label class="spb-switch">
							<input type="checkbox" name="countdown_enabled" id="spb-cd-toggle" value="1" <?php checked( (bool) $spb_value( 'countdown_enabled', 0 ) ); ?>>
							<span class="slider"></span>
						</label>
					</div>

					<div class="spb-field spb-cd-date-wrap" id="spb-cd-date-wrap" style="display:<?php echo $spb_value( 'countdown_enabled', 0 ) ? 'block' : 'none'; ?>;margin-top:10px;">
						<label for="spb-cd-date"><?php esc_html_e( 'Countdown Target Date & Time', 'smart-banner' ); ?></label>
						<input id="spb-cd-date" type="datetime-local" name="countdown_date" value="<?php echo esc_attr( spb_utc_to_local_input( $spb_value( 'countdown_date' ) ) ); ?>">
					</div>

					<div class="spb-section-label" style="margin-top:20px;"><?php esc_html_e( 'Position', 'smart-banner' ); ?></div>

					<div class="spb-position-picker">
						<?php foreach ( $spb_positions as $spb_pos => $spb_label ) : ?>
						<div>
							<input class="spb-position-option" type="radio" name="position" id="pos-<?php echo esc_attr( $spb_pos ); ?>" value="<?php echo esc_attr( $spb_pos ); ?>" <?php checked( $spb_value( 'position', 'top' ), $spb_pos ); ?>>
							<label for="pos-<?php echo esc_attr( $spb_pos ); ?>">
								<div class="spb-pos-icon"><div class="bar <?php echo esc_attr( $spb_icons[ $spb_pos ] ); ?>"></div></div>
								<?php echo esc_html( $spb_label ); ?>
							</label>
						</div>
						<?php endforeach; ?>
					</div>

					<div class="spb-section-label" style="margin-top:20px;">
						<?php
						/* translators: %s: site timezone, e.g. "Asia/Dhaka". */
						printf( esc_html__( 'Schedule (%s)', 'smart-banner' ), esc_html( wp_timezone_string() ) );
						?>
					</div>

					<div class="spb-date-row">
						<div class="spb-field" style="margin:0">
							<label for="spb-start"><?php esc_html_e( 'Start', 'smart-banner' ); ?></label>
							<input id="spb-start" type="datetime-local" name="start_date" value="<?php echo esc_attr( spb_utc_to_local_input( $spb_value( 'start_date' ) ) ); ?>">
						</div>
						<div class="spb-field" style="margin:0">
							<label for="spb-end"><?php esc_html_e( 'End', 'smart-banner' ); ?></label>
							<input id="spb-end" type="datetime-local" name="end_date" value="<?php echo esc_attr( spb_utc_to_local_input( $spb_value( 'end_date' ) ) ); ?>">
						</div>
					</div>

					<div class="spb-section-label" style="margin-top:20px;"><?php esc_html_e( 'Display', 'smart-banner' ); ?></div>

					<div class="spb-toggle-row">
						<div class="spb-toggle-label">
							<?php esc_html_e( 'Active', 'smart-banner' ); ?>
							<small><?php esc_html_e( 'Banner is live on the site', 'smart-banner' ); ?></small>
						</div>
						<label class="spb-switch">
							<input type="hidden" name="status" value="0">
							<input type="checkbox" name="status" value="1" <?php checked( $spb_edit ? (int) $spb_edit->status : 1, 1 ); ?>>
							<span class="slider"></span>
						</label>
					</div>

					<div class="spb-toggle-row">
						<div class="spb-toggle-label">
							<?php esc_html_e( 'Sticky (fixed to top while scrolling)', 'smart-banner' ); ?>
							<small><?php esc_html_e( 'Applies to the whole "Top of Site" stack', 'smart-banner' ); ?></small>
						</div>
						<label class="spb-switch">
							<input type="checkbox" name="sticky" value="1" <?php checked( (bool) $spb_value( 'sticky', 0 ) ); ?>>
							<span class="slider"></span>
						</label>
					</div>

					<div class="spb-submit-row">
						<button type="submit" name="save_banner" value="1" class="spb-btn spb-btn-primary">
							<span class="dashicons dashicons-saved" style="font-size:15px;width:15px;height:15px;"></span>
							<?php $spb_is_editing ? esc_html_e( 'Update Banner', 'smart-banner' ) : esc_html_e( 'Publish Banner', 'smart-banner' ); ?>
						</button>
						<?php if ( $spb_is_editing ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=spb-banners' ) ); ?>" class="spb-btn spb-btn-ghost"><?php esc_html_e( 'Cancel', 'smart-banner' ); ?></a>
						<?php endif; ?>
					</div>

				</form>
			</div>
		</div>

		<div>
			<div class="spb-card" style="position:sticky;top:32px;">
				<div class="spb-card-header">
					<span class="dashicons dashicons-visibility"></span>
					<h2><?php esc_html_e( 'Live Preview', 'smart-banner' ); ?></h2>
				</div>
				<div class="spb-card-body" style="padding:0">
					<div class="spb-preview-shell">
						<div class="spb-preview-chrome">
							<div class="spb-chrome-dot" style="background:#ef4444"></div>
							<div class="spb-chrome-dot" style="background:#f59e0b"></div>
							<div class="spb-chrome-dot" style="background:#10b981"></div>
							<div style="flex:1;background:#fff;border-radius:4px;height:18px;margin-left:6px;opacity:.5;"></div>
						</div>
						<div class="spb-preview-body">
							<div id="spb-preview-bar" style="background:#f7b733;color:#1a1a1a;padding:10px 16px;text-align:center;position:relative;">
								<span style="position:absolute;right:10px;top:8px;font-size:16px;opacity:.6;" aria-hidden="true">&#215;</span>
								<div style="display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;">
									<div>
										<div id="spb-preview-title" style="font-size:13px;font-weight:800;text-transform:uppercase;"><?php esc_html_e( 'Banner Title', 'smart-banner' ); ?></div>
										<div id="spb-preview-sub" style="font-size:11.5px;opacity:.8;"></div>
									</div>
									<div id="spb-preview-countdown" style="display:none;background:rgba(0,0,0,.22);border-radius:6px;padding:3px 8px;">
										<div style="display:flex;gap:4px;align-items:center;">
											<?php
											$spb_units = array(
												'days'    => __( 'Days', 'smart-banner' ),
												'hours'   => __( 'Hrs', 'smart-banner' ),
												'minutes' => __( 'Min', 'smart-banner' ),
												'seconds' => __( 'Sec', 'smart-banner' ),
											);
											foreach ( $spb_units as $spb_unit => $spb_unit_label ) :
												?>
											<div style="text-align:center;min-width:26px;padding:0 3px;font-size:11px;">
												<div id="spb-prev-cd-<?php echo esc_attr( $spb_unit ); ?>" style="font-size:15px;font-weight:900;line-height:1;">00</div>
												<div style="font-size:8px;opacity:.75;text-transform:uppercase;"><?php echo esc_html( $spb_unit_label ); ?></div>
											</div>
											<?php endforeach; ?>
										</div>
									</div>
									<a href="#" id="spb-preview-btn" style="display:none;background:#e85d04;color:#fff;padding:6px 13px;border-radius:5px;font-size:11.5px;font-weight:800;text-decoration:none;">CTA</a>
								</div>
							</div>
							<div style="background:#fff;margin:10px;border-radius:6px;height:55px;opacity:.35;"></div>
							<div style="background:#fff;margin:10px;border-radius:6px;height:28px;opacity:.2;"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>

	<div class="spb-card spb-table-card">
		<div class="spb-table-header">
			<h2><?php esc_html_e( 'All Banners', 'smart-banner' ); ?> <small style="font-weight:400;color:var(--spb-muted);font-size:12px;"><?php esc_html_e( 'drag to reorder', 'smart-banner' ); ?></small></h2>
			<span style="font-size:12px;color:var(--spb-muted);">
				<?php
				/* translators: %s: number of banners. */
				echo esc_html( sprintf( _n( '%s banner', '%s banners', $spb_total, 'smart-banner' ), number_format_i18n( $spb_total ) ) );
				?>
			</span>
		</div>

		<?php if ( $spb_banners ) : ?>
		<form id="spb-reorder-form" method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=spb-banners' ) ); ?>">
			<?php wp_nonce_field( 'spb_reorder' ); ?>
			<input type="hidden" name="spb_reorder" value="1">
			<input type="hidden" name="order" id="spb-order-input" value="">
		</form>

		<table class="spb-table" id="spb-sortable-table">
			<thead>
				<tr>
					<th style="width:30px;"><span class="screen-reader-text"><?php esc_html_e( 'Order', 'smart-banner' ); ?></span></th>
					<th><?php esc_html_e( 'Title', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Colors', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Position', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Countdown', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Status', 'smart-banner' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'smart-banner' ); ?></th>
				</tr>
			</thead>
			<tbody id="spb-sortable-body">
				<?php foreach ( $spb_banners as $spb_b ) : ?>
					<?php $spb_id = (int) $spb_b->id; ?>
				<tr data-id="<?php echo esc_attr( $spb_id ); ?>">
					<td class="spb-drag-handle" title="<?php esc_attr_e( 'Drag to reorder', 'smart-banner' ); ?>">&#10303;</td>
					<td>
						<strong><?php echo esc_html( $spb_b->title ); ?></strong>
						<?php if ( $spb_b->content ) : ?>
						<div style="font-size:11.5px;color:var(--spb-muted);margin-top:2px;"><?php echo esc_html( wp_html_excerpt( $spb_b->content, 60, '...' ) ); ?></div>
						<?php endif; ?>
					</td>
					<td>
						<span class="spb-swatch" style="background:<?php echo esc_attr( (string) $spb_b->background_color ); ?>"></span>
						<span class="spb-swatch" style="background:<?php echo esc_attr( (string) $spb_b->text_color ); ?>"></span>
						<span class="spb-swatch" style="background:<?php echo esc_attr( (string) $spb_b->button_color ); ?>"></span>
					</td>
					<td style="font-size:12px;color:var(--spb-muted);">
						<?php echo esc_html( $spb_positions[ $spb_b->position ] ?? $spb_b->position ); ?>
					</td>
					<td>
						<?php if ( ! empty( $spb_b->countdown_enabled ) && ! empty( $spb_b->countdown_date ) ) : ?>
							<span class="spb-badge" style="background:#eff6ff;color:#1d4ed8;">
								<?php echo esc_html( get_date_from_gmt( $spb_b->countdown_date, 'M j, Y g:i A' ) ); ?>
							</span>
						<?php else : ?>
							<span style="color:var(--spb-muted);font-size:12px;"><?php esc_html_e( 'Off', 'smart-banner' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<span class="spb-badge <?php echo $spb_b->status ? 'active' : 'inactive'; ?>">
							<?php $spb_b->status ? esc_html_e( 'Active', 'smart-banner' ) : esc_html_e( 'Inactive', 'smart-banner' ); ?>
						</span>
					</td>
					<td>
						<div class="spb-actions">
							<a class="spb-action-btn <?php echo $spb_b->status ? '' : 'success'; ?>" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=spb-banners&toggle=' . $spb_id ), 'spb_toggle_' . $spb_id ) ); ?>">
								<?php $spb_b->status ? esc_html_e( 'Pause', 'smart-banner' ) : esc_html_e( 'Enable', 'smart-banner' ); ?>
							</a>
							<a class="spb-action-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=spb-banners&edit=' . $spb_id ) ); ?>"><?php esc_html_e( 'Edit', 'smart-banner' ); ?></a>
							<a class="spb-action-btn danger spb-delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=spb-banners&delete=' . $spb_id ), 'spb_delete_' . $spb_id ) ); ?>"><?php esc_html_e( 'Delete', 'smart-banner' ); ?></a>
						</div>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
		<div class="spb-empty">
			<span class="dashicons dashicons-megaphone"></span>
			<?php esc_html_e( 'No banners yet. Create your first one above.', 'smart-banner' ); ?>
		</div>
		<?php endif; ?>
	</div>

</div>
