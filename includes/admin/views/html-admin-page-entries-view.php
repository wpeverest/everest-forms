<?php
/**
 * Admin View: Entries
 *
 * @package EverestForms/Admin/Entries/Views
 */

defined( 'ABSPATH' ) || exit;

$form_id    = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$entry_id   = isset( $_GET['view-entry'] ) ? absint( $_GET['view-entry'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$entry      = evf_get_entry( $entry_id, true );
$form_data  = evf()->form->get( $form_id, array( 'content_only' => true ) );
$hide_empty = isset( $_COOKIE['everest_forms_entry_hide_empty'] ) && 'true' === $_COOKIE['everest_forms_entry_hide_empty'];
$trash_link = wp_nonce_url(
	add_query_arg(
		array(
			'trash' => $entry_id,
		),
		admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $form_id )
	),
	'trash-entry'
);

$form_entries = evf_get_entries_by_form_id( $form_id, '', '', true );
$form_entries = array_map(
	function ( $el ) {
		return $el['entry_id'];
	},
	$form_entries
);

$entry_index    = array_search( $entry_id, $form_entries ); //phpcs:ignore
$prev_entry     = '';
$next_entry     = '';
$prev_entry_url = '#';
$next_entry_url = '#';

if ( false !== $entry_index ) {
	if ( isset( $form_entries[ $entry_index - 1 ] ) ) {
		$prev_entry     = $form_entries[ $entry_index - 1 ];
		$prev_entry_url = admin_url( sprintf( 'admin.php?page=evf-entries&amp;form_id=%d&amp;view-entry=%d', $form_id, $prev_entry ) );
	}

	if ( isset( $form_entries[ $entry_index + 1 ] ) ) {
		$next_entry     = $form_entries[ $entry_index + 1 ];
		$next_entry_url = admin_url( sprintf( 'admin.php?page=evf-entries&amp;form_id=%d&amp;view-entry=%d', $form_id, $next_entry ) );
	}

	$next_entry = isset( $form_entries[ $entry_index + 1 ] ) ? $form_entries[ $entry_index + 1 ] : '';
}

?>
<div class="wrap everest-forms evf-view-entry-container">
	<div class="view-entry-header">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $form_id ) ); ?>" class="page-title-action"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
  <path d="M10.8846 14.411C10.8002 14.4115 10.7166 14.3953 10.6385 14.3634C10.5604 14.3315 10.4893 14.2845 10.4294 14.2251L4.6602 8.4559C4.60011 8.39631 4.55242 8.32541 4.51988 8.24729C4.48734 8.16918 4.47058 8.08539 4.47058 8.00077C4.47058 7.91615 4.48734 7.83236 4.51988 7.75425C4.55242 7.67613 4.60011 7.60523 4.6602 7.54564L10.4294 1.77641C10.4892 1.71664 10.5601 1.66923 10.6382 1.63689C10.7163 1.60454 10.8 1.58789 10.8846 1.58789C10.9691 1.58789 11.0528 1.60454 11.1309 1.63689C11.209 1.66923 11.2799 1.71664 11.3397 1.77641C11.4604 1.89712 11.5282 2.06083 11.5282 2.23154C11.5282 2.31606 11.5116 2.39976 11.4792 2.47785C11.4469 2.55594 11.3995 2.6269 11.3397 2.68667L6.01917 8.00077L11.3397 13.3149C11.3998 13.3745 11.4475 13.4454 11.48 13.5235C11.5125 13.6016 11.5293 13.6854 11.5293 13.77C11.5293 13.8546 11.5125 13.9384 11.48 14.0165C11.4475 14.0946 11.3998 14.1655 11.3397 14.2251C11.2798 14.2845 11.2087 14.3315 11.1306 14.3634C11.0525 14.3953 10.9689 14.4115 10.8846 14.411Z" fill="#6B6B6B"/>
</svg></a>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'View Entry', 'everest-forms' ); ?></h1>
		<hr class="wp-header-end">
	</div>
	<?php do_action( 'everest_forms_view_entries_notices' ); ?>
	<div class="everest-forms-entry">
		<div class="view-table">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder">
					<!-- Entry Fields metabox -->
					<!-- <div id="evf-entry-nav-buttons">
						<a class="button" id="evf-prev-entry-button" href="<?php echo esc_url( $prev_entry_url ); ?>" <?php echo empty( $prev_entry ) ? esc_attr( 'disabled=disabled' ) : ''; ?> >
							<?php esc_html_e( 'Previous', 'everest-forms' ); ?>
						</a>
						<a class="button" id="evf-next-entry-button" href="<?php echo esc_url( $next_entry_url ); ?>" <?php echo empty( $next_entry ) ? esc_attr( 'disabled=disabled' ) : ''; ?> >
							<?php esc_html_e( 'Next', 'everest-forms' ); ?>
						</a>
					</div> -->
					<div id="post-body-content" style="position: relative;">
						<div id="everest-forms-entry-fields" class="everest-forms-entry-fields">
							<h2 class="hndle">
								<?php do_action( 'everest_forms_before_entry_details_hndle', $entry ); ?>
								<span>
								<?php
								/* translators: %s: Entry ID */
								printf( esc_html__( '%1$s: Entry #%2$s', 'everest-forms' ), esc_html( _draft_or_post_title( $form_id ) ), absint( $entry_id ) );
								?>
								</span>
								<?php do_action( 'everest_forms_after_entry_details_hndle', $entry ); ?>
								<a href="#" class="everest-forms-empty-field-toggle">
									<?php $hide_empty ? esc_html_e( 'Show Empty Fields', 'everest-forms' ) : esc_html_e( 'Hide Empty Fields', 'everest-forms' ); ?>
								</a>
							</h2>
							<div class="inside">
								<table class="wp-list-table widefat fixed striped posts">
									<tbody>
									<?php
									$entry_meta = apply_filters( 'everest_forms_entry_single_data', $entry->meta, $entry, $form_data );

									if ( empty( $entry_meta ) ) {
										// Whoops, no fields! This shouldn't happen under normal use cases.
										echo '<p class="no-fields">' . esc_html__( 'This entry does not have any fields.', 'everest-forms' ) . '</p>';
									} else {
										// Display the fields and their values.
										foreach ( $entry_meta as $meta_key => $meta_value ) {
											// Check if hidden fields exists.
											if ( in_array( $meta_key, apply_filters( 'everest_forms_hidden_entry_fields', array() ), true ) ) {
												continue;
											}

											$meta_value = is_serialized( $meta_value ) ? $meta_value : wp_strip_all_tags( $meta_value );

											// Check for empty serialized value.
											if ( is_serialized( $meta_value ) ) {
												$raw_meta_val = unserialize( $meta_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
												if ( preg_match( '/dropdown_/', $meta_key ) && empty( $raw_meta_val[0] ) ) {
													$meta_value = '';
												} elseif ( ! preg_match( '/dropdown_/', $meta_key ) && empty( $raw_meta_val['label'][0] ) ) {
													$meta_value = '';
												}
											}

											if ( evf_is_json( $meta_value ) ) {
												$meta_value = json_decode( $meta_value, true );
												$meta_value = $meta_value['value'];
											}

											$field_value     = apply_filters( 'everest_forms_html_field_value', $meta_value, $entry_meta[ $meta_key ], $entry_meta, 'entry-single' );
											$field_class     = is_string( $field_value ) && ( '(empty)' === wp_strip_all_tags( $field_value ) || '' === $field_value ) ? ' empty' : '';
											$field_style     = $hide_empty && empty( $field_value ) ? 'display:none;' : '';
											$correct_answers = false;

											// Field name.
											echo '<tr class="everest-forms-entry-field field-name' . esc_attr( $field_class ) . '" style="' . esc_attr( $field_style ) . '"><th>';

											$value = evf_get_form_data_by_meta_key( $form_id, $meta_key, json_decode( $entry->fields ) );

											if ( $value ) {
												if ( apply_filters( 'everest_forms_html_field_label', false ) ) {
													$correct_answers = apply_filters( 'everest_forms_single_entry_label', $value, $meta_key, $field_value );
												} else {
													echo '<strong>' . esc_html( make_clickable( $value ) ) . '</strong>';
												}
											} else {
												echo '<strong>' . esc_html__( 'Field ID', 'everest-forms' ) . '</strong>';
											}

											echo '</th>';
											// Field value.
											echo '<td>';

											if ( ! empty( $field_value ) || is_numeric( $field_value ) ) {
												if ( is_serialized( $field_value ) ) {
													$field_value = maybe_unserialize( $field_value );
													$field_label = isset( $field_value['label'] ) ? $field_value['label'] : $field_value;

													if ( ! empty( $field_label ) && is_array( $field_label ) ) {
														foreach ( $field_label as $field => $value ) {
															$answer_class = '';
															if ( $correct_answers ) {
																if ( in_array( $value, $correct_answers, true ) ) {
																	$answer_class = 'correct_answer';
																} else {
																	$answer_class = 'wrong_answer';
																}
															}
															echo '<span class="list ' . esc_attr( $answer_class ) . '">' . esc_html( wp_strip_all_tags( $value ) ) . '</span>';
														}
													} else {
														echo nl2br( make_clickable( $field_label ) ); // @codingStandardsIgnoreLine
													}
												} elseif ( $correct_answers && false !== $correct_answers ) {
													if ( in_array( $field_value, $correct_answers, true ) ) {
														$answer_class = 'correct_answer';
													} else {
														$answer_class = 'wrong_answer';
													}
														echo '<span class="list ' . esc_attr( $answer_class ) . '">' . esc_html( wp_strip_all_tags( $field_value ) ) . '</span>';
												} else {
													echo nl2br( make_clickable( $field_value ) ); // @codingStandardsIgnoreLine

												}
											} else {
												esc_html_e( 'Empty', 'everest-forms' );
											}

											echo '</td>';
											echo '</tr>';
										}
									}
									?>
									</tbody>
								</table>
							</div>
						</div>

						<?php do_action( 'everest_forms_entry_details_content', $entry, $form_id ); ?>
					</div>
					<!-- Entry Details metabox -->
				</div>
			</div>
		</div>
		<div id="postbox-container-1" class="postbox-container">
			<div id="everest-forms-entry-details">
				<div class="title">

					<h2><?php esc_html_e( 'Entry Details', 'everest-forms' ); ?></h2>
				</div>
				<div class="inside">
					<div id="submitbox" class="submitbox">
						<div class="everest-forms-entry-details-meta">
							<p class="everest-forms-entry-id">
								<span class="dashicons dashicons-admin-network"></span>
								<?php esc_html_e( 'Entry ID:', 'everest-forms' ); ?>
								<strong><?php echo absint( $entry_id ); ?></strong>
							</p>

							<p class="everest-forms-entry-date">
								<span class="dashicons dashicons-calendar"></span>
								<?php esc_html_e( 'Submitted:', 'everest-forms' ); ?>
								<strong><?php echo esc_html( date_i18n( esc_html__( 'M j, Y @ g:ia', 'everest-forms' ), strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 ) ) ); ?> </strong>
							</p>

							<?php if ( ! empty( $entry->date_modified ) ) : ?>
								<p class="everest-forms-entry-modified">
									<span class="dashicons dashicons-calendar"></span>
									<?php esc_html_e( 'Modified:', 'everest-forms' ); ?>
									<strong><?php echo esc_html( date_i18n( esc_html__( 'M j, Y @ g:ia', 'everest-forms' ), strtotime( $entry->date_modified ) + ( get_option( 'gmt_offset' ) * 3600 ) ) ); ?> </strong>
								</p>
							<?php endif; ?>

							<?php if ( ! empty( $entry->user_id ) && 0 !== $entry->user_id ) : ?>
								<p class="everest-forms-entry-user">
									<span class="dashicons dashicons-admin-users"></span>
									<?php
									esc_html_e( 'User:', 'everest-forms' );
									$user      = get_userdata( $entry->user_id );
									$user_name = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
									// phpcs:ignore WordPress.WP.GlobalVariablesOverride
									$user_url = add_query_arg(
										array(
											'user_id' => absint( $user->ID ),
										),
										admin_url( 'user-edit.php' )
									);
									?>
									<strong><a href="<?php echo esc_url( $user_url ); ?>"><?php echo esc_html( $user_name ); ?></a></strong>
								</p>
							<?php endif; ?>

							<?php if ( ! empty( $entry->user_ip_address ) ) : ?>
								<p class="everest-forms-entry-ip">
									<span class="dashicons dashicons-location"></span>
									<?php esc_html_e( 'User IP:', 'everest-forms' ); ?>
									<strong><?php echo esc_html( $entry->user_ip_address ); ?></strong>
								</p>
							<?php endif; ?>

							<?php if ( ! empty( $entry->referer ) ) : ?>
								<p class="everest-forms-entry-referer">
									<span class="dashicons dashicons-admin-links"></span>
									<?php esc_html_e( 'Referer Link:', 'everest-forms' ); ?>
									<strong><a href="<?php echo esc_url( $entry->referer ); ?>" target="_blank"><?php esc_html_e( 'View', 'everest-forms' ); ?></a></strong>
								</p>
							<?php endif; ?>

							<?php
							if ( ! empty( $entry->status ) ) :
								{

								}
								?>
								<p class="everest-forms-entry-status">
									<span class="dashicons dashicons-category"></span>
									<?php esc_html_e( 'Status:', 'everest-forms' ); ?>
									<strong><?php echo ! empty( $entry->status ) ? esc_html( ucwords( sanitize_text_field( $entry->status ) ) ) : esc_html__( 'Completed', 'everest-forms' ); ?></strong>
								</p>
							<?php endif; ?>

							<?php do_action( 'everest_forms_entry_details_sidebar_details', $entry, $entry_meta, $form_data ); ?>
						</div>

						<?php if ( current_user_can( 'everest_forms_edit_entry', $entry->entry_id ) || current_user_can( 'everest_forms_delete_entry', $entry->entry_id ) ) : ?>
							<div id="major-publishing-actions">
								<?php do_action( 'everest_forms_entry_details_sidebar_action', $entry, $form_data ); ?>
								<?php if ( current_user_can( 'everest_forms_delete_entry', $entry->entry_id ) ) : ?>
									<div id="delete-action">
										<a class="submitdelete" aria-label="<?php echo esc_attr__( 'Move to trash', 'everest-forms' ); ?>" href="<?php echo esc_url( $trash_link ); ?>"><?php esc_html_e( 'Move to trash', 'everest-forms' ); ?></a>
									</div>
								<?php endif; ?>
								<div class="clear"></div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php do_action( 'everest_forms_after_entry_details', $entry, $entry_meta, $form_data ); ?>
		</div>
	</div>
</div>
<!--  Toggle displaying empty fields. -->
<script type="text/javascript">
	jQuery( document ).on( 'click', '#everest-forms-entry-fields .everest-forms-empty-field-toggle', function( event ) {
		event.preventDefault();

		// Handle cookie.
		if ( wpCookies.get( 'everest_forms_entry_hide_empty' ) === 'true' ) {

			// User was hiding empty fields, so now display them.
			wpCookies.remove( 'everest_forms_entry_hide_empty' );
			jQuery( this ).text( 'Hide Empty Fields' );
		} else {

			// User was seeing empty fields, so now hide them.
			wpCookies.set( 'everest_forms_entry_hide_empty', 'true', 2592000 ); // 1month.
			jQuery( this ).text( 'Show Empty Fields' );
		}

		jQuery( '.everest-forms-entry-field.empty' ).toggle();
	});
</script>
