<?php
/**
 * Everest Forms Loader Class.
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */
/**
 * Everest Forms Class initializer.
 */
final class ClassEVFLoader {

	/**
	 * Check if the class is intantiated properly.
	 */
	public static function evf() {
		try {
			require_once dirname( __FILE__, 4 ) . '/includes/class-everest-forms.php';
			$EverestForms = new EverestForms();

			// Pessimistic check for if the class file was loaded right.
			if ( class_exists( 'EverestForms' ) && ! is_null( $EverestForms ) ) {
				return array(
					'state'    => true,
					'message'  => 'Everest Forms loaded with no errors',
					'instance' => $EverestForms,
				);
			} else {
				throw new Exception( ' Errors occured. Check the stacktrace' );
			}
		} catch ( \Exception $e ) {
			return array(
				'state'   => false,
				'message' => $e->getMessage(),
			);
		}
	}
}
