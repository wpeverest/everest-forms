<?php
/**
 * Admin View: Edit trackers
 *
 * @package EverestForms/Admin/Entries/Views
 */

defined( 'ABSPATH' ) || exit;

$hide_empty = isset( $_COOKIE['everest_forms_entry_hide_empty'] ) && 'true' === $_COOKIE['everest_forms_entry_hide_empty'] ;

?>
<div class="wrap everest-forms">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'View Entry', 'everest-forms' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=evf-entries&amp;form_id=' . $form_id ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to All Entries', 'everest-forms' ); ?></a>
	<hr class="wp-header-end">
	<div class="everest-forms-entry">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<!-- Left column -->
				<div id="post-body-content" style="position: relative;">
					<!-- Entry Fields metabox -->
					<div id="everest-forms-entry-fields" class="postbox">
						<h2 class="hndle">
							<span><?php echo esc_html( _draft_or_post_title( $form_id ) ); ?></span>
							<a href="#" class="everest-forms-empty-field-toggle">
								<?php echo $hide_empty ? esc_html__( 'Show Empty Fields', 'everest-forms' ) : esc_html__( 'Hide Empty Fields', 'everest-forms' ); ?>
							</a>
						</h2>
						<div class="inside">
							<?php
								$entry = apply_filters( 'everest_forms_entry_single_data', $entry );

								if ( empty( $entry ) ) {
									// Whoops, no fields! This shouldn't happen under normal use cases.
									echo '<p class="no-fields">' . esc_html__( 'This entry does not have any fields.', 'everest-forms' ) . '</p>';
								} else {

									// Display the fields and their values
									foreach ( $entry as $key => $field ) {
									
										$field_value = apply_filters( 'everest_forms_html_field_value', wp_strip_all_tags( $field['meta_value'] ) );
										$field_key = apply_filters( 'everest_forms_html_field_key', wp_strip_all_tags( $field['meta_key'] ) );
										$field_class = empty( $field_value ) ? ' empty' : '';
										$field_style = $hide_empty && empty( $field_value ) ? 'display:none;' : '';

										echo '<div class="everest-forms-entry-field ' . $field_class . '" style="' . $field_style . '">';
											// Field name
											echo '<p class="everest-forms-entry-field-name">';
												echo esc_html( get_form_data_by_meta_key( $form_id, $field_key ) );
											echo '</p>';

											// Field value
											echo '<p class="everest-forms-entry-field-value">';
												echo ! empty( $field_value ) ? nl2br( make_clickable( $field_value ) ) : esc_html__( 'Empty', 'everest-forms' );
											echo '</p>';

										echo '</div>';
									}
								}
							?>
						</div>
					</div>
				</div>

				<!-- Right column -->
				<div id="postbox-container-1" class="postbox-container">
					<?php //do_action( 'wpforms_entry_details_sidebar', $entry, $form_data, $this ); ?>
				</div>
			</div>
		</div>
	</div>
</div>
