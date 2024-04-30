<?php
/**
 * Email Body
 *
 * This is used with the {all_fields} smart tag.
 *
 * This template can be overridden by copying it to yourtheme/everest-forms/emails/body-default.php.
 *
 * HOWEVER, on occasion Everest Forms will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.everestforms.net/
 * @package EverestForms/Templates
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;
$evf_summary_frequency = get_option( 'everest_forms_entries_reporting_frequency', 'Weekkly' );
switch ( $evf_summary_frequency ) {
	case 'Weekly':
		$evf_summary_duration = 'in the past week';
		$offset_from          = '-1 month -1 day';
		$offset_to            = '-1 day';
		break;

	case 'Monthly':
		$evf_summary_duration = 'in the past month';
		$offset_from          = '-8 days';
		$offset_to            = '-1 day';
		break;

	case 'Daily':
		$evf_summary_duration = 'yesterday';
		$offset_from          = '-1 days';
		$offset_to            = '-1 day';
}
?>
<style>
.evf_entries_summary_table tr:nth-child(even) {
	background: #f6f3fa;
}

.evf_entries_conversion_summary_table tr{
	width: 100%;
	text-wrap: nowrap;
	display: flex;
	justify-content: space-between;
	padding: 1rem;
}

table.evf_entries_conversion_summary{
	text-wrap:nowrap;
}

.evf_entries_conversion_summary tr:nth-child(even) {
	background: #f6f3fa;
}

table.evf_entries_conversion_summary td {
	padding: 1rem;
}

thead.evf_entry_summary_thead th{
	padding:1rem;
}
</style>

<div class="everest-forms-message-text">
	<p><strong><?php esc_html_e( 'Hi there! ', 'everest-forms' ); ?> 👋</strong></p>
	<p><?php esc_html_e( 'Let\'s see how your forms performed ' . $evf_summary_duration . ' .', 'everest-forms' ); ?></p>
	<br/>
	<?php
		$evf_entries_data = evf_entries_summaries();
	if ( '' === $evf_entries_data || count( $evf_entries_data ) <= 0 ) {
		echo '<p>' . esc_html__( 'Sorry, there are no forms to display the statistics.', 'everest-forms' ) . '</p>';
	} else {
		?>
	<p><strong><?php esc_html_e( 'Forms Stats', 'everest-forms' ); ?></strong></p>

		<?php
		if ( defined( 'EVF_FORM_ANALYTICS_VERSION' ) ) {
			?>
				<div class="evf_entries_summary" style="overflow-x: auto;">
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="solid #dddddd; display:block;min-width: 100%;border-collapse: collapse;width:100%; display:table; padding-bottom:2rem; text-align:center" class = "evf_entries_conversion_summary">
					<thead class="evf_entry_summary_thead" style="background:#7e3bd0; color:#fff; padding:1rem;">
						<tr>
							<th style="text-align:left;"><?php esc_html_e( 'Form Name', 'everest-forms' ); ?></th>
							<th><?php esc_html_e( 'Impressions', 'everest-forms' ); ?></th>
							<th><?php esc_html_e( 'Conversions', 'everest-forms' ); ?></th>
							<th><?php esc_html_e( 'Conversion Rate', 'everest-forms' ); ?></th>
							<th><?php esc_html_e( 'Abandonments', 'everest-forms' ); ?></th>
							<th><?php esc_html_e( 'Abandonment Rate', 'everest-forms' ); ?></th>
							<th><?php esc_html_e( 'Bounce Rate', 'everest-forms' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $evf_entries_data as $evf_entry_data ) {
							$evf_entries_conversion = ! is_null( evf_fa_get_forms_summary( $evf_entry_data->form_id ) ) ? evf_fa_get_forms_summary( $evf_entry_data->form_id ) : array();
							foreach ( $evf_entries_conversion as $evf_entry_conversion ) {
								?>
								<tr>
									<td style="text-align:left;"><?php echo esc_html( $evf_entry_data->post_title ); ?></td>
									<td><?php echo isset( $evf_entry_conversion->submitted_count ) ? esc_html( $evf_entry_conversion->submitted_count ) : esc_html( '0' ); ?></td>
									<td><?php echo isset( $evf_entry_conversion->total_count ) ? esc_html( $evf_entry_conversion->total_count ) : esc_html( '0' ); ?></td>
									<td><?php echo isset( $evf_entry_conversion->conversion_rate ) ? esc_html( $evf_entry_conversion->conversion_rate ) . '%' : esc_html( '0' ); ?></td>
									<td><?php echo isset( $evf_entry_conversion->abandoned_count ) ? esc_html( $evf_entry_conversion->abandoned_count ) : esc_html( '0' ); ?></td>
									<td><?php echo isset( $evf_entry_conversion->abandonment_rate ) ? esc_html( $evf_entry_conversion->abandonment_rate ) . '%' : esc_html( '0' ); ?></td>
									<td><?php echo isset( $evf_entry_conversion->bounce_rate ) ? esc_html( $evf_entry_conversion->bounce_rate ) . '%' : esc_html( '0' ); ?></td>
								</tr>
								<?php
							}
						}
						?>
					</tbody>
				</table>
				</div>
								<?php
		} else {
			?>

		<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="solid #dddddd; display:block;min-width: 100%;border-collapse: collapse;width:100%; display:table; padding-bottom:2rem" class = "evf_entries_summary_table">
				<thead style="display:block; background:#7e3bd0; color:#fff; padding:1rem;">
				<tr style="display:flex; justify-content:space-between; paddiing:1rem">
					<th><?php esc_html_e( 'Form Name', 'everest-forms' ); ?></th>
					<th><?php esc_html_e( 'Entries', 'everest-forms' ); ?></th>
				</tr>
				</thead>
			<tbody style="display:block;">
				<?php foreach ( $evf_entries_data as $evf_entry_data ) { ?>
					<tr style="display:flex; justify-content:space-between; color:#000; padding:1rem">
						<td><?php echo esc_html( $evf_entry_data->post_title ); ?></td>
						<td><?php echo esc_html( $evf_entry_data->entries_count ); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<div class="evf_advanced_form_analytics_text" style="background:#fafafa; text-align:center; display:inline-block; padding:2rem; margin-top:2rem">
		<h4 style="color:#383838"><?php esc_html_e( 'More Details on Conversion rates', 'everest-forms' ); ?></h4>
		<br/>
		<p style="color:#8f8f8f; display:inline-flex; margin-bottom:2rem;">
				<?php
				echo esc_html(
					apply_filters(
						'everest_forms_analytics_marketing_text',
						__(
							'Business growth with Everest Forms.
		 With the Advanced Form Analytics addon, you can efficiently track user engagement on your forms
		 by monitoring conversions, impressions, bounce rates, and abandonments on a simplified graph.
		 Then, observe user behavior and fine-tune your forms for high lead conversion. Don\'t miss out on valuable
		 opportunities to enhance your conversion rates and push your business forward. ',
							'everest-forms'
						)
					)
				);
				?>
		</p>
		<a style="background:#7545bb; color:#fff; text-decoration:none; padding:0.8rem 1.5rem; border-radius:4px;" href="<?php echo esc_url( 'https://docs.everestforms.net/docs/form-analytics/' ); ?>"><?php esc_html_e( 'Learn More', 'everest-forms' ); ?></a>
	</div>
</div>
				<?php

		}
	}
	?>
