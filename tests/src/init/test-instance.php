<?php
/**
 * Everest Forms InstantiateTest Class
 *
 * @package Everest Forms Unit tests.
 * @version 1.0.0
 * @since   1.6.6
 */

/**
 * Unit test class InstantiateTest.
 */
final class InstantiateTest extends EVFTest {

	/**
	 * Run the test routines.
	 */
	public function test_instance() {
		try {
			$EverestForms = ClassEVFLoader::evf();
			$readmepath   = dirname( __DIR__, 3 ) . '/readme.txt';

			// If state's false, then the loader ran into problems.
			if ( ! empty( $EverestForms['state'] ) && true === $EverestForms['state'] ) {
				$instance = EVF_Tests::instance();

				// Test Versioning to see if it matches the stable tag.
				if ( $readme = fopen( $readmepath, 'r' ) ) {
					$contents = fread( $readme, filesize( $readmepath ) );
					preg_match( '/Stable tag: \d+(\.\d+)*/', $contents, $stable_tag );
					$stable_tag = ! empty( $stable_tag[0] ) ? explode( ':', $stable_tag[0] ) : false;
					$stable_tag = ! empty( end( $stable_tag ) ) && $stable_tag ? end( $stable_tag ) : false;
					$stable_tag = ! empty( $stable_tag ) ? trim( $stable_tag ) : false;
					fclose( $readme );

					// Assert only if version's right, else quit and inform.
					if ( $instance->evf->version === $stable_tag ) {
						$this->print_t( "EVF current version : {$stable_tag}" );
						$this->assertEquals( $instance->evf->version, $stable_tag );
					} else {
						throw new Exception( 'Plugin version mismatch.' );
					}
				} else {
					throw new Exception( 'Could not locate readme file.' );
				}

				// Assert that the loading took place correctly.
				$this->print_t( 'Asserted load' );
				$this->assertSame( $EverestForms['state'], true );

				// Assert that the instance is indeed of Everest Forms.
				$this->print_t( 'Asserted instance validity' );
				$this->assertInstanceOf( 'EverestForms', $instance->evf );

				// Assert that the instance is well initialized by WP.
				$this->print_t( 'Asserted main class mutibility' );
				$this->assertNotEquals( $EverestForms['instance'], $instance->evf );
			} else {
				throw new Exception( $EverestForms['message'] );
			}
		} catch ( Exception $e ) {
			$this->setVerboseErrorHandler();
			$this->JITReporter();
			$this->assertOutput( $e->getMessage() );
		}
	}
}
