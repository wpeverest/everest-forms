<?php
/**
 * Standalone check for EVF_Field_Date_Time::get_dropdown_selected_date().
 *
 * The date dropdowns must preselect the field's own value (editing an entry)
 * and only fall back to today when there is no value. Run from the plugin root:
 *
 *   php tests/date-dropdown-selected-date-check.php
 *
 * Outside the site's own PHP-FPM environment, DB_HOST needs the real host and
 * port, e.g. EVF_CHECK_DB_HOST=127.0.0.1:10069.
 *
 * @package EverestForms
 */

if ( getenv( 'EVF_CHECK_DB_HOST' ) ) {
	define( 'DB_HOST', getenv( 'EVF_CHECK_DB_HOST' ) );
}

require_once dirname( __DIR__, 4 ) . '/wp-load.php';

$field  = new EVF_Field_Date_Time();
$method = new ReflectionMethod( $field, 'get_dropdown_selected_date' );
$method->setAccessible( true );

$today = array(
	'year'  => (int) gmdate( 'Y' ),
	'month' => (int) gmdate( 'm' ),
	'day'   => (int) gmdate( 'd' ),
);

$cases = array(
	'ISO date'          => array( '1985-03-22', 'Y-m-d', 'date', array( 'year' => 1985, 'month' => 3, 'day' => 22 ) ),
	'US date'           => array( '03/22/1985', 'm/d/Y', 'date', array( 'year' => 1985, 'month' => 3, 'day' => 22 ) ),
	'EU date'           => array( '22/03/1985', 'd/m/Y', 'date', array( 'year' => 1985, 'month' => 3, 'day' => 22 ) ),
	'Long date'         => array( 'March 22, 1985', 'F j, Y', 'date', array( 'year' => 1985, 'month' => 3, 'day' => 22 ) ),
	'Date and time'     => array( '1985-03-22 14:30', 'Y-m-d', 'date-time', array( 'year' => 1985, 'month' => 3, 'day' => 22 ) ),
	'Empty falls back'  => array( '', 'Y-m-d', 'date', $today ),
	'Junk falls back'   => array( 'not-a-date', 'Y-m-d', 'date', $today ),
);

$failed = 0;

foreach ( $cases as $name => $case ) {
	list( $value, $format, $datetime_format, $expected ) = $case;

	$primary = array( 'attr' => array( 'value' => $value ) );
	$config  = array(
		'date_format'     => $format,
		'datetime_format' => $datetime_format,
		'time_format'     => 'H:i',
	);

	$actual = $method->invoke( $field, $primary, $config );

	if ( $actual === $expected ) {
		printf( "PASS  %-18s %s\n", $name, wp_json_encode( $actual ) );
	} else {
		++$failed;
		printf( "FAIL  %-18s got %s want %s\n", $name, wp_json_encode( $actual ), wp_json_encode( $expected ) );
	}
}

echo $failed ? "\n{$failed} check(s) failed\n" : "\nAll checks passed\n";
exit( $failed ? 1 : 0 );
