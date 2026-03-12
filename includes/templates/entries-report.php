<?php
/**
 * Entries Report HTML Email Template
 *
 * @var array  $entries_data
 * @var array  $summary
 * @var array  $highlights
 * @var array  $footer
 * @var string $period_label
 * @var bool   $is_test
 *
 * @package EverestForms\Emails\Templates
 * @since   2.0.9
 */

defined( 'ABSPATH' ) || exit;

$colors = (array) apply_filters( 'evf_report_email_colors', array(
	'accent'         => '#7c4dbd',
	'accent_hover'   => '#6a3da8',
	'accent_light'   => '#f3eeff',
	'accent_border'  => '#d4b8f0',
	'bg_outer'       => '#f0eff5',
	'bg_card'        => '#ffffff',
	'bg_alt'         => '#fafafa',
	'bg_footer'      => '#f5f4f9',
	'text_primary'   => '#1a1033',
	'text_secondary' => '#6b7280',
	'text_tertiary'  => '#9ca3af',
	'text_on_accent' => '#ffffff',
	'border'         => '#e5e7eb',
	'positive'       => '#16a34a',
	'negative'       => '#dc2626',
	'unread'         => '#d97706',
	'dot_active'     => '#16a34a',
	'dot_inactive'   => '#d1d5db',
) );

/**
 * Render a change indicator: ▲ green / ▼ red / — neutral.
 *
 * @param float|null $change
 * @param array      $colors
 * @return string
 */
function evf_report_change_html( $change, $colors ) {
	if ( is_null( $change ) ) {
		return '<span style="color:' . esc_attr( $colors['text_tertiary'] ) . ';font-family:Arial,sans-serif;font-size:12px;">—</span>';
	}
	if ( $change > 0 ) {
		return '<span style="color:' . esc_attr( $colors['positive'] ) . ';font-family:Arial,sans-serif;font-size:12px;font-weight:700;">▲ ' . esc_html( $change ) . '%</span>';
	}
	if ( $change < 0 ) {
		return '<span style="color:' . esc_attr( $colors['negative'] ) . ';font-family:Arial,sans-serif;font-size:12px;font-weight:700;">▼ ' . esc_html( abs( $change ) ) . '%</span>';
	}
	return '<span style="color:' . esc_attr( $colors['text_tertiary'] ) . ';font-family:Arial,sans-serif;font-size:12px;">→ 0%</span>';
}

$site_logo = get_site_icon_url( 40 );
$site_name = isset( $footer['site_name'] ) ? $footer['site_name'] : get_bloginfo( 'name' );
$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
$frequency = get_option( 'everest_forms_entries_reporting_frequency', 'Weekly' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title><?php echo esc_html( $period_label ); ?></title>
<style>
body,table,td,a{-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%}
table,td{mso-table-lspace:0pt;mso-table-rspace:0pt}
img{border:0;height:auto;line-height:100%;outline:none;text-decoration:none}
body{margin:0!important;padding:0!important;width:100%!important}
@media screen and (max-width:600px){
  .wrapper{width:100%!important}
  .stat-cell{display:block!important;width:100%!important;border-right:none!important;border-bottom:1px solid #e5e7eb!important;box-sizing:border-box!important}
  .hi-cell{display:block!important;width:100%!important;padding:0 0 8px 0!important}
  .pad{padding-left:20px!important;padding-right:20px!important}
}
</style>
</head>
<body style="margin:0;padding:0;background-color:<?php echo esc_attr( $colors['bg_outer'] ); ?>;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:<?php echo esc_attr( $colors['bg_outer'] ); ?>;">
<tr><td align="center" style="padding:36px 16px 52px;">
<table class="wrapper" border="0" cellpadding="0" cellspacing="0" width="580" style="max-width:580px;width:100%;">

<?php if ( $is_test ) : ?>
<tr>
<td style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px 8px 0 0;padding:10px 24px;text-align:center;">
  <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;font-weight:700;color:#92400e;letter-spacing:0.06em;text-transform:uppercase;">
    ⚠&nbsp;&nbsp;Test Send — live data, triggered manually
  </p>
</td>
</tr>
<?php endif; ?>

<tr>
<td style="background:<?php echo esc_attr( $colors['accent'] ); ?>;
           border-radius:<?php echo $is_test ? '0 0' : '10px 10px'; ?> 0 0;
           padding:30px 36px 26px;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td valign="middle">
      <table border="0" cellpadding="0" cellspacing="0">
      <tr>
        <?php if ( $site_logo ) : ?>
        <td style="padding-right:10px;vertical-align:middle;">
          <img src="<?php echo esc_url( $site_logo ); ?>" width="36" height="36" alt=""
               style="border-radius:6px;display:block;border:2px solid rgba(255,255,255,0.25);">
        </td>
        <?php endif; ?>
        <td valign="middle">
          <p style="margin:0;font-family:Arial,sans-serif;font-size:14px;font-weight:700;color:#fff;">
            <?php echo esc_html( $site_name ); ?>
          </p>
          <p style="margin:1px 0 0;font-family:Arial,sans-serif;font-size:11px;color:rgba(255,255,255,0.55);">
            <?php echo esc_html( $site_host ); ?>
          </p>
        </td>
      </tr>
      </table>
    </td>
    <td align="right" valign="middle">
      <span style="display:inline-block;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);
                   color:#fff;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                   padding:4px 13px;border-radius:20px;letter-spacing:0.1em;text-transform:uppercase;">
        <?php echo esc_html( $frequency ); ?>
      </span>
    </td>
  </tr>
  </table>

  <p style="margin:22px 0 4px;font-family:Arial,sans-serif;font-size:22px;font-weight:700;color:#fff;line-height:1.2;">
    <?php esc_html_e( 'Forms Entries Report', 'everest-forms' ); ?>
  </p>
  <p style="margin:0;font-family:Arial,sans-serif;font-size:12px;color:rgba(255,255,255,0.65);">
    <?php echo esc_html( $period_label ); ?>
    &nbsp;·&nbsp;
    <?php printf( esc_html__( 'Generated %s', 'everest-forms' ), esc_html( $footer['generated_at'] ) ); ?>
  </p>
</td>
</tr>

<tr>
<td style="background:<?php echo esc_attr( $colors['bg_card'] ); ?>;
           border-left:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
           border-right:1px solid <?php echo esc_attr( $colors['border'] ); ?>;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>

    <td class="stat-cell" width="33%" valign="top"
        style="padding:22px 24px;border-right:1px solid <?php echo esc_attr( $colors['border'] ); ?>;">
      <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                text-transform:uppercase;letter-spacing:0.08em;color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;">
        <?php esc_html_e( 'Total Entries', 'everest-forms' ); ?>
      </p>
      <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:32px;font-weight:700;
                color:<?php echo esc_attr( $colors['text_primary'] ); ?>;line-height:1;">
        <?php echo esc_html( number_format( $summary['total_entries'] ) ); ?>
      </p>
      <p style="margin:0;font-size:0;line-height:0;">
        <?php echo evf_report_change_html( $summary['overall_change'], $colors ); ?>
        <span style="font-family:Arial,sans-serif;font-size:11px;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;margin-left:3px;">
          <?php
          if ( 'Daily' === $frequency )       esc_html_e( 'vs yesterday', 'everest-forms' );
          elseif ( 'Monthly' === $frequency ) esc_html_e( 'vs last month', 'everest-forms' );
          else                                esc_html_e( 'vs last week', 'everest-forms' );
          ?>
        </span>
      </p>
    </td>

    <td class="stat-cell" width="33%" valign="top"
        style="padding:22px 24px;border-right:1px solid <?php echo esc_attr( $colors['border'] ); ?>;">
      <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                text-transform:uppercase;letter-spacing:0.08em;color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;">
        <?php esc_html_e( 'Active Forms', 'everest-forms' ); ?>
      </p>
      <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:32px;font-weight:700;
                color:<?php echo esc_attr( $colors['text_primary'] ); ?>;line-height:1;">
        <?php echo esc_html( $summary['active_forms'] ); ?>
        <span style="font-size:16px;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;font-weight:400;">
          / <?php echo esc_html( $summary['total_forms'] ); ?>
        </span>
      </p>
      <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
        <?php esc_html_e( 'with submissions', 'everest-forms' ); ?>
      </p>
    </td>

    <td class="stat-cell" width="34%" valign="top" style="padding:22px 24px;">
      <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                text-transform:uppercase;letter-spacing:0.08em;color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;">
        <?php esc_html_e( 'Unread', 'everest-forms' ); ?>
      </p>
      <p style="margin:0 0 6px;font-family:Arial,sans-serif;font-size:32px;font-weight:700;line-height:1;
                color:<?php echo $summary['total_unread'] > 0 ? esc_attr( $colors['unread'] ) : esc_attr( $colors['text_primary'] ); ?>;">
        <?php echo esc_html( $summary['total_unread'] ); ?>
      </p>
      <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
        <?php esc_html_e( 'need attention', 'everest-forms' ); ?>
      </p>
    </td>

  </tr>
  </table>
</td>
</tr>

<tr>
<td style="height:3px;background:<?php echo esc_attr( $colors['accent'] ); ?>;font-size:0;line-height:0;">&nbsp;</td>
</tr>

<tr>
<td class="pad" style="background:<?php echo esc_attr( $colors['bg_card'] ); ?>;
           border:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
           border-top:none;border-bottom:none;
           padding:26px 32px;">

  <p style="margin:0 0 14px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
            text-transform:uppercase;letter-spacing:0.1em;color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;">
    <?php esc_html_e( 'Form Breakdown', 'everest-forms' ); ?>
  </p>

  <?php if ( empty( $entries_data ) ) : ?>
  <p style="font-family:Arial,sans-serif;font-size:13px;font-style:italic;text-align:center;
            padding:20px 0;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
    <?php esc_html_e( 'No forms selected for this report.', 'everest-forms' ); ?>
  </p>
  <?php else : ?>

  <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr style="border-bottom:2px solid <?php echo esc_attr( $colors['border'] ); ?>;">
      <td width="42%" style="padding:0 0 9px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                             text-transform:uppercase;letter-spacing:0.08em;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
        <?php esc_html_e( 'Form', 'everest-forms' ); ?>
      </td>
      <td width="14%" align="right" style="padding:0 0 9px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                             text-transform:uppercase;letter-spacing:0.08em;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
        <?php esc_html_e( 'Entries', 'everest-forms' ); ?>
      </td>
      <td width="16%" align="right" style="padding:0 0 9px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                             text-transform:uppercase;letter-spacing:0.08em;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
        <?php esc_html_e( 'Change', 'everest-forms' ); ?>
      </td>
      <td width="14%" align="right" style="padding:0 0 9px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
                             text-transform:uppercase;letter-spacing:0.08em;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
        <?php esc_html_e( 'Unread', 'everest-forms' ); ?>
      </td>
      <td width="14%" align="right" style="padding:0 0 9px;">&nbsp;</td>
    </tr>

    <?php foreach ( $entries_data as $form ) :
      $is_active = $form['current'] > 0;
    ?>
    <tr style="border-bottom:1px solid <?php echo esc_attr( $colors['border'] ); ?>;opacity:<?php echo $is_active ? '1' : '0.4'; ?>;">

      <td valign="middle" style="padding:13px 0;">
        <table border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td style="padding-right:8px;vertical-align:middle;">
            <div style="width:7px;height:7px;border-radius:50%;
                        background:<?php echo esc_attr( $is_active ? $colors['dot_active'] : $colors['dot_inactive'] ); ?>;"></div>
          </td>
          <td>
            <span style="font-family:Arial,sans-serif;font-size:13px;font-weight:<?php echo $is_active ? '600' : '400'; ?>;
                         color:<?php echo esc_attr( $colors['text_primary'] ); ?>;">
              <?php echo esc_html( $form['form_name'] ); ?>
            </span>
          </td>
        </tr>
        </table>
      </td>

      <td align="right" valign="middle" style="padding:13px 0;font-family:Arial,sans-serif;font-size:16px;
               font-weight:700;color:<?php echo esc_attr( $colors['text_primary'] ); ?>;">
        <?php echo esc_html( number_format( $form['current'] ) ); ?>
      </td>

      <td align="right" valign="middle" style="padding:13px 0;">
        <?php echo evf_report_change_html( $form['change'], $colors ); ?>
      </td>

      <td align="right" valign="middle" style="padding:13px 0;">
        <?php if ( $form['unread'] > 0 ) : ?>
          <span style="display:inline-block;background:<?php echo esc_attr( $colors['accent_light'] ); ?>;
                       border:1px solid <?php echo esc_attr( $colors['accent_border'] ); ?>;
                       color:<?php echo esc_attr( $colors['accent'] ); ?>;
                       font-family:Arial,sans-serif;font-size:11px;font-weight:700;
                       padding:2px 8px;border-radius:10px;">
            <?php echo esc_html( $form['unread'] ); ?>
          </span>
        <?php else : ?>
          <span style="font-family:Arial,sans-serif;font-size:12px;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">—</span>
        <?php endif; ?>
      </td>

      <td align="right" valign="middle" style="padding:13px 0;">
        <a href="<?php echo esc_url( $form['view_url'] ); ?>"
           style="font-family:Arial,sans-serif;font-size:11px;font-weight:700;
                  color:<?php echo esc_attr( $colors['accent'] ); ?>;text-decoration:none;">
          <?php esc_html_e( 'View →', 'everest-forms' ); ?>
        </a>
      </td>

    </tr>
    <?php endforeach; ?>

  </table>
  <?php endif; ?>

</td>
</tr>

<?php if ( ! empty( $highlights ) ) :
  $icons = array(
    'top_form'       => '🏆',
    'most_improved'  => '📈',
    'unread_alert'   => '🔔',
    'inactive_alert' => '⚠️',
  );
  $highlight_list = array_values( $highlights );
  $highlight_keys = array_keys( $highlights );
  $h_count        = count( $highlight_list );
?>
<tr>
<td class="pad" style="background:<?php echo esc_attr( $colors['bg_alt'] ); ?>;
           border:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
           border-top:none;border-bottom:none;
           padding:24px 32px;">

  <p style="margin:0 0 14px;font-family:Arial,sans-serif;font-size:10px;font-weight:700;
            text-transform:uppercase;letter-spacing:0.1em;color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;">
    <?php esc_html_e( 'Highlights', 'everest-forms' ); ?>
  </p>

  <table border="0" cellpadding="0" cellspacing="0" width="100%">
  <?php for ( $i = 0; $i < $h_count; $i += 2 ) : ?>
  <tr>

    <td class="hi-cell" width="50%" valign="top" style="padding:0 5px 8px 0;">
      <table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
      <td style="background:<?php echo esc_attr( $colors['bg_card'] ); ?>;
                 border:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
                 border-radius:6px;padding:14px 16px;vertical-align:top;">
        <p style="margin:0 0 7px;font-size:17px;line-height:1;">
          <?php echo isset( $icons[ $highlight_keys[ $i ] ] ) ? $icons[ $highlight_keys[ $i ] ] : '•'; ?>
        </p>
        <p style="margin:0;font-family:Arial,sans-serif;font-size:12px;line-height:1.6;
                  color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;">
          <?php echo wp_kses( $highlight_list[ $i ], array( 'strong' => array() ) ); ?>
        </p>
      </td>
      </tr></table>
    </td>

    <?php if ( isset( $highlight_list[ $i + 1 ] ) ) : ?>
    <td class="hi-cell" width="50%" valign="top" style="padding:0 0 8px 5px;">
      <table border="0" cellpadding="0" cellspacing="0" width="100%"><tr>
      <td style="background:<?php echo esc_attr( $colors['bg_card'] ); ?>;
                 border:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
                 border-radius:6px;padding:14px 16px;vertical-align:top;">
        <p style="margin:0 0 7px;font-size:17px;line-height:1;">
          <?php echo isset( $icons[ $highlight_keys[ $i + 1 ] ] ) ? $icons[ $highlight_keys[ $i + 1 ] ] : '•'; ?>
        </p>
        <p style="margin:0;font-family:Arial,sans-serif;font-size:12px;line-height:1.6;
                  color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;">
          <?php echo wp_kses( $highlight_list[ $i + 1 ], array( 'strong' => array() ) ); ?>
        </p>
      </td>
      </tr></table>
    </td>
    <?php else : ?>
    <td width="50%" style="padding:0 0 8px 5px;">&nbsp;</td>
    <?php endif; ?>

  </tr>
  <?php endfor; ?>
  </table>

</td>
</tr>
<?php endif; ?>

<tr>
<td style="background:<?php echo esc_attr( $colors['bg_card'] ); ?>;
           border:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
           border-top:none;border-bottom:none;
           padding:20px 32px 26px;text-align:center;">
  <a href="<?php echo esc_url( $footer['entries_url'] ); ?>"
     style="display:inline-block;background:<?php echo esc_attr( $colors['accent'] ); ?>;
            color:#ffffff;text-decoration:none;font-family:Arial,sans-serif;
            font-size:13px;font-weight:700;padding:12px 30px;border-radius:6px;
            letter-spacing:0.01em;">
    <?php esc_html_e( 'View All Entries →', 'everest-forms' ); ?>
  </a>
</td>
</tr>

<tr>
<td style="background:<?php echo esc_attr( $colors['bg_footer'] ); ?>;
           border:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
           border-top:1px solid <?php echo esc_attr( $colors['border'] ); ?>;
           border-radius:0 0 10px 10px;
           padding:18px 32px;text-align:center;">

  <p style="margin:0 0 7px;">
    <a href="<?php echo esc_url( $footer['settings_url'] ); ?>"
       style="font-family:Arial,sans-serif;font-size:11px;color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;text-decoration:none;">
      <?php esc_html_e( 'Report Settings', 'everest-forms' ); ?>
    </a>
    <span style="color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;padding:0 8px;">&middot;</span>
    <a href="<?php echo esc_url( $footer['entries_url'] ); ?>"
       style="font-family:Arial,sans-serif;font-size:11px;color:<?php echo esc_attr( $colors['text_secondary'] ); ?>;text-decoration:none;">
      <?php esc_html_e( 'All Entries', 'everest-forms' ); ?>
    </a>
  </p>

  <p style="margin:0;font-family:Arial,sans-serif;font-size:11px;color:<?php echo esc_attr( $colors['text_tertiary'] ); ?>;">
    <?php
    printf(
      esc_html__( '%1$s · %2$s', 'everest-forms' ),
      esc_html( $site_name ),
      esc_html( $footer['generated_at'] )
    );
    ?>
  </p>

</td>
</tr>

</table>
</td></tr>
</table>
</body>
</html>
