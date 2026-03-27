<?php
defined( 'ABSPATH' ) || exit;

$site_name        = isset( $footer['site_name'] ) ? $footer['site_name'] : get_bloginfo( 'name' );
$frequency        = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );
$show_total_forms = count( $entries_data ) > 1;

if ( ! function_exists( 'evf_report_change_inline' ) ) :
	function evf_report_change_inline( $change ) {
		if ( is_null( $change ) ) {
			return '';
		}
		$base = 'font-family:Inter,Arial,sans-serif;font-size:12px;font-weight:bold;';
		if ( $change > 0 ) {
			return '<span style="' . $base . 'color:#16a34a;">&#9650; +' . esc_html( $change ) . '%</span>';
		}
		if ( $change < 0 ) {
			return '<span style="' . $base . 'color:#dc2626;">&#9660; ' . esc_html( $change ) . '%</span>';
		}
		return '<span style="' . $base . 'color:#9ca3af;">&#8212; 0%</span>';
	}
endif;

if ( 'Daily' === $frequency ) {
	$period_subtitle = __( 'Daily performance', 'everest-forms' );
	$vs_label        = __( 'vs yesterday', 'everest-forms' );
} elseif ( 'Monthly' === $frequency ) {
	$period_subtitle = __( 'Monthly performance', 'everest-forms' );
	$vs_label        = __( 'vs last month', 'everest-forms' );
} else {
	$period_subtitle = __( 'Weekly performance', 'everest-forms' );
	$vs_label        = __( 'vs last week', 'everest-forms' );
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php echo esc_html( $period_label ); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<style type="text/css">
body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
table,td{mso-table-lspace:0pt;mso-table-rspace:0pt}
img{-ms-interpolation-mode:bicubic;border:0;height:auto;line-height:100%;outline:none;text-decoration:none}
body{margin:0!important;padding:0!important;width:100%!important;background-color:#f9fafb;font-family:Inter,Arial,sans-serif}
@media screen and (max-width:600px){
	.stat-cards-table{width:100%!important}
	.stat-card-wrapper{display:block!important;width:100%!important;padding-bottom:12px!important}
	.stat-icon-cell{width:52px!important;max-width:52px!important;min-width:52px!important}
	.spacer-col{display:none!important;width:0!important;max-width:0!important;overflow:hidden!important;font-size:0!important;line-height:0!important}
}
</style>
</head>
<body style="margin:0;padding:0;background-color:#f9fafb;font-family:Inter,Arial,sans-serif;">

<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f9fafb;">
<tr>
<td align="center" style="padding:40px 16px 56px;">

<table border="0" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;border:1px solid #e5e7eb;">
<tr>
<td style="padding:32px 32px 28px;">
<table border="0" cellpadding="0" cellspacing="0" width="100%">

<?php if ( $is_test ) : ?>
<tr>
	<td style="background-color:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:10px 20px;text-align:center;">
		<p style="margin:0;font-family:Inter,Arial,sans-serif;font-size:11px;font-weight:bold;color:#92400e;letter-spacing:0.06em;text-transform:uppercase;">
			<span style="display:inline-block;width:0;height:0;border-left:5px solid transparent;border-right:5px solid transparent;border-bottom:9px solid #b45309;vertical-align:middle;margin-right:6px;line-height:0;font-size:0;"></span>
			<?php esc_html_e( 'Test Send — live data, triggered manually', 'everest-forms' ); ?>
		</p>
	</td>
</tr>
<tr><td style="height:16px;font-size:0;line-height:0;">&nbsp;</td></tr>
<?php endif; ?>

<tr>
	<td style="padding-bottom:6px;">
		<table border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td valign="middle" style="font-family:Inter,Arial,serif;font-size:24px;font-weight:bold;color:#111827;">
				<?php esc_html_e( 'Entries Summary Report', 'everest-forms' ); ?>
			</td>
			<td valign="middle" align="right" width="80" style="white-space:nowrap;">
				<span style="display:inline-block;background-color:#dcfce7;color:#15803d;font-family:Inter,Arial,sans-serif;font-size:12px;font-weight:bold;padding:4px 12px;border-radius:20px;border:1px solid #bbf7d0;">
					<?php echo esc_html( $frequency ); ?>
				</span>
			</td>
		</tr>
		</table>
	</td>
</tr>

<tr>
	<td style="font-family:Inter,Arial,sans-serif;font-size:13px;color:#6b7280;padding-bottom:6px;">
		<?php echo esc_html( $period_subtitle ); ?>
	</td>
</tr>

<tr>
	<td style="padding-bottom:20px;">
		<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td valign="middle" width="18" style="padding-right:6px;">
				<!--
					Calendar icon: pure HTML table boxes — outer border = calendar frame,
					top strip = header bar, inner cells = date grid. No SVG, no images.
				-->
				<table border="0" cellpadding="0" cellspacing="0" width="13" style="width:13px;border-collapse:collapse;">
				<!-- Calendar header (dark bar) -->
				<tr>
					<td colspan="3" style="height:4px;background-color:#6b7280;border-radius:2px 2px 0 0;font-size:0;line-height:0;">&nbsp;</td>
				</tr>
				<!-- Two hook lines on top of calendar -->
				<tr>
					<td width="4" style="width:4px;height:3px;border-left:1.5px solid #6b7280;font-size:0;line-height:0;">&nbsp;</td>
					<td style="font-size:0;line-height:0;">&nbsp;</td>
					<td width="4" style="width:4px;height:3px;border-right:1.5px solid #6b7280;font-size:0;line-height:0;">&nbsp;</td>
				</tr>
				<!-- Calendar body -->
				<tr>
					<td colspan="3" style="height:6px;border:1.5px solid #6b7280;border-top:none;border-radius:0 0 2px 2px;font-size:0;line-height:0;">&nbsp;</td>
				</tr>
				</table>
			</td>
			<td valign="middle" style="font-family:Inter,Arial,sans-serif;font-size:12px;color:#6b7280;">
				<?php echo esc_html( html_entity_decode( $period_label, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ); ?>
			</td>
		</tr>
		</table>
	</td>
</tr>

<tr><td style="height:1px;background-color:#f3f4f6;font-size:0;line-height:0;padding:0;">&nbsp;</td></tr>
<tr><td style="height:20px;font-size:0;line-height:0;">&nbsp;</td></tr>

<tr>
	<td>
		<table class="stat-cards-table" border="0" cellpadding="0" cellspacing="0" width="100%">
		<tr>

			<?php if ( $show_total_forms ) : ?>
			<td class="stat-card-wrapper" valign="top" width="48%" style="vertical-align:top;padding-bottom:0;">
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #e5e7eb;border-radius:12px;">
				<tr>
					<td style="padding:16px;">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td class="stat-icon-cell" valign="middle" width="52" style="width:52px;max-width:52px;min-width:52px;padding-right:12px;">
								<table border="0" cellpadding="0" cellspacing="0" width="40" height="40" style="width:40px;min-width:40px;height:40px;">
								<tr>
									<td width="40" height="40" align="center" valign="middle" style="background-color:#f3f0ff;border-radius:10px;width:40px;height:40px;min-width:40px;">

										<table border="0" cellpadding="0" cellspacing="0" style="margin:auto;border-collapse:collapse;">
										<tr>
											<td style="width:14px;padding:3px 2px;border:1.5px solid #7c3aed;border-radius:3px;">
												<table border="0" cellpadding="0" cellspacing="0" width="100%">
												<tr><td style="height:2px;background-color:#7c3aed;border-radius:1px;font-size:0;line-height:0;">&nbsp;</td></tr>
												<tr><td style="height:2px;font-size:0;">&nbsp;</td></tr>
												<tr><td style="height:2px;background-color:#7c3aed;border-radius:1px;font-size:0;line-height:0;">&nbsp;</td></tr>
												<tr><td style="height:2px;font-size:0;">&nbsp;</td></tr>
												<tr><td width="7" style="width:7px;height:2px;background-color:#7c3aed;border-radius:1px;font-size:0;line-height:0;">&nbsp;</td></tr>
												</table>
											</td>
										</tr>
										</table>
									</td>
								</tr>
								</table>
							</td>
							<td valign="middle">
								<p style="margin:0 0 4px;font-family:Inter,Arial,sans-serif;font-size:12px;color:#6b7280;"><?php esc_html_e( 'Total Forms', 'everest-forms' ); ?></p>
								<p style="margin:0;font-family:Inter,Arial,sans-serif;font-size:26px;font-weight:bold;color:#111827;line-height:1;"><?php echo esc_html( $summary['total_forms'] ); ?></p>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</td>

			<td class="spacer-col" width="4%" style="font-size:0;line-height:0;">&nbsp;</td>
			<?php endif; ?>

			<td class="stat-card-wrapper" valign="top" width="<?php echo $show_total_forms ? '48%' : '100%'; ?>" style="vertical-align:top;padding-bottom:0;">
				<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #e5e7eb;border-radius:12px;">
				<tr>
					<td style="padding:16px;">
						<table border="0" cellpadding="0" cellspacing="0" width="100%">
						<tr>
							<td class="stat-icon-cell" valign="middle" width="52" style="width:52px;max-width:52px;min-width:52px;padding-right:12px;">
								<table border="0" cellpadding="0" cellspacing="0" width="40" height="40" style="width:40px;min-width:40px;height:40px;">
								<tr>
									<td width="40" height="40" align="center" valign="middle" style="background-color:#f3f0ff;border-radius:10px;width:40px;height:40px;min-width:40px;">

										<table border="0" cellpadding="2" cellspacing="2" style="margin:auto;border-collapse:separate;border-spacing:2px;">
										<tr>
											<td style="width:6px;height:6px;background-color:#7c3aed;border-radius:1px;font-size:0;line-height:0;">&nbsp;</td>
											<td style="width:6px;height:6px;background-color:#7c3aed;border-radius:1px;font-size:0;line-height:0;">&nbsp;</td>
										</tr>
										<tr>
											<td style="width:6px;height:6px;background-color:#7c3aed;border-radius:1px;font-size:0;line-height:0;">&nbsp;</td>
											<td style="width:6px;height:6px;background-color:#7c3aed;border-radius:1px;font-size:0;line-height:0;">&nbsp;</td>
										</tr>
										</table>
									</td>
								</tr>
								</table>
							</td>
							<td valign="middle">
								<p style="margin:0 0 4px;font-family:Inter,Arial,sans-serif;font-size:12px;color:#6b7280;"><?php esc_html_e( 'Total Entries', 'everest-forms' ); ?></p>
								<table border="0" cellpadding="0" cellspacing="0">
								<tr>
									<td valign="baseline" style="font-family:Inter,Arial,sans-serif;font-size:26px;font-weight:bold;color:#111827;line-height:1;padding-right:8px;">
										<?php echo esc_html( number_format( $summary['total_entries'] ) ); ?>
									</td>
									<?php if ( ! is_null( $summary['overall_change'] ) ) : ?>
									<td valign="middle" style="padding-right:4px;white-space:nowrap;">
										<?php echo evf_report_change_inline( $summary['overall_change'] ); ?>
									</td>
									<td valign="middle" style="font-family:Inter,Arial,sans-serif;font-size:12px;color:#9ca3af;padding-right:8px;white-space:nowrap;">
										<?php echo esc_html( $vs_label ); ?>
									</td>
									<?php endif; ?>
									<?php if ( array_key_exists( 'prev_overall_change', $summary ) && ! is_null( $summary['prev_overall_change'] ) ) : ?>
									<td valign="middle" style="padding-right:4px;white-space:nowrap;">
										<?php echo evf_report_change_inline( $summary['prev_overall_change'] ); ?>
									</td>
									<td valign="middle" style="font-family:Inter,Arial,sans-serif;font-size:12px;color:#9ca3af;white-space:nowrap;">
										<?php esc_html_e( 'vs 2 periods ago', 'everest-forms' ); ?>
									</td>
									<?php endif; ?>
								</tr>
								</table>
							</td>
						</tr>
						</table>
					</td>
				</tr>
				</table>
			</td>

		</tr>
		</table>
	</td>
</tr>

<tr><td style="height:28px;font-size:0;line-height:0;">&nbsp;</td></tr>

<tr>
	<td style="font-family:Inter,Arial,sans-serif;font-size:17px;font-weight:bold;color:#111827;padding-bottom:12px;">
		<?php esc_html_e( 'Form Entries', 'everest-forms' ); ?>
	</td>
</tr>

<tr>
	<td>
		<?php if ( empty( $entries_data ) ) : ?>
		<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #e5e7eb;border-radius:12px;border-collapse:separate;border-spacing:0;">
		<tr>
			<td align="center" style="padding:36px 20px;font-family:Inter,Arial,sans-serif;font-size:13px;color:#9ca3af;font-style:italic;">
				<?php esc_html_e( 'No forms selected for this report.', 'everest-forms' ); ?>
			</td>
		</tr>
		</table>
		<?php else : ?>
		<table border="0" cellpadding="0" cellspacing="0" width="100%" style="border:1px solid #e5e7eb;border-radius:12px;border-collapse:separate;border-spacing:0;">
			<tr style="background-color:#f9fafb;">
				<td style="padding:11px 18px;font-family:Inter,Arial,sans-serif;font-size:12px;font-weight:bold;color:#374151;border-bottom:1px solid #e5e7eb;border-radius:12px 0 0 0;width:55%;"><?php esc_html_e( 'Form name', 'everest-forms' ); ?></td>
				<td style="padding:11px 18px;font-family:Inter,Arial,sans-serif;font-size:12px;font-weight:bold;color:#374151;border-bottom:1px solid #e5e7eb;width:25%;"><?php esc_html_e( 'Entries', 'everest-forms' ); ?></td>
				<td align="right" style="padding:11px 18px;font-family:Inter,Arial,sans-serif;font-size:12px;font-weight:bold;color:#374151;border-bottom:1px solid #e5e7eb;border-radius:0 12px 0 0;width:20%;"><?php esc_html_e( 'Actions', 'everest-forms' ); ?></td>
			</tr>
			<?php
			$row_count = count( $entries_data );
			$row_i     = 0;
			foreach ( $entries_data as $form ) :
				++$row_i;
				$is_last      = ( $row_i === $row_count );
				$row_border   = $is_last ? '' : 'border-bottom:1px solid #f3f4f6;';
				$radius_left  = $is_last ? 'border-radius:0 0 0 12px;' : '';
				$radius_right = $is_last ? 'border-radius:0 0 12px 0;' : '';
				?>
			<tr>
				<td style="padding:13px 18px;font-family:Inter,Arial,sans-serif;font-size:13px;color:#111827;<?php echo $row_border . $radius_left; ?>"><?php echo esc_html( $form['form_name'] ); ?></td>
				<td style="padding:13px 18px;font-family:Inter,Arial,sans-serif;font-size:13px;color:#111827;<?php echo $row_border; ?>"><?php echo esc_html( number_format( $form['current'] ) ); ?></td>
				<td align="right" style="padding:13px 18px;<?php echo $row_border . $radius_right; ?>">
					<a href="<?php echo esc_url( $form['view_url'] ); ?>" style="font-family:Inter,Arial,sans-serif;font-size:12px;font-weight:bold;color:#7c3aed;text-decoration:none;"><?php esc_html_e( 'View', 'everest-forms' ); ?></a>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
		<?php endif; ?>
	</td>
</tr>

<tr><td style="height:32px;font-size:0;line-height:0;">&nbsp;</td></tr>
<tr><td style="height:1px;background-color:#f3f4f6;font-size:0;line-height:0;padding:0;">&nbsp;</td></tr>
<tr><td style="height:24px;font-size:0;line-height:0;">&nbsp;</td></tr>

<tr>
	<td align="center" style="padding-bottom:16px;">
		<table border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td align="center" style="background-color:#7c3aed;border-radius:8px;">
				<a href="<?php echo esc_url( $footer['entries_url'] ); ?>" target="_blank" style="display:inline-block;background-color:#7c3aed;color:#ffffff;text-decoration:none;font-family:Inter,Arial,sans-serif;font-size:14px;font-weight:bold;padding:12px 36px;border-radius:8px;mso-padding-alt:12px 36px;border:1px solid #7c3aed;">
					<?php esc_html_e( 'View all entries', 'everest-forms' ); ?>
				</a>
			</td>
		</tr>
		</table>
	</td>
</tr>

<tr>
	<td align="center" style="font-family:Inter,Arial,sans-serif;font-size:12px;color:#9ca3af;">
		<?php printf( esc_html__( 'This email has been generated by %s', 'everest-forms' ), esc_html( $site_name ) ); ?>
	</td>
</tr>

</table>
</td>
</tr>
</table>

</td>
</tr>
</table>
</body>
</html>
