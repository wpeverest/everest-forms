<?php
/**
 * Entries Report Plain Text Email Template
 *
 * @package EverestForms\Emails\Templates
 * @since   2.0.9
 */

defined( 'ABSPATH' ) || exit;

// Guard: provide safe defaults so the template never fatals on missing variables.
$period_label = isset( $period_label ) ? (string) $period_label : '';
$is_test      = isset( $is_test ) ? (bool) $is_test : false;
$summary      = isset( $summary ) && is_array( $summary ) ? $summary : array();
$entries_data = isset( $entries_data ) && is_array( $entries_data ) ? $entries_data : array();
$highlights   = isset( $highlights ) && is_array( $highlights ) ? $highlights : array();
$footer       = isset( $footer ) && is_array( $footer ) ? $footer : array();

$summary = wp_parse_args(
	$summary,
	array(
		'total_entries'  => 0,
		'overall_change' => null,
		'active_forms'   => 0,
		'total_forms'    => 0,
		'total_unread'   => 0,
	)
);

$footer = wp_parse_args(
	$footer,
	array(
		'entries_url'     => admin_url( 'admin.php?page=evf-entries' ),
		'settings_url'    => admin_url( 'admin.php?page=evf-settings&tab=advanced&section=entry_reports' ),
		'unsubscribe_url' => '',
		'generated_at'    => current_time( 'Y-m-d H:i:s' ),
		'plugin_version'  => defined( 'EVF_VERSION' ) ? EVF_VERSION : '',
		'site_url'        => home_url(),
	)
);

$separator = str_repeat( '-', 60 );

?>
<?php echo esc_html( strtoupper( get_bloginfo( 'name' ) ) ); ?> — <?php echo esc_html( html_entity_decode( $period_label, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ); ?>

<?php echo esc_html( $separator ); ?>

<?php if ( $is_test ) : ?>
*** <?php esc_html_e( 'THIS IS A TEST EMAIL — DATA IS LIVE BUT SENT MANUALLY', 'everest-forms' ); ?> ***

<?php echo esc_html( $separator ); ?>

<?php endif; ?>
<?php esc_html_e( 'SUMMARY', 'everest-forms' ); ?>

<?php echo esc_html( $separator ); ?>
<?php esc_html_e( 'Total Entries:', 'everest-forms' ); ?> <?php echo esc_html( number_format_i18n( (int) $summary['total_entries'] ) ); ?>

<?php if ( ! is_null( $summary['overall_change'] ) ) : ?>
<?php
$change    = (float) $summary['overall_change'];
$direction = $change > 0 ? "\xe2\x86\x91" : ( $change < 0 ? "\xe2\x86\x93" : "\xe2\x86\x92" ); // ↑ ↓ →
/* translators: %1$s: arrow symbol, %2$s: absolute percentage value */
echo esc_html( sprintf( __( '%1$s %2$s%% vs last period', 'everest-forms' ), $direction, abs( $change ) ) );
?>

<?php endif; ?>
<?php esc_html_e( 'Active Forms:', 'everest-forms' ); ?> <?php echo esc_html( (int) $summary['active_forms'] . ' / ' . (int) $summary['total_forms'] ); ?>

<?php esc_html_e( 'Unread Entries:', 'everest-forms' ); ?> <?php echo esc_html( number_format_i18n( (int) $summary['total_unread'] ) ); ?>

<?php if ( ! empty( $entries_data ) ) : ?>
<?php echo esc_html( $separator ); ?>
<?php esc_html_e( 'FORM BREAKDOWN', 'everest-forms' ); ?>

<?php echo esc_html( $separator ); ?>
<?php
// Calculate column width from actual data, capped to avoid wrapping in narrow clients.
$max_name = 20;
foreach ( $entries_data as $form ) {
	$name_len = function_exists( 'mb_strlen' ) ? mb_strlen( $form['form_name'] ) : strlen( $form['form_name'] );
	$max_name = max( $max_name, $name_len );
}
$max_name = min( $max_name, 35 );

// Build header using str_pad to avoid variable format strings in printf.
$header = str_pad( __( 'Form Name', 'everest-forms' ), $max_name )
	. '  ' . str_pad( __( 'Entries', 'everest-forms' ), 8, ' ', STR_PAD_LEFT )
	. '  ' . str_pad( __( 'vs Last', 'everest-forms' ), 10, ' ', STR_PAD_LEFT )
	. '  ' . str_pad( __( 'Unread', 'everest-forms' ), 7, ' ', STR_PAD_LEFT );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain-text email, no HTML context.
echo esc_html( $header ) . "\n";
echo esc_html( str_repeat( '-', $max_name + 32 ) ) . "\n";

foreach ( $entries_data as $form ) {
	$name = function_exists( 'mb_strlen' ) && mb_strlen( $form['form_name'] ) > $max_name
		? mb_substr( $form['form_name'], 0, $max_name - 3 ) . '...'
		: $form['form_name'];

	$change = isset( $form['change'] ) ? $form['change'] : null;
	if ( is_null( $change ) ) {
		$change_str = '—';
	} elseif ( $change > 0 ) {
		$change_str = '↑ ' . (int) $change . '%';
	} elseif ( $change < 0 ) {
		$change_str = '↓ ' . abs( (int) $change ) . '%';
	} else {
		$change_str = '→ 0%';
	}

	$unread_str = isset( $form['unread'] ) && $form['unread'] > 0
		? number_format_i18n( (int) $form['unread'] )
		: '—';

	$row = str_pad( $name, $max_name )
		. '  ' . str_pad( number_format_i18n( (int) $form['current'] ), 8, ' ', STR_PAD_LEFT )
		. '  ' . str_pad( $change_str, 10, ' ', STR_PAD_LEFT )
		. '  ' . str_pad( $unread_str, 7, ' ', STR_PAD_LEFT );

	echo esc_html( $row ) . "\n";
}
?>

<?php endif; ?>
<?php if ( ! empty( $highlights ) ) : ?>
<?php echo esc_html( $separator ); ?>
<?php esc_html_e( 'HIGHLIGHTS', 'everest-forms' ); ?>

<?php echo esc_html( $separator ); ?>
<?php foreach ( $highlights as $text ) : ?>
- <?php echo esc_html( wp_strip_all_tags( (string) $text ) ); ?>

<?php endforeach; ?>
<?php endif; ?>
<?php echo esc_html( $separator ); ?>
<?php esc_html_e( 'LINKS', 'everest-forms' ); ?>

<?php echo esc_html( $separator ); ?>
<?php esc_html_e( 'View All Entries:', 'everest-forms' ); ?> <?php echo esc_url( $footer['entries_url'] ); ?>

<?php esc_html_e( 'Manage Settings:', 'everest-forms' ); ?> <?php echo esc_url( $footer['settings_url'] ); ?>

<?php esc_html_e( 'Unsubscribe:', 'everest-forms' ); ?> <?php echo esc_url( $footer['unsubscribe_url'] ); ?>

<?php echo esc_html( $separator ); ?>
<?php
printf(
	/* translators: %1$s: datetime string, %2$s: plugin version number, %3$s: site URL */
	esc_html__( 'Generated on %1$s · Everest Forms v%2$s · %3$s', 'everest-forms' ),
	esc_html( $footer['generated_at'] ),
	esc_html( $footer['plugin_version'] ),
	esc_url( $footer['site_url'] )
);
?>
