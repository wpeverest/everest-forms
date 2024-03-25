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
		break;

	case 'Monthly':
		$evf_summary_duration = 'in the past month';
		break;

	case 'Daily':
		$evf_summary_duration = 'yesterday';
}
?>
<style>
.evf_entries_summary_table tr:nth-child(even) {
    background: #f6f3fa;
}
</style>

<div class="everest-forms-message-text">
	<p><strong><?php esc_html_e( 'Hi there! ', 'everest-forms' ); ?> 👋</strong></p>
	<p><?php esc_html_e( 'Let\'s see how your forms performed ' . $evf_summary_duration . ' .', 'everst-forms' ); ?></p>
	<br/>
	<p><strong><?php esc_html_e( 'Forms Stats', 'everest-forms' ); ?></strong></p>

	<?php
	if ( defined( 'EVF_FORM_ANALYTICS_VERSION' ) ) {
	} else {
		$evf_entries_data          = evf_entries_summaries();
		?>

<table align="left" border="0" cellpadding="0" cellspacing="0" width="100%" style="solid #dddddd; display:block;min-width: 100%;border-collapse: collapse;width:100%; display:table; padding-bottom:2rem" class = "evf_entries_summary_table">
		<thead style="display:block; background:#7e3bd0; color:#fff; padding:1rem;">
		<tr style="display:flex; justify-content:space-between; paddiing:1rem">
			<th><?php esc_html_e( 'Form Name', 'everest-forms' ); ?></th>
			<th><?php esc_html_e( 'Entries', 'everest-forms' ); ?></th>
		</tr>
		</thead>
	<tbody style="display:block;">
		<?php foreach($evf_entries_data as $evf_entry_data){?>
		<tr style="display:flex; justify-content:space-between; color:#000; padding:1rem">
			<td><?php echo $evf_entry_data->post_title ?></td>
			<td><?php echo $evf_entry_data->entries_count ?></td>
		</tr>
		<?php } ?>
	</tbody>
</table>
		<?php
	}
	?>
	<div class="evf_advanced_form_analytics_text" style="background:#fafafa; text-align:center; display:inline-block; padding:2rem; margin-top:2rem">
		<h4 style="color:#383838"><?php esc_html_e('More Details on Conversion rates', 'everest-forms')?></h4>
		<br/>
		<p style="color:#8f8f8f; display:inline-flex; margin-bottom:2rem;"><?php esc_html_e('Business growth with Everest Forms.
		 With the Advanced Form Analytics addon, you can efficiently track user engagement on your forms
		 by monitoring conversions, impressions, bounce rates, and abandonments on a simplified graph.
		 Then, observe user behavior and fine-tune your forms for high lead conversion. Don\'t miss out on valuable
		 opportunities to enhance your conversion rates and push your business forward. ', 'everst-forms')?></p>
		 <a style="background:#7545bb; color:#fff; text-decoration:none; padding:0.8rem 1.5rem; border-radius:4px;" href="<?php echo esc_url('https://docs.everestforms.net/docs/form-analytics/')?>"><?php esc_html_e('Learn More','everest-forms')?></a>
	</div>
</div>
